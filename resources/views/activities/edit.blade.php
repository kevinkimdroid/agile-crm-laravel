@extends('layouts.app')

@section('title', 'Edit activity')

@section('content')
@php
    $cancelUrl = $returnTo ?? route('activities.index', array_filter(['contact_id' => $activity->related_to_id ?? null]));
@endphp
<div class="page-header mb-4">
    <nav class="mb-2">
        @if($returnTo ?? null)
        <a href="{{ $returnTo }}" class="text-muted small">Back to client</a>
        <span class="text-muted mx-1">/</span>
        @else
        <a href="{{ route('activities.index') }}" class="text-muted small">Calendar</a>
        <span class="text-muted mx-1">/</span>
        @endif
        <span class="text-dark">Edit activity</span>
    </nav>
    <h1 class="page-title">Edit activity</h1>
    <p class="page-subtitle">{{ $activity->subject ?? 'Untitled' }} · {{ $activity->activitytype ?? 'Task' }}</p>
</div>

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

@include('activities.partials.form', [
    'formAction' => route('activities.update', $activity->activityid),
    'isEdit' => true,
    'cancelUrl' => $cancelUrl,
])
@endsection
