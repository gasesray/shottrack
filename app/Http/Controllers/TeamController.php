<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Teams;
use App\Models\tournaments;
use App\Imports\TeamsImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ArrayExport;
use Illuminate\Support\Facades\Log;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
{   
    // Get the tournament ID and category from the request
    $tournamentId = $request->input('tournament_id');
    $category = $request->input('category');

    // Fetch teams filtered by the selected tournament and category if provided
    $teams = Teams::when($tournamentId, function ($query) use ($tournamentId) {
        return $query->where('tournament_id', $tournamentId);
    })
    ->when($category, function ($query) use ($category) {
        return $query->where('category', $category);
    })
    ->latest()
    ->paginate(25); // Order by created_at DESC

    // Get all tournaments for the dropdown
    $tournaments = Tournaments::all();

    return view('teams.list', [
        'teams' => $teams,
        'tournaments' => $tournaments,
    ]);
}


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
       // Fetch all tournaments from the database
        $tournaments = Tournaments::all();

        // Pass the tournaments to the view
        return view('teams.create', compact('tournaments'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
{
    // Define validation rules

    $rules = [
        'name' => 'required|min:5',
        'head_coach_name' => 'required|string|max:255',
        'address' => 'required|min:5',
        'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
        'tournament_id' => 'required|exists:tournaments,id',
    ];

    // Add category validation if the tournament has categories
    $tournament = Tournaments::find($request->tournament_id); 
    

    if ($tournament) {
        if ($tournament->has_categories) {
            // Add category validation if tournament has categories
            $rules['category'] = 'required|string|in:Juniors,Seniors';
        }

        if ($tournament->tournament_type === 'school') {
            // Add school-specific validation if tournament type is 'school'
            $rules['school_president'] = 'required|string|max:255';
            $rules['team_acronym'] = 'required|max:7';
            $rules['sports_director'] = 'required|string|max:255';
            $rules['years_playing_in_bucal'] = 'required|integer';
        }
    }


    // Validate the request data
    $validator = Validator::make($request->all(), $rules);

    if ($validator->passes()) {
        $team = new Teams(); // Use the correct model name
        $team->name = $request->name;
        $team->head_coach_name = $request->head_coach_name;
        $team->address = $request->address;
        $team->tournament_id = $request->tournament_id;

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('logos', 'public');
            $team->logo = $logoPath;
        }

        if ($tournament && $tournament->tournament_type === 'school') {
            $team->team_acronym = $request->team_acronym;
            $team->school_president = $request->school_president;
            $team->sports_director = $request->sports_director;
            $team->years_playing_in_bucal = $request->years_playing_in_bucal;
        }

        // Set category if applicable
        if ($tournament && $tournament->has_categories) {
            $team->category = $request->category;
        }

        $team->save();

        return redirect()->route('teams.index')->with('success', 'Team added successfully.');
    } else {
        return redirect()->route('teams.create')->withInput()->withErrors($validator);
    }
}

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $team = Teams::findOrFail($id);
        $tournaments = Tournaments::all(); // Fetch all tournaments

        return view('teams.edit', [
            'team' => $team,
            'tournaments' => $tournaments // Pass tournaments to the view
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Find the existing team and related tournament
        $team = Teams::findOrFail($id); // Use the correct model name
        $tournament = Tournaments::find($request->tournament_id);
    
        // Define base validation rules
        $rules = [
            'name' => 'required|min:5',
            'head_coach_name' => 'required|string|max:255',
            'address' => 'required|min:5',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'tournament_id' => 'required|exists:tournaments,id',
        ];
    
        // Add additional validation if the tournament has categories
        if ($tournament) {
            if ($tournament->has_categories) {
                $rules['category'] = 'required|string|in:Juniors,Seniors';
            }
    
            if ($tournament->tournament_type === 'school') {
                // Add school-specific validation if tournament type is 'school'
                $rules['school_president'] = 'required|string|max:255';
                $rules['team_acronym'] = 'required|max:7';
                $rules['sports_director'] = 'required|string|max:255';
                $rules['years_playing_in_bucal'] = 'required|integer';
            }
        }
    
        // Validate the request data
        $validator = Validator::make($request->all(), $rules);
    
        if ($validator->passes()) {
            // Update team details
            $team->name = $request->name;
            $team->head_coach_name = $request->head_coach_name;
            $team->address = $request->address;
            $team->tournament_id = $request->tournament_id;
    
            // Handle logo upload
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('logos', 'public');
                $team->logo = $logoPath;
            }
    
            // Set school-specific fields if tournament type is 'school'
            if ($tournament && $tournament->tournament_type === 'school') {
                $team->team_acronym = $request->team_acronym;
                $team->school_president = $request->school_president;
                $team->sports_director = $request->sports_director;
                $team->years_playing_in_bucal = $request->years_playing_in_bucal;
            }
    
            // Set category if applicable
            if ($tournament && $tournament->has_categories) {
                $team->category = $request->category;
            }
    
            $team->save();
    
            return redirect()->route('teams.index')->with('success', 'Team updated successfully.');
        } else {
            return redirect()->route('teams.edit', $id)->withInput()->withErrors($validator);
        }
    }
    


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $team = teams::find($request->id);

        if($team == null){
            session()->flash('error','Team not found');
            return response()->json([
                'status' => false
            ]);
        }

        $team->delete();
        session()->flash('error','Team deleted successfully.');
        return response()->json([
            'status' => true
        ]);
    }
    
    public function getByTournament(Request $request)
    {
        $tournamentId = $request->query('tournament_id');
        $category = $request->query('category');
        
        $query = Teams::query(); // Start with a base query

        if ($tournamentId) {
            // Filter by tournament ID if provided
            $query->where('tournament_id', $tournamentId);
        }

        if ($category) {
            // Filter by category if provided
            $query->where('category', $category);
        }
        
        $teams = $query->get();
        
        return response()->json(['teams' => $teams]);
    }

    public function bulkUploadForm()
    {

        $tournaments = Tournaments::all();

        return view('teams.upload', compact('tournaments')); 
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
    
            // Import the file using the TeamsImport class
            Excel::import(new TeamsImport($tournamentId), $request->file('file'));
    
            Log::info('File import completed successfully.');
    
            return redirect()->route('teams.index')->with('success', 'Teams imported successfully.');
        } catch (\Exception $e) {
            // Log the exception
            Log::error('Error during file import.', ['error' => $e->getMessage()]);
            
            session()->flash('error', 'Check the uploaded file. Ensure that all required fields are filled.');
        
            return redirect()->route('teams.bulkUploadForm')->withInput();
        }
    }

    public function downloadSchoolTemplate()
    {
        $headers = [
            ['Team name', 'Head Coach Name', 'Address', 'Category', 'Team Acronym', 'School President', 'Sports Director', 'Years Playing in Bucal'],
        ];

        // Generate and download the Excel file
        return Excel::download(new ArrayExport($headers), 'School_Tournament_Team_template.xlsx');
    }

    public function downloadNonSchoolTemplate()
    {
        $headers = [
            ['Team name', 'Head Coach Name', 'Address', 'Category'],
        ];

        // Generate and download the Excel file
        return Excel::download(new ArrayExport($headers), 'Non_School_Tournament_Team_template.xlsx');
    }
    
}
