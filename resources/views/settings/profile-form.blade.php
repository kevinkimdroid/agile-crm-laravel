@extends('layouts.app')

@section('title', ($isCreate ?? false) ? 'Create Profile' : ('Profile — ' . ($profile->profilename ?? '')))

@section('content')
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small text-uppercase">
                <li class="breadcrumb-item"><a href="{{ route('settings.crm') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('settings.crm', ['section' => 'profiles']) }}">Profiles</a></li>
                <li class="breadcrumb-item active">{{ ($isCreate ?? false) ? 'Create Profile' : ($profile->profilename ?? 'Profile') }}</li>
            </ol>
        </nav>
        <h1 class="page-title mb-0">{{ ($isCreate ?? false) ? 'Create Profile' : 'Profile view' }}</h1>
    </div>
    <a href="{{ route('settings.crm', ['section' => 'profiles']) }}" class="btn btn-outline-secondary btn-sm">Back to Profiles</a>
</div>

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@include('settings.partials.profile-form-body')
@endsection
