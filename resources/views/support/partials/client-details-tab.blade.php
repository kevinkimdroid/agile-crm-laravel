@php
    $erpSvc = app(\App\Services\ErpClientService::class);
    $lifeSystem = $client->life_system ?? $erpSvc->getLifeSystemFromProduct($clientProduct ?? null);
    $lifeSystemLabel = $erpSvc->getClientSystemLabel($lifeSystem);
    $statusCode = strtoupper(trim((string) ($client->status ?? '')));
    $statusClass = $statusCode === 'A' ? 'active' : ($statusCode === 'FL' ? 'lapsed' : 'other');
    $statusLabel = match ($statusCode) {
        'A' => 'Active',
        'FL' => 'Lapsed',
        '' => null,
        default => $statusCode,
    };

    $formatDate = function ($value) {
        if ($value === null || $value === '') {
            return null;
        }
        $ts = strtotime((string) $value);

        return $ts ? date('d M Y', $ts) : (string) $value;
    };

    $formatMoney = function ($value) {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return 'KES ' . number_format((float) $value, 0);
        }

        return (string) $value;
    };

    $totalPaid = $formatMoney($client->paid_mat_amt ?? $client->bal ?? $client->production_amt ?? $client->paidMatAmt ?? null);
    $maturityDate = $formatDate($client->maturity ?? $client->maturity_date ?? $client->maturityDate ?? null);
    $dob = $formatDate($client->prp_dob ?? $client->prpDob ?? null);
    $idNumber = $client->id_no ?? $client->idNo ?? $client->ID_NO ?? null;
    $kraPin = $client->kra_pin ?? $client->kraPin ?? null;

    $sections = [
        [
            'title' => 'Personal details',
            'icon' => 'bi-person-vcard',
            'fields' => [
                ['Life assured', $clientName ?? null, 'person', null, true],
                ['Date of birth', $dob, 'calendar-event'],
                ['ID number', $idNumber, 'card-text'],
                ['KRA PIN', $kraPin, 'upc-scan'],
            ],
        ],
        [
            'title' => 'Contact & timeline',
            'icon' => 'bi-clock-history',
            'fields' => [
                ['Phone', $clientPhone ?? null, 'telephone'],
                ['Email', $clientEmail ?? null, 'envelope'],
                ['Effective date', $formatDate($client->effective_date ?? $client->effectiveDate ?? $client->authorization_date ?? null), 'calendar-check'],
                ['Maturity date', $maturityDate, 'calendar-x'],
                ['Renewal date', $formatDate($client->mendr_renewal_date ?? $client->mendrRenewalDate ?? null), 'arrow-repeat'],
            ],
        ],
        [
            'title' => 'Policy & cover',
            'icon' => 'bi-shield-check',
            'fields' => [
                ['Policy number', $clientPolicy ?? null, 'hash'],
                ['Product', $clientProduct ?? null, 'box'],
                ['System', $lifeSystemLabel ?? null, 'system', $lifeSystem],
                ['Policy status', $statusLabel, 'status', $statusClass],
                ['Who prepared', $client->pol_prepared_by ?? $client->bra_manager ?? null, 'person-badge'],
                ['Intermediary', $client->intermediary ?? $client->agn_name ?? null, 'people'],
                ['Scheme name', $client->scheme_name ?? $client->schemeName ?? null, 'diagram-3'],
                ['Checkoff', $client->checkoff ?? null, 'building'],
            ],
        ],
    ];
@endphp

@php
    $consentGranted = (bool) optional($clientConsent ?? null)->consent_granted;
    $consentRecordedAt = optional($clientConsent ?? null)->consented_at;
    $consentRecordedBy = trim((string) (optional($clientConsent ?? null)->consented_by_name ?? ''));
    $consentPolicy = $clientPolicy ?? $policy ?? '';
@endphp

<div class="client-details-page">
    {{-- Personal identity — first thing staff see --}}
    <div class="card contact-detail-card client-personal-hero mb-0">
        <div class="card-body p-4">
            <div class="client-personal-hero-grid">
                <div class="client-personal-hero-avatar" aria-hidden="true">
                    {{ strtoupper(substr($clientName, 0, 1)) }}{{ strtoupper(substr(strrchr(trim($clientName), ' ') ?: $clientName, 1, 1)) }}
                </div>
                <div class="client-personal-hero-main">
                    <p class="client-personal-hero-eyebrow mb-1">Client profile</p>
                    <h2 class="client-personal-hero-name mb-2">{{ $clientName }}</h2>
                    <div class="client-personal-hero-chips mb-3">
                        <span class="clients-system-badge clients-system-{{ $lifeSystem }}">{{ $lifeSystemLabel }}</span>
                        @if($statusLabel)
                        <span class="clients-status-badge clients-status-{{ $statusClass }}">{{ $statusLabel }}</span>
                        @endif
                        <span class="client-personal-hero-policy font-monospace">{{ $clientPolicy ?: '—' }}</span>
                    </div>
                    <div class="client-personal-hero-contacts">
                        @if($clientPhone)
                        <a href="tel:{{ tel_href($clientPhone) }}" class="client-personal-contact-pill">
                            <i class="bi bi-telephone-fill"></i>{{ $clientPhone }}
                        </a>
                        @endif
                        @if($clientEmail)
                        <a href="mailto:{{ $clientEmail }}" class="client-personal-contact-pill">
                            <i class="bi bi-envelope-fill"></i>{{ $clientEmail }}
                        </a>
                        @endif
                        @if($dob)
                        <span class="client-personal-contact-pill is-static">
                            <i class="bi bi-calendar-event"></i>DOB {{ $dob }}
                        </span>
                        @endif
                        @if($idNumber)
                        <span class="client-personal-contact-pill is-static">
                            <i class="bi bi-card-text"></i>ID {{ $idNumber }}
                        </span>
                        @endif
                    </div>
                </div>
                <div class="client-personal-hero-product">
                    <span class="client-personal-hero-product-label">Product</span>
                    <span class="client-personal-hero-product-value">{{ $clientProduct !== '—' ? $clientProduct : 'Not on file' }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card contact-detail-card client-details-shell">
        @foreach($sections as $sectionIndex => $section)
        <div class="client-details-block {{ $sectionIndex > 0 ? 'client-details-block-divider' : '' }}">
            <div class="client-details-block-head">
                <div class="client-details-block-icon"><i class="bi {{ $section['icon'] }}"></i></div>
                <h3 class="client-details-block-title">{{ $section['title'] }}</h3>
            </div>
            <div class="client-details-fields">
                @foreach($section['fields'] as $field)
                @php
                    $label = $field[0];
                    $value = $field[1] ?? null;
                    $icon = $field[2] ?? 'dot';
                    $extra = $field[3] ?? null;
                    $featured = (bool) ($field[4] ?? false);
                    $isEmpty = $value === null || $value === '' || $value === '—';
                @endphp
                <div class="client-details-field {{ $isEmpty ? 'is-empty' : '' }} {{ $featured ? 'is-featured' : '' }}">
                    <div class="client-details-field-icon"><i class="bi bi-{{ $icon }}"></i></div>
                    <div class="client-details-field-body">
                        <div class="client-details-field-label">{{ $label }}</div>
                        <div class="client-details-field-value">
                            @if($isEmpty)
                                <span class="client-details-muted">Not on file</span>
                            @elseif($icon === 'telephone')
                                <a href="tel:{{ tel_href($value) }}" class="client-details-action-link">{{ $value }}</a>
                            @elseif($icon === 'envelope')
                                <a href="mailto:{{ $value }}" class="client-details-action-link">{{ $value }}</a>
                            @elseif($icon === 'system')
                                <span class="clients-system-badge clients-system-{{ $extra }}">{{ $value }}</span>
                            @elseif($icon === 'status')
                                <span class="clients-status-badge clients-status-{{ $extra }}">{{ $value }}</span>
                            @elseif($icon === 'hash' || $icon === 'card-text' || $icon === 'upc-scan')
                                <span class="client-details-code">{{ $value }}</span>
                            @elseif($icon === 'cash-stack')
                                <span class="client-details-money">{{ $value }}</span>
                            @elseif($featured)
                                <span class="client-details-name">{{ $value }}</span>
                            @else
                                {{ $value }}
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>

    {{-- Client consent --}}
    <div class="client-consent-card card contact-detail-card" id="clientConsentCard">
        <div class="card-body p-4">
            <div class="client-consent-head">
                <div class="client-consent-icon"><i class="bi bi-shield-check"></i></div>
                <div>
                    <h3 class="client-consent-title mb-1">Client consent</h3>
                    <p class="client-consent-intro mb-0">Record whether this client has agreed that staff may view their details and communicate with them.</p>
                </div>
            </div>
            <div class="client-consent-control">
                <div class="form-check client-consent-check">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        id="clientConsentCheckbox"
                        @checked($consentGranted)
                        data-policy="{{ $consentPolicy }}"
                        data-url="{{ route('support.clients.consent') }}"
                    >
                    <label class="form-check-label" for="clientConsentCheckbox">
                        This client has consented for people to view their details and communicate with the said client.
                    </label>
                </div>
                <div class="client-consent-meta" id="clientConsentMeta">
                    @if($consentGranted && $consentRecordedAt)
                        <i class="bi bi-check-circle-fill"></i>
                        <span>Recorded {{ $consentRecordedAt->format('d M Y, H:i') }}@if($consentRecordedBy !== '') by {{ $consentRecordedBy }}@endif</span>
                    @else
                        <i class="bi bi-exclamation-circle"></i>
                        <span>No consent recorded yet.</span>
                    @endif
                </div>
                <div class="client-consent-status text-muted small d-none" id="clientConsentStatus" aria-live="polite"></div>
            </div>
        </div>
    </div>

    {{-- Payments — after personal & policy information --}}
    <div class="client-payments-zone">
        <div class="client-payments-zone-head">
            <div class="client-payments-zone-icon"><i class="bi bi-wallet2"></i></div>
            <div>
                <h3 class="client-payments-zone-title mb-0">Payments</h3>
                <p class="client-payments-zone-desc mb-0">Collect premium and view payment history for this policy.</p>
            </div>
            @if($totalPaid)
            <div class="client-payments-total-paid">
                <span class="client-payments-total-label">Total paid</span>
                <span class="client-payments-total-value">{{ $totalPaid }}</span>
            </div>
            @endif
        </div>
        @include('support.partials.client-mpesa-premium-card')
    </div>

    @if($contact ?? null)
    <div class="client-details-linked">
        <i class="bi bi-link-45deg"></i>
        <span>This client is linked to a CRM prospect.</span>
        <a href="{{ route('contacts.show', $contact->contactid) }}" class="btn btn-sm btn-primary ms-auto">
            <i class="bi bi-person me-1"></i>Open prospect
        </a>
    </div>
    @endif
</div>
