@extends('layouts.app')

@section('title', ($template ? 'Edit' : 'Add') . ' Template')

@section('content')
@php
    $isSms = old('module_name', optional($template)->module_name ?? ($prefillModule ?? '')) === 'Broadcast SMS';
    $returnTo = $returnTo ?? null;
@endphp
<div class="page-header d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb" class="mb-2">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tools.email-templates') }}">Templates</a></li>
                @if ($returnTo)
                    <li class="breadcrumb-item"><a href="{{ $returnTo }}">Broadcast</a></li>
                @endif
                <li class="breadcrumb-item active">{{ $template ? 'Edit' : 'Add' }}</li>
            </ol>
        </nav>
        <h1 class="page-title">{{ $template ? 'Edit' : 'Create' }} {{ $isSms ? 'SMS' : 'email' }} template</h1>
        <p class="page-subtitle mb-0">Reusable copy for mass {{ $isSms ? 'SMS (Broadcast SMS module)' : 'email (Broadcast or Marketing module)' }}.</p>
    </div>
    <a href="{{ $returnTo ?: route('tools.email-templates') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> {{ $returnTo ? 'Back to broadcast' : 'Back to list' }}
    </a>
</div>

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-4">
        <ul class="mb-0">
            @foreach ($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<form action="{{ $template ? route('tools.email-templates.update', $template) : route('tools.email-templates.store') }}" method="POST" id="tplForm">
    @csrf
    @if($template) @method('PUT') @endif
    @if ($returnTo && !$template)
        <input type="hidden" name="return_to" value="{{ $returnTo }}">
    @endif

    @if (!$template)
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <button type="button" class="tpl-type-card w-100 text-start {{ !$isSms ? 'active' : '' }}" data-module="Broadcast" id="tplTypeEmail">
                <span class="tpl-type-icon tpl-type-email"><i class="bi bi-envelope-fill"></i></span>
                <span>
                    <strong>Email template</strong>
                    <span class="d-block small text-muted">Subject + body · modules Broadcast or Marketing</span>
                </span>
            </button>
        </div>
        <div class="col-md-6">
            <button type="button" class="tpl-type-card w-100 text-start {{ $isSms ? 'active' : '' }}" data-module="Broadcast SMS" id="tplTypeSms">
                <span class="tpl-type-icon tpl-type-sms"><i class="bi bi-chat-dots-fill"></i></span>
                <span>
                    <strong>SMS template</strong>
                    <span class="d-block small text-muted">Message body only · module Broadcast SMS</span>
                </span>
            </button>
        </div>
    </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Template name <span class="text-danger">*</span></label>
                    <input type="text" name="template_name" class="form-control" value="{{ old('template_name', optional($template)->template_name ?? '') }}" required placeholder="e.g. Pension rate announcement">
                </div>
                <div class="col-md-6" id="tplSubjectWrap">
                    <label class="form-label fw-semibold">Subject <span class="text-danger tpl-email-only">*</span></label>
                    <input type="text" name="subject" id="tplSubjectInput" class="form-control" value="{{ old('subject', optional($template)->subject ?? '') }}" placeholder="e.g. Important update from Kenya Orient">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Description <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="text" name="description" class="form-control" value="{{ old('description', optional($template)->description ?? '') }}" placeholder="When to use this template">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Module</label>
                    <select name="module_name" id="tplModuleSelect" class="form-select">
                        <option value="Broadcast" {{ old('module_name', optional($template)->module_name ?? ($prefillModule ?? 'Broadcast')) === 'Broadcast' ? 'selected' : '' }}>Broadcast (email)</option>
                        <option value="Marketing" {{ old('module_name', optional($template)->module_name ?? '') === 'Marketing' ? 'selected' : '' }}>Marketing (email)</option>
                        <option value="Broadcast SMS" {{ old('module_name', optional($template)->module_name ?? '') === 'Broadcast SMS' ? 'selected' : '' }}>Broadcast SMS (text only)</option>
                        @foreach ($modules ?? [] as $mod)
                            @if (!in_array($mod, ['Broadcast', 'Marketing', 'Broadcast SMS'], true))
                                <option value="{{ $mod }}" {{ old('module_name', optional($template)->module_name ?? '') == $mod ? 'selected' : '' }}>{{ $mod }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold" id="tplBodyLabel">Message body</label>
                    <textarea name="body" id="tplBodyInput" class="form-control" rows="12" placeholder="Use @{{firstname}}, @{{first_name}}, @{{name}}, @{{email}} for personalization.">{{ old('body', optional($template)->body ?? '') }}</textarea>
                    <p class="small text-muted mt-2 mb-0" id="tplBodyHint">
                        Email templates are sent as <strong>plain text</strong>. SMS templates use the body only (max 1600 characters).
                    </p>
                    <div class="d-flex flex-wrap gap-1 mt-2">
                        @foreach (['{{firstname}}', '{{first_name}}', '{{lastname}}', '{{name}}', '{{email}}'] as $token)
                            <button type="button" class="btn btn-sm btn-outline-secondary tpl-token" data-token="{{ $token }}">{{ $token }}</button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer bg-transparent border-top d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary-custom" id="tplSubmitBtn">
                <i class="bi bi-check-lg me-2"></i>{{ $template ? 'Update' : 'Create' }} template
            </button>
            <a href="{{ $returnTo ?: route('tools.email-templates') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </div>
</form>

<style>
.tpl-type-card {
    display: flex; align-items: center; gap: 1rem; padding: 1rem 1.15rem;
    border: 2px solid #e2e8f0; border-radius: 14px; background: #fff; cursor: pointer;
    transition: border-color 0.15s, box-shadow 0.15s;
}
.tpl-type-card:hover { border-color: #94a3b8; }
.tpl-type-card.active { border-color: var(--agile-primary, #0E4385); box-shadow: 0 0 0 3px rgba(14, 67, 133, 0.12); }
.tpl-type-icon {
    width: 2.75rem; height: 2.75rem; border-radius: 12px; display: inline-flex;
    align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;
}
.tpl-type-email { background: rgba(14, 67, 133, 0.12); color: #0E4385; }
.tpl-type-sms { background: rgba(13, 92, 74, 0.12); color: #0d5c4a; }
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var moduleSelect = document.getElementById('tplModuleSelect');
    var subjectWrap = document.getElementById('tplSubjectWrap');
    var subjectInput = document.getElementById('tplSubjectInput');
    var bodyHint = document.getElementById('tplBodyHint');
    var bodyInput = document.getElementById('tplBodyInput');
    var submitBtn = document.getElementById('tplSubmitBtn');

    function isSmsModule() {
        return moduleSelect && moduleSelect.value === 'Broadcast SMS';
    }
    function syncTypeUi() {
        var sms = isSmsModule();
        if (subjectWrap) subjectWrap.style.display = sms ? 'none' : '';
        if (subjectInput) subjectInput.required = !sms;
        if (bodyHint) {
            bodyHint.innerHTML = sms
                ? 'SMS templates store <strong>message text only</strong> (max 1600 chars). Subject is not sent.'
                : 'Email templates are sent as <strong>plain text</strong>. Use placeholders for personalization.';
        }
        if (bodyInput) bodyInput.maxLength = sms ? 1600 : 65535;
        if (submitBtn) submitBtn.innerHTML = '<i class="bi bi-check-lg me-2"></i>' + (sms ? 'Create SMS template' : 'Create email template');
        document.querySelectorAll('.tpl-type-card').forEach(function(card) {
            var mod = card.getAttribute('data-module');
            card.classList.toggle('active', mod === 'Broadcast SMS' ? sms : !sms && (moduleSelect.value === 'Broadcast' || moduleSelect.value === 'Marketing'));
        });
    }
    moduleSelect && moduleSelect.addEventListener('change', syncTypeUi);
    document.querySelectorAll('.tpl-type-card').forEach(function(card) {
        card.addEventListener('click', function() {
            if (moduleSelect) moduleSelect.value = card.getAttribute('data-module') || 'Broadcast';
            syncTypeUi();
        });
    });
    document.querySelectorAll('.tpl-token').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!bodyInput) return;
            var token = btn.getAttribute('data-token') || '';
            var start = bodyInput.selectionStart || bodyInput.value.length;
            var end = bodyInput.selectionEnd || start;
            bodyInput.value = bodyInput.value.slice(0, start) + token + bodyInput.value.slice(end);
            bodyInput.focus();
            bodyInput.selectionStart = bodyInput.selectionEnd = start + token.length;
        });
    });
    syncTypeUi();
});
</script>
@endpush
@endsection
