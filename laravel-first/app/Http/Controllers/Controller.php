<?php

namespace App\Http\Controllers;

use Closure;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

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
