@if ($errors->any())
    {!! \MetaFramework\Support\Responses\ResponseMessages::criticalNotice(__('mfw-support::mfw-support.validation-banner')) !!}
@endif
