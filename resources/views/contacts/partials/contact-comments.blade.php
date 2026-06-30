<div class="card contact-detail-card mb-4" id="contact-comments">
    <div class="card-body p-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <h6 class="text-uppercase small fw-bold text-muted mb-0">Comments</h6>
        </div>
        <form method="POST" action="{{ route('contacts.comments.store', $contact->contactid) }}" enctype="multipart/form-data">
            @csrf
            <textarea name="body" class="form-control mb-2 @error('body') is-invalid @enderror" rows="3" placeholder="Post your comment here" required>{{ old('body') }}</textarea>
            @error('body')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            @error('attachment')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <input type="file" name="attachment" id="contactCommentAttachment" class="d-none" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.jpg,.jpeg,.png,.gif,.webp">
                    <label for="contactCommentAttachment" class="btn btn-sm btn-outline-primary mb-0">
                        <i class="bi bi-paperclip me-1"></i>Attach Files
                    </label>
                    <span class="small text-muted" id="contactCommentFileName"></span>
                    <i class="bi bi-info-circle text-muted" title="Attach files to your comment"></i>
                </div>
                <button type="submit" class="btn btn-sm btn-success">Post</button>
            </div>
        </form>
        @if(($contactComments ?? collect())->isNotEmpty())
        <ul class="list-unstyled mb-0 mt-4 border-top pt-3">
            @foreach($contactComments as $c)
            <li class="py-3 {{ ! $loop->last ? 'border-bottom' : '' }}">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                    <span class="fw-semibold small">{{ $c->author_display }}</span>
                    <span class="text-muted small text-nowrap">{{ $c->created_at?->format('d M Y, H:i') ?? '' }}</span>
                </div>
                <p class="mb-1 small">{!! nl2br(e($c->body)) !!}</p>
                @if($c->attachment_url)
                <a href="{{ $c->attachment_url }}" class="small" target="_blank" rel="noopener">
                    <i class="bi bi-paperclip me-1"></i>{{ $c->attachment_name ?? 'Attachment' }}
                </a>
                @endif
            </li>
            @endforeach
        </ul>
        @elseif(($comments ?? collect())->isNotEmpty())
        <ul class="list-unstyled mb-0 mt-4 border-top pt-3">
            @foreach($comments as $c)
            <li class="py-3 {{ ! $loop->last ? 'border-bottom' : '' }}">
                <p class="mb-1 small">{{ $c->commentcontent ?? $c->comments ?? '' }}</p>
                <small class="text-muted">{{ $c->createdtime ?? '' }}</small>
            </li>
            @endforeach
        </ul>
        @else
        <div class="summary-empty-box py-4 text-center text-muted mt-3 border-top">
            <i class="bi bi-chat-dots opacity-50 d-block mb-2"></i>
            No comments yet.
        </div>
        @endif
    </div>
</div>
