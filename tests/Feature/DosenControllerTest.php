<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Dosen;

class DosenController extends Controller
{
    // Middleware untuk autentikasi
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    // GET /api/dosen
    public function index()
    {
        $dosens = Dosen::all(); // tanpa eager load relasi
        return response()->json($dosens, 200);
    }

    // GET /api/dosen/{id}
    public function show($id)
    {
        $dosen = Dosen::find($id);

        if (!$dosen) {
            return response()->json(['message' => 'Dosen not found'], 404);
        }

        return response()->json($dosen, 200);
    }

    // POST /api/dosen
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'nik' => 'required|string|unique:dosens,nik',
            'prodi_id' => 'required|exists:program_studis,id',
            'bidang_keahlian' => 'required|string',
        ]);

        $dosen = Dosen::create($validated);

        return response()->json($dosen, 201);
    }
}
