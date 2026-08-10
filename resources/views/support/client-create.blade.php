@extends('layouts.app')

@section('title', 'Create Client')

@section('content')
<nav class="breadcrumb-nav mb-3">
    <a href="{{ route('support.customers') }}" class="text-muted small text-decoration-none">Clients</a>
    <span class="text-muted mx-2">/</span>
    <span class="text-dark small fw-semibold">Create Client</span>
</nav>

<h1 class="app-page-title mb-4">Create Client</h1>

<form method="POST" action="{{ route('support.clients.store') }}">
    @csrf

    <div class="app-card mb-4">
        <div class="p-4">
            <h6 class="text-uppercase small fw-bold mb-4" style="color:var(--agile-primary);letter-spacing:0.08em">Client Details</h6>
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                    <input type="text" name="first_name" class="form-control" value="{{ old('first_name') }}" required>
                    @error('first_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Last Name</label>
                    <input type="text" name="last_name" class="form-control" value="{{ old('last_name') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                    @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Phone</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="e.g. 0712345678">
                </div>
            </div>
        </div>
    </div>

    <div class="app-card mb-4">
        <div class="p-4">
            <h6 class="text-uppercase small fw-bold mb-4" style="color:var(--agile-primary);letter-spacing:0.08em">KYC Details</h6>
            <div class="row g-4">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">ID / Passport No.</label>
                    <input type="text" name="id_no" class="form-control font-monospace" value="{{ old('id_no') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">KRA PIN</label>
                    <input type="text" name="kra_pin" class="form-control font-monospace" value="{{ old('kra_pin') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Gender</label>
                    <select name="gender" class="form-select">
                        <option value="">Select…</option>
                        @foreach(['Male', 'Female', 'Other'] as $g)
                            <option value="{{ $g }}" {{ old('gender') === $g ? 'selected' : '' }}>{{ $g }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Occupation</label>
                    @php $occOld = old('occupation'); $occIsOther = $occOld && ! in_array($occOld, $occupations, true); @endphp
                    <select name="occupation" class="form-select" id="occupationSelect" data-other-target="#occupationOther">
                        <option value="">Select…</option>
                        @foreach($occupations as $occ)
                        <option value="{{ $occ }}" {{ $occOld === $occ ? 'selected' : '' }}>{{ $occ }}</option>
                        @endforeach
                        <option value="Other" {{ $occIsOther ? 'selected' : '' }}>Other</option>
                    </select>
                    <input type="text" name="occupation_other" id="occupationOther" class="form-control mt-2 {{ $occIsOther ? '' : 'd-none' }}" placeholder="Enter occupation" value="{{ $occIsOther ? $occOld : '' }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Postal Code</label>
                    <input type="text" name="postal_code" class="form-control" value="{{ old('postal_code') }}">
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Physical / Postal Address</label>
                    <input type="text" name="address" class="form-control" value="{{ old('address') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">City / Town</label>
                    <input type="text" name="city" class="form-control" value="{{ old('city') }}">
                </div>
            </div>
        </div>
    </div>

    <div class="app-card mb-4">
        <div class="p-4">
            <h6 class="text-uppercase small fw-bold mb-4" style="color:var(--agile-primary);letter-spacing:0.08em">Policy &amp; Segment</h6>
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Policy Number</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-magic"></i></span>
                        <input type="text" class="form-control font-monospace bg-light" value="{{ $previewPolicyNo }}" readonly>
                    </div>
                    <div class="form-text">Auto-generated and assigned when you save.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Product</label>
                    <select name="product" class="form-select">
                        <option value="">Select a product…</option>
                        @foreach($products as $class => $items)
                        <optgroup label="{{ $class }}">
                            @foreach($items as $product)
                            <option value="{{ $product }}" {{ old('product') === $product ? 'selected' : '' }}>{{ $product }}</option>
                            @endforeach
                        </optgroup>
                        @endforeach
                    </select>
                    @error('product')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Intermediary (Agent)</label>
                    @if($agents->isNotEmpty())
                    <select name="intermediary" class="form-select">
                        <option value="">Select an agent…</option>
                        @foreach($agents as $agent)
                        <option value="{{ $agent->label() }}" {{ old('intermediary') === $agent->label() ? 'selected' : '' }}>{{ $agent->label() }}</option>
                        @endforeach
                    </select>
                    @else
                    <input type="text" name="intermediary" class="form-control" value="{{ old('intermediary') }}" placeholder="No agents seeded yet">
                    @endif
                    @error('intermediary')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Client Type <span class="text-danger">*</span></label>
                    <select name="system" class="form-select" required>
                        @foreach($systems as $val => $label)
                            <option value="{{ $val }}" {{ old('system', $defaultSystem) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        @foreach($statuses as $val => $label)
                            <option value="{{ $val }}" {{ old('status', 'A') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Notes</label>
                    <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn app-btn-primary"><i class="bi bi-check-lg me-1"></i>Create Client</button>
        <a href="{{ route('support.customers') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

<script>
(function () {
    var sel = document.getElementById('occupationSelect');
    var other = document.getElementById('occupationOther');
    if (!sel || !other) return;
    function sync() {
        var isOther = sel.value === 'Other';
        other.classList.toggle('d-none', !isOther);
        if (isOther) { other.focus(); } else { other.value = ''; }
    }
    sel.addEventListener('change', sync);
})();
</script>
@endsection
