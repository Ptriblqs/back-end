<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MahasiswaController extends Controller
{
    /**
     * Display a listing of mahasiswa
     */
    public function index()
    {
        $mahasiswa = Mahasiswa::with(['user', 'programStudi'])->get();
        return response()->json($mahasiswa, 200);
    }

    /**
     * Display the specified mahasiswa
     */
    public function show($id)
    {
        $mahasiswa = Mahasiswa::with(['user', 'programStudi'])->find($id);
        
        if (!$mahasiswa) {
            return response()->json([
                'message' => 'Mahasiswa not found'
            ], 404);
        }
        
        return response()->json($mahasiswa, 200);
    }

    /**
     * Store a newly created mahasiswa
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'nim' => 'required|string|unique:mahasiswa,nim',
            'prodi_id' => 'required|exists:program_studis,id',
            'portofolio' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 400);
        }

        $mahasiswa = Mahasiswa::create([
            'user_id' => $request->user_id,
            'nim' => $request->nim,
            'prodi_id' => $request->prodi_id,
            'portofolio' => $request->portofolio,
        ]);

        return response()->json($mahasiswa, 201);
    }

    /**
     * Update the specified mahasiswa
     */
    public function update(Request $request, $id)
    {
        $mahasiswa = Mahasiswa::find($id);
        
        if (!$mahasiswa) {
            return response()->json([
                'message' => 'Mahasiswa not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nim' => 'string|unique:mahasiswa,nim,' . $id,
            'prodi_id' => 'exists:program_studis,id',
            'portofolio' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 400);
        }

        $mahasiswa->update($request->only(['nim', 'prodi_id', 'portofolio']));

        return response()->json($mahasiswa, 200);
    }

    /**
     * Remove the specified mahasiswa
     */
    public function destroy($id)
    {
        $mahasiswa = Mahasiswa::find($id);
        
        if (!$mahasiswa) {
            return response()->json([
                'message' => 'Mahasiswa not found'
            ], 404);
        }
        
        $mahasiswa->delete();

        return response()->json([
            'message' => 'Mahasiswa deleted successfully'
        ], 200);
    }
}