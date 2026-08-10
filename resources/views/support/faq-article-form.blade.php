@extends('layouts.app')

@section('title', $article->exists ? 'Edit FAQ' : 'New FAQ')

@section('content')
@php $isEdit = $article->exists; @endphp
<nav class="mb-3">
    <a href="{{ route('support.faq') }}" class="text-muted small text-decoration-none">FAQ &amp; Knowledge Base</a>
    <span class="text-muted mx-2">/</span>
    <a href="{{ route('support.faq.manage') }}" class="text-muted small text-decoration-none">Manage</a>
    <span class="text-muted mx-2">/</span>
    <span class="text-dark small fw-semibold">{{ $isEdit ? 'Edit FAQ' : 'New FAQ' }}</span>
</nav>

<h1 class="app-page-title mb-4">{{ $isEdit ? 'Edit FAQ' : 'New FAQ' }}</h1>

<form method="POST" action="{{ $isEdit ? route('support.faq.articles.update', $article) : route('support.faq.articles.store') }}">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="app-card mb-4">
        <div class="p-4">
            <div class="row g-4">
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Question <span class="text-danger">*</span></label>
                    <input type="text" name="question" class="form-control" value="{{ old('question', $article->question) }}" required>
                    @error('question')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        @foreach($statuses as $val => $label)
                            <option value="{{ $val }}" {{ old('status', $article->status ?? 'published') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Category</label>
                    <select name="faq_category_id" class="form-select">
                        <option value="">Uncategorised</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ (string) old('faq_category_id', $article->faq_category_id) === (string) $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Tags</label>
                    <input type="text" name="tags" class="form-control" value="{{ old('tags', $article->tags) }}" placeholder="comma,separated,keywords">
                    <div class="form-text">Used to match this FAQ to tickets and searches.</div>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Answer <span class="text-danger">*</span></label>
                    <textarea name="answer" class="form-control" rows="8" required placeholder="The resolution / guidance…">{{ old('answer', $article->answer) }}</textarea>
                    @error('answer')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn app-btn-primary"><i class="bi bi-check-lg me-1"></i>{{ $isEdit ? 'Save changes' : 'Create FAQ' }}</button>
        <a href="{{ route('support.faq.manage') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
@endsection
