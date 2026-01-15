<?php

namespace App\Services;

use App\Models\Notifikasi;
use Carbon\Carbon;

class NotifikasiService
{
    /**
     * Kirim notifikasi ke user
     */
    public function kirimNotifikasi($idUser, $jenis, $pesan)
    {
        return Notifikasi::create([
            'id_user' => $idUser,
            'jenis' => $jenis,
            'pesan' => $pesan,
            'is_read' => false,
        ]);
    }

    // ... (semua method lama tetap dipertahankan) ...

    /**
     * Notifikasi reminder untuk task kanban yang mendekati deadline
     */
    public function notifikasiKanbanReminder($kanbanTask, $userId)
    {
        $deadline = Carbon::parse($kanbanTask->due_date);
        $now = Carbon::now();
        $diff = $now->diffInDays($deadline, false); // negatif = sudah lewat

        // Cegah notifikasi duplikat hari ini untuk task ini
        $existing = Notifikasi::where('id_user', $userId)
            ->where('jenis', 'pengingat')
            ->where('pesan', 'LIKE', "%{$kanbanTask->title}%")
            ->whereDate('created_at', Carbon::today())
            ->exists();

        if ($existing) return false;

        if ($diff < 0) {
            $pesan = "Task '{$kanbanTask->title}' sudah melewati tenggat waktu! Segera selesaikan.";
        } elseif ($diff == 0) {
            $pesan = "PENGINGAT: Task '{$kanbanTask->title}' jatuh tempo HARI INI!";
        } elseif ($diff == 1) {
            $pesan = "Pengingat: Task '{$kanbanTask->title}' akan jatuh tempo besok!";
        } elseif ($diff <= 3) {
            $pesan = "Pengingat: Task '{$kanbanTask->title}' akan jatuh tempo dalam {$diff} hari.";
        } else {
            return false;
        }

        $this->kirimNotifikasi($userId, 'pengingat', $pesan);
        return true;
    }

    /**
     * Notifikasi ketika task selesai
     */
    public function notifikasiKanbanDone($kanbanTask, $userId)
    {
        $pesan = "Selamat! Task '{$kanbanTask->title}' telah selesai dikerjakan.";
        $this->kirimNotifikasi($userId, 'diterima', $pesan);
        return true;
    }

    /**
     * Notifikasi task baru
     */
    public function notifikasiKanbanBaru($kanbanTask, $userId)
    {
        $deadline = Carbon::parse($kanbanTask->due_date)->locale('id')->isoFormat('D MMMM YYYY');
        $pesan = "Task baru '{$kanbanTask->title}' telah ditambahkan dengan tenggat waktu {$deadline}.";
        $this->kirimNotifikasi($userId, 'update', $pesan);
        return true;
    }
}