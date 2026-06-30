@php
    $detailFields = [
        ['Last Name', $contact->lastname ?? null],
        ['First Name', $contact->firstname ?? null],
        ['Id Number', $contact->idNumber ?? null],
        ['Policy Number', $contact->policy_number ?? null],
        ['Prospect Id', $contact->contact_no ?? null],
        ['PIN', $contact->pin ?? null],
        ['Office Phone', $contact->phone ?? null],
        ['Mobile Phone', $contact->mobile ?? null],
        ['Primary Email', $contact->email ?? null],
        ['Secondary Email', $contact->secondaryemail ?? $contact->otheremail ?? null],
        ['Title', $contact->title ?? null],
        ['Department', $contact->department ?? null],
        ['Fax', $contact->fax ?? null],
        ['Date of Birth', null],
        ['Organization Name', null],
        ['Lead Source', null],
        ['Reports To', $contact->reportsto ?? null],
        ['Assistant', null],
        ['Assistant Phone', null],
        ['Do Not Call', isset($contact->donotcall) ? ($contact->donotcall ? 'Yes' : 'No') : null],
        ['Email Opt Out', isset($contact->emailoptout) ? ($contact->emailoptout ? 'Yes' : 'No') : null],
        ['Reference', isset($contact->reference) ? ($contact->reference ? 'Yes' : 'No') : null],
        ['Assigned To', $contact->assigned_to_name ?? null],
        ['Notify Owner', isset($contact->notify_owner) ? ($contact->notify_owner ? 'Yes' : 'No') : null],
        ['Is Converted From Lead', isset($contact->isconvertedfromlead) ? ($contact->isconvertedfromlead ? 'Yes' : 'No') : null],
        ['Created Time', $contact->createdtime ? date('d-m-Y H:i', strtotime($contact->createdtime)) : null],
        ['Modified Time', $contact->modifiedtime ? date('d-m-Y H:i', strtotime($contact->modifiedtime)) : null],
        ['Source', $contact->source ?? null],
    ];
    $left = array_slice($detailFields, 0, (int) ceil(count($detailFields) / 2));
    $right = array_slice($detailFields, (int) ceil(count($detailFields) / 2));
    $displayValue = fn ($value) => ($value !== null && $value !== '') ? $value : '—';
@endphp

<div class="card contact-detail-card mb-4">
    <div class="card-body p-4">
        <h6 class="text-uppercase small fw-bold text-muted mb-3">Basic Information</h6>
        <div class="row">
            <div class="col-md-6">
                <dl class="row mb-0">
                    @foreach($left as $f)
                    <dt class="col-sm-5 text-muted small">{{ $f[0] }}</dt>
                    <dd class="col-sm-7 mb-2">{{ $displayValue($f[1]) }}</dd>
                    @endforeach
                </dl>
            </div>
            <div class="col-md-6">
                <dl class="row mb-0">
                    @foreach($right as $f)
                    <dt class="col-sm-5 text-muted small">{{ $f[0] }}</dt>
                    <dd class="col-sm-7 mb-2">{{ $displayValue($f[1]) }}</dd>
                    @endforeach
                </dl>
            </div>
        </div>
    </div>
</div>
