@php
    $params = $params ?? [];
    $csvParams = $csvParams ?? null;
    $csvWithoutFormat = $csvWithoutFormat ?? false;
    $showCsv = $showCsv ?? true;
    $showExcel = $showExcel ?? true;
    $showPdf = $showPdf ?? true;
    $excelLabel = $excelLabel ?? 'Export Excel';
    $pdfLabel = $pdfLabel ?? 'Export PDF';
    $csvLabel = $csvLabel ?? 'Export CSV';
    $size = $size ?? 'sm';
    $baseParams = $params;
    $csvQuery = $csvParams ?? $params;
    $csvLinkParams = $csvQuery;
    if (! $csvWithoutFormat) {
        $csvLinkParams['format'] = 'csv';
    }
@endphp

<div class="d-flex flex-wrap gap-2 align-items-center no-print">
    @if($showExcel)
    <a href="{{ route($route, array_merge($baseParams, ['format' => 'xlsx'])) }}" class="btn btn-primary btn-{{ $size }}">
        <i class="bi bi-file-earmark-spreadsheet me-1"></i>{{ $excelLabel }}
    </a>
    @endif
    @if($showPdf)
    <a href="{{ route($route, array_merge($baseParams, ['format' => 'pdf'])) }}" class="btn btn-outline-danger btn-{{ $size }}">
        <i class="bi bi-file-earmark-pdf me-1"></i>{{ $pdfLabel }}
    </a>
    @endif
    @if($showCsv)
    <a href="{{ route($route, array_merge($csvLinkParams)) }}" class="btn btn-outline-secondary btn-{{ $size }}">
        <i class="bi bi-download me-1"></i>{{ $csvLabel }}
    </a>
    @endif
</div>
