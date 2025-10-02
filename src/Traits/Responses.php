<?php

declare(strict_types=1);

namespace MetaFramework\Traits;

use Illuminate\Http\RedirectResponse;
use Throwable;

trait Responses
{
    protected array $response = [];
    protected string|null $redirect_route = null;
    protected string|null $redirect_to = null;
    private bool $disable_responses = false;
    protected bool $disable_redirects = false;
    protected bool $ajax_mode = false;
    protected bool $debugMode = false;
    protected bool $keepErrors = false;
    protected bool $restrictedToDev = false;
    protected bool $as_exception = false;
    protected string $messageKey = 'messages';
    protected bool $consoleLog = false;

    public function reset()
    {
        $this->response          = [];
        $this->redirect_route    = null;
        $this->redirect_to       = null;
        $this->disable_responses = false;
        $this->disable_redirects = false;
        $this->ajax_mode         = false;
        $this->debugMode         = false;
        $this->keepErrors        = false;
        $this->restrictedToDev   = false;
        $this->messageKey        = 'mfw_messages';
    }

    public function getDefaultMessageKey(): string
    {
        return 'mfw_messages';
    }

    public function throwException(): self
    {
        $this->as_exception = true;

        return $this;
    }

    public function shouldBeException(): bool
    {
        return $this->as_exception === true;
    }

    public function disableExceptionMode(): self
    {
        $this->as_exception = false;

        return $this;
    }

    public function enableAjaxMode(): static
    {
        $this->ajax_mode = true;
        $this->messageKey = 'mfw_ajax_messages';

        return $this;
    }

    public function disableAjaxMode(): static
    {
        $this->ajax_mode = false;
        $this->messageKey = $this->getDefaultMessageKey();

        return $this;
    }

    public function disableMessages(): static
    {
        $this->disable_responses = true;

        return $this;
    }

    public function enableMessages(): static
    {
        $this->disable_responses = false;

        return $this;
    }

    public function enabledMessages(): bool
    {
        return $this->disable_responses === false;
    }

    public function fetchResponse(): array
    {
        if ($this->debugMode && ! $this->keepErrors) {
            unset($this->response['error']);
        }

        return $this->response;
    }

    public function consoleLog(): self
    {
        $this->consoleLog = true;
        $this->messageKey = $this->getMessageKey().'_log';
        return $this;
    }

    /*
     * Force keep errors if disabled by debug mode or else
     */
    public function keepErrors(): self
    {
        $this->keepErrors        = true;
        $this->response['error'] = true;

        return $this;
    }

    public function fetchMessages(): array
    {
        return $this->response[$this->messageKey] ?? [];
    }

    public function disableRedirects(): static
    {
        $this->disable_redirects = true;

        return $this;
    }

    public function fetchErrorMessages(): array
    {
        if (array_key_exists($this->getMessageKey(), $this->response)) {
            $this->response[$this->getMessageKey()] = array_filter($this->response[$this->getMessageKey()], fn($key) => array_filter($key, fn($key) => in_array($key, ['danger', 'warning']), ARRAY_FILTER_USE_KEY));
        }

        return $this->response;
    }

    public function fetchResponseElement(string $key)
    {
        return $this->response[$key] ?? null;
    }

    public function hasErrors(): bool
    {
        return array_key_exists('error', $this->response);
    }

    public function mustAbort(): bool
    {
        return array_key_exists('abort', $this->response);
    }

    public function canContinue(): bool
    {
        return ! $this->mustAbort();
    }

    public function responseNotice($message): static
    {
        if ($this->enabledMessages()) {
            $this->response[$this->getMessageKey()][]['info'] = $message;
        }

        return $this;
    }

    public function responseSuccess($message, string|int $key = ''): static
    {
        if ($this->enabledMessages()) {
            if ( ! empty($key)) {
                $this->response[$this->getMessageKey()][$key]['success'] = $message;
            } else {
                $this->response[$this->getMessageKey()][]['success'] = $message;
            }

            return $this;
        }

        return $this;
    }

    public function whitout(string $element): static
    {
        unset($this->response[$element]);

        return $this;
    }

    protected function responseError($message): void
    {
        if ($this->enabledMessages()) {
            $this->response['error']                          = true;
            $this->response[$this->getMessageKey()][]['danger'] = $message;
        }
    }

    protected function responseLog($message): void
    {
        $this->response[$this->getMessageKey()][]['log'] = $message;
    }

    protected function responseAbort($message): void
    {
        if ($this->enabledMessages()) {
            $this->response['abort']                          = true;
            $this->response[$this->getMessageKey()][]['danger'] = $message;
        }
    }

    protected function responseWarning($message, bool $error = true): void
    {
        if ($this->enabledMessages()) {
            if ($error) {
                $this->response['error'] = true;
            }
            $this->response[$this->getMessageKey()][]['warning'] = $message;
        }
    }

    protected function responseDebug($message, string $notice = ''): void
    {
        $this->debugMode = true;

        $this->responseWarning('<b>DEBUG&nbsp;|&nbsp;</b> '.$notice, error: false);
        $this->response[$this->getMessageKey()][]['debug'] = $message;
    }


    public function responseElement(string $key, $value): static
    {
        $this->response[$key] = $value;

        return $this;
    }

    protected function flash(string $key = 'session_response'): void
    {
        session()->flash($key, $this->fetchResponse());
    }

    public function flashResponse(string $key = 'session_response'): void
    {
        session()->flash($key, $this->fetchResponse());
    }

    public function redirectTo(string $route): static
    {
        $this->redirect_to = $route;

        return $this;
    }

    public function redirectRoute(string $route): static
    {
        $this->redirect_route = $route;

        return $this;
    }

    public function sendResponse(?string $message = null, ?string $type = null): RedirectResponse
    {
        if ($message) {
            if ($type && method_exists($this, 'response'.ucfirst($type))) {
                $this->{'response'.ucfirst($type)}($message);
            } else {
                $this->responseWarning($message);
            }
        }

        $this->flash();

        if ($this->redirect_route) {
            return redirect()->route($this->redirect_route);
        }

        if ($this->redirect_to) {
            return redirect()->to($this->redirect_to);
        }

        return redirect()->back()->with('session_response', $this->fetchResponse());
    }

    public function pushMessages(object $object): static
    {
        $messages = $object->fetchResponse()[$this->getMessageKey()] ?? [];

        $object->removeFromResponse($this->getMessageKey());

        $this->response = array_merge($this->response, $object->fetchResponse());

        if ($messages) {
            foreach ($messages as $message) {
                $this->response[$this->getMessageKey()][] = $message;
            }
            if ($object->hasErrors()) {
                $this->response['error'] = true;
            }
        }

        return $this;
    }

    public function responseException(Throwable $e, string $message = ''): static
    {
        $this->responseError(! empty($message) ? $message : __('mfw.errors.error'));

        if (auth()->check() && auth()->user()->hasRole('dev')) {
            $this->responseWarning($e->getMessage());
        }
        report($e);

        return $this;
    }

    /**
     * Redirige vers une route après l'enregistrement
     *
     * @param  string  $url
     *
     * @return void
     */
    public function saveAndRedirect(string $url): void
    {
        if (request()->filled('custom_redirect')) {
            $this->redirect_to = request('custom_redirect');
        } else {
            if (request()->has('save_and_redirect')) {
                $this->redirect_to = $url;
            }
        }
    }

    public function getMessageKey(): string
    {
        return $this->messageKey;
    }

    public function tabRedirect(): void
    {
        if (request()->filled('mfw_tab_redirect')) {
            session()->flash('mfw_tab_redirect', request('mfw_tab_redirect'));
        }
    }

    public function removeFromResponse(string $key): void
    {
        unset($this->response[$key]);
    }

    public function isInDebugMode(): bool
    {
        return $this->debugMode;
    }

    public function isRestrictedToDev(): bool
    {
        return $this->restrictedToDev;
    }

    public function restrictToDev(): self
    {
        $this->restrictedToDev               = true;
        $this->response['restricted_to_dev'] = true;

        return $this;
    }

    public function isAjaxMode(): bool
    {
        return $this->ajax_mode;
    }

    public function isInConsoleMode(): bool
    {
        return $this->consoleLog;
    }

    public function shouldBeAjax(bool $ajax): static
    {
        if ($ajax) {
            $this->ajaxMode();
        }

        return $this;
    }

}
