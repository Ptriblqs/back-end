<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProgramStudi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProgramStudiController extends Controller
{
    public function index()
    {
        $programStudi = ProgramStudi::all();
        return response()->json($programStudi, 200);
    }

    public function show($id)
    {
        $prodi = ProgramStudi::find($id);
        
        if (!$prodi) {
            return response()->json([
                'message' => 'Program Studi not found'
            ], 404);
        }

        return response()->json($prodi, 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_prodi' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 400);
        }

        $prodi = ProgramStudi::create([
            'nama_prodi' => $request->nama_prodi
        ]);

        return response()->json($prodi, 201);
    }

    public function update(Request $request, $id)
    {
        $prodi = ProgramStudi::find($id);
        
        if (!$prodi) {
            return response()->json([
                'message' => 'Program Studi not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama_prodi' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 400);
        }

        $prodi->update([
            'nama_prodi' => $request->nama_prodi
        ]);

        return response()->json($prodi, 200);
    }

    public function destroy($id)
    {
        $prodi = ProgramStudi::find($id);
        
        if (!$prodi) {
            return response()->json([
                'message' => 'Program Studi not found'
            ], 404);
        }

        $prodi->delete();

        return response()->json([
            'message' => 'Program Studi deleted successfully'
        ], 200);
    }
}