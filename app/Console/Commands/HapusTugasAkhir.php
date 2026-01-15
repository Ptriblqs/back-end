<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;


class HapusTugasAkhir extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:hapus-tugas-akhir';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $batas = Carbon::now()->subDays(7);
        $jumlah = TugasAkhir::where('status', 'tolak')
        ->where('updated_at', '<', $batas)
        ->delete();

        $this->info("$jumlah data tugas akhir yang ditolak telah dihapus");
    }
}
