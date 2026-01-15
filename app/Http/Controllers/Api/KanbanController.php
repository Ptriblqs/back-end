<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KanbanTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Services\NotifikasiService; // Pastikan ini di-import

class KanbanController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $now = Carbon::now('Asia/Jakarta');

        // Tandai task expired
        KanbanTask::where('user_id', $user->id)
            ->where('due_date', '<', $now)
            ->where('status', '!=', 'Done')
            ->update(['is_expired' => true]);

        KanbanTask::where('user_id', $user->id)
            ->where('status', 'Done')
            ->update(['is_expired' => false]);

        // Ambil task aktif
        $activeTasks = KanbanTask::where('user_id', $user->id)
            ->where('status', '!=', 'Done')
            ->whereNotNull('due_date')
            ->get();

        // Cek & kirim notifikasi
        $notifikasiService = app(NotifikasiService::class);
        foreach ($activeTasks as $task) {
            $notifikasiService->notifikasiKanbanReminder($task, $user->id);
        }

        // Response data
        $tasks = KanbanTask::where('user_id', $user->id)
            ->orderBy('due_date', 'asc')
            ->get()
            ->groupBy('status');

        return response()->json([
            'success' => true,
            'data' => [
                'todo' => $tasks->get('To Do', collect())->values(),
                'in_progress' => $tasks->get('In Progress', collect())->values(),
                'done' => $tasks->get('Done', collect())->values(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:To Do,In Progress,Done',
            'due_date' => 'required|date',
        ]);

        $task = KanbanTask::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? '',
            'status' => $validated['status'],
            'due_date' => Carbon::parse($validated['due_date']),
            'is_expired' => false,
        ]);

        // Kirim notifikasi task baru
        $notifikasiService = app(NotifikasiService::class);
        $notifikasiService->notifikasiKanbanBaru($task, Auth::id());

        return response()->json([
            'success' => true,
            'message' => 'Task berhasil ditambahkan',
            'data' => $task,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $task = KanbanTask::where('user_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:To Do,In Progress,Done',
            'due_date' => 'required|date',
        ]);

        $statusOrder = ['To Do', 'In Progress', 'Done'];
        $currentIndex = array_search($task->status, $statusOrder);
        $newIndex = array_search($validated['status'], $statusOrder);

        if ($newIndex > $currentIndex + 1) {
            return response()->json([
                'success' => false,
                'message' => 'Status harus berurutan: To Do → In Progress → Done',
            ], 422);
        }

        $now = Carbon::now('Asia/Jakarta');
        $dueDate = Carbon::parse($validated['due_date'])->timezone('Asia/Jakarta');
        $isExpired = ($validated['status'] !== 'Done' && $now->greaterThan($dueDate));

        $task->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? '',
            'status' => $validated['status'],
            'due_date' => $dueDate,
            'is_expired' => $isExpired,
        ]);

        // Notifikasi jika selesai
        if ($validated['status'] === 'Done') {
            $notifikasiService = app(NotifikasiService::class);
            $notifikasiService->notifikasiKanbanDone($task, Auth::id());
        }

        return response()->json([
            'success' => true,
            'message' => 'Task berhasil diupdate',
            'data' => $task,
        ]);
    }

    public function destroy($id)
    {
        $task = KanbanTask::where('user_id', Auth::id())->findOrFail($id);
        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Task berhasil dihapus',
        ]);
    }

    public function moveStatus(Request $request, $id)
    {
        $task = KanbanTask::where('user_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:To Do,In Progress,Done',
        ]);

        $statusOrder = ['To Do', 'In Progress', 'Done'];
        $currentIndex = array_search($task->status, $statusOrder);
        $newIndex = array_search($validated['status'], $statusOrder);

        if ($newIndex > $currentIndex + 1) {
            return response()->json([
                'success' => false,
                'message' => 'Status harus berurutan: To Do → In Progress → Done',
            ], 422);
        }

        $isExpired = $task->is_expired;
        if ($validated['status'] === 'Done') {
            $isExpired = false;
            $notifikasiService = app(NotifikasiService::class);
            $notifikasiService->notifikasiKanbanDone($task, Auth::id());
        }

        $task->update([
            'status' => $validated['status'],
            'is_expired' => $isExpired,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status berhasil diubah',
            'data' => $task,
        ]);
    }
}