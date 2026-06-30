<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1e293b; margin: 24px; }
        .header { border-bottom: 2px solid #0E4385; padding-bottom: 10px; margin-bottom: 14px; }
        .brand { font-size: 8px; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b; margin-bottom: 4px; }
        .title { font-size: 16px; font-weight: bold; color: #0E4385; margin: 0; }
        .meta { font-size: 8px; color: #64748b; margin-top: 6px; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th { background: #0E4385; color: #fff; padding: 5px 4px; text-align: left; font-size: 8px; word-wrap: break-word; }
        td { border-bottom: 1px solid #e2e8f0; padding: 4px; vertical-align: top; word-wrap: break-word; overflow-wrap: break-word; }
        tr:nth-child(even) td { background: #f8fafc; }
        .footer { margin-top: 14px; font-size: 7px; color: #94a3b8; }
        .notice { margin-top: 8px; padding: 6px 8px; background: #fff7ed; border: 1px solid #fed7aa; color: #9a3412; font-size: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">Kenya Orient Insurance · CRM Reports</div>
        <h1 class="title">{{ $title }}</h1>
        @if(!empty($meta))
        <div class="meta">
            @foreach($meta as $label => $value)
                @if(is_string($label) && $value !== null && $value !== '' && $label !== 'orientation')
                    <strong>{{ $label }}:</strong> {{ $value }}@if(!$loop->last) · @endif
                @endif
            @endforeach
        </div>
        @endif
    </div>

    @if(!empty($headings))
    <table>
        <thead>
            <tr>
                @foreach($headings as $heading)
                <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
            <tr>
                @foreach($row as $cell)
                <td>{{ $cell }}</td>
                @endforeach
            </tr>
            @empty
            <tr>
                <td colspan="{{ count($headings) }}">No data available for this report.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @else
    <p>No data available for this report.</p>
    @endif

    @if($truncated ?? false)
    <div class="notice">Showing first {{ \App\Services\ReportPdfExportService::MAX_ROWS }} of {{ number_format($totalRows) }} rows. Export to Excel or CSV for the full dataset.</div>
    @endif

    <div class="footer">Generated {{ $generatedAt }} · {{ count($rows) }} row(s) shown</div>
</body>
</html>
