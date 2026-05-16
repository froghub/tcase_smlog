<?php

namespace App\Jobs;

use App\Models\Notification;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendMessageJob implements ShouldQueue
{
    use Queueable, Batchable, Dispatchable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $errorChance = 10;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Notification $notification)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }
        //Подменяем запрос и передачу данных на имитацию с небольшим шансом фейла
//        $response = Http::fake([
//            'test.local' => function () {
//                if (rand(1, 100) <= $this->errorChance) {
//                    return Http::response(['error' => 'Server Error'], 500);
//                }
//                return Http::response(['status' => 'success', 'data' => []], 200);
//            },
//        ]);

        $response = Http::post('test.ru', [
            'type' => $this->notification->channel,
            'to' => $this->notification->recipient_id,
            'text' => $this->notification->message,
        ]);

        if ($response->successful()) {
            $this->notification->update(['status' => 'sent']);

            //Не описано откуда берется статус
            //либо шлюз вызывает при успехе эндпоинт, либо периодическая проверка нужна. Потому пока просто
            $this->notification->update(['status' => 'delivered']);
        } else {
            // Если все попытки исчерпаны — выставляем статус отброшено
            if ($this->attempts() >= $this->tries) {
                $this->notification->update(['status' => 'failed']);
            }
            throw new \Exception('Gateway temporary unavailable. Retrying...');
        }
    }
}
