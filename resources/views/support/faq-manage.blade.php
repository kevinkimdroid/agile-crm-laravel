@extends('layouts.app')

@section('title', 'Manage Knowledge Base')

@section('content')
<nav class="mb-3">
    <a href="{{ route('support.faq') }}" class="text-muted small text-decoration-none">FAQ &amp; Knowledge Base</a>
    <span class="text-muted mx-2">/</span>
    <span class="text-dark small fw-semibold">Manage</span>
</nav>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <h1 class="app-page-title mb-0">Manage Knowledge Base</h1>
    <a href="{{ route('support.faq.articles.create') }}" class="btn app-btn-primary"><i class="bi bi-plus-lg me-1"></i>New FAQ</a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle-fill me-1"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-4">
    <div class="col-lg-5">
        <div class="app-card">
            <div class="p-4">
                <h6 class="text-uppercase small fw-bold mb-3" style="color:var(--agile-primary);letter-spacing:0.08em">Categories</h6>

                <form method="POST" action="{{ route('support.faq.categories.store') }}" class="mb-4">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label small fw-semibold mb-1">New category</label>
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Claims" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold mb-1">Description <span class="text-muted">(optional)</span></label>
                        <input type="text" name="description" class="form-control form-control-sm" placeholder="Short topic description">
                    </div>
                    <div class="row g-2 align-items-end">
                        <div class="col-8">
                            <label class="form-label small fw-semibold mb-1">Sort order</label>
                            <input type="number" name="sort_order" class="form-control form-control-sm" value="0" min="0">
                        </div>
                        <div class="col-4">
                            <button class="btn btn-primary btn-sm w-100"><i class="bi bi-plus-lg me-1"></i>Add</button>
                        </div>
                    </div>
                    @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </form>

                <div class="list-group">
                    @forelse($categories as $cat)
                    <div class="list-group-item">
                        <form method="POST" action="{{ route('support.faq.categories.update', $cat) }}" class="d-flex flex-wrap align-items-center gap-2">
                            @csrf
                            @method('PUT')
                            <input type="text" name="name" class="form-control form-control-sm" style="max-width:11rem" value="{{ $cat->name }}" required>
                            <input type="number" name="sort_order" class="form-control form-control-sm" style="width:5rem" value="{{ $cat->sort_order }}" min="0" title="Sort order">
                            <span class="badge bg-light text-muted border">{{ $cat->articles_count }} FAQ{{ $cat->articles_count === 1 ? '' : 's' }}</span>
                            <div class="ms-auto d-flex gap-1">
                                <button class="btn btn-sm btn-outline-secondary" title="Save"><i class="bi bi-check-lg"></i></button>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('support.faq.categories.destroy', $cat) }}" class="mt-1 text-end"
                              onsubmit="return confirm('Delete this category? FAQs will be kept but uncategorised.');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash me-1"></i>Delete</button>
                        </form>
                    </div>
                    @empty
                    <div class="list-group-item text-muted small">No categories yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="app-card">
            <div class="p-4">
                <h6 class="text-uppercase small fw-bold mb-3" style="color:var(--agile-primary);letter-spacing:0.08em">FAQs</h6>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Question</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($articles as $article)
                            <tr>
                                <td class="fw-medium">{{ \Illuminate\Support\Str::limit($article->question, 70) }}</td>
                                <td class="small text-muted">{{ $article->category->name ?? '—' }}</td>
                                <td>
                                    @php $badge = ['published' => 'bg-success', 'draft' => 'bg-warning text-dark', 'archived' => 'bg-secondary'][$article->status] ?? 'bg-light text-dark'; @endphp
                                    <span class="badge {{ $badge }}">{{ $statuses[$article->status] ?? $article->status }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('support.faq.articles.edit', $article) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                    <form method="POST" action="{{ route('support.faq.articles.destroy', $article) }}" class="d-inline"
                                          onsubmit="return confirm('Delete this FAQ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-muted small text-center py-4">No FAQs yet. Add your first one.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
