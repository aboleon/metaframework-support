<?php

declare(strict_types=1);

namespace MetaFramework\Support\Tests\Unit\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MetaFramework\Support\Tests\TestCase;
use MetaFramework\Support\Traits\Ajax;
use MetaFramework\Support\Traits\Responses;

class AjaxTraitTest extends TestCase
{
    public function test_distribute_returns_error_when_action_missing(): void
    {
        $this->app->instance('request', Request::create('/ajax', 'POST'));

        $subject = new AjaxDummy;
        $response = $subject->distribute($this->app['request']);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(400, $response->getStatusCode());

        $payload = $response->getData(true);

        $this->assertTrue($payload['error']);
        $this->assertSame('This request cannot be interpreted.', $payload['mfw_ajax_messages'][0]['danger']);
    }

    public function test_distribute_returns_error_when_action_missing_on_class(): void
    {
        $this->app->instance('request', Request::create('/ajax', 'POST', ['action' => 'missingMethod']));

        $subject = new AjaxDummy;
        $response = $subject->distribute($this->app['request']);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(405, $response->getStatusCode());

        $payload = $response->getData(true);

        $this->assertTrue($payload['error']);
        $this->assertSame('This request cannot be processed.', $payload['mfw_ajax_messages'][0]['danger']);
    }

    public function test_distribute_calls_action_and_collects_input_and_callback(): void
    {
        $this->app->instance('request', Request::create('/ajax', 'POST', [
            'action' => 'ping',
            'callback' => 'handlePing',
            'value' => '42',
        ]));

        $subject = new AjaxDummy;
        $response = $subject->distribute($this->app['request']);

        $this->assertIsArray($response);
        $this->assertSame('handlePing', $response['callback']);
        $this->assertSame('42', $response['input']['value']);
        $this->assertSame('pong', $response['mfw_ajax_messages'][0]['success']);
    }
}

class AjaxDummy
{
    use Ajax;
    use Responses;

    public function __construct()
    {
        $this->reset();
    }

    public function ping(Request $request): array
    {
        $this->responseSuccess('pong');

        return $this->fetchResponse();
    }
}
