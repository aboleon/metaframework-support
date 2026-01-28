<div class="messages" id="{{ $id }}"{!! $ajax ? ' data-ajax="'.$ajax.'"' : '' !!}>
    {!! MetaFramework\Support\Services\ResponseParser::parseResponse(session('session_response')) !!}
    @php
    session()->forget('session_response');
    @endphp
</div>
