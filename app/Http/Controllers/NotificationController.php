<?php

namespace App\Http\Controllers;

use App\Http\Requests\NotificationSendRequest;
use App\Jobs\SendMessageJob;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class NotificationController extends Controller
{
    public function send(NotificationSendRequest $request)
    {
        $idempotencyKey = $request->header('X-Idempotency-Key');
        if ($idempotencyKey && !Cache::lock('idempotency:'.$idempotencyKey, 60)->get()) {
            return response()->json(['error' => 'Duplicate request detected.'], 409);
        }

        $validated = $request->validated();

        $batchId = Str::uuid()->toString();
        $jobs = [];
        $priority = $validated['priority'] ?? 'default';

        foreach ($validated['recipients'] as $recipient) {
            $notification = Notification::create([
                'batch_id' => $batchId,
                'recipient_id' => $recipient,
                'channel' => $validated['channel'],
                'message' => $validated['message'],
                'status' => 'queued'
            ]);

//            $jobs[] = (new SendMessageJob($notification))->onQueue($priority);
            $jobs[] = new SendMessageJob($notification);
        }

        Bus::batch($jobs)->onQueue($priority)->dispatch();

        return response()->json(['batch_id' => $batchId, 'status' => 'accepted'], 202);
    }

    public function recipientHistory($id)
    {
        return response()->json(Notification::where('recipient_id', $id)->get());
    }
}
