<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class QueueWork extends Command
{
    protected $signature = 'q';

    protected $description = 'Kuyruk çalıştırır';

    public function handle()
    {
        dump('Queue working...');
        Artisan::call('queue:work', [
            '--queue' => 'channels,default',
        ]);
    }
}
