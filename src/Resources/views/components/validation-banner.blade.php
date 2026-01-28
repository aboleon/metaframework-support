@if ($errors->any())
    {!! \MetaFramework\Support\Responses\ResponseMessages::criticalNotice(__('mfw.validation-banner')) !!}
@endif
