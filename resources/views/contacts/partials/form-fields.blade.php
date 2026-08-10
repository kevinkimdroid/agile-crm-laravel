@php
    /** @var object|null $contact */
    $c = $contact ?? null;
    $leadSources = $leadSources ?? [];
    $val = function (string $key, $default = '') use ($c) {
        $fromContact = $c ? data_get($c, $key) : null;
        if ($fromContact instanceof \Carbon\CarbonInterface) {
            $fromContact = $fromContact->format('Y-m-d');
        }
        return old($key, $fromContact ?? $default);
    };
@endphp

<div class="card mb-4">
    <div class="card-body p-4">
        <h6 class="text-uppercase small fw-bold text-muted mb-3">Basic information</h6>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">First Name <span class="text-danger">*</span></label>
                <input type="text" name="firstname" class="form-control" value="{{ $val('firstname') }}" required>
                @error('firstname')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                <input type="text" name="lastname" class="form-control" value="{{ $val('lastname') }}" required>
                @error('lastname')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Title / Position</label>
                <input type="text" name="title" class="form-control" value="{{ $val('title') }}" placeholder="e.g. Finance Manager">
            </div>
            <div class="col-md-6">
                <label class="form-label">Department / Organization</label>
                <input type="text" name="department" class="form-control" value="{{ $val('department') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Lead Source</label>
                <select name="leadsource" class="form-select">
                    <option value="">Select…</option>
                    @foreach($leadSources as $source)
                        <option value="{{ $source }}" @selected($val('leadsource') === $source)>{{ $source }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Date of Birth</label>
                <input type="date" name="birthday" class="form-control" value="{{ $val('birthday') }}">
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body p-4">
        <h6 class="text-uppercase small fw-bold text-muted mb-3">Contact details</h6>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Primary Email</label>
                <input type="email" name="email" class="form-control" value="{{ $val('email') }}">
                @error('email')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Secondary Email</label>
                <input type="email" name="secondaryemail" class="form-control" value="{{ $val('secondaryemail') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Mobile</label>
                <input type="text" name="mobile" class="form-control" value="{{ $val('mobile') }}" placeholder="e.g. 0712345678">
            </div>
            <div class="col-md-4">
                <label class="form-label">Office Phone</label>
                <input type="text" name="phone" class="form-control" value="{{ $val('phone') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Home Phone</label>
                <input type="text" name="homephone" class="form-control" value="{{ $val('homephone') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Fax</label>
                <input type="text" name="fax" class="form-control" value="{{ $val('fax') }}">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <div class="form-check mb-2">
                    <input type="checkbox" name="donotcall" value="1" class="form-check-input" id="donotcall" @checked((string) $val('donotcall', '0') === '1' || $val('donotcall') === 1 || $val('donotcall') === true)>
                    <label class="form-check-label" for="donotcall">Do not call</label>
                </div>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <div class="form-check mb-2">
                    <input type="checkbox" name="emailoptout" value="1" class="form-check-input" id="emailoptout" @checked((string) $val('emailoptout', '0') === '1' || $val('emailoptout') === 1 || $val('emailoptout') === true)>
                    <label class="form-check-label" for="emailoptout">Email opt out</label>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body p-4">
        <h6 class="text-uppercase small fw-bold text-muted mb-3">Identity &amp; policy</h6>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">ID / Passport No.</label>
                <input type="text" name="id_number" class="form-control font-monospace" value="{{ $val('id_number', $val('idNumber')) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">KRA PIN</label>
                <input type="text" name="kra_pin" class="form-control font-monospace" value="{{ $val('kra_pin', $val('pin')) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Policy Number</label>
                <input type="text" name="policy_number" class="form-control font-monospace" value="{{ $val('policy_number') }}" placeholder="If already known">
            </div>
            <div class="col-md-6">
                <label class="form-label">Estimated business worth (KES)</label>
                <input type="text" name="lead_business_worth" class="form-control" value="{{ $val('lead_business_worth', $val('cf_872')) }}">
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body p-4">
        <h6 class="text-uppercase small fw-bold text-muted mb-3">Mailing address</h6>
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label">Street / Address</label>
                <input type="text" name="mailingstreet" class="form-control" value="{{ $val('mailingstreet') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">City / Town</label>
                <input type="text" name="mailingcity" class="form-control" value="{{ $val('mailingcity') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">County / State</label>
                <input type="text" name="mailingstate" class="form-control" value="{{ $val('mailingstate') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Postal Code</label>
                <input type="text" name="mailingzip" class="form-control" value="{{ $val('mailingzip') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Country</label>
                <input type="text" name="mailingcountry" class="form-control" value="{{ $val('mailingcountry', 'Kenya') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">P.O. Box</label>
                <input type="text" name="mailingpobox" class="form-control" value="{{ $val('mailingpobox') }}">
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body p-4">
        <h6 class="text-uppercase small fw-bold text-muted mb-3">Notes</h6>
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="3" placeholder="Opportunity notes, products of interest, next steps…">{{ $val('description') }}</textarea>
    </div>
</div>
