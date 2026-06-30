<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #1e293b; margin: 20px; }
        .cover { border-bottom: 2px solid #0E4385; padding-bottom: 12px; margin-bottom: 18px; }
        .brand { font-size: 8px; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b; }
        .title { font-size: 18px; font-weight: bold; color: #0E4385; margin: 6px 0 0; }
        .section { page-break-before: always; }
        .section:first-of-type { page-break-before: auto; }
        .section-title { font-size: 12px; font-weight: bold; color: #0E4385; margin: 0 0 8px; padding-bottom: 4px; border-bottom: 1px solid #e2e8f0; }
        .meta { font-size: 7px; color: #64748b; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; margin-bottom: 8px; }
        th { background: #0E4385; color: #fff; padding: 4px 3px; text-align: left; font-size: 7px; }
        td { border-bottom: 1px solid #e2e8f0; padding: 3px; vertical-align: top; word-wrap: break-word; }
        tr:nth-child(even) td { background: #f8fafc; }
        .footer { margin-top: 10px; font-size: 7px; color: #94a3b8; }
        .notice { margin: 6px 0; padding: 5px 6px; background: #fff7ed; border: 1px solid #fed7aa; color: #9a3412; font-size: 7px; }
    </style>
</head>
<body>
    <div class="cover">
        <div class="brand">Kenya Orient Insurance · CRM Reports</div>
        <h1 class="title">{{ $title }}</h1>
        <div class="footer">Generated {{ $generatedAt }}</div>
    </div>

    @foreach($sections as $section)
    <div class="section">
        <h2 class="section-title">{{ $section['title'] }}</h2>
        @if(!empty($section['meta']))
        <div class="meta">
            @foreach($section['meta'] as $label => $value)
                @if(is_string($label) && $value !== null && $value !== '' && $label !== 'orientation')
                    <strong>{{ $label }}:</strong> {{ $value }}@if(!$loop->last) · @endif
                @endif
            @endforeach
        </div>
        @endif

        @if(!empty($section['headings']))
        <table>
            <thead>
                <tr>
                    @foreach($section['headings'] as $heading)
                    <th>{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($section['rows'] as $row)
                <tr>
                    @foreach($row as $cell)
                    <td>{{ $cell }}</td>
                    @endforeach
                </tr>
                @empty
                <tr>
                    <td colspan="{{ count($section['headings']) }}">No data</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @endif

        @if($section['truncated'] ?? false)
        <div class="notice">Showing first {{ \App\Services\ReportPdfExportService::MAX_ROWS }} of {{ number_format($section['totalRows']) }} rows in this section.</div>
        @endif
    </div>
    @endforeach
</body>
</html>
