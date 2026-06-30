<div class="card contact-detail-card mb-4" id="client-comments">
    <div class="card-body p-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <div class="client-details-block-icon"><i class="bi bi-chat-left-text"></i></div>
            <h6 class="text-uppercase small fw-bold text-muted mb-0">Comments</h6>
        </div>
        <form method="POST" action="{{ route('support.clients.comments.store') }}">
            @csrf
            <input type="hidden" name="policy" value="{{ $clientPolicy }}">
            <textarea name="body" class="form-control mb-2 @error('body') is-invalid @enderror" rows="3" placeholder="Add an internal note about this client…" required>{{ old('body') }}</textarea>
            @error('body')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-send me-1"></i>Post comment</button>
            </div>
        </form>
        @if(($clientComments ?? collect())->isNotEmpty())
        <ul class="list-unstyled mb-0 mt-4 border-top pt-3">
            @foreach($clientComments as $comment)
            <li class="py-3 {{ ! $loop->last ? 'border-bottom' : '' }}">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                    <span class="fw-semibold small">{{ $comment->author_display }}</span>
                    <span class="text-muted small text-nowrap">{{ $comment->created_at?->format('d M Y, H:i') ?? '' }}</span>
                </div>
                <p class="mb-0 small text-body-secondary">{!! nl2br(e($comment->body)) !!}</p>
            </li>
            @endforeach
        </ul>
        @else
        <div class="summary-empty-box py-4 text-center text-muted mt-3 border-top">
            <i class="bi bi-chat-dots opacity-50 d-block mb-2"></i>
            No comments yet. Post the first note for this client.
        </div>
        @endif
    </div>
</div>
