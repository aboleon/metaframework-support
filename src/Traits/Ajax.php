<?php

declare(strict_types=1);

namespace MetaFramework\Support\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait Ajax
{
    use Responses;

    public function distribute(Request $request): array|JsonResponse
    {
        $this->ajaxMode();

        if (!request()->filled('action')) {
            $this->responseError(__('mfw-support::mfw-support.ajax.request_cannot_be_interpreted'));
            return response()->json($this->response, 400);
        }

        if (!method_exists(static::class, request('action'))) {
            $this->responseError(__('mfw-support::mfw-support.ajax.request_cannot_be_processed'));
            return response()->json($this->response, 405);
        }


        return $this->{request('action')}($request);
    }

    public function fetchInput(): static
    {
        $this->response['input'] = request()->all();
        return $this;
    }

    public function fetchCallback(): static
    {
        if (request()->filled('callback')) {
            $this->response['callback'] = request('callback');
        }
        return $this;
    }

    public function ajaxMode(): static
    {
        $this->enableAjaxMode();
        $this->fetchInput();
        $this->fetchCallback();

        return $this;
    }
}
