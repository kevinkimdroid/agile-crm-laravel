@extends('layouts.app')

@section('title', 'Edit ' . $contact->full_name)

@section('content')
<div class="page-header">
    <nav class="mb-2"><a href="{{ route('contacts.index') }}" class="text-muted small">Prospects</a> / <a href="{{ route('contacts.show', $contact->contactid) }}">{{ $contact->full_name }}</a> / Edit</nav>
    <h1 class="page-title">Edit Prospect</h1>
    <p class="page-subtitle">Update contact, identity, address and source details.</p>
</div>

@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<form method="POST" action="{{ route('contacts.update', $contact->contactid) }}" style="max-width: 920px;">
    @csrf
    @method('PUT')
    @include('contacts.partials.form-fields', ['contact' => $contact, 'leadSources' => $leadSources ?? []])
    <div class="mb-4">
        <button type="submit" class="btn btn-primary-custom">Update Prospect</button>
        <a href="{{ route('contacts.show', $contact->contactid) }}" class="btn btn-outline-secondary ms-2">Cancel</a>
    </div>
</form>
@endsection
