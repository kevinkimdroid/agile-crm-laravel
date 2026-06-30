<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;

class ReportPdfExportService
{
    public const MAX_ROWS = 500;

    /**
     * @param  array<int, string>  $headings
     * @param  array<int, array<int, mixed>>  $rows
     * @param  array<string, mixed>  $meta
     */
    public function download(
        string $title,
        array $headings,
        array $rows,
        string $filename,
        array $meta = []
    ): Response {
        $totalRows = count($rows);
        $truncated = false;
        if ($totalRows > self::MAX_ROWS) {
            $rows = array_slice($rows, 0, self::MAX_ROWS);
            $truncated = true;
        }

        $html = View::make('pdf.report-table', [
            'title' => $title,
            'headings' => $headings,
            'rows' => $rows,
            'meta' => $meta,
            'generatedAt' => now()->format('Y-m-d H:i'),
            'truncated' => $truncated,
            'totalRows' => $totalRows,
        ])->render();

        $paper = ($meta['orientation'] ?? '') === 'landscape' ? 'a4' : 'a4';
        $orientation = ($meta['orientation'] ?? '') === 'landscape' ? 'landscape' : 'portrait';

        $pdf = Pdf::loadHTML($html)
            ->setPaper($paper, $orientation)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true);

        $safeName = $this->safeFilename($filename);

        return $pdf->download($safeName . '.pdf');
    }

    /**
     * @param  array<int, array{title: string, headings: array<int, string>, rows: array<int, array<int, mixed>>, meta?: array<string, mixed>}>  $sections
     */
    public function downloadMultiSection(string $title, array $sections, string $filename): Response
    {
        $prepared = [];
        foreach ($sections as $section) {
            $rows = $section['rows'] ?? [];
            $totalRows = count($rows);
            $truncated = false;
            if ($totalRows > self::MAX_ROWS) {
                $rows = array_slice($rows, 0, self::MAX_ROWS);
                $truncated = true;
            }
            $prepared[] = [
                'title' => (string) ($section['title'] ?? 'Section'),
                'headings' => $section['headings'] ?? [],
                'rows' => $rows,
                'meta' => $section['meta'] ?? [],
                'truncated' => $truncated,
                'totalRows' => $totalRows,
            ];
        }

        $html = View::make('pdf.report-multi-section', [
            'title' => $title,
            'sections' => $prepared,
            'generatedAt' => now()->format('Y-m-d H:i'),
        ])->render();

        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4', 'landscape')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true);

        return $pdf->download($this->safeFilename($filename) . '.pdf');
    }

    private function safeFilename(string $filename): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9._-]+/', '-', trim($filename));

        return $safe !== '' ? $safe : 'report-' . date('Y-m-d');
    }
}
