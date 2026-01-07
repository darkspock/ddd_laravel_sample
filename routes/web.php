<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return '';
});

// Serve React frontend from /app
Route::get('/app/{any?}', function () {
    $path = public_path('app/index.html');

    if (!file_exists($path)) {
        abort(404, 'Frontend not built. Run: cd frontend && pnpm build');
    }

    return response()->file($path);
})->where('any', '.*');
