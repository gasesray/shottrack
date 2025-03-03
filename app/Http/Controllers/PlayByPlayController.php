<?php

namespace App\Http\Controllers;

use Illuminate\Console\Scheduling\ScheduleTestCommand;
use Illuminate\Http\Request;
use App\Models\PlayerStat;
use App\Models\PlayByPlay;
use App\Models\Players;
use App\Models\Schedule;
use App\Models\Score;
use Illuminate\Support\Facades\Log; 
use Illuminate\Support\Facades\DB;
class PlayByPlayController extends Controller
{

    private function formatPlayerName($player)
    {
        return $player->first_name[0] . '. ' . $player->last_name;
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

    private function getPoints($type_of_stat, $result) {
        switch ($type_of_stat) {
            case 'two_point':
                return $result === 'made' ? 2 : 0;
            case 'three_point':
                return $result === 'made' ? 3 : 0;
            case 'free_throw':
                return $result === 'made' ? 1 : 0;
            case 'assist':
            case 'offensive_rebound':
            case 'defensive_rebound':
            case 'steal':
            case 'block':
            case 'foul':
            case 'technical_foul':
            case 'unsportsmanlike_foul':
            case 'disqualifying_foul':
                return 0; 
            default:
                return 0;
        }
    }

    public function deletePlayByPlay($id)
    {

        $playByPlay = PlayByPlay::with('schedule')->findOrFail($id);

        $schedule = $playByPlay->schedule;

        $deletedQuarter = $playByPlay->quarter; 
        $playerId = $playByPlay->player_id;
        $deletedTimestamp = $playByPlay->created_at;

        $player = Players::find($playerId);
        $deletedTeamId = $player ? $player->team_id : null;

        if ($playByPlay->result === 'missed') {
            $playerStat = PlayerStat::where('player_id', $playByPlay->player_id)
                                    ->where('schedule_id', $playByPlay->schedule_id)
                                    ->first();

            if ($playerStat) {
                switch ($playByPlay->type_of_stat) {
                    case 'two_point':
                        $playerStat->two_pt_fg_attempt -= 1;
                        break;
                    case 'three_point':
                        $playerStat->three_pt_fg_attempt -= 1;
                        break;
                    case 'free_throw':
                        $playerStat->free_throw_attempt -= 1;
                        break;
                    default:
                        break;
                }

                $playerStat->save();
            }

            $playByPlay->delete();
        }
    
        if ($playByPlay->result === 'made') {
            $playerStat = PlayerStat::where('player_id', $playByPlay->player_id)
                                    ->where('schedule_id', $playByPlay->schedule_id)
                                    ->first();

            if (!$playerStat) {
                return response()->json(['error' => 'Player stats not found for the given schedule.'], 404);
            }

            switch ($playByPlay->type_of_stat) {
                case 'two_point':
                    $playerStat->points -= 2;
                    $playerStat->two_pt_fg_attempt -= 1;
                    $playerStat->two_pt_fg_made -= 1;

                    $attempts = $playerStat->two_pt_fg_attempt;
                    $makes = $playerStat->two_pt_fg_made;

                    $percentage = ($attempts > 0) ? ($makes / $attempts) * 100 : 0;

                    $playerStat->two_pt_percentage = $percentage;
                    $playerStat->plus_minus -=2;
                    $playerStat->save(); 

                    $schedule = Schedule::find($playByPlay->schedule_id);
                    
                    $subsequentPlays = PlayByPlay::where('schedule_id', $playByPlay->schedule_id)
                        ->where('created_at', '>', $deletedTimestamp)
                        ->orderBy('created_at', 'asc')
                        ->get();

                    foreach ($subsequentPlays as $play) {
                        
                        if ($deletedTeamId == $schedule->team1_id) {
                            $play->team_A_score = (int)$play->team_A_score - 2;
                        } 
                        else if ($deletedTeamId == $schedule->team2_id) {
                            $play->team_B_score = (int)$play->team_B_score - 2;
                        }

                        $play->save();
                    }

                    if ($deletedTeamId) {
                        Log::info('Updating scores table with:', [
                            'schedule_id' => $schedule->id,
                            'team_id' => $deletedTeamId,
                            'quarter' => $deletedQuarter
                        ]);
                
                        $scoreEntry = DB::table('scores')->where([
                            'schedule_id' => $schedule->id,
                            'team_id' => $deletedTeamId,
                            'quarter' => $deletedQuarter
                        ])->first();
                
                        if ($scoreEntry) {
                            
                            DB::table('scores')->where([
                                'schedule_id' => $schedule->id,
                                'team_id' => $deletedTeamId,
                                'quarter' => $deletedQuarter
                            ])->update([
                                'score' => $scoreEntry->score - 2
                            ]);
                        }
                    }

                    break;

                case 'three_point':
                    $playerStat->points -= 3;
                    $playerStat->three_pt_fg_attempt -= 1;
                    $playerStat->three_pt_fg_made -= 1;

                    $attempts = $playerStat->three_pt_fg_attempt;
                    $makes = $playerStat->three_pt_fg_made;

                    $percentage = ($attempts > 0) ? ($makes / $attempts) * 100 : 0;

                    $playerStat->three_pt_percentage = $percentage;
                    $playerStat->plus_minus -=3;
                    $playerStat->save(); 

                    Log::info('Deleted Team ID: ' . $deletedTeamId);

                    $schedule = Schedule::find($playByPlay->schedule_id);
                    
                    $subsequentPlays = PlayByPlay::where('schedule_id', $playByPlay->schedule_id)
                        ->where('created_at', '>', $deletedTimestamp)
                        ->orderBy('created_at', 'asc')
                        ->get();

                    foreach ($subsequentPlays as $play) {
                        
                        if ($deletedTeamId == $schedule->team1_id) {
                            $play->team_A_score = (int)$play->team_A_score - 3;
                        } 
                        else if ($deletedTeamId == $schedule->team2_id) {
                            $play->team_B_score = (int)$play->team_B_score - 3;
                        }

                        $play->save();
                    }

                    if ($deletedTeamId) {
                        Log::info('Updating scores table with:', [
                            'schedule_id' => $schedule->id,
                            'team_id' => $deletedTeamId,
                            'quarter' => $deletedQuarter
                        ]);
                
                        $scoreEntry = DB::table('scores')->where([
                            'schedule_id' => $schedule->id,
                            'team_id' => $deletedTeamId,
                            'quarter' => $deletedQuarter
                        ])->first();
                
                        if ($scoreEntry) {
                            
                            DB::table('scores')->where([
                                'schedule_id' => $schedule->id,
                                'team_id' => $deletedTeamId,
                                'quarter' => $deletedQuarter
                            ])->update([
                                'score' => $scoreEntry->score - 3
                            ]);
                        }
                    }

                    break;
                case 'free_throw':
                    $playerStat->points -= 1;
                    $playerStat->free_throw_attempt -= 1;
                    $playerStat->free_throw_made -= 1;

                    $attempts = $playerStat->free_throw_attempt;
                    $makes = $playerStat->free_throw_made;

                    $percentage = ($attempts > 0) ? ($makes / $attempts) * 100 : 0;

                    $playerStat->free_throw_percentage = $percentage;
                    $playerStat->plus_minus -=1;

                    Log::info('Deleted Team ID: ' . $deletedTeamId);

                    $schedule = Schedule::find($playByPlay->schedule_id);
                    
                    $subsequentPlays = PlayByPlay::where('schedule_id', $playByPlay->schedule_id)
                        ->where('created_at', '>', $deletedTimestamp)
                        ->orderBy('created_at', 'asc')
                        ->get();

                    foreach ($subsequentPlays as $play) {
                        
                        if ($deletedTeamId == $schedule->team1_id) {
                            $play->team_A_score = (int)$play->team_A_score - 1;
                        } 
                        // If the deleted play was from Team 2
                        else if ($deletedTeamId == $schedule->team2_id) {
                            $play->team_B_score = (int)$play->team_B_score - 1;
                        }

                        $play->save();
                    }

                    if ($deletedTeamId) {
                        Log::info('Updating scores table with:', [
                            'schedule_id' => $schedule->id,
                            'team_id' => $deletedTeamId,
                            'quarter' => $deletedQuarter
                        ]);
                
                        $scoreEntry = DB::table('scores')->where([
                            'schedule_id' => $schedule->id,
                            'team_id' => $deletedTeamId,
                            'quarter' => $deletedQuarter
                        ])->first();
                
                        if ($scoreEntry) {
                            
                            DB::table('scores')->where([
                                'schedule_id' => $schedule->id,
                                'team_id' => $deletedTeamId,
                                'quarter' => $deletedQuarter
                            ])->update([
                                'score' => $scoreEntry->score - 1
                            ]);
                        }
                    }

                    $playerStat->save();

                    break;
                case 'assist':
                    $playerStat->assists -= 1;
                    break;
                case 'turnover':
                    $playerStat->turnovers -= 1;
                        break;
                case 'offensive_rebound':
                    $playerStat->offensive_rebounds -= 1;
                    break;
                case 'defensive_rebound':
                    $playerStat->defensive_rebounds -= 1;
                    break;
                case 'steal':
                    $playerStat->steals -= 1;
                    break;
                case 'block':
                    $playerStat->blocks -= 1;
                    break;
                case 'foul':
                    $playerStat->personal_fouls -= 1;
                    break;
                case 'technical_foul':
                    $playerStat->technical_fouls -= 1;
                    break;
                case 'unsportsmanlike_foul':
                    $playerStat->unsportsmanlike_fouls -= 1;
                    break;
                case 'disqualifying_foul':
                    $playerStat->disqualifying_fouls -= 1;
                    break;

                default:
                    break;
            }

            $playerStat->save();
        }

        $playByPlay->delete();

        $teamAScore = PlayerStat::whereHas('player', function ($query) use ($schedule) {
            $query->where('team_id', $schedule->team1->id);
        })
        ->where('schedule_id', $schedule->id)  
        ->sum('points');

        $teamBScore = PlayerStat::whereHas('player', function ($query) use ($schedule) {
            $query->where('team_id', $schedule->team2->id);
        })
        ->where('schedule_id', $schedule->id)  
        ->sum('points');

        Log::info('Schedule:', ['schedule' => $schedule]); 
        Log::info('Team A ID:', ['team1_id' => $schedule->team1->id]);
        Log::info('Team B ID:', ['team2_id' => $schedule->team2->id]);
        

        return response()->json([
            'success' => true,
            'team_a_score' => $teamAScore, 
            'team_b_score' => $teamBScore 
        ]);
    }
    
    public function getPlayByPlay($scheduleId)
    {
        // Fetch the play-by-play data sorted by game_time
        $entries = PlayByPlay::where('schedule_id', $scheduleId)
                            ->orderBy('game_time', 'desc')
                            ->get();

        // Debug the retrieved entries
        Log::info('Retrieved play-by-play data:', $entries->toArray());

        // Format the data
        $formattedEntries = $entries->map(function ($stat) {
            $formattedName = $this->formatPlayerName($stat->player); 
            $action = $this->getActionText($stat->type_of_stat, $stat->result);
            $points = $this->getPoints($stat->type_of_stat, $stat->result); 
            return [
                'id' => $stat->id,
                'player_number' => $stat->player->number,
                'game_time' => $stat->game_time,
                'player_name' => $formattedName,
                'type_of_stat' => $stat->type_of_stat, 
                'action' => $action,
                'points' => $points,
                'team_A_score' => $stat->team_A_score, 
                'team_B_score' => $stat->team_B_score, 
            ];
        });

        // Return the play-by-play data
        return response()->json([
            'play_by_play' => $formattedEntries,
        ]);
    }

    public function getTeamFouls($scheduleId, $quarter)
    {
        $teamFouls = PlayByPlay::where('schedule_id', $scheduleId)
            ->where('quarter', $quarter)
            ->whereIn('type_of_stat', ['foul', 'technical_foul', 'unsportsmanlike_foul', 'disqualifying_foul'])
            ->join('players', 'play_by_plays.player_id', '=', 'players.id')
            ->select('players.team_id', DB::raw('COUNT(play_by_plays.id) as fouls'))
            ->groupBy('players.team_id')
            ->get();

        $schedule = Schedule::with(['team1', 'team2'])->find($scheduleId);

        // Initialize response with 0 fouls for both teams
        $response = [
            'team_1' => $schedule->team1_id ?? null,
            'team_1_fouls' => 0,
            'team_2' => $schedule->team2_id ?? null,
            'team_2_fouls' => 0,
        ];

        foreach ($teamFouls as $foul) {
            if ($foul->team_id == $response['team_1']) {
                $response['team_1_fouls'] = $foul->fouls;
            } elseif ($foul->team_id == $response['team_2']) {
                $response['team_2_fouls'] = $foul->fouls;
            }
        }

        return response()->json($response);
    }
}