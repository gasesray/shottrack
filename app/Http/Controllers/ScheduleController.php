<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Schedule;
use App\Models\Teams;
use App\Models\tournaments;
use App\Rules\Time12HourFormat;
use App\Models\Score;
use App\Models\PlayerStat;
use App\models\Players;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Imports\SchedulesImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ArrayExport;
use Carbon\Carbon;

class ScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $tournamentId = $request->input('tournament_id');
        $category = $request->input('category');

        // Fetch schedules filtered by the selected tournament and category if provided
        $schedules = Schedule::with(['team1', 'team2', 'scores.team']) // Load related teams and scores
            ->when($tournamentId, function ($query) use ($tournamentId) {
                return $query->where('tournament_id', $tournamentId);
            })
            ->when($category, function ($query) use ($category, $tournamentId) {
                return $query->whereHas('team1', function ($q) use ($category, $tournamentId) {
                    $q->where('category', $category)->where('tournament_id', $tournamentId);
                })->orWhereHas('team2', function ($q) use ($category, $tournamentId) {
                    $q->where('category', $category)->where('tournament_id', $tournamentId);
                });
            })
            ->orderBy('date', 'asc')
            ->paginate(25);

        $tournaments = Tournaments::all(); 
        $categories = $tournamentId ? $tournaments->where('id', $tournamentId)->pluck('category')->unique() : collect();

        return view('schedules.list', [
            'schedules' => $schedules,
            'tournaments' => $tournaments,
            'categories' => $categories,
        ]);
    }

    public function getSchedulesByTournament($tournamentId)
    {
        // Get the category from the request
        $category = request()->get('category');

        // Fetch schedules based on tournament and optionally category
        $query = Schedule::where('tournament_id', $tournamentId)
                        ->with(['team1', 'team2']);

        // Filter by category if specified
        if ($category) {
            $query->where('category', $category); // Adjust this line based on your database structure
        }

        $schedules = $query->get();

        return response()->json([
            'schedules' => $schedules,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $tournamentId = $request->query('tournament_id');
        $tournaments = Tournaments::all();
        $teams = [];

        if ($tournamentId) {
            $teams = Teams::where('tournament_id', $tournamentId)->get();
        }

        return view('schedules.create', compact('tournaments', 'teams'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validation rules
        $validator = Validator::make($request->all(), [
            'tournament_id' => 'required|exists:tournaments,id',
            'date' => 'required|date',
            'time' => ['required', new Time12HourFormat],
            'venue' => 'required|string|max:255',
            'team1_id' => 'required|exists:teams,id|different:team2_id',
            'team2_id' => 'required|exists:teams,id',
            'category' => 'nullable|string',
            'team1_color' => 'nullable|string',
            'team2_color' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->route('schedules.create')->withInput()->withErrors($validator);
        }

        // Convert 12-hour time format to 24-hour format
        $time12Hour = $request->input('time');
        $dateTime = \DateTime::createFromFormat('g:i A', $time12Hour);
        $time24Hour = $dateTime ? $dateTime->format('H:i') : null;

        if (!$dateTime) {
            return redirect()->route('schedules.create')
                ->withInput()
                ->withErrors(['time' => 'Invalid time format.']);
        }

        $proposedTime = $dateTime->getTimestamp();

        // Check for scheduling conflicts at the same venue
        $existingSchedules = Schedule::where('date', $request->date)
            ->where('venue', $request->venue)
            ->get();

            foreach ($existingSchedules as $existing) {
                // Parse the time from the database
                $existingTime = \DateTime::createFromFormat('H:i:s', $existing->time);
            
                if ($existingTime) {
                    $existingTimestamp = $existingTime->getTimestamp();
                    $timeDifference = abs($proposedTime - $existingTimestamp) / 60; // Time difference in minutes
            
                    // Validate against the 90-minute rule
                    if ($timeDifference < 90) {
                        return redirect()->route('schedules.create')
                            ->withInput()
                            ->withErrors([
                                'time' => 'Games at the same venue must be scheduled at least 1 hour and 30 minutes apart.',
                                'venue' => 'The selected venue is not available within the selected time.',
                            ]);
                    }
                } else {
                    // Handle parsing errors
                    return redirect()->route('schedules.create')
                        ->withInput()
                        ->withErrors(['time' => 'Failed to parse an existing schedule time.']);
                }
            }

        // Fetch the tournament to check if it has categories
        $tournamentId = $request->input('tournament_id');
        $tournament = Tournaments::findOrFail($tournamentId);
        $hasCategories = $tournament->has_categories;

        // Create the new schedule
        $schedule = new Schedule();
        $schedule->tournament_id = $request->tournament_id;
        $schedule->date = $request->date;
        $schedule->time = $time24Hour;
        $schedule->venue = $request->venue;
        $schedule->team1_id = $request->team1_id;
        $schedule->team2_id = $request->team2_id;
        $schedule->team1_color = $request->team1_color;
        $schedule->team2_color = $request->team2_color;

        // Only set category if the tournament has categories
        if ($hasCategories) {
            $schedule->category = $request->input('category');
        }

        $schedule->save();
        $this->initializePlayerStats($schedule);

        return redirect()->route('schedules.index')->with('success', 'Game schedule added successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show($scheduleId)
    {
        $schedule = Schedule::findOrFail($scheduleId);
    
        return view('playerstats.create', compact('schedule'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $tournaments = Tournaments::all();
        
        $schedule = Schedule::findOrFail($id);

        $formattedTime = Carbon::parse($schedule->time)->format('g:i A');

        // Fetch all teams for the selected tournament and category
        $teams = Teams::where('tournament_id', $schedule->tournament_id)
                    ->where('category', $schedule->category)
                    ->get();

        // Categories
        $categories = ['juniors', 'seniors'];

        return view('schedules.edit', compact('schedule', 'tournaments', 'categories', 'teams', 'formattedTime'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $schedule = Schedule::findOrFail($id);

        // Find the tournament to check if it has categories
        $tournamentId = $request->input('tournament_id');
        $tournament = Tournaments::findOrFail($tournamentId);
        $hasCategories = $tournament->has_categories;

        $validator = Validator::make($request->all(), [
            'tournament_id' => 'required|exists:tournaments,id',
            'date' => 'required|date',
            'time' => ['required', new Time12HourFormat],
            'venue' => 'required|string|max:255',
            'team1_id' => 'required|exists:teams,id',
            'team2_id' => 'required|exists:teams,id',
            'category' => 'nullable|string',
            'team1_color' => 'nullable|string',
            'team2_color' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->route('schedules.edit', $id)->withInput()->withErrors($validator);
        }

        // Convert 12-hour format to 24-hour format
        $time12Hour = $request->input('time');
        $dateTime = \DateTime::createFromFormat('g:i A', $time12Hour);
        $time24Hour = $dateTime ? $dateTime->format('H:i') : null;

        // Update the schedule
        $schedule->tournament_id = $request->tournament_id;
        $schedule->date = $request->date;
        $schedule->time = $time24Hour;
        $schedule->venue = $request->venue;
        $schedule->team1_id = $request->team1_id;
        $schedule->team2_id = $request->team2_id;
        $schedule->team1_color = $request->team1_color;
        $schedule->team2_color = $request->team2_color;

        if ($hasCategories) {
            $schedule->category = $request->input('category');
        } else {
            $schedule->category = null; // Clear the category if the tournament doesn't have categories
        }

        $schedule->save();

        return redirect()->route('schedules.index')->with('success', 'Game schedule updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id, Request $request)
    {
        $schedule = Schedule::find($request->id);

        if($schedule == null){
            session()->flash('error','Schedule not found');
            return response()->json([
                'status' => false
            ]);
        }

        $schedule->delete();
        session()->flash('error','Schedule deleted successfully.');
        return response()->json([
            'status' => true
        ]);
    }

    public function showMatch($schedule_id) {
        $schedule = Schedule::find($schedule_id); 
        $teamA = Teams::find($schedule->teamA_id);
        $teamB = Teams::find($schedule->teamB_id);
        
        return view('match', compact('schedule', 'teamA', 'teamB'));
    }

    public function storeGameTime(Request $request)
    {
        $request->validate([
            'schedule_id' => 'required|integer|exists:schedules,id',
            'current_quarter' => 'required|integer',
            'game_time' => 'required|string',
            'quarter_elapsed_time' => 'required|integer',
            'player_minutes' => 'sometimes|array',
        ]);

        $schedule = Schedule::with(['team1', 'team2'])->findOrFail($request->schedule_id);

        $team1Id = $schedule->team1->id; 
        $team2Id = $schedule->team2->id; 

        $teamIds = [
            'teamA' => $team1Id,
            'teamB' => $team2Id,
        ];

        if ($request->has('player_minutes')) {
            foreach ($request->player_minutes as $team => $players) {
                if (array_key_exists($team, $teamIds)) {
                    foreach ($players as $playerNumber => $minutes) {
                        // Find the player based on team ID and player number
                        $player = Players::where('team_id', $teamIds[$team])
                            ->where('number', $playerNumber)
                            ->first();

                        if ($player) {
                            Log::info("Updating PlayerStat for player_id: {$player->id}, schedule_id: {$schedule->id}, minutes: $minutes, team_id: {$teamIds[$team]}");

                            // Find existing PlayerStat or create new instance
                            $playerStat = PlayerStat::where('player_id', $player->id)
                                ->where('schedule_id', $schedule->id)
                                ->first();

                            if ($playerStat) {
                                // Update minutes if record exists
                                $playerStat->minutes++; 
                                $playerStat->save();
                            } else {
                                Log::warning("PlayerStat for player_id: {$player->id} not found for schedule_id: {$schedule->id}. No updates were made.");
                            }
                        } else {
                            Log::warning("Player with number $playerNumber not found for team {$teamIds[$team]}.");
                        }
                    }
                } else {
                    Log::warning("Team {$team} not found in team IDs.");
                }
            }
        }

        $schedule->current_quarter = $request->current_quarter;
        // $schedule->total_elapsed_time = $request->total_elapsed_time;
        $schedule->quarter_elapsed_time = $request->quarter_elapsed_time;

        $totalGameTime = 2400; 
        // $remaining_game_time = max(0, $totalGameTime - $schedule->total_elapsed_time);
        // $schedule->remaining_game_time = $remaining_game_time;

        $schedule->save();

        return response()->json(['message' => 'Game time updated successfully']);
    }

    public function getGameDetails($scheduleId) {
        $schedule = Schedule::find($scheduleId);
        
        if ($schedule) {
            $quarterLength = 600; 
    
            // Retrieve the current quarter and elapsed time
            $currentQuarter = $schedule->current_quarter;
            $quarterElapsedTime = $schedule->quarter_elapsed_time; // This should come directly from the DB
            $totalElapsedTime = $schedule->total_elapsed_time;
    
            // Calculate time spent in the current quarter
            $timeSpentInCurrentQuarter = $totalElapsedTime - (($currentQuarter - 1) * $quarterLength);
            $timeSpentInCurrentQuarter = max(0, min($timeSpentInCurrentQuarter, $quarterLength));
    
            // Calculate remaining game time in the current quarter
            $remaining_game_time = max(0, $quarterLength - $timeSpentInCurrentQuarter);
    
            // Return essential items: current quarter and quarter elapsed time
            return response()->json([
                'remaining_game_time' => $remaining_game_time,
                'quarter_elapsed_time' => $quarterElapsedTime,  // Corrected to actual quarter elapsed time
                'current_quarter' => $currentQuarter,
            ]);
        } else {
            return response()->json(['error' => 'Schedule not found.'], 404);
        }
    }
    

    public function getScores($scheduleId)
    {
        // Fetch the latest scores for the given schedule ID from the scores table
        $scoreEntry = Score::where('schedule_id', $scheduleId)
                        ->orderBy('created_at', 'desc')
                        ->first();

        if ($scoreEntry) {
            return response()->json([
                'scores' => [
                    'team_a' => $scoreEntry->team_a_score,
                    'team_b' => $scoreEntry->team_b_score,
                ],
            ]);
        }

        // Default response if no entries are found
        return response()->json([
            'scores' => [
                'team_a' => 0,
                'team_b' => 0,
            ],
        ]);
    }

    private function initializePlayerStats(Schedule $schedule)
    {
        // Get all players for Team 1
        $team1Players = Players::where('team_id', $schedule->team1_id)->get();

        // Get all players for Team 2
        $team2Players = Players::where('team_id', $schedule->team2_id)->get();

        // Initialize stats for Team 1 players
        foreach ($team1Players as $player) {
            PlayerStat::create([
                'player_id' => $player->id,
                'schedule_id' => $schedule->id,
                'team_id' => $player->team_id,
                'minutes' => 0,
                'points' => 0,
                'assists' => 0,
                'rebounds' => 0,
                'steals' => 0,
                'blocks' => 0,
                'turnovers' => 0,
                'fouls' => 0,
                'plus_minus' => 0,
            ]);
        }

        // Initialize stats for Team 2 players
        foreach ($team2Players as $player) {
            PlayerStat::create([
                'player_id' => $player->id,
                'schedule_id' => $schedule->id,
                'team_id' => $player->team_id,
                'points' => 0,
                'minutes' => 0,
                'assists' => 0,
                'rebounds' => 0,
                'steals' => 0,
                'blocks' => 0,
                'turnovers' => 0,
                'fouls' => 0,
                'plus_minus' => 0,
            ]);
        }
    }

    public function updateTeamStats($scheduleId)
    {
        $schedule = Schedule::find($scheduleId);
        
        if ($schedule->is_completed) {
            // Retrieve the total scores for each team in the schedule
            $teamScores = Score::where('schedule_id', $scheduleId)
                                ->select('team_id', DB::raw('SUM(score) as total_score'))
                                ->groupBy('team_id')
                                ->get();

            if ($teamScores->count() !== 2) {
                return "Invalid score data or teams missing"; // Handle edge cases (e.g., incomplete scores)
            }

            // Compare the scores between the two teams
            $team1 = $teamScores->first();
            $team2 = $teamScores->last(); 

            // Find the corresponding teams from the teams table
            $team1Model = Teams::find($team1->team_id);
            $team2Model = Teams::find($team2->team_id);

            // Determine the winner and update wins/losses
            if ($team1->total_score > $team2->total_score) {
                // Team 1 wins
                $team1Model->wins += 1;
                $team2Model->losses += 1;
            } elseif ($team1->total_score < $team2->total_score) {
                // Team 2 wins
                $team2Model->wins += 1;
                $team1Model->losses += 1;
            } 

            // Increment games played for both teams
            $team1Model->games_played += 1;
            $team2Model->games_played += 1;

            // Save the updates to both teams
            $team1Model->save();
            $team2Model->save();
        }
    }

    public function markAsCompleted(Request $request, $id)
    {
        $schedule = Schedule::findOrFail($id);
        $schedule->is_completed = $request->input('is_completed');
        $schedule->save();

        // Call updateTeamStats after marking the schedule as completed
        if ($schedule->is_completed) {
            $this->updateTeamStats($id);
        }

        return response()->json(['success' => true]);
    }

    public function bulkUploadForm()
    {

        $tournaments = Tournaments::all();

        return view('schedules.upload', compact('tournaments')); 
    }

    public function bulkUpload(Request $request)
    {
        Log::info('bulkUpload method called.');
    
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
            'tournament_id' => 'required|exists:tournaments,id',
        ]);
    
        try {
            Log::info('Request validation passed.', [
                'tournament_id' => $request->input('tournament_id'),
                'file_name' => $request->file('file')->getClientOriginalName(),
            ]);
    
            $tournamentId = $request->input('tournament_id');
            
            Log::info('Starting file import.', ['tournament_id' => $tournamentId]);
    
            // Import the file using the PlayersImport class
            Excel::import(new SchedulesImport($tournamentId), $request->file('file'));
    
            Log::info('File import completed successfully.');
    
            return redirect()->route('schedules.index')->with('success', 'Schedules imported successfully.');
        } catch (\Exception $e) {
            // Log the exception
            Log::error('Error during file import.', ['error' => $e->getMessage()]);
            
            session()->flash('error', 'Check the uploaded file. Ensure that all required fields are filled.');
        
            return redirect()->route('schedules.bulkUploadForm')->withInput();
        }
    }

    public function downloadSchoolTemplate()
    {
        $headers = [
            ['Category', 'Date', 'Time', 'Venue', 'Team 1 Name', 'Team 1 Color', 'Team 2 Name', 'Team 2 Color'],
        ];

        // Generate and download the Excel file
        return Excel::download(new ArrayExport($headers), 'School_Tournament_Schedule_template.xlsx');
    }

    public function downloadNonSchoolTemplate()
    {
        $headers = [
            ['Category', 'Date', 'Time', 'Venue', 'Team 1 Name', 'Team 2 Name'],
        ];

        // Generate and download the Excel file
        return Excel::download(new ArrayExport($headers), 'Non_School_Tournament_Schedule_template.xlsx');
    }
    
    public function updateGameTime(Request $request)
    {
        $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
            'new_time' => ['required', 'regex:/^\d{1,2}:\d{2}$/'],
        ]);

        // Convert `new_time` to seconds 
        $timeParts = explode(':', $request->input('new_time'));
        $minutes = (int) $timeParts[0];
        $seconds = (int) $timeParts[1];
        $remainingQuarterTime = ($minutes * 60) + $seconds;

        $secondsInQuarter = 600; 

        // Calculate elapsed time in the quarter
        $newQuarterElapsedTime = $secondsInQuarter - $remainingQuarterTime;

        $schedule = Schedule::findOrFail($request->input('schedule_id'));

        $schedule->quarter_elapsed_time = $newQuarterElapsedTime;

        $schedule->save();

        return response()->json(['message' => 'Time updated successfully.']);
    }
}