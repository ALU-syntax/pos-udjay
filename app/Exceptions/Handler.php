<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->renderable(function (\Illuminate\Database\QueryException $e, $request) {
            if ($request->is('api/v1/order-table/*', 'api/v1/internal/order-table/*')) {
                return response()->json(['status' => 'error', 'code' => 'ORDER_TABLE_RETRY_REQUIRED',
                    'message' => 'Refresh payment-state before retrying.'], 503);
            }
        });
        $this->reportable(function (Throwable $e) {
            //
        });
    }
}
