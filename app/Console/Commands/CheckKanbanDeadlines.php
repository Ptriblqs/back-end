<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\KanbanTask; // Pastikan ini sesuai dengan model yang Anda gunakan
use App\Services\NotifikasiService;
use Carbon\Carbon;

class CheckKanbanDeadlines extends Command
{
    protected $signature = 'kanban:check-deadlines';
    protected $description = 'Check kanban deadlines and send notifications';

    protected $notifikasiService;

    public function __construct(NotifikasiService $notifikasiService)
    {
        parent::__construct();
        $this->notifikasiService = $notifikasiService;
    }

    public function handle()
    {
        $this->info('Checking kanban deadlines...');

        // Ambil semua task yang belum done dan ada due_date
        $kanbans = KanbanTask::with('user') // Pastikan relasi 'user' ada di model KanbanTask
            ->where('status', '!=', 'Done')
            ->whereNotNull('due_date')
            ->get();

        $notifCount = 0;

        foreach ($kanbans as $kanban) {
            if (!$kanban->user) { // Menggunakan 'user' bukan 'mahasiswa'
                continue;
            }

            $sent = $this->notifikasiService->notifikasiKanbanReminder(
                $kanban,
                $kanban->user->id // Menggunakan user_id dari relasi user
            );

            if ($sent) {
                $notifCount++;
                $this->info("✓ Sent notification for task: {$kanban->title}");
            }
        }

        $this->info("✓ Done! Sent {$notifCount} notifications.");
        return 0;
    }
}