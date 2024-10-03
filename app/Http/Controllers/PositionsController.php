<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Positions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use function Laravel\Prompts\search;

class PositionsController extends Controller
{
    public function index(Request $request)
    {

        $position = Positions::all();

        return Inertia::render('Personnel/Positions',[
            'positions' =>$position
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            "position" => 'required|unique:positions,position'
        ]);

        try {

            $result = Positions::create($request->all());

            $response = [
                "type"      => "success",
                "message"   => "Successfully added a position."
            ];

        } catch (\Exception $e) {
            Log::channel('store')->error(json_encode([
                "controller" => "PositionsController",
                "params" => $e
            ]));

            $response = [
                "type"      => "error",
                "message"   => "Server Error. Please Try Again Later."
            ];
        }

        return Inertia::render('Personnel/Positions',[
            'positions' => Positions::all(),
            'response' => $response
        ]);
    }

    public function show(string $id)
    {
        dd("stest");
    }

    public function update(Request $request, string $id)
    {

    }

    public function destroy(string $id)
    {

    }

    public function search(Request $request){
        $position = [];
        if($request->exists("search")){
            $search = $request->search;
            $position = Positions::where('position','LIKE',"%{$search}%")
                    ->orWhere('abbreviation','LIKE',"%{$search}%")
                    ->get();
        }

        return response()->json($position);
    }
}
