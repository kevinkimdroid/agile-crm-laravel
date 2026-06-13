<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

/**
 * Serves the legacy crm-client-feedback/index.php page through Laravel.
 *
 * Kept as an invokable controller (instead of a route closure) so that
 * `php artisan route:cache` works — closures cannot be serialized.
 */
class CrmClientFeedbackProxyController extends Controller
{
    public function __invoke(): Response
    {
        ob_start();
        require base_path('crm-client-feedback/index.php');

        return response(ob_get_clean() ?: '')
            ->header('Content-Type', 'text/html; charset=utf-8');
    }
}
