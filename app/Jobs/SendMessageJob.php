<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Services\Notification\GatewayProvider;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SendMessageJob implements ShouldQueue
{
    use Queueable, Batchable, Dispatchable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public int $backoff = 5;

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

        $lock = Cache::lock("notification_processing:{$this->notification->id}", 30);
        if (!$lock->get()) {
            $this->release(5);
            return;
        }

        try {
            //Проверяем статус в БД
            $this->notification->refresh();
            if (in_array($this->notification->status, ['sent', 'delivered'])) {
                $lock->release();
                return;
            }

            $provider = GatewayProvider::make($this->notification->channel);
            $isSuccess = $provider->send(
                $this->notification->recipient_id,
                $this->notification->message
            );

            if ($isSuccess) {
                $this->notification->update(['status' => 'sent']);
                //Не описано откуда берется статус
                //либо шлюз вызывает при успехе эндпоинт, либо периодическая проверка нужна. Потому пока просто
                $this->notification->update(['status' => 'delivered']);
            } else {
                // Если все попытки исчерпаны — выставляем статус отброшено
                if ($this->attempts() >= $this->tries) {
                    $this->notification->update(['status' => 'failed']);
                }
                throw new \Exception('Retrying...');
            }
        } finally {
            $lock->release();
        }

    }
}
