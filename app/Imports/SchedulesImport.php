<?php

namespace App\Imports;

use App\Models\Schedule;
use App\Models\Teams;
use App\Models\tournaments;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;  
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Rules\Time12HourFormat;
use App\Models\Players;
use App\Models\PlayerStat;

class SchedulesImport implements ToModel, WithHeadingRow, WithValidation
{
    protected $tournamentId;

    /**
     * Constructor to initialize the tournament ID.
     *
     * @param int $tournamentId
     */
    public function __construct($tournamentId)
    {
        $this->tournamentId = $tournamentId;
    }

    /**
     * Validation rules for each row in the import.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            '*.date' => 'required|date',
            '*.time' => ['required', new Time12HourFormat],
            '*.venue' => 'required|string|max:255',
            '*.team_1_name' => 'required|string|max:255|different:*.team_2_name',
            '*.team_2_name' => 'required|string|max:255',
            '*.category' => 'nullable|string',
            '*.team_1_color' => 'nullable|string',
            '*.team_2_color' => 'nullable|string',
        ];
    }

    /**
     * Map each row to a Schedule model instance.
     *
     * @param array $row
     * @return Schedule|null
     * @throws \Exception
     */
    public function model(array $row)
    {
        $tournament = tournaments::find($this->tournamentId);

        if (!$tournament) {
            throw new \Exception("Tournament not found for ID: {$this->tournamentId}");
        }

        $team1 = Teams::where('name', $row['team_1_name'] ?? null)
            ->where('tournament_id', $this->tournamentId)
            ->first();

        $team2 = Teams::where('name', $row['team_2_name'] ?? null)
            ->where('tournament_id', $this->tournamentId)
            ->first();

        if (!$team1 || !$team2) {
            $missingTeam = !$team1 ? $row['team_1_name'] : $row['team_2_name'];
            throw new \Exception("Team '{$missingTeam}' not found in the tournament ID: {$this->tournamentId}");
        }

        $matchDate = $this->parseDate($row['date'] ?? null);
        $matchTime = $this->validateTime($row['time'] ?? null);

        Log::info('Parsed date and time', ['raw_date' => $row['date'], 'parsed_date' => $matchDate, 'raw_time' => $row['time'], 'parsed_time' => $matchTime]);

        Log::info('Schedule data before saving', [
            'date' => $matchDate, 
            'time' => $matchTime,  
            'venue' => $row['venue'], 
            'tournament_id' => $this->tournamentId,  
            'team1_id' => $team1->id, 
            'team2_id' => $team2->id,
            'category' => $tournament->has_categories ? $row['category'] : null,  
        ]);

        $schedule = Schedule::create([
            'tournament_id' => $this->tournamentId,
            'date' => $matchDate,
            'time' => $matchTime,
            'venue' => $row['venue'],
            'team1_id' => $team1->id,
            'team1_color' => $row['team_1_color'] ?? null,
            'team2_id' => $team2->id,
            'team2_color' => $row['team_2_color'] ?? null,
            'category' => $tournament->has_categories ? $row['category'] : null,
        ]);

        $this->initializePlayerStats($schedule);

        return $schedule;
    }

    /**
     * Parses the date from the row and converts it to 'Y-m-d' format.
     *
     * @param string|null $date
     * @return string|null
     */
    private function parseDate(?string $date): ?string
    {
        if (!$date) {
            return null;
        }

        // Log the raw date received
        Log::info('Received raw date', ['raw_date' => $date]);

        try {
            $parsedDate = Carbon::createFromFormat('m/d/Y', trim($date))->format('Y-m-d');

            // Log the parsed date
            Log::info('Parsed date', ['parsed_date' => $parsedDate]);

            return $parsedDate;
        } catch (\Exception $e) {
            Log::error('Invalid date format', ['raw_date' => $date, 'error' => $e->getMessage()]);
            throw new \Exception("Invalid date format for '{$date}'. Expected format: m/d/Y");
        }
    }


    /**
     * Validates and converts the time to 'H:i:s' format (24-hour).
     *
     * @param string $time
     * @return string
     */
    private function validateTime(string $time): string
    {
        $validator = Validator::make(['time' => $time], [
            'time' => [new Time12HourFormat],
        ]);

        if ($validator->fails()) {
            throw new \Exception("Invalid time format for '{$time}'. Expected format: 12-hour format (e.g., 12:00 PM).");
        }

        // Convert the time to 24-hour format
        $dateTime = \DateTime::createFromFormat('g:i A', trim($time));
        if (!$dateTime) {
            throw new \Exception("Failed to convert '{$time}' to 24-hour format.");
        }

        return $dateTime->format('H:i:s'); // Return as 'H:i:s' 
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
}
