@extends('layouts.app')

@section('title', 'Email & SMS broadcast')

@section('content')
@php
    $listLimit = $listLimit ?? 'all';
    $listMax = (int) ($listMax ?? 5000);
    $totalMatching = (int) ($totalMatching ?? 0);
    $hasListFilters = ($search ?? '') !== '' || ($clientType ?? 'all') !== 'all'
        || ($listLimit !== 'all' && $listLimit !== '')
        || !empty($hideListEmailRecent) || !empty($hideListSmsRecent);
    $customerList = $customers ?? collect();
    $duplicatesCollapsed = (int) ($duplicatesCollapsed ?? 0);
    $withEmailCount = 0;
    $withPhoneCount = 0;
    foreach ($customerList as $c) {
        $emCandidates = [
            trim((string) ($c->email ?? '')),
            trim((string) ($c->otheremail ?? '')),
            trim((string) ($c->secondaryemail ?? '')),
            trim((string) ($c->email_adr ?? '')),
            trim((string) ($c->client_email ?? '')),
            trim((string) ($c->mem_email ?? '')),
        ];
        $hasEm = false;
        foreach ($emCandidates as $cand) {
            if ($cand === '') continue;
            if (filter_var($cand, FILTER_VALIDATE_EMAIL)) { $hasEm = true; break; }
            foreach (preg_split('/[;,\s]+/', $cand, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $part) {
                if (filter_var(trim($part), FILTER_VALIDATE_EMAIL)) { $hasEm = true; break 2; }
            }
        }
        $ph = trim($c->mobile ?? $c->phone ?? '');
        if ($hasEm) $withEmailCount++;
        if ($ph !== '') $withPhoneCount++;
    }
@endphp

<div class="bc-page">
    <div class="bc-hero mb-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h1 class="bc-hero-title mb-1">Broadcast</h1>
                <p class="bc-hero-desc mb-0">Pick Email or SMS, choose people, write the message, send.</p>
            </div>
            <div class="d-flex flex-wrap gap-2 bc-hero-stats">
                <span class="bc-chip"><strong>{{ number_format($customerList->count()) }}</strong> people
                    @if ($duplicatesCollapsed > 0)
                        <span class="opacity-75">· {{ number_format($duplicatesCollapsed) }} merged</span>
                    @elseif ($totalMatching > $customerList->count())
                        <span class="opacity-75">of {{ number_format($totalMatching) }}</span>
                    @endif
                </span>
                <span class="bc-chip bc-chip-email"><i class="bi bi-envelope"></i> {{ number_format($withEmailCount) }} email</span>
                <span class="bc-chip bc-chip-sms"><i class="bi bi-phone"></i> {{ number_format($withPhoneCount) }} phone</span>
                <span class="bc-chip bc-chip-pick"><strong id="bcStatSelected">0</strong> selected</span>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show bc-alert" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('warning'))
        <div class="alert alert-warning alert-dismissible fade show bc-alert" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show bc-alert" role="alert">
            <i class="bi bi-x-circle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (!empty($broadcastLifeSegmentNeedsErp))
        <div class="alert alert-warning bc-alert">
            <i class="bi bi-plug me-2"></i>Life segments need an ERP-backed client list. Set <code>CLIENTS_VIEW_SOURCE</code> to <code>erp_http</code>, <code>erp_sync</code>, or <code>erp</code> in <code>.env</code>, then reload.
        </div>
    @endif
    @if (empty($broadcastHistoryReady))
        <div class="alert alert-info bc-alert">
            <i class="bi bi-info-circle me-2"></i>Run <code>php artisan migrate</code> to enable send history and duplicate protection.
        </div>
    @endif

    {{-- Find people --}}
    <form method="GET" action="{{ route('marketing.broadcast') }}" id="bcFilterForm" class="bc-filter-bar mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-semibold small mb-1" for="bcSearchInput">Search</label>
                <input type="search" name="search" id="bcSearchInput" class="form-control form-control-sm"
                    value="{{ $search ?? '' }}" placeholder="Name, policy, email, or phone">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold small mb-1" for="bcClientTypeFilter">Segment</label>
                <select name="client_type" id="bcClientTypeFilter" class="form-select form-select-sm">
                    <option value="all" {{ ($clientType ?? 'all') === 'all' ? 'selected' : '' }}>All contacts</option>
                    @if (!empty($broadcastUsesErpClients) && !empty($lifeSystemOptions))
                        <optgroup label="Support → Clients">
                            @foreach ($lifeSystemOptions as $opt)
                                <option value="{{ $opt['value'] }}" {{ ($clientType ?? '') === $opt['value'] ? 'selected' : '' }}>{{ $opt['label'] }}</option>
                            @endforeach
                        </optgroup>
                    @endif
                    @foreach ($recordSources ?? [] as $src)
                        @php $sv = 's|' . $src; @endphp
                        <option value="{{ $sv }}" {{ ($clientType ?? '') === $sv ? 'selected' : '' }}>Record source: {{ $src }}</option>
                    @endforeach
                    @foreach ($contactTypeValues ?? [] as $tv)
                        @php $tvv = 't|' . $tv; @endphp
                        <option value="{{ $tvv }}" {{ ($clientType ?? '') === $tvv ? 'selected' : '' }}>Vtiger field: {{ $tv }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small mb-1" for="bcListLimit">Show</label>
                <select name="limit" id="bcListLimit" class="form-select form-select-sm">
                    @foreach ([250, 500, 1000] as $n)
                        @if ($n <= $listMax)
                            <option value="{{ $n }}" {{ (string) $listLimit === (string) $n ? 'selected' : '' }}>{{ number_format($n) }}</option>
                        @endif
                    @endforeach
                    <option value="all" {{ $listLimit === 'all' ? 'selected' : '' }}>All</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary-custom flex-grow-1">Show</button>
                @if ($hasListFilters)
                    <a href="{{ route('marketing.broadcast') }}" class="btn btn-sm btn-outline-secondary" title="Clear filters">
                        <i class="bi bi-x-lg"></i>
                    </a>
                @endif
            </div>
        </div>
        <div class="d-flex flex-wrap gap-3 mt-2">
            <div class="form-check form-switch mb-0">
                <input type="checkbox" class="form-check-input" name="hide_list_email_recent" value="1" id="hideListEmailRecent"
                    {{ !empty($hideListEmailRecent) ? 'checked' : '' }} {{ empty($broadcastHistoryReady) ? 'disabled' : '' }}>
                <label class="form-check-label small" for="hideListEmailRecent">Hide recent email</label>
            </div>
            <div class="form-check form-switch mb-0">
                <input type="checkbox" class="form-check-input" name="hide_list_sms_recent" value="1" id="hideListSmsRecent"
                    {{ !empty($hideListSmsRecent) ? 'checked' : '' }} {{ empty($broadcastHistoryReady) ? 'disabled' : '' }}>
                <label class="form-check-label small" for="hideListSmsRecent">Hide recent SMS</label>
            </div>
        </div>
    </form>

    <form method="POST" action="{{ route('marketing.broadcast.send') }}" id="broadcastForm" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="search" value="{{ $search ?? '' }}">
        <input type="hidden" name="client_type" id="bcSendClientType" value="{{ old('client_type', $clientType ?? 'all') }}">
        @if (($listLimit ?? 'all') !== 'all')
            <input type="hidden" name="limit" value="{{ $listLimit }}">
        @endif
        @if (!empty($hideListEmailRecent))
            <input type="hidden" name="hide_list_email_recent" value="1">
        @endif
        @if (!empty($hideListSmsRecent))
            <input type="hidden" name="hide_list_sms_recent" value="1">
        @endif

        <div class="bc-channel-cards mb-3" role="tablist">
            <button type="button" class="bc-channel-card bc-channel-card-email active" id="tab-email"
                data-bs-toggle="tab" data-bs-target="#pane-email" role="tab" data-channel="email" aria-selected="true">
                <div class="bc-channel-card-icon"><i class="bi bi-envelope-fill"></i></div>
                <div class="bc-channel-card-text">
                    <strong>Email</strong>
                    <span>Write and send</span>
                </div>
                <span class="bc-channel-card-badge">{{ number_format($withEmailCount) }} ready</span>
            </button>
            <button type="button" class="bc-channel-card bc-channel-card-sms" id="tab-sms"
                data-bs-toggle="tab" data-bs-target="#pane-sms" role="tab" data-channel="sms" aria-selected="false">
                <div class="bc-channel-card-icon"><i class="bi bi-chat-dots-fill"></i></div>
                <div class="bc-channel-card-text">
                    <strong>SMS</strong>
                    <span>Short text message</span>
                </div>
                <span class="bc-channel-card-badge">{{ number_format($withPhoneCount) }} ready</span>
            </button>
        </div>

            <input type="hidden" name="channel" id="broadcastChannel" value="email">

            <div class="bc-workspace">
            <div class="bc-compose">
            <div class="tab-content">
                <div class="tab-pane fade show active" id="pane-email" role="tabpanel">
                    @php
                        $emailTplList = $emailAdvertTemplates ?? collect();
                        $emailTplEmpty = $emailTplList->isEmpty();
                        $emailMergeTokens = [
                            ['token' => '{{'.'firstname}}', 'label' => 'First name'],
                            ['token' => '{{'.'name}}', 'label' => 'Full name'],
                            ['token' => '{{'.'email}}', 'label' => 'Email'],
                        ];
                    @endphp
                    <div class="card bc-card bc-composer-card bc-letter-card">
                        <div class="card-body p-0">
                            <div class="bc-letter-head">
                                <div>
                                    <span class="bc-letter-kicker">New email</span>
                                    <strong>Kenya Orient</strong>
                                </div>
                                <span class="bc-letter-stamp"><i class="bi bi-envelope-heart"></i></span>
                            </div>
                            <div class="p-3">
                            <div class="bc-tpl-bar mb-3">
                                <select id="bcEmailTemplateSelect" class="form-select form-select-sm" {{ $emailTplEmpty ? 'disabled' : '' }}>
                                    <option value="">Email template…</option>
                                    @foreach ($emailTplList as $tpl)
                                        <option value="{{ $tpl->id }}">{{ $tpl->template_name }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="bcApplyEmailTemplate" {{ $emailTplEmpty ? 'disabled' : '' }}>Use</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="bcSaveEmailAsTpl">Save</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="bcOpenEmailTplModal">New</button>
                                <button type="button" class="btn btn-sm btn-link" id="loadPensionTemplate">Pension 2025</button>
                            </div>
                            <p class="small text-muted mb-2" id="bcEmailTemplateHint">Load a saved template or write a new message.</p>
                            <div class="mb-2">
                                <label class="form-label fw-semibold small mb-1" for="broadcastSubject">Subject <span class="text-danger">*</span></label>
                                <input type="text" name="subject" id="broadcastSubject" class="form-control bc-email-field"
                                    value="{{ old('subject') }}" maxlength="200" placeholder="e.g. Important update from Kenya Orient">
                                @error('subject')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-semibold small mb-0" for="broadcastBody">Message <span class="text-danger">*</span></label>
                                <button type="button" class="btn btn-link btn-sm p-0" id="clearEmailTemplate">Clear</button>
                            </div>
                            <textarea name="body" id="broadcastBody" class="form-control bc-email-field bc-letter-body" rows="7"
                                placeholder="Write like a letter. Insert first name or full name below.">{{ old('body') }}</textarea>
                            @error('body')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            <div class="d-flex flex-wrap gap-1 mt-2 mb-2">
                                @foreach ($emailMergeTokens as $tok)
                                    <button type="button" class="btn btn-sm bc-token-chip bc-insert-token" data-target="broadcastBody" data-token="{{ $tok['token'] }}">{{ $tok['label'] }}</button>
                                @endforeach
                            </div>
                            <label class="form-label fw-semibold small mb-1" for="emailAttachmentInput">Attachment (optional)</label>
                            <input type="file" name="email_attachment" id="emailAttachmentInput" class="form-control form-control-sm"
                                accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.ppt,.pptx">
                            <div class="bc-email-mockup mt-3">
                                <div class="bc-email-mockup-bar">
                                    <span class="bc-email-mockup-dot"></span>
                                    <span class="bc-email-mockup-dot"></span>
                                    <span class="bc-email-mockup-dot"></span>
                                </div>
                                <div class="bc-email-mockup-body">
                                    <div class="bc-email-mockup-subject" id="bcPreviewSubject">Subject line…</div>
                                    <div class="bc-email-mockup-content small" id="bcEmailPreview">Start typing to see a preview with sample names.</div>
                                </div>
                            </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="pane-sms" role="tabpanel">
                    @php
                        $smsTplList = $smsAdvertTemplates ?? collect();
                        $smsTplEmpty = $smsTplList->isEmpty();
                        $smsMergeTokens = [
                            ['token' => '{{'.'firstname}}', 'label' => 'First name'],
                            ['token' => '{{'.'name}}', 'label' => 'Full name'],
                        ];
                    @endphp
                    <div class="card bc-card bc-composer-card bc-sms-card">
                        <div class="card-body p-3">
                            <div class="bc-tpl-bar mb-3">
                                <select id="bcSmsTemplateSelect" class="form-select form-select-sm" {{ $smsTplEmpty ? 'disabled' : '' }}>
                                    <option value="">SMS template…</option>
                                    @foreach ($smsTplList as $tpl)
                                        <option value="{{ $tpl->id }}">{{ $tpl->template_name }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-sm btn-outline-success" id="bcApplySmsTemplate" {{ $smsTplEmpty ? 'disabled' : '' }}>Use</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="bcSaveSmsAsTpl">Save</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="bcOpenSmsTplModal">New</button>
                            </div>
                            <p class="small text-muted mb-2" id="bcSmsTemplateHint">Load a saved SMS or type a new one.</p>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-semibold small mb-0" for="broadcastSmsMessage">SMS text <span class="text-danger">*</span></label>
                                <span class="badge rounded-pill bc-sms-segments" id="bcSmsCharCount">0 / 1600</span>
                            </div>
                            <textarea name="message" id="broadcastSmsMessage" class="form-control bc-sms-field" rows="6" maxlength="1600"
                                placeholder="Keep it short. Long texts split into extra SMS.">{{ old('message') }}</textarea>
                            @error('message')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            <div class="d-flex flex-wrap gap-1 mt-2 mb-3">
                                @foreach ($smsMergeTokens as $tok)
                                    <button type="button" class="btn btn-sm bc-token-chip bc-insert-token" data-target="broadcastSmsMessage" data-token="{{ $tok['token'] }}">{{ $tok['label'] }}</button>
                                @endforeach
                            </div>
                            <div class="bc-phone-mockup mx-auto">
                                <div class="bc-phone-notch"></div>
                                <div class="bc-phone-screen">
                                    <div class="bc-phone-header">Kenya Orient</div>
                                    <div class="bc-phone-bubble" id="bcSmsPreview">Your SMS preview appears here…</div>
                                    <div class="bc-phone-time small text-muted" id="bcSmsSegmentsHint">1 segment</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>

        {{-- Recipients --}}
        <section class="bc-people">
            <div class="card bc-card">
                <div class="card-header bc-recipients-head">
                    <div class="d-flex flex-wrap align-items-center gap-2 flex-grow-1">
                        <span class="fw-semibold"><i class="bi bi-people me-1"></i>Audience</span>
                        <span class="badge bg-light text-dark border" id="bcVisibleCount">{{ $customerList->count() }} people</span>
                        @if ($duplicatesCollapsed > 0)
                            <span class="small text-muted">{{ $duplicatesCollapsed }} duplicate{{ $duplicatesCollapsed === 1 ? '' : 's' }} merged</span>
                        @elseif ($totalMatching > $customerList->count())
                            <span class="small text-muted">of {{ number_format($totalMatching) }} matching</span>
                        @endif
                    </div>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <div class="input-group input-group-sm bc-table-search">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="search" class="form-control" id="bcTableSearch" placeholder="Find a person…" autocomplete="off">
                        </div>
                    </div>
                </div>
                <div class="bc-select-bar">
                    <label class="bc-select-all" for="bcSelectAll">
                        <input type="checkbox" class="form-check-input bc-check-lg" id="bcSelectAll">
                        <span>
                            <strong>Select all</strong>
                            <span class="d-block small text-muted" id="bcSelectAllHint">Tick everyone on this list</span>
                        </span>
                    </label>
                    <div class="d-flex flex-wrap gap-1">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="bcSelectAllEmail" title="Only people with an email">Email only</button>
                        <button type="button" class="btn btn-sm btn-outline-success" id="bcSelectAllSms" title="Only people with a phone">Phone only</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="bcSelectNone">Clear</button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive bc-table-wrap">
                        <table class="table mb-0 align-middle bc-recipients-table bc-people-cards">
                            <thead class="visually-hidden">
                                <tr>
                                    <th>Select</th>
                                    <th>Person</th>
                                </tr>
                            </thead>
                            <tbody id="bcRecipientsBody">
                                @forelse ($customerList as $c)
                                    @php
                                        $cid = (int) $c->contactid;
                                        $fullName = trim(($c->firstname ?? '') . ' ' . ($c->lastname ?? ''));
                                        if ($fullName === '') $fullName = 'Contact #' . $cid;
                                        $policyNo = trim((string) ($c->policy_number ?? $c->policy_no ?? ''));
                                        $product = trim((string) ($c->product ?? ''));
                                        $emCandidates = [
                                            trim((string) ($c->email ?? '')),
                                            trim((string) ($c->otheremail ?? '')),
                                            trim((string) ($c->secondaryemail ?? '')),
                                            trim((string) ($c->email_adr ?? '')),
                                            trim((string) ($c->client_email ?? '')),
                                            trim((string) ($c->mem_email ?? '')),
                                        ];
                                        $em = '';
                                        foreach ($emCandidates as $cand) {
                                            if ($cand === '') continue;
                                            if (filter_var($cand, FILTER_VALIDATE_EMAIL)) { $em = $cand; break; }
                                            foreach (preg_split('/[;,\s]+/', $cand, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $part) {
                                                $candidate = trim((string) $part);
                                                if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_EMAIL)) { $em = $candidate; break 2; }
                                            }
                                        }
                                        $ph = trim($c->mobile ?? $c->phone ?? '');
                                        $hasEm = $em !== '' && filter_var($em, FILTER_VALIDATE_EMAIL);
                                        $hasPh = $ph !== '';
                                        $lb = $lastBroadcastByContact[$cid] ?? ['email' => null, 'sms' => null];
                                        $searchBlob = strtolower($fullName . ' ' . $policyNo . ' ' . $product . ' ' . $em . ' ' . $ph);
                                        $nameParts = preg_split('/\s+/', $fullName) ?: [];
                                        $ini = strtoupper(substr($nameParts[0] ?? 'C', 0, 1) . substr(end($nameParts) ?: '', 0, 1));
                                        $hue = abs(crc32($fullName)) % 360;
                                    @endphp
                                    <tr class="bc-row" data-has-email="{{ $hasEm ? '1' : '0' }}" data-has-phone="{{ $hasPh ? '1' : '0' }}"
                                        data-search="{{ $searchBlob }}" tabindex="0" role="button" aria-label="Toggle {{ $fullName }}">
                                        <td class="bc-person-cell" colspan="2">
                                            <div class="bc-person">
                                                <div class="bc-person-check" onclick="event.stopPropagation()">
                                                    <input type="checkbox" class="form-check-input bc-check" name="contact_ids[]" value="{{ $c->contactid }}"
                                                        data-has-email="{{ $hasEm ? '1' : '0' }}" data-has-phone="{{ $hasPh ? '1' : '0' }}">
                                                </div>
                                                <div class="bc-avatar" style="--bc-hue: {{ $hue }}">{{ $ini }}</div>
                                                <div class="bc-person-body">
                                                    <div class="bc-person-name">
                                                        {{ $fullName }}
                                                        @if ((int) ($c->duplicate_count ?? 0) > 0)
                                                            <span class="bc-merged" title="{{ (int) $c->duplicate_count }} duplicate record{{ (int) $c->duplicate_count === 1 ? '' : 's' }} merged into this row">merged</span>
                                                        @endif
                                                    </div>
                                                    <div class="bc-person-sub">
                                                        @if ($policyNo !== '')
                                                            <span>{{ $policyNo }}</span>
                                                        @endif
                                                        @if ($product !== '')
                                                            <span>{{ Str::limit($product, 28) }}</span>
                                                        @endif
                                                    </div>
                                                    <div class="bc-person-reach">
                                                        @if ($hasEm)
                                                            <span title="{{ $em }}"><i class="bi bi-envelope"></i> {{ $em }}</span>
                                                        @else
                                                            <span class="text-muted">No email</span>
                                                        @endif
                                                        @if ($hasPh)
                                                            <span><i class="bi bi-phone"></i> {{ $ph }}</span>
                                                        @endif
                                                        @if (!empty($broadcastHistoryReady) && !empty($lb['email']))
                                                            <span class="text-success" title="{{ $lb['email']->format('Y-m-d H:i') }}">Last email {{ $lb['email']->diffForHumans() }}</span>
                                                        @endif
                                                        @if (!empty($broadcastHistoryReady) && !empty($lb['sms']))
                                                            <span class="text-success" title="{{ $lb['sms']->format('Y-m-d H:i') }}">Last SMS {{ $lb['sms']->diffForHumans() }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="bc-empty-row">
                                        <td colspan="2" class="text-center py-5">
                                            <i class="bi bi-inbox display-6 text-muted d-block mb-2"></i>
                                            <p class="text-muted mb-2">
                                                @if (!empty($broadcastLifeSegmentNeedsErp))
                                                    Enable ERP-backed Clients for life segments, or choose &quot;All contacts&quot;.
                                                @else
                                                    No contacts match your filters. Try clearing search or changing segment.
                                                @endif
                                            </p>
                                            @if ($hasListFilters)
                                                <a href="{{ route('marketing.broadcast') }}" class="btn btn-sm btn-outline-primary">Clear filters</a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="bc-no-results small text-muted text-center py-3" id="bcNoResults" hidden>
                        No rows match your table filter. <button type="button" class="btn btn-link btn-sm p-0" id="bcClearTableSearch">Clear filter</button>
                    </div>
                </div>
                <div class="card-footer bc-people-footer">
                    <div class="bc-upload-zone bc-upload-zone-compact" id="bcUploadZone">
                        <input type="file" name="recipients_file" id="bcRecipientsFile" class="bc-upload-input"
                            accept=".xlsx,.xls,.csv,.txt">
                        <i class="bi bi-cloud-arrow-up"></i>
                        <span class="bc-upload-text">Upload Excel/CSV</span>
                        <span class="bc-upload-filename small fw-semibold text-primary" id="bcUploadFilename" hidden></span>
                    </div>
                    <a href="{{ route('marketing.broadcast.template') }}" class="btn btn-sm btn-outline-primary">Template</a>
                    @error('recipients_file')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    <input type="hidden" name="skip_recent_sends" value="0">
                    <div class="form-check form-switch mb-0">
                        <input type="checkbox" class="form-check-input" name="skip_recent_sends" id="skipRecentSends" value="1"
                            {{ (string) old('skip_recent_sends', '1') !== '0' ? 'checked' : '' }}
                            {{ empty($broadcastHistoryReady) ? 'disabled' : '' }}>
                        <label class="form-check-label small" for="skipRecentSends">
                            Skip recent <span id="skipChannelLabel">email</span> ({{ (int) ($skipRecentDays ?? 14) }} days)
                        </label>
                    </div>
                    <details class="small">
                        <summary class="text-muted">Advanced send filter</summary>
                        <select class="form-select form-select-sm mt-2" id="bcSendClientTypeOverride">
                            <option value="">Same as list filter</option>
                            <option value="all">All (no extra filter)</option>
                            @if (!empty($broadcastUsesErpClients) && !empty($lifeSystemOptions))
                                @foreach ($lifeSystemOptions as $opt)
                                    <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                                @endforeach
                            @endif
                            @foreach ($recordSources ?? [] as $src)
                                @php $sv = 's|' . $src; @endphp
                                <option value="{{ $sv }}">Record source: {{ $src }}</option>
                            @endforeach
                            @foreach ($contactTypeValues ?? [] as $tv)
                                @php $tvv = 't|' . $tv; @endphp
                                <option value="{{ $tvv }}">Vtiger field: {{ $tv }}</option>
                            @endforeach
                        </select>
                    </details>
                </div>
            </div>
        </section>
            </div>{{-- /bc-workspace --}}

        {{-- Sticky action bar --}}
        <div class="bc-action-bar">
            <div class="bc-action-inner" id="bcActionInner">
                <div class="bc-action-summary">
                    <span class="bc-action-channel bc-action-channel-email" id="bcActionChannel"><i class="bi bi-envelope-fill"></i> Email broadcast</span>
                    <span class="bc-action-divider">·</span>
                    <span><strong id="bcCount">0</strong> selected</span>
                    <span class="bc-action-file" id="bcActionFile" hidden><span class="bc-action-divider">·</span><i class="bi bi-file-earmark"></i> File attached</span>
                </div>
                <button type="submit" class="btn btn-lg bc-send-btn bc-send-btn-email" id="bcSubmit">
                    <i class="bi bi-send-fill me-1"></i><span id="bcSubmitLabel">Send email</span>
                </button>
            </div>
        </div>
        <div class="bc-action-spacer"></div>
    </form>

    <details class="bc-tips mt-2">
        <summary class="small text-muted"><i class="bi bi-lightbulb me-1"></i>Tips for large campaigns</summary>
        <ul class="small text-muted mb-0 mt-2 ps-3">
            <li>People with an email (or phone, for SMS) are selected automatically. Use the buttons to change that, or upload a spreadsheet.</li>
            <li>Keep <strong>Skip duplicate sends</strong> on to avoid messaging the same people twice within {{ (int) ($skipRecentDays ?? 14) }} days.</li>
            <li>Max {{ $maxRecipients ?? 500 }} recipients per send — split larger lists into batches.</li>
        </ul>
    </details>
</div>

{{-- Quick template modal --}}
<div class="modal fade" id="bcTplModal" tabindex="-1" aria-labelledby="bcTplModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bc-modal-content">
            <div class="modal-header bc-modal-header" id="bcTplModalHeader">
                <div>
                    <span class="bc-modal-badge" id="bcTplModalBadge">EMAIL</span>
                    <h5 class="modal-title mt-1" id="bcTplModalTitle">Save email template</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="bcTplModalAlert" class="alert alert-danger small py-2" hidden role="alert"></div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="bcTplModalName">Template name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="bcTplModalName" maxlength="255" placeholder="e.g. Pension rate 2025">
                    </div>
                    <div class="col-md-6" id="bcTplModalSubjectWrap">
                        <label class="form-label fw-semibold" for="bcTplModalSubject">Subject <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="bcTplModalSubject" maxlength="255" placeholder="Email subject line">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold" for="bcTplModalDesc">Description <span class="text-muted fw-normal">(optional)</span></label>
                        <input type="text" class="form-control" id="bcTplModalDesc" maxlength="500" placeholder="When to use this template">
                    </div>
                    <div class="col-md-6" id="bcTplModalModuleWrap">
                        <label class="form-label fw-semibold" for="bcTplModalModule">Module</label>
                        <select class="form-select" id="bcTplModalModule">
                            <option value="Broadcast">Broadcast (email)</option>
                            <option value="Marketing">Marketing (email)</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold" for="bcTplModalBody">Message body</label>
                        <textarea class="form-control" id="bcTplModalBody" rows="8" placeholder="Template message text"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary-custom" id="bcTplModalSave">
                    <i class="bi bi-check-lg me-1"></i>Save template
                </button>
            </div>
        </div>
    </div>
</div>

@php
    $emailTemplatesById = [];
    foreach (($emailAdvertTemplates ?? collect()) as $tpl) {
        $emailTemplatesById[$tpl->id] = [
            'subject' => (string) ($tpl->subject ?? ''),
            'body' => (string) ($tpl->body ?? ''),
            'description' => (string) ($tpl->description ?? ''),
        ];
    }
    $smsTemplatesById = [];
    foreach (($smsAdvertTemplates ?? collect()) as $tpl) {
        $smsTemplatesById[$tpl->id] = [
            'body' => (string) ($tpl->body ?? ''),
            'description' => (string) ($tpl->description ?? ''),
        ];
    }
@endphp
<script type="application/json" id="bcEmailTemplatesById">@json($emailTemplatesById)</script>
<script type="application/json" id="bcSmsTemplatesById">@json($smsTemplatesById)</script>

<style>
.bc-page { padding-bottom: 0.5rem; }
.bc-hero {
    background: linear-gradient(135deg, var(--agile-primary-dark, #122952) 0%, var(--agile-primary, #0E4385) 55%, #2563eb 100%);
    border-radius: 16px; color: #fff; padding: 1.1rem 1.35rem; position: relative; overflow: hidden;
}
.bc-hero::after {
    content: ''; position: absolute; right: -2rem; top: -2rem; width: 8rem; height: 8rem;
    border-radius: 50%; background: rgba(255,255,255,0.06); pointer-events: none;
}
.bc-hero-icon {
    width: 3rem; height: 3rem; border-radius: 12px; background: rgba(255,255,255,0.15);
    display: inline-flex; align-items: center; justify-content: center; font-size: 1.35rem;
}
.bc-hero-title { font-size: 1.35rem; font-weight: 700; color: #fff; margin: 0; }
.bc-hero-desc { font-size: 0.85rem; color: rgba(255,255,255,0.88); max-width: 36rem; }
.bc-chip {
    display: inline-flex; align-items: center; gap: 0.35rem;
    background: rgba(255,255,255,0.14); border: 1px solid rgba(255,255,255,0.18);
    border-radius: 999px; padding: 0.3rem 0.7rem; font-size: 0.78rem; color: #fff;
}
.bc-chip strong { font-weight: 700; }
.bc-chip-email { background: rgba(147, 197, 253, 0.22); }
.bc-chip-sms { background: rgba(110, 231, 183, 0.22); }
.bc-chip-pick { background: rgba(253, 224, 71, 0.28); color: #fff; }
.bc-token-chip {
    border: 1px dashed #94a3b8; background: #f8fafc; color: #334155; border-radius: 999px; font-size: 0.75rem;
}
.bc-token-chip:hover { border-color: var(--agile-primary, #0E4385); color: var(--agile-primary, #0E4385); background: #eff6ff; }
.bc-letter-card { overflow: hidden; }
.bc-letter-head {
    display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;
    padding: 0.85rem 1rem; background: linear-gradient(90deg, #0b3569, #1560a8); color: #fff;
}
.bc-letter-kicker { display: block; font-size: 0.68rem; letter-spacing: 0.08em; text-transform: uppercase; opacity: 0.8; }
.bc-letter-stamp {
    width: 2.25rem; height: 2.25rem; border-radius: 50%; background: rgba(255,255,255,0.18);
    display: inline-flex; align-items: center; justify-content: center;
}
.bc-letter-body { font-family: Georgia, "Times New Roman", serif; line-height: 1.55; }
.bc-people-cards { border-collapse: separate; border-spacing: 0; }
.bc-people-cards td { border: 0; padding: 0; }
.bc-person-cell { padding: 0 !important; }
.bc-person {
    display: flex; align-items: flex-start; gap: 0.7rem; padding: 0.7rem 0.9rem;
    border-bottom: 1px solid #eef2f7;
}
.bc-person-check { padding-top: 0.55rem; }
.bc-avatar {
    width: 2.35rem; height: 2.35rem; border-radius: 50%; flex-shrink: 0;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 0.72rem; font-weight: 700; color: #fff; letter-spacing: 0.02em;
    background: hsl(var(--bc-hue, 210) 42% 42%);
}
.bc-person-body { min-width: 0; flex: 1; }
.bc-person-name { font-weight: 600; color: #0f172a; display: flex; flex-wrap: wrap; align-items: center; gap: 0.35rem; }
.bc-person-sub { font-size: 0.75rem; color: #64748b; display: flex; flex-wrap: wrap; gap: 0.45rem; margin-top: 0.1rem; }
.bc-person-reach { font-size: 0.75rem; color: #475569; display: flex; flex-wrap: wrap; gap: 0.65rem; margin-top: 0.25rem; }
.bc-person-reach i { opacity: 0.7; }
.bc-merged {
    font-size: 0.62rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase;
    background: #eef2ff; color: #3730a3; border-radius: 999px; padding: 0.12rem 0.45rem;
}
.bc-filter-bar {
    background: #fff; border: 1px solid var(--agile-border, #e2e8f0); border-radius: 14px;
    padding: 0.9rem 1rem; box-shadow: 0 2px 8px rgba(14, 67, 133, 0.04);
}
.bc-tpl-bar {
    display: flex; flex-wrap: wrap; align-items: center; gap: 0.4rem;
}
.bc-tpl-bar .form-select { min-width: 10rem; flex: 1 1 12rem; }
.bc-workspace { display: grid; gap: 1rem; }
@media (min-width: 1100px) {
    .bc-workspace {
        grid-template-columns: minmax(0, 1.2fr) minmax(340px, 0.8fr);
        grid-template-areas: "people compose";
        align-items: start;
    }
    .bc-people { grid-area: people; min-width: 0; }
    .bc-compose { grid-area: compose; position: sticky; top: 0.75rem; }
}
.bc-people-footer {
    display: flex; flex-wrap: wrap; align-items: center; gap: 0.85rem;
    background: #f8fafc; border-top: 1px solid #e2e8f0;
}
.bc-upload-zone-compact {
    flex: 1 1 14rem; flex-direction: row; justify-content: flex-start; gap: 0.5rem;
    padding: 0.45rem 0.75rem; text-align: left; min-height: 0;
}
.bc-upload-zone-compact .bc-upload-text { font-size: 0.8rem; }
.bc-alert { border-radius: 12px; }
.bc-stat {
    background: linear-gradient(135deg, #fff 0%, #f8fbff 100%);
    border: 1px solid rgba(14, 67, 133, 0.12); border-radius: 14px;
    padding: 1rem 1.15rem; height: 100%; box-shadow: 0 2px 8px rgba(14, 67, 133, 0.04);
}
.bc-stat-highlight { border-color: var(--agile-primary, #0E4385); background: linear-gradient(135deg, #f0f7ff 0%, #fff 100%); }
.bc-stat-label {
    display: block; font-size: 0.68rem; font-weight: 700; letter-spacing: 0.06em;
    text-transform: uppercase; color: #64748b; margin-bottom: 0.35rem;
}
.bc-stat-value { display: block; font-size: 1.5rem; font-weight: 700; color: var(--agile-primary, #0E4385); line-height: 1.1; }
.bc-stat-hint { display: block; font-size: 0.75rem; color: #94a3b8; margin-top: 0.35rem; }
.bc-step-head {
    display: flex; flex-wrap: wrap; align-items: flex-start; gap: 0.85rem; margin-bottom: 1rem;
}
.bc-step-num {
    width: 2rem; height: 2rem; border-radius: 50%; background: var(--agile-primary, #0E4385); color: #fff;
    font-weight: 700; font-size: 0.9rem; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.bc-step-title { font-size: 1.05rem; font-weight: 700; margin: 0 0 0.15rem; color: var(--agile-text, #1e293b); }
.bc-step-desc { font-size: 0.85rem; color: #64748b; }
.bc-card { border-radius: 14px; border: 1px solid var(--agile-border, #e2e8f0); overflow: hidden; }
.bc-filter-chip {
    display: inline-block; font-size: 0.75rem; padding: 0.2rem 0.55rem; border-radius: 999px;
    background: rgba(14, 67, 133, 0.08); color: var(--agile-primary, #0E4385); margin: 0.15rem 0.25rem 0.15rem 0;
}
.bc-toggle-card {
    background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 0.65rem 0.85rem;
}
.bc-stat-email { border-color: rgba(14, 67, 133, 0.2); background: linear-gradient(135deg, #f0f7ff 0%, #fff 100%); }
.bc-stat-email .bc-stat-value { color: #0E4385; }
.bc-stat-sms { border-color: rgba(13, 92, 74, 0.2); background: linear-gradient(135deg, #f0faf6 0%, #fff 100%); }
.bc-stat-sms .bc-stat-value { color: #0d5c4a; }
.bc-hero-sms-btn { background: rgba(255,255,255,0.15); color: #fff; border: 1px solid rgba(255,255,255,0.35); }
.bc-hero-sms-btn:hover { background: rgba(255,255,255,0.25); color: #fff; }
.bc-channel-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; }
@media (max-width: 767.98px) { .bc-channel-cards { grid-template-columns: 1fr; } }
.bc-channel-card {
    display: flex; align-items: center; gap: 0.65rem; padding: 0.55rem 0.85rem;
    border: 2px solid #e2e8f0; border-radius: 999px; background: #fff; text-align: left;
    cursor: pointer; transition: all 0.15s ease; width: 100%;
}
.bc-channel-card:hover { border-color: #94a3b8; }
.bc-channel-card-icon {
    width: 2.75rem; height: 2.75rem; border-radius: 12px; display: inline-flex;
    align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;
}
.bc-channel-card-email .bc-channel-card-icon { background: rgba(14, 67, 133, 0.12); color: #0E4385; }
.bc-channel-card-sms .bc-channel-card-icon { background: rgba(13, 92, 74, 0.12); color: #0d5c4a; }
.bc-channel-card-text { flex: 1; min-width: 0; }
.bc-channel-card-text strong { display: block; font-size: 0.95rem; color: #1e293b; }
.bc-channel-card-text span { display: block; font-size: 0.78rem; color: #64748b; margin-top: 0.1rem; }
.bc-channel-card-badge {
    font-size: 0.72rem; font-weight: 600; padding: 0.25rem 0.55rem; border-radius: 999px; white-space: nowrap;
}
.bc-channel-card-email.active { border-color: #0E4385; box-shadow: 0 0 0 3px rgba(14, 67, 133, 0.12); }
.bc-channel-card-email.active .bc-channel-card-badge { background: rgba(14, 67, 133, 0.1); color: #0E4385; }
.bc-channel-card-sms.active { border-color: #0d5c4a; box-shadow: 0 0 0 3px rgba(13, 92, 74, 0.12); }
.bc-channel-card-sms.active .bc-channel-card-badge { background: rgba(13, 92, 74, 0.1); color: #0d5c4a; }
.bc-pane-banner {
    display: flex; align-items: center; gap: 0.85rem; padding: 0.85rem 1.15rem;
    border-radius: 14px 14px 0 0; color: #fff;
}
.bc-pane-banner i { font-size: 1.35rem; opacity: 0.9; }
.bc-pane-banner strong { display: block; font-size: 0.95rem; }
.bc-pane-banner span { display: block; font-size: 0.78rem; opacity: 0.88; }
.bc-pane-banner-email { background: linear-gradient(90deg, #0b3569, #1560a8); }
.bc-pane-banner-sms { background: linear-gradient(90deg, #064032, #13806a); }
.bc-tpl-badge {
    font-size: 0.65rem; font-weight: 800; letter-spacing: 0.08em; padding: 0.2rem 0.5rem; border-radius: 6px;
}
.bc-tpl-badge-email { background: rgba(255,255,255,0.2); }
.bc-tpl-badge-sms { background: rgba(255,255,255,0.2); }
.bc-composer-email { border: 2px solid rgba(14, 67, 133, 0.15) !important; border-top: none !important; border-radius: 0 0 16px 16px !important; }
.bc-composer-sms { border: 2px solid rgba(13, 92, 74, 0.15) !important; border-top: none !important; border-radius: 0 0 16px 16px !important; }
.bc-email-mockup {
    border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; background: #fff;
    box-shadow: 0 4px 16px rgba(14, 67, 133, 0.08);
}
.bc-email-mockup-bar { display: flex; gap: 0.35rem; padding: 0.5rem 0.75rem; background: #f1f5f9; border-bottom: 1px solid #e2e8f0; }
.bc-email-mockup-dot { width: 0.5rem; height: 0.5rem; border-radius: 50%; background: #cbd5e1; }
.bc-email-mockup-body { padding: 1rem; }
.bc-email-mockup-subject { font-weight: 700; font-size: 0.9rem; color: #0E4385; margin-bottom: 0.65rem; padding-bottom: 0.5rem; border-bottom: 1px dashed #e2e8f0; }
.bc-email-mockup-content { white-space: pre-wrap; color: #334155; line-height: 1.55; min-height: 6rem; }
.bc-phone-mockup {
    width: 200px; background: #1e293b; border-radius: 28px; padding: 0.65rem;
    box-shadow: 0 12px 32px rgba(0,0,0,0.2);
}
.bc-phone-notch { width: 40%; height: 4px; background: #334155; border-radius: 999px; margin: 0 auto 0.5rem; }
.bc-phone-screen { background: #f8fafc; border-radius: 20px; padding: 1rem 0.85rem; min-height: 220px; }
.bc-phone-header { font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; margin-bottom: 0.75rem; text-align: center; }
.bc-phone-bubble {
    background: #dcf8e8; color: #1e293b; border-radius: 14px 14px 14px 4px; padding: 0.65rem 0.75rem;
    font-size: 0.82rem; line-height: 1.45; white-space: pre-wrap; word-break: break-word;
}
.bc-phone-time { text-align: center; margin-top: 0.75rem; }
.bc-modal-header.bc-modal-header-email { background: linear-gradient(90deg, #0b3569, #1560a8); color: #fff; }
.bc-modal-header.bc-modal-header-sms { background: linear-gradient(90deg, #064032, #13806a); color: #fff; }
.bc-modal-header .btn-close { filter: invert(1); }
.bc-modal-badge { font-size: 0.65rem; font-weight: 800; letter-spacing: 0.08em; opacity: 0.85; }
.bc-action-channel-email { color: #0E4385 !important; }
.bc-action-channel-sms { color: #0d5c4a !important; }
.bc-send-btn-email { background: #0E4385 !important; border-color: #0E4385 !important; color: #fff !important; }
.bc-send-btn-sms { background: #0d5c4a !important; border-color: #0d5c4a !important; color: #fff !important; }
.bc-composer-card { border-radius: 16px; }
.bc-tpl-sidebar { background: linear-gradient(165deg, #0b3569 0%, #0E4385 45%, #1560a8 100%); }
.bc-tpl-sidebar-sms { background: linear-gradient(165deg, #064032 0%, #0d5c4a 50%, #13806a 100%); }
.bc-composer-sms { border-color: rgba(13, 92, 74, 0.15) !important; }
.bc-tpl-sidebar .form-select { border: none; }
.bc-upload-zone {
    position: relative; border: 2px dashed #cbd5e1; border-radius: 12px; padding: 1.25rem 1rem;
    text-align: center; cursor: pointer; transition: border-color 0.15s, background 0.15s;
    display: flex; flex-direction: column; align-items: center; gap: 0.25rem;
}
.bc-upload-zone-compact i { font-size: 1.1rem; color: var(--agile-primary, #0E4385); }
.bc-upload-zone:hover, .bc-upload-zone.bc-dragover { border-color: var(--agile-primary, #0E4385); background: rgba(14, 67, 133, 0.04); }
.bc-upload-input { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
.bc-upload-icon { font-size: 1.75rem; color: var(--agile-primary, #0E4385); opacity: 0.7; }
.bc-upload-text { font-size: 0.9rem; font-weight: 500; color: #475569; }
.bc-recipients-head {
    display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem;
    background: #f8fafc; border-bottom: 1px solid #e2e8f0;
}
.bc-select-bar {
    display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem;
    padding: 0.7rem 1rem; background: #f0f7ff; border-bottom: 1px solid #dbe7f5; position: sticky; top: 0; z-index: 3;
}
.bc-select-all {
    display: flex; align-items: center; gap: 0.65rem; margin: 0; cursor: pointer; user-select: none;
}
.bc-check-lg, .bc-person .bc-check {
    width: 1.25rem; height: 1.25rem; margin: 0; cursor: pointer; flex-shrink: 0;
}
.bc-table-search { max-width: 14rem; }
.bc-table-wrap { max-height: min(52vh, 520px); overflow-y: auto; }
.bc-recipients-table thead { position: sticky; top: 0; z-index: 2; background: #f1f5f9; }
.bc-recipients-table thead th {
    font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #64748b;
    border-bottom: 1px solid #e2e8f0;
}
.bc-row.bc-row-selected .bc-person { background: rgba(14, 67, 133, 0.07); }
.bc-row:hover .bc-person { background: rgba(14, 67, 133, 0.04); }
.bc-row.bc-row-hidden { display: none; }
.bc-row:focus { outline: 2px solid rgba(14, 67, 133, 0.35); outline-offset: -2px; }
.bc-badge-warn { font-size: 0.65rem; background: #fef3c7; color: #92400e; }
.bc-sms-segments { background: rgba(13, 92, 74, 0.1); color: #0d5c4a; }
.bc-action-bar {
    position: sticky; bottom: 0; z-index: 40; margin: 0 -0.25rem;
    padding: 0.75rem 0; background: linear-gradient(to top, #eef1f6 60%, transparent);
}
.bc-action-inner {
    display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem;
    background: #fff; border: 1px solid var(--agile-border, #e2e8f0); border-radius: 14px;
    padding: 0.85rem 1.25rem; box-shadow: 0 -4px 24px rgba(15, 23, 42, 0.08);
}
.bc-action-summary { font-size: 0.9rem; color: #475569; display: flex; flex-wrap: wrap; align-items: center; gap: 0.35rem; }
.bc-action-channel { font-weight: 600; color: var(--agile-primary, #0E4385); }
.bc-action-divider { color: #cbd5e1; }
.bc-action-file { color: #059669; }
.bc-action-spacer { height: 1rem; }
.bc-send-btn { min-width: 11rem; }
.bc-tips summary { cursor: pointer; }
@media (max-width: 767.98px) {
    .bc-step-head { flex-direction: column; }
    .bc-action-inner { flex-direction: column; align-items: stretch; }
    .bc-send-btn { width: 100%; }
}
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var channelInput = document.getElementById('broadcastChannel');
    var tabEmail = document.getElementById('tab-email');
    var tabSms = document.getElementById('tab-sms');
    var checks = document.querySelectorAll('.bc-check');
    var countEl = document.getElementById('bcCount');
    var statSelected = document.getElementById('bcStatSelected');
    var attachmentInput = document.getElementById('emailAttachmentInput');
    var subjectInput = document.getElementById('broadcastSubject');
    var bodyInput = document.getElementById('broadcastBody');
    var smsMessage = document.getElementById('broadcastSmsMessage');
    var smsCharEl = document.getElementById('bcSmsCharCount');
    var actionChannel = document.getElementById('bcActionChannel');
    var actionFile = document.getElementById('bcActionFile');
    var recipientsFile = document.getElementById('bcRecipientsFile');
    var uploadFilename = document.getElementById('bcUploadFilename');
    var sendClientType = document.getElementById('bcSendClientType');
    var sendOverride = document.getElementById('bcSendClientTypeOverride');
    var filterClientType = document.getElementById('bcClientTypeFilter');
    var previewSubject = document.getElementById('bcPreviewSubject');
    var previewBox = document.getElementById('bcEmailPreview');
    var smsPreview = document.getElementById('bcSmsPreview');
    var smsSegmentsHint = document.getElementById('bcSmsSegmentsHint');
    var submitBtn = document.getElementById('bcSubmit');
    var submitLabel = document.getElementById('bcSubmitLabel');
    var tableSearch = document.getElementById('bcTableSearch');
    var visibleCount = document.getElementById('bcVisibleCount');
    var noResults = document.getElementById('bcNoResults');

    function parseJsonScript(id) {
        var el = document.getElementById(id);
        if (!el || !el.textContent) return {};
        try { return JSON.parse(el.textContent); } catch (e) { return {}; }
    }
    var emailTemplatesById = parseJsonScript('bcEmailTemplatesById');
    var smsTemplatesById = parseJsonScript('bcSmsTemplatesById');

    // Sync send segment from list filter unless overridden
    if (sendOverride) {
        sendOverride.addEventListener('change', function() {
            if (sendClientType) {
                sendClientType.value = sendOverride.value || (filterClientType ? filterClientType.value : 'all');
            }
        });
    }

    // Template helpers
    var emailTplSelect = document.getElementById('bcEmailTemplateSelect');
    var emailTplHint = document.getElementById('bcEmailTemplateHint');
    function syncEmailTplHint() {
        if (!emailTplHint || !emailTplSelect) return;
        var id = emailTplSelect.value;
        if (!id || !emailTemplatesById[id]) {
            emailTplHint.textContent = 'Select a template to preview its description.';
            return;
        }
        emailTplHint.textContent = emailTemplatesById[id].description || 'No description for this template.';
    }
    emailTplSelect && emailTplSelect.addEventListener('change', syncEmailTplHint);
    syncEmailTplHint();

    document.getElementById('bcApplyEmailTemplate')?.addEventListener('click', function() {
        var id = emailTplSelect && emailTplSelect.value;
        if (!id || !emailTemplatesById[id]) { alert('Choose a template first.'); return; }
        var t = emailTemplatesById[id];
        if (subjectInput) subjectInput.value = t.subject || '';
        if (bodyInput) bodyInput.value = t.body || '';
        if (bodyInput) bodyInput.focus();
        updateEmailPreview();
    });

    var smsTplSelect = document.getElementById('bcSmsTemplateSelect');
    var smsTplHint = document.getElementById('bcSmsTemplateHint');
    function syncSmsTplHint() {
        if (!smsTplHint || !smsTplSelect) return;
        var id = smsTplSelect.value;
        if (!id || !smsTemplatesById[id]) {
            smsTplHint.textContent = 'Select a template to preview its description.';
            return;
        }
        smsTplHint.textContent = smsTemplatesById[id].description || 'No description for this template.';
    }
    smsTplSelect && smsTplSelect.addEventListener('change', syncSmsTplHint);
    syncSmsTplHint();

    document.getElementById('bcApplySmsTemplate')?.addEventListener('click', function() {
        var id = smsTplSelect && smsTplSelect.value;
        if (!id || !smsTemplatesById[id]) { alert('Choose a template first.'); return; }
        if (smsMessage) smsMessage.value = smsTemplatesById[id].body || '';
        if (smsMessage) smsMessage.focus();
        updateSmsCharCount();
    });

    function addTemplateOption(selectEl, map, tpl) {
        if (!selectEl || !tpl) return;
        var id = String(tpl.id);
        map[id] = {
            subject: tpl.subject || '',
            body: tpl.body || '',
            description: tpl.description || '',
        };
        var opt = document.createElement('option');
        opt.value = id;
        opt.textContent = tpl.template_name;
        selectEl.appendChild(opt);
        selectEl.disabled = false;
        selectEl.value = id;
        var applyBtn = selectEl.id === 'bcEmailTemplateSelect'
            ? document.getElementById('bcApplyEmailTemplate')
            : document.getElementById('bcApplySmsTemplate');
        if (applyBtn) applyBtn.disabled = false;
    }

    // Quick-save template modal
    var tplModalEl = document.getElementById('bcTplModal');
    var tplModal = tplModalEl && typeof bootstrap !== 'undefined' ? new bootstrap.Modal(tplModalEl) : null;
    var tplModalChannel = 'email';
    var tplModalHeader = document.getElementById('bcTplModalHeader');
    var tplModalBadge = document.getElementById('bcTplModalBadge');
    var tplModalTitle = document.getElementById('bcTplModalTitle');
    var tplModalSubjectWrap = document.getElementById('bcTplModalSubjectWrap');
    var tplModalModuleWrap = document.getElementById('bcTplModalModuleWrap');
    var tplModalAlert = document.getElementById('bcTplModalAlert');
    var tplStoreUrl = @json(route('marketing.broadcast.templates.store'));
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    function openTplModal(channel, prefillFromComposer) {
        tplModalChannel = channel;
        var isSms = channel === 'sms';
        if (tplModalHeader) {
            tplModalHeader.className = 'modal-header bc-modal-header ' + (isSms ? 'bc-modal-header-sms' : 'bc-modal-header-email');
        }
        if (tplModalBadge) tplModalBadge.textContent = isSms ? 'SMS' : 'EMAIL';
        if (tplModalTitle) tplModalTitle.textContent = isSms ? 'Save SMS template' : 'Save email template';
        if (tplModalSubjectWrap) tplModalSubjectWrap.style.display = isSms ? 'none' : '';
        if (tplModalModuleWrap) tplModalModuleWrap.style.display = isSms ? 'none' : '';
        document.getElementById('bcTplModalName').value = '';
        document.getElementById('bcTplModalDesc').value = '';
        document.getElementById('bcTplModalSubject').value = prefillFromComposer && subjectInput ? subjectInput.value : '';
        document.getElementById('bcTplModalBody').value = prefillFromComposer
            ? (isSms ? (smsMessage ? smsMessage.value : '') : (bodyInput ? bodyInput.value : ''))
            : '';
        if (tplModalAlert) tplModalAlert.hidden = true;
        if (tplModal) tplModal.show();
        setTimeout(function() { document.getElementById('bcTplModalName')?.focus(); }, 200);
    }

    document.getElementById('bcOpenEmailTplModal')?.addEventListener('click', function() { openTplModal('email', false); });
    document.getElementById('bcOpenSmsTplModal')?.addEventListener('click', function() { openTplModal('sms', false); });
    document.getElementById('bcSaveEmailAsTpl')?.addEventListener('click', function() { openTplModal('email', true); });
    document.getElementById('bcSaveSmsAsTpl')?.addEventListener('click', function() { openTplModal('sms', true); });

    document.getElementById('bcTplModalSave')?.addEventListener('click', function() {
        var name = (document.getElementById('bcTplModalName')?.value || '').trim();
        var desc = (document.getElementById('bcTplModalDesc')?.value || '').trim();
        var subj = (document.getElementById('bcTplModalSubject')?.value || '').trim();
        var body = (document.getElementById('bcTplModalBody')?.value || '').trim();
        var mod = document.getElementById('bcTplModalModule')?.value || 'Broadcast';
        if (!name) { alert('Enter a template name.'); return; }
        if (tplModalChannel === 'email' && !subj) { alert('Enter a subject for the email template.'); return; }
        if (tplModalChannel === 'sms' && !body) { alert('Enter SMS message text.'); return; }
        var btn = document.getElementById('bcTplModalSave');
        if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving…'; }
        var payload = {
            channel: tplModalChannel,
            template_name: name,
            description: desc,
            body: body,
        };
        if (tplModalChannel === 'email') {
            payload.subject = subj;
            payload.module_name = mod;
        }
        fetch(tplStoreUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload),
        })
        .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, status: r.status, data: data }; }); })
        .then(function(res) {
            if (!res.ok || !res.data.ok) {
                var msg = res.data.message;
                if (!msg && res.data.errors) {
                    msg = Object.values(res.data.errors).flat().join(' ');
                }
                throw new Error(msg || 'Could not save template.');
            }
            var tpl = res.data.template;
            if (tplModalChannel === 'sms') {
                addTemplateOption(smsTplSelect, smsTemplatesById, tpl);
                syncSmsTplHint();
            } else {
                addTemplateOption(emailTplSelect, emailTemplatesById, tpl);
                syncEmailTplHint();
            }
            if (tplModal) tplModal.hide();
        })
        .catch(function(err) {
            if (tplModalAlert) {
                tplModalAlert.textContent = err.message || 'Save failed.';
                tplModalAlert.hidden = false;
            } else {
                alert(err.message || 'Save failed.');
            }
        })
        .finally(function() {
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Save template'; }
        });
    });

    function fillPreview(text) {
        return (text || '')
            .replace(/\{\{first_name\}\}/gi, 'Jane')
            .replace(/\{\{firstname\}\}/gi, 'Jane')
            .replace(/\{\{last_name\}\}/gi, 'Doe')
            .replace(/\{\{name\}\}/gi, 'Jane Doe')
            .replace(/\{\{email\}\}/gi, 'jane@example.com');
    }

    function updateSmsCharCount() {
        if (!smsCharEl || !smsMessage) return;
        var len = (smsMessage.value || '').length;
        var segments = len <= 160 ? 1 : Math.ceil(len / 153);
        smsCharEl.textContent = len + ' / 1600 · ~' + segments + ' SMS';
        if (smsSegmentsHint) smsSegmentsHint.textContent = segments + ' segment' + (segments === 1 ? '' : 's');
        updateSmsPreview();
    }

    function updateSmsPreview() {
        if (!smsPreview || !smsMessage) return;
        var text = (smsMessage.value || '').trim();
        smsPreview.textContent = text ? fillPreview(text) : 'Your SMS preview appears here…';
    }

    function updateEmailPreview() {
        if (previewSubject && subjectInput) {
            previewSubject.textContent = (subjectInput.value || '').trim() || 'Subject line…';
        }
        if (!previewBox || !bodyInput) return;
        var text = (bodyInput.value || '').trim();
        previewBox.textContent = text ? fillPreview(text) : 'Start typing to see a preview with sample names.';
    }
    subjectInput && subjectInput.addEventListener('input', updateEmailPreview);
    bodyInput && bodyInput.addEventListener('input', updateEmailPreview);
    smsMessage && smsMessage.addEventListener('input', updateSmsCharCount);
    updateSmsCharCount();
    updateEmailPreview();

    // Insert placeholder tokens
    document.querySelectorAll('.bc-insert-token').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var targetId = btn.getAttribute('data-target');
            var token = btn.getAttribute('data-token') || '';
            var el = targetId ? document.getElementById(targetId) : null;
            if (!el) return;
            var start = el.selectionStart || el.value.length;
            var end = el.selectionEnd || start;
            el.value = el.value.slice(0, start) + token + el.value.slice(end);
            el.focus();
            el.selectionStart = el.selectionEnd = start + token.length;
            if (targetId === 'broadcastBody') updateEmailPreview();
            if (targetId === 'broadcastSmsMessage') updateSmsCharCount();
        });
    });

    // Channel switching
    function selectForChannel(ch) {
        var attr = ch === 'sms' ? 'data-has-phone' : 'data-has-email';
        document.querySelectorAll('.bc-row:not(.bc-row-hidden) .bc-check').forEach(function(cb) {
            cb.checked = cb.getAttribute(attr) === '1';
        });
        updateCount();
    }
    function setChannel(ch) {
        if (channelInput) channelInput.value = ch;
        if (attachmentInput) attachmentInput.disabled = ch === 'sms';
        document.querySelectorAll('.bc-channel-card').forEach(function(btn) {
            var active = btn.getAttribute('data-channel') === ch;
            btn.classList.toggle('active', active);
            btn.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        if (actionChannel) {
            actionChannel.className = 'bc-action-channel ' + (ch === 'sms' ? 'bc-action-channel-sms' : 'bc-action-channel-email');
            actionChannel.innerHTML = ch === 'sms'
                ? '<i class="bi bi-chat-dots-fill"></i> SMS broadcast'
                : '<i class="bi bi-envelope-fill"></i> Email broadcast';
        }
        if (submitBtn) {
            submitBtn.classList.remove('bc-send-btn-email', 'bc-send-btn-sms');
            submitBtn.classList.add(ch === 'sms' ? 'bc-send-btn-sms' : 'bc-send-btn-email');
        }
        if (submitLabel) submitLabel.textContent = ch === 'sms' ? 'Send SMS' : 'Send email';
    }
    function onTabShown(ch) {
        var prev = channelInput ? channelInput.value : 'email';
        setChannel(ch);
        syncSkipLabel(ch);
        if (prev !== ch) selectForChannel(ch);
    }
    tabEmail && tabEmail.addEventListener('shown.bs.tab', function() { onTabShown('email'); });
    tabSms && tabSms.addEventListener('shown.bs.tab', function() { onTabShown('sms'); });
    tabEmail && tabEmail.addEventListener('click', function() { onTabShown('email'); });
    tabSms && tabSms.addEventListener('click', function() { onTabShown('sms'); });

    var skipChEl = document.getElementById('skipChannelLabel');
    function syncSkipLabel(ch) {
        if (skipChEl) skipChEl.textContent = ch === 'sms' ? 'SMS' : 'email';
    }
    setChannel('email');

    var filterForm = document.getElementById('bcFilterForm');
    if (filterClientType && filterForm) {
        filterClientType.addEventListener('change', function() { filterForm.submit(); });
    }
    var listLimit = document.getElementById('bcListLimit');
    if (listLimit && filterForm) {
        listLimit.addEventListener('change', function() { filterForm.submit(); });
    }
    ['hideListEmailRecent', 'hideListSmsRecent'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el && filterForm) el.addEventListener('change', function() { filterForm.submit(); });
    });

    // Selection count
    function visibleChecks() {
        return document.querySelectorAll('.bc-row:not(.bc-row-hidden) .bc-check');
    }
    function syncMaster() {
        var master = document.getElementById('bcSelectAll');
        var hint = document.getElementById('bcSelectAllHint');
        var boxes = visibleChecks();
        var n = 0;
        boxes.forEach(function(cb) { if (cb.checked) n++; });
        if (master) {
            master.checked = boxes.length > 0 && n === boxes.length;
            master.indeterminate = n > 0 && n < boxes.length;
        }
        if (hint) {
            if (boxes.length === 0) hint.textContent = 'No people on this list';
            else if (n === boxes.length) hint.textContent = 'All ' + boxes.length + ' people selected';
            else if (n === 0) hint.textContent = 'Tick to select all ' + boxes.length + ' people';
            else hint.textContent = n + ' of ' + boxes.length + ' selected — tick to select the rest';
        }
    }
    function updateCount() {
        var n = document.querySelectorAll('.bc-check:checked').length;
        if (countEl) countEl.textContent = n;
        if (statSelected) statSelected.textContent = n;
        document.querySelectorAll('.bc-row').forEach(function(row) {
            var cb = row.querySelector('.bc-check');
            if (cb) row.classList.toggle('bc-row-selected', cb.checked);
        });
        syncMaster();
    }

    document.getElementById('bcSelectAll')?.addEventListener('click', function(e) {
        e.stopPropagation();
    });
    document.getElementById('bcSelectAll')?.addEventListener('change', function() {
        var on = this.checked;
        visibleChecks().forEach(function(cb) { cb.checked = on; });
        updateCount();
    });

    document.getElementById('bcSelectAllEmail')?.addEventListener('click', function() {
        document.querySelectorAll('.bc-row:not(.bc-row-hidden) .bc-check').forEach(function(cb) {
            cb.checked = cb.getAttribute('data-has-email') === '1';
        });
        updateCount();
    });
    document.getElementById('bcSelectAllSms')?.addEventListener('click', function() {
        document.querySelectorAll('.bc-row:not(.bc-row-hidden) .bc-check').forEach(function(cb) {
            cb.checked = cb.getAttribute('data-has-phone') === '1';
        });
        updateCount();
    });
    document.getElementById('bcSelectNone')?.addEventListener('click', function() {
        checks.forEach(function(cb) { cb.checked = false; });
        updateCount();
    });
    checks.forEach(function(cb) { cb.addEventListener('change', updateCount); });

    // Click row to toggle
    document.querySelectorAll('.bc-row').forEach(function(row) {
        row.addEventListener('click', function() {
            var cb = row.querySelector('.bc-check');
            if (cb) { cb.checked = !cb.checked; updateCount(); }
        });
        row.addEventListener('keydown', function(e) {
            if (e.key === ' ' || e.key === 'Enter') { e.preventDefault(); row.click(); }
        });
    });
    selectForChannel('email');

    // Table filter
    function filterTable() {
        var q = (tableSearch && tableSearch.value || '').trim().toLowerCase();
        var visible = 0;
        document.querySelectorAll('.bc-row').forEach(function(row) {
            var match = !q || (row.getAttribute('data-search') || '').indexOf(q) !== -1;
            row.classList.toggle('bc-row-hidden', !match);
            if (match) visible++;
        });
        if (visibleCount) visibleCount.textContent = visible + ' people';
        if (noResults) noResults.hidden = visible > 0 || !q;
        syncMaster();
    }
    tableSearch && tableSearch.addEventListener('input', filterTable);
    document.getElementById('bcClearTableSearch')?.addEventListener('click', function() {
        if (tableSearch) { tableSearch.value = ''; filterTable(); tableSearch.focus(); }
    });

    // Upload zone
    function syncFileLabel() {
        var has = recipientsFile && recipientsFile.files && recipientsFile.files.length > 0;
        if (actionFile) actionFile.hidden = !has;
        if (uploadFilename) {
            if (has) {
                uploadFilename.textContent = recipientsFile.files[0].name;
                uploadFilename.hidden = false;
            } else {
                uploadFilename.hidden = true;
            }
        }
    }
    recipientsFile && recipientsFile.addEventListener('change', syncFileLabel);
    syncFileLabel();

    var uploadZone = document.getElementById('bcUploadZone');
    if (uploadZone && recipientsFile) {
        ['dragenter', 'dragover'].forEach(function(ev) {
            uploadZone.addEventListener(ev, function(e) { e.preventDefault(); uploadZone.classList.add('bc-dragover'); });
        });
        ['dragleave', 'drop'].forEach(function(ev) {
            uploadZone.addEventListener(ev, function(e) {
                e.preventDefault();
                uploadZone.classList.remove('bc-dragover');
                if (ev === 'drop' && e.dataTransfer.files.length) {
                    try {
                        var dt = new DataTransfer();
                        dt.items.add(e.dataTransfer.files[0]);
                        recipientsFile.files = dt.files;
                    } catch (err) {
                        /* fallback: native assignment where supported */
                        recipientsFile.files = e.dataTransfer.files;
                    }
                    syncFileLabel();
                }
            });
        });
    }

    // Quick templates
    document.getElementById('loadPensionTemplate')?.addEventListener('click', function() {
        if (subjectInput) subjectInput.value = '2025 Pension Declared Rate of Return';
        if (bodyInput) {
            bodyInput.value =
                'Dear @{{first_name}},\n\n' +
                'We are pleased to inform you that your pension contributions earned a return of 12.25% in 2025, up from 11.5% in 2024.\n\n' +
                'Please find the official communication on the declared rate of return attached for your records.\n\n' +
                'A detailed breakdown, including how this rate has been applied and its impact on your accumulated funds, will be provided in your Member Statement in due course.\n\n' +
                'For enquiries, please call 0709 551 150 or email life@geminialife.co.ke.\n\n' +
                'Thank you.\n' +
                'AGILE CRAFT LIFE INSURANCE CO. LTD';
            bodyInput.focus();
        }
        updateEmailPreview();
    });
    document.getElementById('clearEmailTemplate')?.addEventListener('click', function() {
        if (subjectInput) subjectInput.value = '';
        if (bodyInput) bodyInput.value = '';
        updateEmailPreview();
    });

    // Submit validation
    document.getElementById('broadcastForm')?.addEventListener('submit', function(e) {
        var n = document.querySelectorAll('.bc-check:checked').length;
        var hasFile = recipientsFile && recipientsFile.files && recipientsFile.files.length > 0;
        if (n < 1 && !hasFile) {
            e.preventDefault();
            alert('Select at least one contact or upload a recipient file.');
            return false;
        }
        var ch = channelInput ? channelInput.value : 'email';
        if (ch === 'email') {
            if (!subjectInput || !(subjectInput.value || '').trim()) {
                e.preventDefault();
                alert('Please enter an email subject.');
                if (subjectInput) subjectInput.focus();
                return false;
            }
            if (!bodyInput || !(bodyInput.value || '').trim()) {
                e.preventDefault();
                alert('Please enter an email message.');
                if (bodyInput) bodyInput.focus();
                return false;
            }
        } else if (!smsMessage || !(smsMessage.value || '').trim()) {
            e.preventDefault();
            alert('Please enter SMS text.');
            if (smsMessage) smsMessage.focus();
            return false;
        }
        var targetDesc = n > 0 ? n + ' selected contact(s)' : 'recipients from your file';
        if (!confirm('Send ' + ch.toUpperCase() + ' to ' + targetDesc + '?\n\nThis cannot be undone.')) {
            e.preventDefault();
            return false;
        }
        var btn = document.getElementById('bcSubmit');
        if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Sending…'; }
    });
});
</script>
@endpush
@endsection
