@php
    $commentReturnTab = $commentReturnTab ?? 'updates';
    $clientDocumentsUrl = ! empty($clientShowBase)
        ? route('support.clients.show', array_merge($clientShowBase, ['tab' => 'documents']))
        : route('support.clients.show', ['policy' => $clientPolicy, 'tab' => 'documents']);
@endphp
<div class="card contact-detail-card mb-4" id="client-comments">
    <div class="card-body p-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <h6 class="text-uppercase small fw-bold text-muted mb-0">Comments</h6>
        </div>
        <form method="POST" action="{{ route('support.clients.comments.store') }}">
            @csrf
            <input type="hidden" name="policy" value="{{ $clientPolicy }}">
            <input type="hidden" name="return_tab" value="{{ $commentReturnTab }}">
            <textarea name="body" class="form-control mb-2 @error('body') is-invalid @enderror" rows="3" placeholder="Post your comment here" required>{{ old('body') }}</textarea>
            @error('body')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ $clientDocumentsUrl }}#client-documents" class="btn btn-sm btn-outline-primary mb-0">
                        <i class="bi bi-paperclip me-1"></i>Attach Files
                    </a>
                    <i class="bi bi-info-circle text-muted" title="Upload files on the Documents tab"></i>
                </div>
                <button type="submit" class="btn btn-sm btn-success">Post</button>
            </div>
        </form>
        @if(($clientComments ?? collect())->isNotEmpty())
        <h6 class="text-uppercase small fw-bold text-muted mt-4 mb-0 pt-3 border-top">Recent Comments</h6>
        <ul class="list-unstyled mb-0 mt-2">
            @foreach($clientComments as $comment)
            <li class="py-3 {{ ! $loop->last ? 'border-bottom' : '' }}">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                    <span class="fw-semibold small">{{ $comment->author_display }}</span>
                    <span class="text-muted small text-nowrap">{{ $comment->created_at?->format('d M Y, H:i') ?? '' }}</span>
                </div>
                <p class="mb-0 small">{!! nl2br(e($comment->body)) !!}</p>
            </li>
            @endforeach
        </ul>
        @else
        <div class="summary-empty-box py-4 text-center text-muted mt-3 border-top">
            <i class="bi bi-chat-dots opacity-50 d-block mb-2"></i>
            No comments
        </div>
        @endif
    </div>
</div>
