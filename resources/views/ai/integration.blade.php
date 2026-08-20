@extends('layouts.app')

@section('title', 'AI Assistant')

@section('content')
@php
    $hasResult = (bool) session('ai_result');
    $flashError = session('error');
@endphp

<div class="ai-page">
    <div class="ai-stage">
        @if($hasResult)
            <div class="ai-thread">
                <div class="ai-msg ai-msg--user">
                    <div class="ai-msg__avatar" aria-hidden="true">You</div>
                    <div class="ai-msg__body">{{ old('prompt') }}</div>
                </div>
                <div class="ai-msg ai-msg--assistant">
                    <div class="ai-msg__avatar" aria-hidden="true"><i class="bi bi-stars"></i></div>
                    <div class="ai-msg__body">{{ session('ai_result') }}</div>
                </div>
            </div>
        @else
            <div class="ai-welcome">
                <div class="ai-welcome__icon"><i class="bi bi-stars"></i></div>
                <h1 class="ai-welcome__title">How can I help?</h1>
                <p class="ai-welcome__text">
                    Draft replies, summarize cases, or polish customer messages.
                </p>

                @if(!$aiConfigured)
                    <p class="ai-welcome__muted">AI is temporarily unavailable. Please try again later.</p>
                @else
                    <div class="ai-suggestions">
                        <button type="button" class="ai-chip" data-prompt="Draft a polite follow-up for a delayed claim.">Delayed claim follow-up</button>
                        <button type="button" class="ai-chip" data-prompt="Write a short SMS reminding a client their policy matures next week.">Maturity SMS reminder</button>
                        <button type="button" class="ai-chip" data-prompt="Summarize this customer complaint in 3 bullet points and suggest next steps.">Complaint summary</button>
                    </div>
                @endif
            </div>
        @endif

        @if($flashError)
            <div class="ai-flash" role="alert">{{ $flashError }}</div>
        @endif
    </div>

    <div class="ai-dock">
        <form method="POST" action="{{ route('ai.integration.generate') }}" class="ai-composer" id="aiComposer">
            @csrf
            <input type="hidden" name="system_prompt" value="You are a Kenyan insurance CRM assistant. Keep responses concise, professional, and practical.">
            <input type="hidden" name="max_tokens" value="400">
            <input type="hidden" name="temperature" value="0.4">

            <div class="ai-composer__box">
                <textarea
                    name="prompt"
                    id="aiPrompt"
                    rows="1"
                    class="ai-composer__input @error('prompt') is-invalid @enderror"
                    placeholder="{{ $aiConfigured ? 'Message AI Assistant…' : 'AI is unavailable right now' }}"
                    {{ $aiConfigured ? '' : 'disabled' }}
                >{{ old('prompt') }}</textarea>
                <button type="submit" class="ai-composer__send" @if(!$aiConfigured) disabled @endif title="Send" aria-label="Send">
                    <i class="bi bi-arrow-up"></i>
                </button>
            </div>
            @error('prompt')
                <div class="ai-composer__error">{{ $message }}</div>
            @enderror
            <p class="ai-composer__hint">AI can make mistakes. Review drafts before sending to clients.</p>
        </form>
    </div>
</div>
@endsection

@push('head')
<style>
    .app-content:has(.ai-page) {
        padding-top: 0.75rem;
        padding-bottom: 0;
    }

    .ai-page {
        --ai-ink: #0f172a;
        --ai-muted: #64748b;
        --ai-line: rgba(15, 23, 42, 0.08);
        --ai-surface: #ffffff;
        --ai-soft: #f8fafc;
        --ai-accent: var(--agile-primary, #1B3F7A);
        display: flex;
        flex-direction: column;
        min-height: calc(100vh - 7.5rem);
        max-width: 760px;
        margin: 0 auto;
    }

    .ai-stage {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 1rem 0 1.25rem;
    }

    .ai-welcome {
        text-align: center;
        padding: 1.5rem 0.5rem 2rem;
    }

    .ai-welcome__icon {
        width: 3.25rem;
        height: 3.25rem;
        margin: 0 auto 1rem;
        border-radius: 999px;
        display: grid;
        place-items: center;
        background: color-mix(in srgb, var(--ai-accent) 12%, white);
        color: var(--ai-accent);
        font-size: 1.35rem;
    }

    .ai-welcome__title {
        font-size: clamp(1.6rem, 2.5vw, 2rem);
        font-weight: 650;
        letter-spacing: -0.02em;
        color: var(--ai-ink);
        margin: 0 0 0.5rem;
    }

    .ai-welcome__text {
        color: var(--ai-muted);
        margin: 0 auto 1.25rem;
        max-width: 28rem;
        font-size: 0.98rem;
        line-height: 1.5;
    }

    .ai-welcome__muted {
        color: var(--ai-muted);
        font-size: 0.92rem;
        margin: 0;
    }

    .ai-suggestions {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 0.55rem;
        max-width: 40rem;
        margin: 0 auto;
    }

    .ai-chip {
        border: 1px solid var(--ai-line);
        background: var(--ai-surface);
        color: #334155;
        border-radius: 999px;
        padding: 0.55rem 0.9rem;
        font-size: 0.86rem;
        line-height: 1.2;
        transition: background .15s ease, border-color .15s ease, transform .15s ease;
    }

    .ai-chip:hover {
        background: var(--ai-soft);
        border-color: color-mix(in srgb, var(--ai-accent) 25%, #cbd5e1);
        transform: translateY(-1px);
    }

    .ai-thread {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
        padding: 0.5rem 0 1rem;
        width: 100%;
    }

    .ai-msg {
        display: grid;
        grid-template-columns: 2.25rem 1fr;
        gap: 0.75rem;
        align-items: start;
    }

    .ai-msg__avatar {
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 999px;
        display: grid;
        place-items: center;
        font-size: 0.7rem;
        font-weight: 700;
        background: #e2e8f0;
        color: #334155;
    }

    .ai-msg--assistant .ai-msg__avatar {
        background: color-mix(in srgb, var(--ai-accent) 14%, white);
        color: var(--ai-accent);
        font-size: 1rem;
    }

    .ai-msg__body {
        white-space: pre-wrap;
        font-size: 0.98rem;
        line-height: 1.6;
        color: var(--ai-ink);
        padding-top: 0.2rem;
    }

    .ai-msg--user .ai-msg__body {
        background: var(--ai-soft);
        border: 1px solid var(--ai-line);
        border-radius: 1rem;
        padding: 0.85rem 1rem;
    }

    .ai-flash {
        margin-top: 0.75rem;
        border-radius: 12px;
        padding: 0.75rem 0.9rem;
        background: #fff7ed;
        border: 1px solid #fed7aa;
        color: #9a3412;
        font-size: 0.9rem;
    }

    .ai-dock {
        position: sticky;
        bottom: 0;
        padding: 0.5rem 0 1rem;
        background: linear-gradient(to top, #f1f5f9 55%, rgba(241, 245, 249, 0));
    }

    .ai-composer__box {
        display: flex;
        align-items: flex-end;
        gap: 0.55rem;
        background: var(--ai-surface);
        border: 1px solid var(--ai-line);
        border-radius: 1.35rem;
        padding: 0.55rem 0.55rem 0.55rem 1rem;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
    }

    .ai-composer__input {
        flex: 1;
        border: 0 !important;
        box-shadow: none !important;
        outline: none !important;
        resize: none;
        background: transparent;
        min-height: 1.5rem;
        max-height: 10rem;
        padding: 0.55rem 0;
        font-size: 0.98rem;
        line-height: 1.45;
        color: var(--ai-ink);
    }

    .ai-composer__input:disabled {
        opacity: 0.65;
        cursor: not-allowed;
    }

    .ai-composer__send {
        width: 2.35rem;
        height: 2.35rem;
        border: 0;
        border-radius: 999px;
        display: grid;
        place-items: center;
        background: var(--ai-accent);
        color: #fff;
        flex-shrink: 0;
        transition: opacity .15s ease, transform .15s ease;
    }

    .ai-composer__send:hover:not(:disabled) {
        transform: translateY(-1px);
    }

    .ai-composer__send:disabled {
        opacity: 0.45;
        cursor: not-allowed;
    }

    .ai-composer__hint,
    .ai-composer__error {
        margin: 0.55rem 0.35rem 0;
        font-size: 0.78rem;
        color: var(--ai-muted);
        text-align: center;
    }

    .ai-composer__error {
        color: #b91c1c;
    }

    @media (max-width: 640px) {
        .ai-page { min-height: calc(100vh - 6rem); }
        .ai-suggestions { flex-direction: column; align-items: stretch; }
        .ai-chip { text-align: left; }
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    var input = document.getElementById('aiPrompt');
    if (!input) return;

    function autosize() {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 160) + 'px';
    }

    input.addEventListener('input', autosize);
    autosize();

    document.querySelectorAll('.ai-chip').forEach(function (chip) {
        chip.addEventListener('click', function () {
            if (input.disabled) return;
            input.value = chip.getAttribute('data-prompt') || '';
            autosize();
            input.focus();
        });
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            var form = document.getElementById('aiComposer');
            if (form && !input.disabled && input.value.trim() !== '') {
                form.requestSubmit();
            }
        }
    });
})();
</script>
@endpush
