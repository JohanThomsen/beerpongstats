<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Broadcast;
use App\Events\PocPing;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('ws:ping {message?}', function (?string $message = null) {
    $payload = $message ?? 'pong';

    // Dispatch a simple broadcast to the public "poc" channel
    broadcast(new PocPing($payload));

    $this->info("Broadcasted PocPing with message: {$payload}");
})->purpose('Broadcast a test websocket event to the public mvp channel');
