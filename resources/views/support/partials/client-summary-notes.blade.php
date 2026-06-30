<div class="card contact-detail-card mb-4" id="client-documents">
    <div class="card-body p-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <div class="client-details-block-icon"><i class="bi bi-file-earmark-text"></i></div>
            <h6 class="text-uppercase small fw-bold text-muted mb-0">Documents</h6>
        </div>
        <form method="POST" action="{{ route('support.clients.documents.store') }}" enctype="multipart/form-data" class="client-document-upload-form">
            @csrf
            <input type="hidden" name="policy" value="{{ $clientPolicy }}">
            <div class="row g-2 mb-2">
                <div class="col-md-5">
                    <input type="text" name="title" class="form-control form-control-sm @error('title') is-invalid @enderror" placeholder="Document title (optional)" value="{{ old('title') }}" maxlength="255">
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-7">
                    <input type="file" name="document" id="clientDocumentFile" class="form-control form-control-sm @error('document') is-invalid @enderror" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.jpg,.jpeg,.png,.gif,.webp" required>
                    @error('document')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-upload me-1"></i>Upload document</button>
            </div>
        </form>
        @if(($clientDocuments ?? collect())->isNotEmpty())
        <div class="client-documents-grid mt-4 border-top pt-3">
            @foreach($clientDocuments as $doc)
            <div class="client-document-item">
                <div class="client-document-item-head">
                    <div class="client-document-item-icon">
                        @if(str_starts_with(strtolower((string) ($doc->mime_type ?? '')), 'image/'))
                        <i class="bi bi-file-earmark-image text-primary"></i>
                        @elseif(strtolower((string) ($doc->mime_type ?? '')) === 'application/pdf')
                        <i class="bi bi-file-earmark-pdf text-danger"></i>
                        @else
                        <i class="bi bi-file-earmark text-secondary"></i>
                        @endif
                    </div>
                    <div class="client-document-item-meta">
                        <div class="fw-semibold small">{{ $doc->display_title }}</div>
                        <div class="text-muted small">
                            {{ $doc->file_size_human }}
                            · {{ $doc->uploaded_by_display }}
                            · {{ $doc->created_at?->format('d M Y, H:i') ?? '' }}
                        </div>
                    </div>
                </div>
                @if($doc->is_previewable && $doc->public_url)
                <div class="client-document-preview mt-2">
                    @if(str_starts_with(strtolower((string) ($doc->mime_type ?? '')), 'image/'))
                    <a href="{{ $doc->public_url }}" target="_blank" rel="noopener">
                        <img src="{{ $doc->public_url }}" alt="{{ $doc->display_title }}" class="client-document-preview-img">
                    </a>
                    @else
                    <iframe src="{{ $doc->public_url }}" class="client-document-preview-pdf" title="{{ $doc->display_title }}"></iframe>
                    @endif
                </div>
                @endif
                <div class="client-document-item-actions mt-2">
                    @if($doc->public_url)
                    <a href="{{ $doc->public_url }}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">
                        <i class="bi bi-eye me-1"></i>View
                    </a>
                    @endif
                    <a href="{{ route('support.clients.documents.download', $doc->id) }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-download me-1"></i>Download
                    </a>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="summary-empty-box py-4 text-center text-muted mt-3 border-top">
            <i class="bi bi-folder2-open opacity-50 d-block mb-2"></i>
            No documents uploaded yet.
        </div>
        @endif
    </div>
</div>
