<?php

namespace App\Http\Controllers;
use App\Models\Player;
use App\Models\Teams;
use App\Models\Schedule;
use App\Models\Players;
use App\Models\PlayerStat;
use App\Models\tournaments;
use App\Models\PlayByPlay;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\TeamStat;
use App\Models\Score;
use App\Models\TeamMetric;
use Illuminate\Support\Facades\Validator;

class PlayerStatsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Retrieve the schedule_id from query parameters
        $scheduleId = $request->query('schedule_id');
        $team1Id = $request->query('team1_id');
        $team2Id = $request->query('team2_id');

        // Find the schedule with its teams and ensure it exists
        $schedule = Schedule::with(['team1', 'team2'])->find($scheduleId);
        if (!$schedule) {
            return redirect()->route('playerstats.index')->with('error', 'Schedule not found.');
        }

        // Retrieve player statistics for both teams in the specified schedule
        $playerStatsTeam1 = PlayerStat::with('player') // Eager load player data
            ->where('schedule_id', $scheduleId)
            ->where('team_id', $schedule->team1_id)
            ->orderBy('minutes', 'desc')
            ->get();

        $playerStatsTeam2 = PlayerStat::with('player') // Eager load player data
            ->where('schedule_id', $scheduleId)
            ->where('team_id', $schedule->team2_id)
            ->orderBy('minutes', 'desc')
            ->get();

        // Retrieve remaining players not in the player statistics
        $remainingPlayersTeam1 = Players::where('team_id', $schedule->team1_id)
            ->whereNotIn('id', $playerStatsTeam1->pluck('player_id')) // Exclude players with stats
            ->get();

        $remainingPlayersTeam2 = Players::where('team_id', $schedule->team2_id)
            ->whereNotIn('id', $playerStatsTeam2->pluck('player_id')) // Exclude players with stats
            ->get();

        // Fetch total points for both teams
        $teamAScore = PlayerStat::whereHas('player', function ($query) use ($schedule) {
            //Player stats for players on team a and the specific schedule
            $query->where('team_id', $schedule->team1->id)
                ->where('schedule_id', $schedule->id); 
        })->sum('points');
        
        $teamBScore = PlayerStat::whereHas('player', function ($query) use ($schedule) {
            //Player stats for players on Team b and the specific schedule
            $query->where('team_id', $schedule->team2->id)
                ->where('schedule_id', $schedule->id); 
        })->sum('points');

        $playByPlayData = PlayByPlay::with('player') // Eager load player data
                ->where('schedule_id', $scheduleId)
                ->get();

            // Format the play data with action text
            foreach ($playByPlayData as $play) {
                $play->action_text = $this->getActionText($play->type_of_stat, $play->result);
            }

        // Fetch team metrics for both teams
        $teamMetricsTeam1 = TeamMetric::where('team_id', $schedule->team1_id)->get();
        $teamMetricsTeam2 = TeamMetric::where('team_id', $schedule->team2_id)->get();

        $totalPointsOffTurnoverTeam1 = $teamMetricsTeam1->sum('points_off_turnover');
        $totalPointsOffTurnoverTeam2 = $teamMetricsTeam2->sum('points_off_turnover');
        $totalFastBreakPointsTeam1 = $teamMetricsTeam1->sum('fast_break_points');
        $totalFastBreakPointsTeam2 = $teamMetricsTeam2->sum('fast_break_points');
        $totalSecondChancePointsTeam1 = $teamMetricsTeam1->sum('second_chance_points');
        $totalSecondChancePointsTeam2 = $teamMetricsTeam2->sum('second_chance_points');
        $totalStarterPointsTeam1 = $teamMetricsTeam1->sum('starter_points');
        $totalStarterPointsTeam2 = $teamMetricsTeam2->sum('starter_points');
        $totalBenchPointsTeam1 = $teamMetricsTeam1->sum('bench_points');
        $totalBenchPointsTeam2 = $teamMetricsTeam2->sum('bench_points');

        // Pass data to the view
        return view('playerstats.list', compact(
'schedule', 'playerStatsTeam1', 'playerStatsTeam2', 'remainingPlayersTeam1',
            'remainingPlayersTeam2', 'teamAScore', 'teamBScore', 'playByPlayData', 
            'totalPointsOffTurnoverTeam1', 
            'totalPointsOffTurnoverTeam2',
            'totalFastBreakPointsTeam1',
            'totalFastBreakPointsTeam2',
            'totalSecondChancePointsTeam1',
            'totalSecondChancePointsTeam2',
            'totalStarterPointsTeam1',
            'totalStarterPointsTeam2',
            'totalBenchPointsTeam1',
            'totalBenchPointsTeam2'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create($schedule_id)
    {
        $schedule = Schedule::with(['team1.players', 'team2.players'])->findOrFail($schedule_id);
        $teams = [$schedule->team1, $schedule->team2];
        $players = $schedule->team1->players->merge($schedule->team2->players);

        $team1Name = $schedule->team1->name; 
        $team2Name = $schedule->team2->name;

        $playersTeamA = Players::where('team_id', $schedule->team1->id)->get();
        $playersTeamB = Players::where('team_id', $schedule->team2->id)->get();

        $remaining_game_time = $schedule->remaining_game_time; 
        $currentQuarter = $schedule->current_quarter; 

        // Filter for starting and bench players for Team A
        $startingPlayersTeamA = $playersTeamA->where('is_starting', true)->take(5); // Assume 'is_starting' is the flag for starters
        $benchPlayersTeamA = $playersTeamA->where('is_starting', false);

        // Filter for starting and bench players for Team B
        $startingPlayersTeamB = $playersTeamB->where('is_starting', true)->take(5);
        $benchPlayersTeamB = $playersTeamB->where('is_starting', false);

        // Fetch total points for both teams
        $teamAScore = PlayerStat::whereHas('player', function ($query) use ($schedule) {
            $query->where('team_id', $schedule->team1->id);
        })
        ->where('schedule_id', $schedule->id)  
        ->sum('points');
        
        // Fetch total points for Team B for this specific schedule
        $teamBScore = PlayerStat::whereHas('player', function ($query) use ($schedule) {
            $query->where('team_id', $schedule->team2->id);
        })
        ->where('schedule_id', $schedule->id)  
        ->sum('points');

        return view('playerstats.create', compact('schedule_id', 'teams', 'players', 'team1Name', 'team2Name', 'playersTeamA', 'playersTeamB', 'startingPlayersTeamA', 
        'startingPlayersTeamB', 'benchPlayersTeamA', 'benchPlayersTeamB', 'teamAScore', 'teamBScore', 'remaining_game_time', 'currentQuarter'));
    }

    public function store(Request $request)
    {
        Log::info('Received data:', $request->all());
        $validated = $request->validate([
            'player_number' => 'required|integer',
            'team' => 'required|integer',
            'type_of_stat' => 'required|string',
            'result' => 'required|string|in:made,missed',
            'schedule_id' => 'required|integer',
            'game_time' => 'required|string',
            'quarter' => 'required|string',
            'starting_players' => 'required|array'
        ]);
        Log::info('Shot result:', ['result' => $validated['result']]);

        // Find the player by number and team ID
        $player = Players::where('number', $validated['player_number'])
                        ->where('team_id', $validated['team'])
                        ->first();

        if (!$player) {
            return response()->json(['error' => 'Player not found'], 404);
        }

        // Create or update the stat record
        $stat = PlayerStat::where('player_id', $player->id)
                        ->where('schedule_id', $validated['schedule_id'])
                        ->first();

        // If no stat record exists, create a new one
        if (!$stat) {
            $stat = new PlayerStat();
            $stat->player_id = $player->id;
            $stat->schedule_id = $validated['schedule_id'];
            $stat->team_id = $validated['team'];
            $stat->points = 0;
            $stat->setAttribute('two_pt_fg_attempt', 0);
            $stat->setAttribute('two_pt_fg_made', 0);
            $stat->setAttribute('two_pt_percentage', 0);
            $stat->setAttribute('three_pt_fg_attempt', 0);
            $stat->setAttribute('three_pt_fg_made', 0);
            $stat->setAttribute('three_pt_percentage', 0);
            $stat->setAttribute('free_throw_attempt', 0);
            $stat->setAttribute('free_throw_made', 0);
            $stat->setAttribute('free_throw_percentage', 0);
            $stat->setAttribute('free_throw_attempt_rate', 0);
            $stat->setAttribute('rebounds', 0);
            $stat->setAttribute('offensive_rebounds', 0);
            $stat->setAttribute('defensive_rebounds', 0);
            $stat->setAttribute('assists', 0);
            $stat->setAttribute('blocks', 0);
            $stat->setAttribute('steals', 0);
            $stat->setAttribute('turnovers', 0);
            $stat->setAttribute('personal_fouls', 0);
            $stat->setAttribute('technical_fouls', 0);
            $stat->setAttribute('unsportsmanlike_fouls', 0);
            $stat->setAttribute('disqualifying_fouls', 0);
            $stat->setAttribute('effective_field_goal_percentage', 0);
            $stat->setAttribute('turnover_ratio', 0);
            $stat->setAttribute('minutes', 0);
        }
        
        // Use helper methods to determine points and action
        $points = $this->getPoints($validated['type_of_stat'], $validated['result']);
        $message = '';

        // Update the stat based on the type of stat
        switch ($validated['type_of_stat']) {
            case 'two_point':
                // Increment the number of attempts
                $stat->two_pt_fg_attempt++;

                if ($validated['result'] === 'made') {
                    $stat->two_pt_fg_made++;
                    $stat->points += $points; 
                }

                // Calculate the percentage
                $attempts = $stat->two_pt_fg_attempt;
                $makes = $stat->two_pt_fg_made;

                $percentage = ($attempts > 0) ? ($makes / $attempts) * 100 : 0;

                // Set the percentage
                $stat->two_pt_percentage = $percentage;
                $stat->save(); 

                break;

            case 'three_point':
                Log::info('Before Increment: ', ['three_pt_fg_attempt' => $stat->three_pt_fg_attempt]);
                $stat->three_pt_fg_attempt++;
                Log::info('After Increment: ', ['three_pt_fg_attempt' => $stat->three_pt_fg_attempt]);
                if ($validated['result'] === 'made') {
                    $stat->three_pt_fg_made++;
                    $stat->points += $points;
                }
    
                $attempts = $stat->three_pt_fg_attempt;
                $makes = $stat->three_pt_fg_made;
    
                 $percentage = ($attempts > 0) ? ($makes / $attempts) * 100 : 0;
    
                $stat->three_pt_percentage = $percentage;
                $stat->save(); 
    
                break;
            
            case 'free_throw':
                $stat->free_throw_attempt++;
                if ($validated['result'] === 'made') {
                    $stat->free_throw_made++;
                    $stat->points += $points;
                }

                $attempts = $stat->free_throw_attempt;
                $makes = $stat->free_throw_made;

                $percentage = ($attempts > 0) ? ($makes / $attempts) * 100 : 0;

                $stat->free_throw_percentage = $percentage;

                // Calculate the free throw attempt rate
                $two_pt_attempts = $stat->two_pt_fg_attempt;
                $three_pt_attempts = $stat->three_pt_fg_attempt;
                $free_throw_attempt = $stat->free_throw_attempt;

                // Calculate the free throw attempt rate
                $total_fg_attempts = $two_pt_attempts + $three_pt_attempts;
                $fta_rate = ($total_fg_attempts > 0) ? ($free_throw_attempt / $total_fg_attempts) : 0;

                // Set the free throw attempt rate 
                $stat->free_throw_attempt_rate = $fta_rate * 100;
                $stat->save(); 

                break;
            case 'offensive_rebound':
                $stat->rebounds++;
                $stat->offensive_rebounds++;
                break;
            case 'defensive_rebound':
                $stat->rebounds++;
                $stat->defensive_rebounds++;
                break;
            case 'block':
                $stat->blocks++;
                break;
            case 'steal':
                $stat->steals++;
                break;
            case 'turnover':
                $stat->turnovers++;
                break;
            case 'foul':
                $stat->personal_fouls++; // Increment the foul count first
            
                if ($stat->personal_fouls == 5) { // Check after incrementing
                    $stat->save(); // Save the stat after reaching 5 fouls
                    $message = 'Player has now reached the maximum number of Personal Fouls. Please Substitute Now.';
                }
                break;
            case 'assist':
                $stat->assists++;
                break;
            case 'technical_foul':
                $stat->technical_fouls++;

                if ($stat->technical_fouls == 2) { 
                    $stat->save(); 
                    $message = 'Player has now reached the maximum number of Technical Fouls. Please Substitute Now.';
                }
                break;
            case 'unsportsmanlike_foul':
                $stat->unsportsmanlike_fouls++;

                if ($stat->unsportsmanlike_fouls == 2) { 
                    $stat->save(); 
                    $message = 'Player has now reached the maximum number of Unsportsmanlike Fouls. Please Substitute Now.';
                }
                break;
            case 'disqualifying_foul':
                $stat->disqualifying_fouls++;

                if ($stat->disqualifying_fouls == 1) { 
                    $stat->save();
                    $message = 'Player commited a Disqualifying Foul. Please Substitute Now.';
                }
                break;
        }

        $two_pt_made = $stat->two_pt_fg_made;
        $three_pt_made = $stat->three_pt_fg_made;
        $two_pt_attempts = $stat->two_pt_fg_attempt;
        $three_pt_attempts = $stat->three_pt_fg_attempt;
        $assists = $stat->assists;
        $turnovers = $stat->turnovers;
        $free_throw_attempt = $stat->free_throw_attempt;

        $total_fg_made = $two_pt_made + $three_pt_made;
        $total_fg_attempts = $two_pt_attempts + $three_pt_attempts;

        $eFG_percentage = ($total_fg_attempts > 0) ? (($total_fg_made + 0.5 * $three_pt_made) / $total_fg_attempts) * 100 : 0;
        $stat->effective_field_goal_percentage = $eFG_percentage;

        $turnover_ratio = ($total_fg_attempts + ($free_throw_attempt * 0.44) + $assists + $turnovers) > 0
        ? ($turnovers * 100) / ($total_fg_attempts + ($free_throw_attempt * 0.44) + $assists + $turnovers)
        : 0;

        $stat->turnover_ratio = $turnover_ratio; 
        
        $stat->save();

        // Calculate total scores for both teams
        $schedule = Schedule::findOrFail($validated['schedule_id']);
        $teamAScore = PlayerStat::whereHas('player', function ($query) use ($schedule) {
            $query->where('team_id', $schedule->team1->id);
        })
        ->where('schedule_id', $schedule->id)  
        ->sum('points');
        
        // Fetch total points for Team B for this specific schedule
        $teamBScore = PlayerStat::whereHas('player', function ($query) use ($schedule) {
            $query->where('team_id', $schedule->team2->id);
        })
        ->where('schedule_id', $schedule->id)  
        ->sum('points');

        PlayByPlay::create([
            'player_id' => $player->id,
            'schedule_id' => $validated['schedule_id'],
            'type_of_stat' => $validated['type_of_stat'],
            'result' => $validated['result'],
            'game_time' => $validated['game_time'],
            'quarter' => $validated['quarter'],
            'team_A_score' => $teamAScore,
            'team_B_score' => $teamBScore
        ]);

        $teamAId = $schedule->team1_id; 
        $teamBId = $schedule->team2_id;

        $shootingTeamId = $validated['team'];

        // Determine the actual shooting team ID
        $actualShootingTeamId = ($shootingTeamId == (string) $teamAId) ? $teamAId : (($shootingTeamId == (string) $teamBId) ? $teamBId : null);
        
        if ($actualShootingTeamId === null) {
            return response()->json(['error' => 'Invalid team ID provided.'], 400);
        }
        
        // Determine opposing team ID
        $opposingTeamId = ($actualShootingTeamId == $teamAId) ? $teamBId : $teamAId;
        
        $pointsScored = 0;
        
        // Determine points scored based on the type of stat
        if ($validated['result'] === 'made') {
            if ($validated['type_of_stat'] === 'two_point') {
                $pointsScored = 2; 
            } elseif ($validated['type_of_stat'] === 'three_point') {
                $pointsScored = 3; 
            } elseif ($validated['type_of_stat'] === 'free_throw') {
                $pointsScored = 1; 
            }
        
            // Update plus-minus for the offensive team
            $startingPlayersOffensive = $validated['starting_players'][$shootingTeamId == "1" ? 'teamA' : 'teamB'] ?? [];

            // Store the starter players' data in the session for Team A
            if (!session()->has('starting_players_team_a')) {
                session(['starting_players_team_a' => $validated['starting_players']['teamA'] ?? []]);
            }
            
            foreach ($startingPlayersOffensive as $playerNumber) {
                $playerId = Players::where('number', $playerNumber)->where('team_id', $actualShootingTeamId)->pluck('id')->first();
                if ($playerId) {
                    $currentPlayerStat = PlayerStat::where('player_id', $playerId)->where('schedule_id', $validated['schedule_id'])->first();
                    if ($currentPlayerStat) {
                        $currentPlayerStat->plus_minus += $pointsScored; // Add points to plus-minus for scoring team
                        $currentPlayerStat->save();
                    }
                }
            }
        
            // Update plus-minus for the defensive team
            $startingPlayersDefensive = $validated['starting_players'][$opposingTeamId == $teamAId ? 'teamA' : 'teamB'] ?? [];

            // Store the starter players' data in the session for Team B
            if (!session()->has('starting_players_team_b')) {
                session(['starting_players_team_b' => $validated['starting_players']['teamB'] ?? []]);
            }
            
            foreach ($startingPlayersDefensive as $playerNumber) {
                $playerId = Players::where('number', $playerNumber)->where('team_id', $opposingTeamId)->pluck('id')->first();
                if ($playerId) {
                    $currentPlayerStat = PlayerStat::where('player_id', $playerId)->where('schedule_id', $validated['schedule_id'])->first();
                    if ($currentPlayerStat) {
                        $currentPlayerStat->plus_minus -= $pointsScored; // Subtract points from plus-minus for defending team
                        $currentPlayerStat->save();
                    }
                }
            }

            // Get all player IDs for Team A and Team B
            $allPlayersTeamA = Players::where('team_id', $teamAId)->pluck('id')->toArray();
            $allPlayersTeamB = Players::where('team_id', $teamBId)->pluck('id')->toArray();

            // Calculate starter and bench points for Team A
            $startingPlayersTeamA = session('starting_players_team_a', []);
            $benchPlayersTeamA = array_diff($allPlayersTeamA, array_map(function ($playerNumber) use ($teamAId) {
                return Players::where('number', $playerNumber)->where('team_id', $teamAId)->pluck('id')->first();
            }, $startingPlayersTeamA));

            $starterPointsTeamA = 0;
            $benchPointsTeamA = 0;

            foreach ($allPlayersTeamA as $playerId) {
                $playerStat = PlayerStat::where('player_id', $playerId)
                                        ->where('schedule_id', $validated['schedule_id'])
                                        ->first();
                if ($playerStat) {
                    if (in_array($playerId, $benchPlayersTeamA)) {
                        $benchPointsTeamA += $playerStat->points;
                    } else {
                        $starterPointsTeamA += $playerStat->points;
                    }
                }
            }

            // Save starter and bench points for Team A
            $teamMetricA = TeamMetric::where('schedule_id', $validated['schedule_id'])
                                ->where('team_id', $teamAId)
                                ->first();

            if ($teamMetricA) {
                $teamMetricA->starter_points = $starterPointsTeamA;
                $teamMetricA->bench_points = $benchPointsTeamA;
                $teamMetricA->save();
            }

            // Calculate starter and bench points for Team B
            $startingPlayersTeamB = session('starting_players_team_b', []);
            $benchPlayersTeamB = array_diff($allPlayersTeamB, array_map(function ($playerNumber) use ($teamBId) {
                return Players::where('number', $playerNumber)->where('team_id', $teamBId)->pluck('id')->first();
            }, $startingPlayersTeamB));

            $starterPointsTeamB = 0;
            $benchPointsTeamB = 0;

            foreach ($allPlayersTeamB as $playerId) {
                $playerStat = PlayerStat::where('player_id', $playerId)
                                        ->where('schedule_id', $validated['schedule_id'])
                                        ->first();
                if ($playerStat) {
                    if (in_array($playerId, $benchPlayersTeamB)) {
                        $benchPointsTeamB += $playerStat->points;
                    } else {
                        $starterPointsTeamB += $playerStat->points;
                    }
                }
            }

            // Save starter and bench points for Team B
            $teamMetricB = TeamMetric::where('schedule_id', $validated['schedule_id'])
                                ->where('team_id', $teamBId)
                                ->first();

            if ($teamMetricB) {
                $teamMetricB->starter_points = $starterPointsTeamB;
                $teamMetricB->bench_points = $benchPointsTeamB;
                $teamMetricB->save();
            }
        }

        return response()->json([
            'message' => $message,
            'teamAScore' => $teamAScore,
            'teamBScore' => $teamBScore
        ]);
    }

    private function getPoints($type_of_stat, $result) {
        switch ($type_of_stat) {
            case 'two_point':
                return $result === 'made' ? 2 : 0;
            case 'three_point':
                return $result === 'made' ? 3 : 0;
            case 'free_throw':
                return $result === 'made' ? 1 : 0;
            default:
                return 0;
        }
    }
    
    public function show($id) 
    {
        $schedule = Schedule::findOrFail($id);
    
        // Pass the schedule to the view
        return view('playerstats.create', compact('schedule'));
    }
    

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $player_stats = PlayerStat::findOrFail($id);
        
        // If the stats do not exist, create a new instance
        if (!$player_stats) {
            $player_stats = new PlayerStat();
            // Set the player_id and schedule_id here if needed, but you may want to pass them from the request or route parameters
            $player_stats->player_id = request()->get('player_id'); 
            $player_stats->schedule_id = request()->get('schedule_id');
        } else {
            // If the stats exist, find the associated player
            $player = Players::findOrFail($player_stats->player_id);
        }
        return view('playerstats.edit', compact('player_stats', 'player'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
{
    Log::info('Update method called with ID: ' . $id);
    Log::info('Request data: ', $request->all());

    $scheduleId = $request->input('schedule_id');
    $team1Id = $request->input('team1_id');
    $team2Id = $request->input('team2_id');

    $validator = Validator::make($request->all(), [
        'f2ptfgm' => 'nullable|integer|min:0', 
        'f2ptfga' => 'nullable|integer|min:0', 
        'f3ptfgm' => 'nullable|integer|min:0', 
        'f3ptfga' => 'nullable|integer|min:0', 
        'defensive_rebounds' => 'nullable|integer|min:0', 
        'offensive_rebounds' => 'nullable|integer|min:0', 
        'free_throw_made' => 'nullable|integer|min:0', 
        'free_throw_attempt' => 'nullable|integer|min:0', 
        'steals' => 'nullable|integer|min:0', 
        'blocks' => 'nullable|integer|min:0', 
        'turnovers' => 'nullable|integer|min:0', 
        'personal_fouls' => 'nullable|integer|min:0', 
        'assists' => 'nullable|integer|min:0', 
    ]);

    // Fetch the player stats to update
    $player_stats = PlayerStat::findOrFail($id); // This will throw a 404 if not found

    // Update the player stats with validated data
    if ($validator->passes()) {
        // Use a loop to avoid redundancy
        $fieldsToUpdate = [
            'f2ptfgm' => 'two_pt_fg_made',
            'f2ptfga' => 'two_pt_fg_attempt',
            'f3ptfgm' => 'three_pt_fg_made',
            'f3ptfga' => 'three_pt_fg_attempt',
            'defensive_rebounds' => 'defensive_rebounds',
            'offensive_rebounds' => 'offensive_rebounds',
            'free_throw_made' => 'free_throw_made',
            'free_throw_attempt' => 'free_throw_attempt',
            'steals' => 'steals',
            'blocks' => 'blocks',
            'turnovers' => 'turnovers',
            'personal_fouls' => 'personal_fouls',
            'assists' => 'assists',
        ];

        foreach ($fieldsToUpdate as $requestField => $modelField) {
            if ($request->filled($requestField)) {
                $player_stats->$modelField = $request->$requestField;
            }
        }

        $points = 0;
        $points += ($request->input('f2ptfgm') ?? $player_stats->two_pt_fg_made) * 2; 
        $points += ($request->input('f3ptfgm') ?? $player_stats->three_pt_fg_made) * 3; 
        $points += ($request->input('free_throw_made') ?? $player_stats->free_throw_made) * 1; 

        // Update the player's points
        $player_stats->points = $points;


        // Save changes
        $player_stats->save();

        return redirect()->route('schedules.index')->with('success', 'Player Stat updated successfully.');
    }

    return redirect()->route('playerstats.edit', $id)->withInput()->withErrors($validator);
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
    // Find the player stat by ID
    $playerStat = PlayerStat::findOrFail($id); 

    // Delete the player stat
    $playerStat->delete();

    // Redirect to the player stats list page with a success message
    return response()->json(['success' => 'Player Stat deleted successfully.']);
    }

    private function getActionText($type_of_stat, $result)
    {
        switch ($type_of_stat) {
            case 'two_point':
                return $result === 'made' ? 'MADE a 2-point field goal' : 'MISSED a 2-point field goal';
            case 'three_point':
                return $result === 'made' ? 'MADE a 3-point field goal' : 'MISSED a 3-point field goal';
            case 'free_throw':
                return $result === 'made' ? 'MADE a free throw' : 'MISSED a free throw';
            case 'offensive_rebound':
                return 'Grabbed an offensive rebound';
            case 'defensive_rebound':
                return 'Grabbed a defensive rebound';
            case 'block':
                return 'Blocked a shot';
            case 'steal':
                return 'Stole the ball';
            case 'turnover':
                return 'Committed a turnover';
            case 'foul':
                return 'Committed a foul';
            case 'assist':
                return 'MADE an assist';
            case 'technical_foul':
                return 'Committed a Technical Foul';
            case 'unsportsmanlike_foul':
                return 'Committed an Unsportsmanlike Foul';
            case 'disqualifying_foul':
                return 'Committed a Disqualifying Foul';
            default:
                return 'Unknown action';
        }
    }

    public function playerLeaderboard(Request $request)
    {
        // Retrieve the selected tournament ID and category from the request
        $tournamentId = $request->query('tournament_id');
        $category = $request->query('category');
    
        // Fetch all tournaments to display in the filter
        $tournaments = Tournaments::all();
    
        // Define the stats 
        $stats = [
            'points', 'assists', 'rebounds', 'blocks', 'steals', 
            'personal_fouls', 'turnovers', 'two_pt_percentage', 'three_pt_percentage',
            'free_throw_percentage', 'free_throw_made',
            'turnover_ratio', 'free_throw_attempt_rate', 
            'effective_field_goal_percentage', 'plus_minus', 
            'two_pt_fg_attempt', 'two_pt_fg_made', 
            'three_pt_fg_attempt', 'three_pt_fg_made'
        ];
    
        // Initialize array to hold top players by stats
        $topPlayersByStats = [];
    
        foreach ($stats as $stat) {
            // Build the base query for fetching players with their average stats
            $query = Players::select(
                    'players.id', 
                    'players.first_name', 
                    'players.last_name', 
                    'players.category', 
                    'players.team_id', 
                    'teams.team_acronym'
                )
                ->join('player_stats', 'players.id', '=', 'player_stats.player_id')
                ->join('schedules', 'player_stats.schedule_id', '=', 'schedules.id')
                ->join('teams', 'players.team_id', '=', 'teams.id')
                ->groupBy(
                    'players.id', 
                    'players.first_name', 
                    'players.last_name', 
                    'players.category', 
                    'players.team_id', 
                    'teams.team_acronym'
                )
                ->selectRaw("AVG(player_stats.$stat) as average_$stat")
                ->havingRaw("COUNT(DISTINCT player_stats.schedule_id) > 0");
    
            // Apply optional filters for tournament and category
            if ($tournamentId) {
                $query->where('schedules.tournament_id', $tournamentId);
            }
    
            if ($category) {
                $query->where('players.category', $category);
            }
    
            // Fetch top 5 players for each stat
            $topPlayersByStats[$stat] = $query
                ->orderBy("average_$stat", 'desc')
                ->get();
        }
    
        return view('leaderboards.players', compact('tournaments', 'topPlayersByStats'));
    }

    public function checkPlayerFouls($playerId, $scheduleId)
    {
        $playerStat = PlayerStat::where('player_id', $playerId)
                                ->where('schedule_id', $scheduleId)
                                ->first();  

        if (!$playerStat) {
            return response()->json(['message' => 'Player has no statistics for this schedule.'], 404);
        }

        $maxFouls = 5;
        $maxTechnicalFouls = 2;
        $maxUnsportsmanlikeFouls = 2; 
        $maxDisqualifyingFouls = 1; 
        $totalFouls = $playerStat->technical_fouls + 
            $playerStat->unsportsmanlike_fouls + 
            $playerStat->disqualifying_fouls;

        if ($playerStat->personal_fouls >= $maxFouls || $totalFouls >= $maxFouls) {
            return response()->json([
                'message' => 'This player has exceeded the maximum number of fouls.',
                'code' => 'FOUL_LIMIT_EXCEEDED',
            ], 400);
        }

        if ($playerStat->technical_fouls >= $maxTechnicalFouls) {
            return response()->json([
                'success' => false,
                'message' => 'This player has exceeded the maximum number of technical fouls.',
                'code' => 'FOUL_LIMIT_EXCEEDED',
            ], 400);
        }

        if ($playerStat->unsportsmanlike_fouls >= $maxUnsportsmanlikeFouls) {
            return response()->json([
                'success' => false,
                'message' => 'This player has exceeded the maximum number of unsportsmanlike fouls.',
                'code' => 'FOUL_LIMIT_EXCEEDED',
            ], 400);
        }

        if ($playerStat->disqualifying_fouls >= $maxDisqualifyingFouls) {
            return response()->json([
                'success' => false,
                'message' => 'This player has received a disqualifying foul and cannot be substituted.',
                'code' => 'FOUL_LIMIT_EXCEEDED',
            ], 400);
        }

        Log::info('Player ID: ' . $playerId . ' - Fouls:', [
            'personal_fouls' => $playerStat->personal_fouls,
            'technical_fouls' => $playerStat->technical_fouls,
            'unsportsmanlike_fouls' => $playerStat->unsportsmanlike_fouls,
            'disqualifying_fouls' => $playerStat->disqualifying_fouls,
        ]);

        return response()->json([
            'message' => 'Player can be substituted.',
        ], 200);    }

    
}