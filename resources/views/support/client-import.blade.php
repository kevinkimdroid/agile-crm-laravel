@extends('layouts.app')

@section('title', 'Import Clients')

@section('content')
<nav class="breadcrumb-nav mb-3">
    <a href="{{ route('support.customers') }}" class="text-muted small text-decoration-none">Clients</a>
    <span class="text-muted mx-2">/</span>
    <span class="text-dark small fw-semibold">Import</span>
</nav>

<h1 class="app-page-title mb-4">Import Clients</h1>

@if (session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-4">
    <div class="col-lg-7">
        <div class="app-card p-4">
            <h6 class="text-uppercase small fw-bold mb-4" style="color:var(--agile-primary);letter-spacing:0.08em">Upload CSV</h6>
            <form method="POST" action="{{ route('support.clients.import.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-semibold">CSV file <span class="text-danger">*</span></label>
                    <input type="file" name="file" class="form-control" accept=".csv,text/csv" required>
                    @error('file')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Default client type</label>
                    <select name="default_system" class="form-select" style="max-width:16rem">
                        @foreach($systems as $val => $label)
                            <option value="{{ $val }}" {{ $val === 'individual' ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Used for rows that don't specify a <code>system</code> column.</div>
                </div>
                <button type="submit" class="btn app-btn-primary"><i class="bi bi-upload me-1"></i>Import</button>
                <a href="{{ route('support.customers') }}" class="btn btn-outline-secondary">Cancel</a>
            </form>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="app-card p-4">
            <h6 class="text-uppercase small fw-bold mb-3" style="color:var(--agile-primary);letter-spacing:0.08em">Format</h6>
            <p class="text-muted small mb-1">The first row must be a header. Recognised columns:</p>
            <p class="small font-monospace text-break">first_name, last_name, id_no, kra_pin, date_of_birth, gender, email, phone, address, city, postal_code, occupation, product, intermediary, system, status, policy_no</p>
            <ul class="small text-muted mb-3">
                <li>Only <strong>a name</strong> is required — a single <code>name</code> column also works.</li>
                <li><code>system</code>: {{ implode(', ', array_keys($systems)) }}</li>
                <li><code>status</code>: {{ collect($statuses)->map(fn($l,$k) => "$k ($l)")->implode(', ') }}</li>
                <li><code>date_of_birth</code>: any clear date, e.g. <code>1990-05-14</code></li>
                <li><code>gender</code>: Male / Female (m / f accepted)</li>
                <li><code>policy_no</code>: include for POC policies (e.g. <code>KOL-IND-10001</code>). Blank = auto-generated. Existing numbers are skipped.</li>
                <li>Upload <strong>CSV</strong> (Excel: File → Save As → CSV UTF-8).</li>
            </ul>

            <details class="mb-3">
                <summary class="small fw-semibold" style="cursor:pointer;color:var(--agile-primary)">Valid product names ({{ count(collect($products)->flatten()) }})</summary>
                <div class="small text-muted mt-2">
                    @foreach($products as $class => $items)
                    <div class="mb-1"><span class="fw-semibold">{{ $class }}:</span> {{ implode(', ', $items) }}</div>
                    @endforeach
                    <div class="mt-1">Unknown products are kept as typed.</div>
                </div>
            </details>

            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('support.clients.import.template') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-download me-1"></i>Download sample CSV (Excel)</a>
                <a href="{{ asset('samples/clients-import-sample.csv') }}" class="btn btn-sm btn-outline-secondary" download><i class="bi bi-file-earmark-spreadsheet me-1"></i>Static sample file</a>
            </div>
            <p class="text-muted small mt-2 mb-0">Sample includes 10 clients across Individual, Group, Mortgage and Pension with policy numbers for ticket testing.</p>
        </div>
    </div>
</div>
@endsection
