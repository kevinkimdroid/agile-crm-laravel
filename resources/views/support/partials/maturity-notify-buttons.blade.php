@php
    $notifyScreen = $notifyScreen ?? 'maturities';
    $notifyEventType = $notifyEventType ?? 'maturity';
    $notifyPolicy = $notifyPolicy ?? '';
    $notifyEventDate = $notifyEventDate ?? '';
    $notifyClientName = $notifyClientName ?? '';
    $notifyProduct = $notifyProduct ?? '';
    $notifyEmail = $notifyEmail ?? '';
    $notifyPhone = $notifyPhone ?? '';
    $notifySubject = $notifySubject ?? '';
    $notifyMessage = $notifyMessage ?? '';
    $emailSent = ! empty($emailSent);
    $smsSent = ! empty($smsSent);
@endphp
<div class="d-inline-flex flex-wrap align-items-center justify-content-end gap-1">
    <button type="button"
        class="btn btn-sm {{ $emailSent ? 'btn-success' : 'btn-outline-primary' }}"
        title="{{ $emailSent ? 'Maturity email sent' : 'Email client' }}"
        data-bs-toggle="modal" data-bs-target="#maturityNotifyModal"
        data-channel="email"
        data-screen="{{ $notifyScreen }}"
        data-event-type="{{ $notifyEventType }}"
        data-policy="{{ $notifyPolicy }}"
        data-event-date="{{ $notifyEventDate }}"
        data-client-name="{{ $notifyClientName }}"
        data-product="{{ $notifyProduct }}"
        data-email="{{ $notifyEmail }}"
        data-phone="{{ $notifyPhone }}"
        data-subject="{{ $notifySubject }}"
        data-message="{{ $notifyMessage }}">
        <i class="bi bi-envelope-at"></i>
    </button>
    <button type="button"
        class="btn btn-sm {{ $smsSent ? 'btn-success' : 'btn-outline-secondary' }}"
        title="{{ $smsSent ? 'Maturity SMS sent' : 'SMS client' }}"
        data-bs-toggle="modal" data-bs-target="#maturityNotifyModal"
        data-channel="sms"
        data-screen="{{ $notifyScreen }}"
        data-event-type="{{ $notifyEventType }}"
        data-policy="{{ $notifyPolicy }}"
        data-event-date="{{ $notifyEventDate }}"
        data-client-name="{{ $notifyClientName }}"
        data-product="{{ $notifyProduct }}"
        data-email="{{ $notifyEmail }}"
        data-phone="{{ $notifyPhone }}"
        data-subject=""
        data-message="">
        <i class="bi bi-chat-dots"></i>
    </button>
</div>
