<?php

declare(strict_types=1);

namespace MetaFramework\Support\Tests\Unit\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use MetaFramework\Support\Tests\TestCase;
use MetaFramework\Support\Traits\Responses;
use Throwable;

class ResponsesTraitTest extends TestCase
{
    public function test_ajax_mode_updates_message_key_and_state(): void
    {
        $subject = new ResponsesDummy;

        $subject->enableAjaxMode();

        $this->assertTrue($subject->isAjaxMode());
        $this->assertSame('mfw_ajax_messages', $subject->getMessageKey());

        $subject->disableAjaxMode();

        $this->assertFalse($subject->isAjaxMode());
        $this->assertSame('mfw_messages', $subject->getMessageKey());
    }

    public function test_response_success_supports_keyed_and_unkeyed_messages(): void
    {
        $subject = new ResponsesDummy;

        $subject->responseSuccess('Saved', 'primary')
            ->responseSuccess('Queued');

        $messages = $subject->fetchMessages();

        $this->assertSame('Saved', $messages['primary']['success']);
        $this->assertSame('Queued', $messages[0]['success']);
    }

    public function test_fetch_response_drops_error_in_debug_mode_unless_kept(): void
    {
        $subject = new ResponsesDummy;

        $subject->triggerWarning('Warning')
            ->triggerDebug('Debug');

        $response = $subject->fetchResponse();

        $this->assertArrayNotHasKey('error', $response);

        $subject = new ResponsesDummy;

        $subject->triggerWarning('Warning')
            ->triggerDebug('Debug')
            ->keepErrors();

        $response = $subject->fetchResponse();

        $this->assertArrayHasKey('error', $response);
    }

    public function test_fetch_error_messages_filters_non_error_types(): void
    {
        $subject = new ResponsesDummy;

        $subject->responseSuccess('Saved')
            ->triggerWarning('Heads up');

        $response = $subject->fetchErrorMessages();

        $messages = array_values($response[$subject->getMessageKey()] ?? []);

        $this->assertCount(1, $messages);
        $this->assertSame('Heads up', $messages[0]['warning']);
    }

    public function test_send_response_redirects_to_route_and_flashes_response(): void
    {
        Route::get('/target', fn () => 'ok')->name('mfw.target');

        $subject = new ResponsesDummy;
        $response = $subject->redirectRoute('mfw.target')->sendResponse('Saved', 'success');

        $this->assertSame(url('/target'), $response->getTargetUrl());
        $this->assertTrue(session()->has('session_response'));
    }

    public function test_push_messages_merges_responses_and_errors(): void
    {
        $primary = new ResponsesDummy;
        $secondary = new ResponsesDummy;

        $primary->responseSuccess('Primary');
        $secondary->triggerError('Secondary');

        $primary->pushMessages($secondary);

        $messages = $primary->fetchMessages();

        $this->assertCount(2, $messages);
        $this->assertTrue($primary->hasErrors());
    }

    public function test_response_exception_sets_error_and_reports(): void
    {
        Auth::shouldReceive('check')->andReturn(false);

        $subject = new ResponsesDummy;
        $subject->triggerException(new \RuntimeException('Boom'));

        $this->assertTrue($subject->hasErrors());
    }

    public function test_console_log_appends_message_key_suffix(): void
    {
        $subject = new ResponsesDummy;

        $subject->consoleLog()
            ->responseSuccess('Logged');

        $this->assertSame('mfw_messages_log', $subject->getMessageKey());
        $this->assertSame('Logged', $subject->fetchMessages()[0]['success']);
    }
}

class ResponsesDummy
{
    use Responses;

    public function __construct()
    {
        $this->reset();
    }

    public function triggerError(string $message): self
    {
        $this->responseError($message);

        return $this;
    }

    public function triggerWarning(string $message, bool $error = true): self
    {
        $this->responseWarning($message, $error);

        return $this;
    }

    public function triggerDebug(string $message, string $notice = ''): self
    {
        $this->responseDebug($message, $notice);

        return $this;
    }

    public function triggerException(Throwable $exception, string $message = ''): self
    {
        return $this->responseException($exception, $message);
    }
}
