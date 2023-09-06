<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Queue;
use App\Models\SmsCampaign;
use App\Services\AuthService;
use Illuminate\Http\Request;

class SmsSendingTestController extends Controller
{
    public function testSmsSendingWithRabbitMQ()
    {
        // Fake the Queue to avoid actual job processing
        Queue::fake();

        // Get the authenticated user
        $user = auth()->user();

        // Create a new SmsCampaign instance for testing purposes
        $campaign = SmsCampaign::make([
            'name' => "Test Campaign",
        ]);

        // Dispatch the SendSmsCampaign job on the 'rabbitmq' connection and 'user_campaigns' queue
        dispatch(new SendSmsCampaign($user, $campaign))
            ->onConnection('rabbitmq')
            ->onQueue('user_campaigns');

        // Assert that the SendSmsCampaign job was pushed onto the 'test_queue'
        Queue::assertPushedOn('test_queue', SendSmsCampaign::class);
    }

}
