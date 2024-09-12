<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\teams;
use App\Models\tournaments;

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
        'team_acronym' => 'required|max:5',
        'head_coach_name' => 'required|string|max:255',
        'school_president' => 'required|string|max:255',
        'sports_director' => 'required|string|max:255',
        'years_playing_in_bucal' => 'required|integer',
        'address' => 'required|min:5',
        'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
        'tournament_id' => 'required|exists:tournaments,id',
    ];

    // Add category validation if the tournament has categories
    $tournament = Tournaments::find($request->tournament_id); // Use the correct model name
    if ($tournament && $tournament->has_categories) {
        $rules['category'] = 'required|string|in:Juniors,Seniors';
    }

    // Validate the request data
    $validator = Validator::make($request->all(), $rules);

    if ($validator->passes()) {
        $team = new Teams(); // Use the correct model name
        $team->name = $request->name;
        $team->team_acronym = $request->team_acronym;
        $team->head_coach_name = $request->head_coach_name;
        $team->school_president = $request->school_president;
        $team->sports_director = $request->sports_director;
        $team->years_playing_in_bucal = $request->years_playing_in_bucal;
        $team->address = $request->address;
        $team->tournament_id = $request->tournament_id;

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('logos', 'public');
            $team->logo = $logoPath;
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
        $team = teams::findOrFail($id); // Use the correct model name
        $tournament = Tournaments::find($request->tournament_id); // Use the correct model name

        $validator = Validator::make($request->all(), [
            'category' => 'nullable|string|in:Juniors,Seniors',
            'name' => 'required|min:5',
            'team_acronym' => 'required|max:5',
            'head_coach_name' => 'required|string|max:255',
            'school_president' => 'required|string|max:255',
            'sports_director' => 'required|string|max:255',
            'years_playing_in_bucal' => 'required|integer',
            'address' => 'required|min:5',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            'tournament_id' => 'nullable|exists:tournaments,id',
        ]);

        if ($validator->passes()) {
            // Update team details
            $team->name = $request->name;
            $team->team_acronym = $request->team_acronym;
            $team->head_coach_name = $request->head_coach_name; // Fixed field name
            $team->school_president = $request->school_president;
            $team->sports_director = $request->sports_director;
            $team->years_playing_in_bucal = $request->years_playing_in_bucal;
            $team->address = $request->address;

             // Handle logo upload
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('logos', 'public');
                $team->logo = $logoPath;
            }
            
            // Update tournament_id only if provided
            if ($request->has('tournament_id')) {
                $team->tournament_id = $request->tournament_id;
                $tournament = Tournaments::find($request->tournament_id); // Fetch updated tournament if necessary
            }
            
            // Check if the tournament allows categories
            if ($tournament && $tournament->has_categories && !$request->has('category')) {
                return redirect()->route('teams.edit', $id)->withInput()->withErrors(['category' => 'Category is required for this tournament.']);
            }

            if ($tournament && !$tournament->has_categories && $request->has('category')) {
                return redirect()->route('teams.edit', $id)->withInput()->withErrors(['category' => 'Category is not allowed for this tournament.']);
            }

            // Update the team
            $team->category = $request->category; // Set the category if applicable
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
    
}
