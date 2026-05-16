<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NotificationPipelineTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

   protected function setUp(): void
   {
       parent::setUp();
       //отдельная очередь, иначе воркер контейнера будет все забирать
       config(['queue.connections.rabbitmq.options.queue.exchange_routing_key' => 'testing_%s']);
   }

    protected function tearDown(): void
    {
        try {
            $queueManager = app('queue');
            $connection = $queueManager->connection('rabbitmq');

            $connection->purge('testing_high');
            $connection->purge('testing_default');
        } catch (\Throwable $e) {
            //чистить нечего
        }
        parent::tearDown();
    }

    public function test_notification_pipeline_success()
    {

        //перехватываем запросы
        Http::fake([
            '*test.ru*' => Http::response(['status' => 'success'], 200)
        ]);


        $response = $this->postJson('/api/notifications/send', [
            'channel' => 'sms',
            'message' => 'Your security code: 4412',
            'recipients' => ['+79991112233'],
            'priority' => 'high',
        ], [
            'X-Idempotency-Key' => 'test-unique-key-123'
        ]);

        $response->assertStatus(202);
        $response->assertJsonStructure(['batch_id', 'status']);

        $this->assertDatabaseHas('notifications', [
            'recipient_id' => '+79991112233',
            'status' => 'queued'
        ]);

        $this->artisan('queue:work', [
            '--once' => true,
            '--queue' => 'testing_high'
        ]);

        $this->assertDatabaseHas('notifications', [
            'recipient_id' => '+79991112233',
            'status' => 'delivered'
        ]);
    }

    public function test_idempotency_blocks_duplicate_requests()
    {
        Http::fake([
            '*test.ru*' => Http::response(['status' => 'success'], 200)
        ]);

        $payload = [
            'channel' => 'email',
            'message' => 'Hello',
            'recipients' => ['test@example.com']
        ];

        $response1 = $this->postJson('/api/notifications/send', $payload, [
            'X-Idempotency-Key' => 'same-key'
        ]);
        $response1->assertStatus(202);

        $response2 = $this->postJson('/api/notifications/send', $payload, [
            'X-Idempotency-Key' => 'same-key'
        ]);
        $response2->assertStatus(409);
    }

    public function test_rabbitmq_retry_mechanism_on_gateway_failure()
    {
        Http::fake(['*test.local*' => Http::response(['error' => 'Gateway Dead'], 500)]);

        $randomPhone = '+79997776655';

        $this->postJson('/api/notifications/send', [
            'channel' => 'sms',
            'message' => 'Retry testing',
            'recipients' => [$randomPhone],
            'priority' => 'default'
        ], ['X-Idempotency-Key' => fake()->uuid()]);

        $this->artisan('queue:work', [
            'connection' => 'rabbitmq',
            '--once' => true,
            '--queue' => 'testing_default'
        ]);

        $this->assertDatabaseHas('notifications', [
            'recipient_id' => $randomPhone,
            'status' => 'queued'
        ]);
    }

}
