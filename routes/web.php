<?php

use App\Http\Controllers\PermissionController;
use App\Http\Controllers\Player_StatsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\PlayerStatsController;
use App\Http\Controllers\PlayByPlayController;
use App\Http\Controllers\ScoreController;
use App\Http\Controllers\TeamStatController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\TeamMetricController;

Route::get('/', function () {
    return view('dashboard');
})->name('dashboard');

Route::get('/tournaments', [TournamentController::class, 'index'])->name('tournaments.index');
Route::get('/teams', [TeamController::class, 'index'])->name('teams.index');
Route::get('/players', [PlayerController::class, 'index'])->name('players.index');
Route::get('/schedules', [ScheduleController::class, 'index'])->name('schedules.index');
Route::get('/playerstats', [PlayerStatsController::class, 'index'])->name('playerstats.index');
Route::get('/teams-by-tournament', [TeamController::class, 'getByTournament'])->name('teams.by_tournament');
Route::get('/team-stats', [TeamStatController::class, 'index'])->name('leaderboards.index');
Route::get('/leaderboards/players', [PlayerStatsController::class, 'playerLeaderboard'])->name('leaderboards.players');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    //Permissions routes

    Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
    Route::get('/permissions/create', [PermissionController::class, 'create'])->name('permissions.create');
    Route::post('/permissions', [PermissionController::class, 'store'])->name('permissions.store');
    Route::get('/permissions/{id}/edit', [PermissionController::class, 'edit'])->name('permissions.edit');
    Route::post('/permissions/{id}', [PermissionController::class, 'update'])->name('permissions.update');
    Route::delete('/permissions', [PermissionController::class, 'destroy'])->name('permissions.destroy');

    //Roles routes

    Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
    Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
    Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
    Route::get('/roles/{id}/edit', [RoleController::class, 'edit'])->name('roles.edit');
    Route::post('/roles/{id}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('/roles', [RoleController::class, 'destroy'])->name('roles.destroy');

    //User routes
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::post('/users/{id}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users', [UserController::class, 'destroy'])->name('users.destroy');

    //Tournament routes
    // Route::get('/tournaments', [TournamentController::class, 'index'])->name('tournaments.index');
    Route::get('/tournaments/create', [TournamentController::class, 'create'])->name('tournaments.create');
    Route::post('/tournaments', [TournamentController::class, 'store'])->name('tournament.store');
    Route::get('/tournaments/{id}/edit', [TournamentController::class, 'edit'])->name('tournaments.edit');
    Route::post('/tournaments/{id}', [TournamentController::class, 'update'])->name('tournaments.update');
    Route::delete('/tournaments', [TournamentController::class, 'destroy'])->name('tournaments.destroy');

    //Team routes
    // Route::get('/teams', [TeamController::class, 'index'])->name('teams.index');
    Route::get('/teams/create', [TeamController::class, 'create'])->name('teams.create');
    Route::post('/teams', [TeamController::class, 'store'])->name('teams.store');
    Route::get('/teams/{id}/edit', [TeamController::class, 'edit'])->name('teams.edit');
    Route::put('/teams/{id}', [TeamController::class, 'update'])->name('teams.update');
    Route::delete('/teams', [TeamController::class, 'destroy'])->name('teams.destroy');  

    // Route to display the bulk upload form
    Route::get('/teams/upload', [TeamController::class, 'bulkUploadForm'])->name('teams.bulkUploadForm');
    // Route to handle the bulk upload submission
    Route::post('/teams/upload', [TeamController::class, 'bulkUpload'])->name('teams.bulkUpload');
    // Route to download the school team template
    Route::get('/teams/template/download/school', [TeamController::class, 'downloadSchoolTemplate'])->name('teams.template.download.school');
    // Route to download the non-school team template
    Route::get('/teams/template/download/non-school', [TeamController::class, 'downloadNonSchoolTemplate'])->name('teams.template.download.nonSchool');


    //Player routes
    // Route::get('/players', [PlayerController::class, 'index'])->name('players.index');
    Route::get('/players/create', [PlayerController::class, 'create'])->name('players.create');
    Route::post('/players', [PlayerController::class, 'store'])->name('players.store');
    Route::get('/players/{id}/edit', [PlayerController::class, 'edit'])->name('players.edit');
    Route::put('/players/{id}', [PlayerController::class, 'update'])->name('players.update');
    Route::delete('/players/{id}', [PlayerController::class, 'destroy'])->name('players.destroy');

    Route::get('/players-by-team', [PlayerController::class, 'getByTeam'])->name('players.by_team');

     // Route to display the bulk upload form
    Route::get('/players/upload', [PlayerController::class, 'bulkUploadForm'])->name('players.bulkUploadForm');
     // Route to handle the bulk upload submission
    Route::post('/players/upload', [PlayerController::class, 'bulkUpload'])->name('players.bulkUpload');
     // Route to download the school team template
    Route::get('/players/template/download/school', [PlayerController::class, 'downloadSchoolTemplate'])->name('players.template.download.school');
     // Route to download the non-school team template
    Route::get('/players/template/download/non-school', [PlayerController::class, 'downloadNonSchoolTemplate'])->name('players.template.download.nonSchool');

    
    //player stats routes
    Route::get('/playerstats/create', [PlayerStatsController::class, 'create'])->name('playerstats.create');
    Route::post('/playerstats', [PlayerStatsController::class, 'store'])->name('playerstats.store');

    //Schedule routes
    // Route::get('/schedules', [ScheduleController::class, 'index'])->name('schedules.index');
    Route::get('/schedules/create', [ScheduleController::class, 'create'])->name('schedules.create');
    Route::post('/schedules', [ScheduleController::class, 'store'])->name('schedules.store');
    Route::get('/schedules/{id}/edit', [ScheduleController::class, 'edit'])->name('schedules.edit');
    Route::put('/schedules/{id}', [ScheduleController::class, 'update'])->name('schedules.update');
    Route::delete('/schedules/{id}', [ScheduleController::class, 'destroy'])->name('schedules.destroy');

    Route::post('/schedules/store-game-time', [ScheduleController::class, 'storeGameTime'])->name('schedules.storeGameTime');
    Route::get('/getGameDetails/{scheduleId}', [ScheduleController::class, 'getGameDetails']);
    Route::get('/scores/{scheduleId}', [ScoreController::class, 'getScores'])->name('scores.get');
    Route::post('/schedules/{id}/complete', [ScheduleController::class, 'markAsCompleted']);
    Route::post('/schedules/update-game-time', [ScheduleController::class, 'updateGameTime'])->name('schedules.updateGameTime');

     // Route to display the bulk upload form
    Route::get('/schedules/upload', [ScheduleController::class, 'bulkUploadForm'])->name('schedules.bulkUploadForm');
     // Route to handle the bulk upload submission
    Route::post('/schedules/upload', [ScheduleController::class, 'bulkUpload'])->name('schedules.bulkUpload');
     // Route to download the school team template
    Route::get('/schedules/template/download/school', [ScheduleController::class, 'downloadSchoolTemplate'])->name('schedules.template.download.school');
     // Route to download the non-school team template
    Route::get('/schedules/template/download/non-school', [ScheduleController::class, 'downloadNonSchoolTemplate'])->name('schedules.template.download.nonSchool');


    //Player Stats routes
    // Route::get('/playerstats', [PlayerStatsController::class, 'index'])->name('playerstats.index');
    Route::get('/playerstats/create/{schedule_id}', [PlayerStatsController::class, 'create'])->name('playerstats.create');
    Route::post('/playerstats', [PlayerStatsController::class, 'store'])->name('playerstats.store');
    Route::get('/playerstats/{id}/edit', [PlayerStatsController::class, 'edit'])->name('playerstats.edit');
    Route::put('/playerstats/{id}', [PlayerStatsController::class, 'update'])->name('playerstats.update');
    Route::delete('/playerstats/{id}', [PlayerStatsController::class, 'destroy'])->name('playerstats.destroy');
    Route::delete('/playbyplay/{id}/delete', [PlayByPlayController::class, 'deletePlayByPlay'])->name('playbyplay.delete');

    Route::get('/playbyplay/{scheduleId}', [PlayByPlayController::class, 'getPlayByPlay'])->name('playbyplay.get');

    Route::post('/scores', [ScoreController::class, 'store'])->name('scores.store');
    Route::put('/scores/{score}', [ScoreController::class, 'update'])->name('scores.update');



    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('/chart-data-a/{schedule_id}', [AnalyticsController::class, 'getChartPointsTeamA'])->name('chart.data.A');
    Route::get('/chart-data-b/{schedule_id}', [AnalyticsController::class, 'getChartPointsTeamB'])->name('chart.data.B');

    Route::get('/assists-chart-data-a/{scheduleId}', [AnalyticsController::class, 'getChartAssistsTeamA']);
    Route::get('/assists-chart-data-b/{scheduleId}', [AnalyticsController::class, 'getChartAssistsTeamB']);
    
    Route::get('/rebounds-chart-data-a/{scheduleId}', [AnalyticsController::class, 'getChartReboundsTeamA']);
    Route::get('/rebounds-chart-data-b/{scheduleId}', [AnalyticsController::class, 'getChartReboundsTeamB']);

    Route::get('/steals-chart-data-a/{scheduleId}', [AnalyticsController::class, 'getChartStealsTeamA']);
    Route::get('/steals-chart-data-b/{scheduleId}', [AnalyticsController::class, 'getChartStealsTeamB']);

    Route::get('/blocks-chart-data-a/{scheduleId}', [AnalyticsController::class, 'getChartBlocksTeamA']);
    Route::get('/blocks-chart-data-b/{scheduleId}', [AnalyticsController::class, 'getChartBlocksTeamB']);

    Route::get('/perfoul-chart-data-a/{scheduleId}', [AnalyticsController::class, 'getChartPerFoulTeamA']);
    Route::get('/perfoul-chart-data-b/{scheduleId}', [AnalyticsController::class, 'getChartPerFoulTeamB']);

    Route::get('/turnovers-chart-data-a/{scheduleId}', [AnalyticsController::class, 'getChartTurnoversTeamA']);
    Route::get('/turnovers-chart-data-b/{scheduleId}', [AnalyticsController::class, 'getChartTurnoversTeamB']);

    Route::get('/offensive_rebounds-chart-data-a/{scheduleId}', [AnalyticsController::class, 'getChartOReboundsTeamA']);
    Route::get('/offensive_rebounds-chart-data-b/{scheduleId}', [AnalyticsController::class, 'getChartOReboundsTeamB']);

    Route::get('/defensive_rebounds-chart-data-a/{scheduleId}', [AnalyticsController::class, 'getChartDReboundsTeamA']);
    Route::get('/defensive_rebounds-chart-data-b/{scheduleId}', [AnalyticsController::class, 'getChartDReboundsTeamB']);

    Route::get('/two_pt_fg_attempt-chart-data-a/{scheduleId}', [AnalyticsController::class, 'getChartTwoPointFGAttemptTeamA']);
    Route::get('/two_pt_fg_attempt-chart-data-b/{scheduleId}', [AnalyticsController::class, 'getChartTwoPointFGAttemptTeamB']);

    Route::get('/two_pt_fg_made-chart-data-a/{scheduleId}', [AnalyticsController::class, 'getChartTwoPointFGMadeTeamA']);
    Route::get('/two_pt_fg_made-chart-data-b/{scheduleId}', [AnalyticsController::class, 'getChartTwoPointFGMadeTeamB']);

    Route::get('/three_pt_fg_attempt-chart-data-a/{scheduleId}', [AnalyticsController::class, 'getChartThreePointFGAttemptTeamA']);
    Route::get('/three_pt_fg_attempt-chart-data-b/{scheduleId}', [AnalyticsController::class, 'getChartThreePointFGAttemptTeamB']);

    Route::get('/three_pt_fg_made-chart-data-a/{scheduleId}', [AnalyticsController::class, 'getChartThreePointFGMadeTeamA']);
    Route::get('/three_pt_fg_made-chart-data-b/{scheduleId}', [AnalyticsController::class, 'getChartThreePointFGMadeTeamB']);

    Route::get('/two_pt_percentage-chart-data-a/{scheduleId}', [AnalyticsController::class, 'getChartTwoPointPercentageTeamA']);
    Route::get('/two_pt_percentage-chart-data-b/{scheduleId}', [AnalyticsController::class, 'getChartTwoPointPercentageTeamB']);

    Route::get('/three_pt_percentage-chart-data-a/{scheduleId}', [AnalyticsController::class, 'getChartThreePointPercentageTeamA']);
    Route::get('/three_pt_percentage-chart-data-b/{scheduleId}', [AnalyticsController::class, 'getChartThreePointPercentageTeamB']);

    Route::get('/free_throw_attempt-chart-data-a/{scheduleId}', [AnalyticsController::class, 'getChartFreeThrowAttemptTeamA']);
    Route::get('/free_throw_attempt-chart-data-b/{scheduleId}', [AnalyticsController::class, 'getChartFreeThrowAttemptTeamB']);

    Route::get('/free_throw_made-chart-data-a/{scheduleId}', [AnalyticsController::class, 'getChartFreeThrowMadeTeamA']);
    Route::get('/free_throw_made-chart-data-b/{scheduleId}', [AnalyticsController::class, 'getChartFreeThrowMadeTeamB']);

    Route::get('/free_throw_percentage-chart-data-a/{scheduleId}', [AnalyticsController::class, 'getChartFreeThrowPercentageTeamA']);
    Route::get('/free_throw_percentage-chart-data-b/{scheduleId}', [AnalyticsController::class, 'getChartFreeThrowPercentageTeamB']);

    Route::get('/free_throw_attempt_rate-chart-data-a/{scheduleId}', [AnalyticsController::class, 'getChartFreeThrowAttemptRateTeamA']);
    Route::get('/free_throw_attempt_rate-chart-data-b/{scheduleId}', [AnalyticsController::class, 'getChartFreeThrowAttemptRateTeamB']);

    Route::get('/plus_minus-chart-data-a/{scheduleId}', [AnalyticsController::class, 'getChartPlusMinusTeamA']);
    Route::get('/plus_minus-chart-data-b/{scheduleId}', [AnalyticsController::class, 'getChartPlusMinusTeamB']);

    Route::get('/effective_field_goal_percentage-chart-data-a/{scheduleId}', [AnalyticsController::class, 'getChartEffectiveFieldGoalPercentageTeamA']);
    Route::get('/effective_field_goal_percentage-chart-data-b/{scheduleId}', [AnalyticsController::class, 'getChartEffectiveFieldGoalPercentageTeamB']);

    Route::get('/turnover_ratio-chart-data-a/{scheduleId}', [AnalyticsController::class, 'getChartTurnoverRatioTeamA']);
    Route::get('/turnover_ratio-chart-data-b/{scheduleId}', [AnalyticsController::class, 'getChartTurnoverRatioTeamB']);
    Route::get('/schedules-by-tournament/{tournamentId}', [ScheduleController::class, 'getSchedulesByTournament']);

    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

    //Team Metric Routes:
    Route::post('/team-metrics/store', [TeamMetricController::class, 'store'])->name('team-metrics.store');

    Route::get('/team-fouls/{scheduleId}/{quarter}', [PlayByPlayController::class, 'getTeamFouls']);
    Route::get('/check-fouls/{playerId}/{scheduleId}', [PlayerStatsController::class, 'checkPlayerFouls']);     
});
require __DIR__.'/auth.php';
