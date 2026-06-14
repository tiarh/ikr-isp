<?php

namespace App\Jobs;

use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWaNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 15;

    public function __construct(public string $recipient, public string $message) {}

    public function handle(): void
    {
        try {
            $http = new Client([
                'base_uri' => rtrim(config('psb.evolution.api_url'), '/') . '/',
                'timeout'  => 15,
                'headers'  => [
                    'apikey'       => config('psb.evolution.api_key'),
                    'Content-Type' => 'application/json',
                ],
            ]);
            $instance = config('psb.evolution.instance', 'skynet-ikr');
            $res = $http->post("message/sendText/{$instance}", [
                'json' => [
                    'number' => $this->recipient,
                    'text'   => $this->message,
                ],
            ]);
            Log::info('WA notif sent', [
                'recipient' => $this->recipient,
                'status'    => $res->getStatusCode(),
            ]);
        } catch (\Throwable $e) {
            Log::error('WA notif failed', [
                'recipient' => $this->recipient,
                'err'       => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
