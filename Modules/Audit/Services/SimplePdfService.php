<?php

namespace Modules\Audit\Services;

class SimplePdfService
{
    protected $pageWidth = 841.89;
    protected $pageHeight = 595.28;
    protected $margin = 24.0;
    protected $fontSize = 7.2;
    protected $lineHeight = 9.0;
    protected $padding = 3.0;

    public function download($rows, array $columns, string $filename)
    {
        $pdf = $this->render($rows, $columns);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . str_replace('"', '', $filename) . '"',
            'Content-Length' => strlen($pdf),
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }

    public function render($rows, array $columns): string
    {
        $columnWidths = $this->columnWidths(array_keys($columns));
        $pages = [];
        $page = $this->newPage($columns, $columnWidths);

        foreach ($rows as $row) {
            $cells = [];
            $maxLines = 1;
            foreach (array_keys($columns) as $key) {
                $value = (string) data_get($row, $key, '');
                $lines = $this->wrap($value, $columnWidths[$key] - ($this->padding * 2));
                $cells[$key] = $lines;
                $maxLines = max($maxLines, count($lines));
            }

            $rowHeight = max(15.0, ($maxLines * $this->lineHeight) + ($this->padding * 2));
            if (($page['y'] - $rowHeight) < $this->margin) {
                $pages[] = $page['content'];
                $page = $this->newPage($columns, $columnWidths);
            }

            $page['content'] .= $this->drawRow($page['y'], $rowHeight, $cells, $columnWidths, false);
            $page['y'] -= $rowHeight;
        }

        if (empty($rows) || (is_countable($rows) && count($rows) === 0)) {
            $page['content'] .= $this->drawEmptyRow($page['y'], 24.0, array_sum($columnWidths));
            $page['y'] -= 24.0;
        }

        $pages[] = $page['content'];

        return $this->assemblePdf($pages);
    }

    protected function newPage(array $columns, array $columnWidths): array
    {
        $content = '';
        $content .= "0.12 0.12 0.12 rg\n";
        $content .= $this->text($this->margin, $this->pageHeight - 28, 'Audit Findings Report', 15, true);
        $content .= $this->text($this->margin, $this->pageHeight - 43, 'Generated ' . date('d M Y H:i'), 8.5, false, '0.35 0.35 0.35');

        $headerTop = $this->pageHeight - 58;
        $headerHeight = 24.0;
        $headerCells = [];
        foreach ($columns as $key => $label) {
            $headerCells[$key] = $this->wrap($label, $columnWidths[$key] - ($this->padding * 2));
        }
        $content .= $this->drawRow($headerTop, $headerHeight, $headerCells, $columnWidths, true);

        return [
            'content' => $content,
            'y' => $headerTop - $headerHeight,
        ];
    }

    protected function columnWidths(array $keys): array
    {
        $preferred = [
            'finding_no' => 94,
            'module' => 58,
            'rule_code' => 70,
            'severity' => 52,
            'status' => 64,
            'title' => 150,
            'business_name' => 128,
            'location_name' => 86,
            'last_seen_display' => 86,
        ];

        $available = $this->pageWidth - ($this->margin * 2);
        $widths = [];
        $sum = 0.0;
        foreach ($keys as $key) {
            $widths[$key] = (float) ($preferred[$key] ?? 80);
            $sum += $widths[$key];
        }

        if ($sum <= 0) {
            return $widths;
        }

        $scale = $available / $sum;
        foreach ($widths as $key => $width) {
            $widths[$key] = $width * $scale;
        }

        return $widths;
    }

    protected function drawRow(float $top, float $height, array $cells, array $widths, bool $header): string
    {
        $out = '';
        $x = $this->margin;
        $bottom = $top - $height;

        if ($header) {
            $out .= "0.94 0.95 0.96 rg\n";
            $out .= sprintf("%.2F %.2F %.2F %.2F re f\n", $this->margin, $bottom, array_sum($widths), $height);
        }

        $out .= "0.76 0.78 0.80 RG\n0.45 w\n";
        foreach ($widths as $key => $width) {
            $out .= sprintf("%.2F %.2F %.2F %.2F re S\n", $x, $bottom, $width, $height);
            $lines = $cells[$key] ?? [''];
            $fontSize = $header ? 7.3 : $this->fontSize;
            $lineHeight = $header ? 9.2 : $this->lineHeight;
            $textY = $top - $this->padding - $fontSize;
            foreach ($lines as $line) {
                if ($textY < ($bottom + 1.5)) {
                    break;
                }
                $out .= $this->text($x + $this->padding, $textY, $line, $fontSize, $header);
                $textY -= $lineHeight;
            }
            $x += $width;
        }

        return $out;
    }

    protected function drawEmptyRow(float $top, float $height, float $width): string
    {
        $bottom = $top - $height;
        $out = "0.76 0.78 0.80 RG\n0.45 w\n";
        $out .= sprintf("%.2F %.2F %.2F %.2F re S\n", $this->margin, $bottom, $width, $height);
        $out .= $this->text($this->margin + 6, $top - 15, 'No audit findings match the selected filters.', 8.2, false, '0.35 0.35 0.35');
        return $out;
    }

    protected function wrap(string $value, float $width): array
    {
        $value = trim(preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'))));
        if ($value === '') {
            return [''];
        }

        $maxChars = max(4, (int) floor($width / ($this->fontSize * 0.52)));
        $words = preg_split('/\s+/u', $value) ?: [$value];
        $lines = [];
        $line = '';

        foreach ($words as $word) {
            $word = (string) $word;
            while (strlen($word) > $maxChars) {
                if ($line !== '') {
                    $lines[] = $line;
                    $line = '';
                }
                $lines[] = substr($word, 0, $maxChars);
                $word = substr($word, $maxChars);
            }

            $candidate = $line === '' ? $word : ($line . ' ' . $word);
            if (strlen($candidate) <= $maxChars) {
                $line = $candidate;
            } else {
                if ($line !== '') {
                    $lines[] = $line;
                }
                $line = $word;
            }
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines ?: [''];
    }

    protected function text(float $x, float $y, string $value, float $size, bool $bold = false, string $color = '0.12 0.12 0.12'): string
    {
        $font = $bold ? 'F2' : 'F1';
        $safe = $this->pdfText($value);
        return $color . " rg\nBT /{$font} " . sprintf('%.2F', $size) . ' Tf ' . sprintf('%.2F %.2F', $x, $y) . ' Td (' . $safe . ") Tj ET\n";
    }

    protected function pdfText(string $text): string
    {
        $text = str_replace(["\r", "\n", "\t"], ' ', $text);
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
            if ($converted !== false) {
                $text = $converted;
            }
        }
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $text);
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    protected function assemblePdf(array $pageStreams): string
    {
        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

        $kids = [];
        $nextObject = 5;
        foreach ($pageStreams as $stream) {
            $pageObject = $nextObject++;
            $contentObject = $nextObject++;
            $kids[] = $pageObject . ' 0 R';

            $objects[$contentObject] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "endstream";
            $objects[$pageObject] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . sprintf('%.2F %.2F', $this->pageWidth, $this->pageHeight) . '] '
                . '/Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ' . $contentObject . ' 0 R >>';
        }

        $objects[2] = '<< /Type /Pages /Count ' . count($kids) . ' /Kids [' . implode(' ', $kids) . '] >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0 => 0];
        foreach ($objects as $number => $body) {
            $offsets[$number] = strlen($pdf);
            $pdf .= $number . " 0 obj\n" . $body . "\nendobj\n";
        }

        $xref = strlen($pdf);
        $max = max(array_keys($objects));
        $pdf .= "xref\n0 " . ($max + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= $max; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
        }
        $pdf .= "trailer\n<< /Size " . ($max + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";

        return $pdf;
    }
}
