@extends('layouts.app')

@section('title', 'Add Prospect')

@section('content')
<div class="page-header">
    <nav class="mb-2"><a href="{{ route('contacts.index') }}" class="text-muted small">Prospects</a> / Add</nav>
    <h1 class="page-title">Add Prospect</h1>
    <p class="page-subtitle">Capture contact, identity, address and source details.</p>
</div>

@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<form method="POST" action="{{ route('contacts.store') }}" style="max-width: 920px;">
    @csrf
    @include('contacts.partials.form-fields', ['contact' => null, 'leadSources' => $leadSources ?? []])
    <div class="mb-4">
        <button type="submit" class="btn btn-primary-custom">Create Prospect</button>
        <a href="{{ route('contacts.index') }}" class="btn btn-outline-secondary ms-2">Cancel</a>
    </div>
</form>
@endsection
