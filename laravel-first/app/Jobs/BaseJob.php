<?php

namespace App\Jobs;

use Closure;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class BaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected function multiple_process( Closure $closure ): void
    {
        try {
            DB::beginTransaction();
            $closure();
            DB::commit();
        } catch ( Exception $exception ) {
            DB::rollBack();
            info("Queue hatası.");
        }
    }

    protected function single_process( Closure $closure ): void
    {
        try {
            $closure();
        } catch ( Exception $exception ) {
            info("Queue hatası.");
        }
    }
}
