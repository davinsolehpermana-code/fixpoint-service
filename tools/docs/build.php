<?php
/**
 * Generator dokumen FixPoint Service: Markdown (subset) -> DOCX dan PDF.
 *
 * Dipakai untuk menghasilkan docs/03-manual/TECHNICAL_DOCS.docx/.pdf dan
 * docs/05-defense/INDIVIDUAL_DEFENSE_CHECKLIST.docx/.pdf dari satu sumber
 * (markdown) agar isi kedua format selalu sama.
 *
 * Jalankan: php tools/docs/build.php tools/docs/technical-docs.md docs/03-manual/TECHNICAL_DOCS
 *
 * Markdown yang didukung:
 *   # / ## / ###      heading
 *   paragraf          teks biasa
 *   - item            daftar butir
 *   1. item           daftar bernomor
 *   | a | b |         tabel (baris pertama header, baris kedua ---)
 *   ```...```         blok kode
 *   > kutipan         catatan
 *   <!-- pagebreak -->  pindah halaman
 * Front matter di awal file: --- / key: value / ---
 *
 * DOCX dibuat langsung sebagai OOXML (ZipArchive) tanpa dependensi baru.
 * PDF dibuat dengan dompdf yang sudah tersedia di vendor/ proyek.
 */

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

/* -------------------------------------------------------------------------
 | Parsing markdown
 | ---------------------------------------------------------------------- */

function parse_front_matter(string $raw): array
{
    $meta = [];
    if (preg_match('/^---\s*\n(.*?)\n---\s*\n/s', $raw, $m)) {
        foreach (explode("\n", $m[1]) as $line) {
            if (!str_contains($line, ':')) {
                continue;
            }
            [$key, $value] = explode(':', $line, 2);
            $meta[trim($key)] = trim($value);
        }
        $raw = substr($raw, strlen($m[0]));
    }

    return [$meta, $raw];
}

/**
 * @return array<int, array<string, mixed>>
 */
function parse_blocks(string $markdown): array
{
    $lines = preg_split('/\r\n|\r|\n/', $markdown);
    $blocks = [];
    $count = count($lines);

    for ($i = 0; $i < $count; $i++) {
        $line = $lines[$i];

        if (trim($line) === '') {
            continue;
        }

        if (str_starts_with(trim($line), '<!-- pagebreak')) {
            $blocks[] = ['type' => 'pagebreak'];
            continue;
        }

        if (preg_match('/^(#{1,3})\s+(.*)$/', $line, $m)) {
            $blocks[] = [
                'type' => 'heading',
                'level' => strlen($m[1]),
                'text' => trim($m[2]),
            ];
            continue;
        }

        if (str_starts_with(trim($line), '```')) {
            $code = [];
            $i++;
            while ($i < $count && !str_starts_with(trim($lines[$i]), '```')) {
                $code[] = rtrim($lines[$i]);
                $i++;
            }
            $blocks[] = ['type' => 'code', 'text' => implode("\n", $code)];
            continue;
        }

        if (preg_match('/^\s*-\s+(.*)$/', $line, $m)) {
            $items = [];
            while ($i < $count && preg_match('/^\s*-\s+(.*)$/', $lines[$i], $mm)) {
                $items[] = trim($mm[1]);
                $i++;
            }
            $i--;
            $blocks[] = ['type' => 'bullets', 'items' => $items];
            continue;
        }

        if (preg_match('/^\s*(\d+)\.\s+(.*)$/', $line, $m)) {
            $items = [];
            $start = (int) $m[1];
            while ($i < $count && preg_match('/^\s*(\d+)\.\s+(.*)$/', $lines[$i], $mm)) {
                $items[] = trim($mm[2]);
                $i++;
            }
            $i--;
            $blocks[] = ['type' => 'numbers', 'items' => $items, 'start' => $start];
            continue;
        }

        if (str_starts_with(trim($line), '|')) {
            $rows = [];
            while ($i < $count && str_starts_with(trim($lines[$i]), '|')) {
                $rows[] = trim($lines[$i]);
                $i++;
            }
            $i--;
            $blocks[] = ['type' => 'table'] + parse_table($rows);
            continue;
        }

        if (str_starts_with(trim($line), '>')) {
            $quote = [];
            while ($i < $count && str_starts_with(trim($lines[$i]), '>')) {
                $quote[] = trim(ltrim(trim($lines[$i]), '>'));
                $i++;
            }
            $i--;
            $blocks[] = ['type' => 'note', 'text' => implode(' ', $quote)];
            continue;
        }

        // Paragraf: gabungkan baris berurutan sampai baris kosong / blok baru.
        $paragraph = [trim($line)];
        while ($i + 1 < $count) {
            $next = $lines[$i + 1];
            if (trim($next) === ''
                || preg_match('/^(#{1,3}\s|\s*-\s|\s*\d+\.\s|```|\||>)/', ltrim($next))
                || str_starts_with(trim($next), '<!-- pagebreak')) {
                break;
            }
            $paragraph[] = trim($next);
            $i++;
        }
        $blocks[] = ['type' => 'paragraph', 'text' => implode(' ', $paragraph)];
    }

    return $blocks;
}

/**
 * @param  array<int, string>  $rows
 * @return array{header: array<int, string>, rows: array<int, array<int, string>>}
 */
function parse_table(array $rows): array
{
    $cells = function (string $row): array {
        $trimmed = trim($row, "|\t ");
        $parts = preg_split('/(?<!\\\\)\|/', $trimmed) ?: [];

        return array_map(fn ($cell) => trim(str_replace('\\|', '|', $cell)), $parts);
    };

    $header = $cells(array_shift($rows) ?? '');
    $body = [];

    foreach ($rows as $row) {
        if (preg_match('/^\|?[\s:-]+\|/', $row) && !preg_match('/[a-zA-Z0-9]/', $row)) {
            continue;
        }
        $body[] = $cells($row);
    }

    return ['header' => $header, 'rows' => $body];
}

/** Teks inline sederhana: **tebal** dan `kode`. */
function inline_runs(string $text): array
{
    $runs = [];
    $pattern = '/(\*\*[^*]+\*\*|`[^`]+`)/';

    foreach (preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE) as $chunk) {
        if ($chunk === '' || $chunk === null) {
            continue;
        }

        if (str_starts_with($chunk, '**') && str_ends_with($chunk, '**')) {
            $runs[] = ['text' => substr($chunk, 2, -2), 'bold' => true];
        } elseif (str_starts_with($chunk, '`') && str_ends_with($chunk, '`')) {
            $runs[] = ['text' => substr($chunk, 1, -1), 'code' => true];
        } else {
            $runs[] = ['text' => $chunk];
        }
    }

    return $runs;
}

/* -------------------------------------------------------------------------
 | DOCX
 | ---------------------------------------------------------------------- */

function xml_escape(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function docx_runs(string $text, string $extra = '', bool $forceBold = false): string
{
    $xml = '';
    foreach (inline_runs($text) as $run) {
        $props = '';
        if ($forceBold || ($run['bold'] ?? false)) {
            $props .= '<w:b/>';
        }
        if ($run['code'] ?? false) {
            $props .= '<w:rFonts w:ascii="Consolas" w:hAnsi="Consolas"/><w:color w:val="1F3A5F"/>';
        }
        if ($extra !== '') {
            $props .= $extra;
        }
        $rPr = $props !== '' ? '<w:rPr>' . $props . '</w:rPr>' : '';
        $xml .= '<w:r>' . $rPr . '<w:t xml:space="preserve">' . xml_escape($run['text']) . '</w:t></w:r>';
    }

    return $xml !== '' ? $xml : '<w:r><w:t xml:space="preserve"></w:t></w:r>';
}

function docx_paragraph(string $inner, string $pPr = ''): string
{
    return '<w:p>' . ($pPr !== '' ? '<w:pPr>' . $pPr . '</w:pPr>' : '') . $inner . '</w:p>';
}

function docx_table(array $header, array $rows): string
{
    $border = '<w:tblBorders>'
        . '<w:top w:val="single" w:sz="4" w:color="B7C4D6"/>'
        . '<w:left w:val="single" w:sz="4" w:color="B7C4D6"/>'
        . '<w:bottom w:val="single" w:sz="4" w:color="B7C4D6"/>'
        . '<w:right w:val="single" w:sz="4" w:color="B7C4D6"/>'
        . '<w:insideH w:val="single" w:sz="4" w:color="B7C4D6"/>'
        . '<w:insideV w:val="single" w:sz="4" w:color="B7C4D6"/>'
        . '</w:tblBorders>';

    $xml = '<w:tbl><w:tblPr><w:tblW w:w="5000" w:type="pct"/><w:tblLayout w:type="autofit"/>'
        . $border . '<w:tblCellMar><w:left w:w="80" w:type="dxa"/><w:right w:w="80" w:type="dxa"/></w:tblCellMar>'
        . '</w:tblPr>';

    $cell = function (string $text, bool $isHeader): string {
        $shade = $isHeader ? '<w:shd w:val="clear" w:color="auto" w:fill="E4ECF7"/>' : '';
        $pPr = '<w:spacing w:before="20" w:after="20" w:line="240" w:lineRule="auto"/>';

        return '<w:tc><w:tcPr>' . $shade . '<w:vAlign w:val="center"/></w:tcPr>'
            . docx_paragraph(docx_runs($text, '<w:sz w:val="17"/>', $isHeader), $pPr)
            . '</w:tc>';
    };

    $xml .= '<w:tr><w:trPr><w:tblHeader/></w:trPr>';
    foreach ($header as $text) {
        $xml .= $cell($text, true);
    }
    $xml .= '</w:tr>';

    foreach ($rows as $row) {
        $xml .= '<w:tr>';
        foreach ($row as $index => $text) {
            $xml .= $cell($text, false);
        }
        // Samakan jumlah sel bila baris kurang dari header.
        for ($i = count($row); $i < count($header); $i++) {
            $xml .= $cell('', false);
        }
        $xml .= '</w:tr>';
    }

    return $xml . '</w:tbl>' . docx_paragraph('', '<w:spacing w:after="60"/>');
}

function build_docx(array $meta, array $blocks, string $path): void
{
    $body = '';

    // ---- Cover ----
    $body .= docx_paragraph(
        docx_runs(strtoupper((string) ($meta['doc_type'] ?? 'DOKUMEN TEKNIS')), '<w:sz w:val="22"/><w:color w:val="2563EB"/>', true),
        '<w:jc w:val="center"/><w:spacing w:before="2400" w:after="240"/>'
    );
    $body .= docx_paragraph(
        docx_runs((string) ($meta['title'] ?? 'Dokumen'), '<w:sz w:val="52"/>', true),
        '<w:jc w:val="center"/><w:spacing w:after="160"/>'
    );
    $body .= docx_paragraph(
        docx_runs((string) ($meta['subtitle'] ?? ''), '<w:sz w:val="26"/><w:color w:val="475569"/>'),
        '<w:jc w:val="center"/><w:spacing w:after="720"/>'
    );

    foreach (['project', 'identity', 'version', 'date'] as $key) {
        if (!empty($meta[$key])) {
            $label = [
                'project' => 'Proyek',
                'identity' => 'Aplikasi',
                'version' => 'Versi',
                'date' => 'Tanggal',
            ][$key];
            $body .= docx_paragraph(
                docx_runs($label . ': ' . $meta[$key], '<w:sz w:val="22"/>'),
                '<w:jc w:val="center"/><w:spacing w:after="60"/>'
            );
        }
    }

    $body .= docx_paragraph('<w:r><w:br w:type="page"/></w:r>');

    // ---- Daftar isi (field TOC, diperbarui otomatis saat dokumen dibuka) ----
    $body .= docx_paragraph(
        docx_runs('DAFTAR ISI', '<w:sz w:val="28"/>', true),
        '<w:jc w:val="center"/><w:spacing w:after="240"/>'
    );
    $body .= '<w:p><w:r><w:fldChar w:fldCharType="begin" w:dirty="true"/></w:r>'
        . '<w:r><w:instrText xml:space="preserve"> TOC \\o "1-2" \\h \\z \\u </w:instrText></w:r>'
        . '<w:r><w:fldChar w:fldCharType="separate"/></w:r>'
        . '<w:r><w:t>Daftar isi akan ditampilkan otomatis saat dokumen dibuka di Word/LibreOffice.</w:t></w:r>'
        . '<w:r><w:fldChar w:fldCharType="end"/></w:r></w:p>';
    $body .= docx_paragraph('<w:r><w:br w:type="page"/></w:r>');

    // ---- Isi ----
    foreach ($blocks as $block) {
        switch ($block['type']) {
            case 'heading':
                $level = min(3, max(1, (int) $block['level']));
                $body .= docx_paragraph(
                    docx_runs($block['text']),
                    '<w:pStyle w:val="Heading' . $level . '"/>'
                );
                break;

            case 'paragraph':
                $body .= docx_paragraph(docx_runs($block['text']), '<w:spacing w:after="120" w:line="288" w:lineRule="auto"/><w:jc w:val="both"/>');
                break;

            case 'bullets':
                foreach ($block['items'] as $item) {
                    $body .= docx_paragraph(
                        docx_runs('• '.$item),
                        '<w:ind w:left="360" w:hanging="200"/><w:spacing w:after="60" w:line="276" w:lineRule="auto"/>'
                    );
                }
                break;

            case 'numbers':
                $n = $block['start'];
                foreach ($block['items'] as $item) {
                    $body .= docx_paragraph(
                        docx_runs($n . '. ' . $item),
                        '<w:ind w:left="360" w:hanging="200"/><w:spacing w:after="60" w:line="276" w:lineRule="auto"/>'
                    );
                    $n++;
                }
                break;

            case 'table':
                $body .= docx_table($block['header'], $block['rows']);
                break;

            case 'code':
                foreach (explode("\n", $block['text']) as $line) {
                    $body .= docx_paragraph(
                        '<w:r><w:rPr><w:rFonts w:ascii="Consolas" w:hAnsi="Consolas"/><w:sz w:val="16"/><w:color w:val="1F3A5F"/></w:rPr>'
                        . '<w:t xml:space="preserve">' . xml_escape($line === '' ? ' ' : $line) . '</w:t></w:r>',
                        '<w:shd w:val="clear" w:color="auto" w:fill="F4F6F9"/><w:spacing w:after="0" w:line="240" w:lineRule="auto"/><w:ind w:left="120"/>'
                    );
                }
                $body .= docx_paragraph('', '<w:spacing w:after="120"/>');
                break;

            case 'note':
                $body .= docx_paragraph(
                    docx_runs($block['text'], '<w:sz w:val="20"/><w:color w:val="1E40AF"/>'),
                    '<w:shd w:val="clear" w:color="auto" w:fill="EFF6FF"/><w:pBdr><w:left w:val="single" w:sz="18" w:space="6" w:color="2563EB"/></w:pBdr>'
                    . '<w:ind w:left="120"/><w:spacing w:before="80" w:after="160" w:line="276" w:lineRule="auto"/>'
                );
                break;

            case 'pagebreak':
                $body .= docx_paragraph('<w:r><w:br w:type="page"/></w:r>');
                break;
        }
    }

    $sectPr = '<w:sectPr>'
        . '<w:footerReference w:type="default" r:id="rId3"/>'
        . '<w:pgSz w:w="11906" w:h="16838"/>'
        . '<w:pgMar w:top="1418" w:right="1191" w:bottom="1418" w:left="1191" w:header="709" w:footer="709" w:gutter="0"/>'
        . '<w:cols w:space="708"/>'
        . '</w:sectPr>';

    $document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" '
        . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<w:body>' . $body . $sectPr . '</w:body></w:document>';

    $styles = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:docDefaults>
    <w:rPrDefault><w:rPr>
      <w:rFonts w:ascii="Calibri" w:hAnsi="Calibri" w:eastAsia="Calibri" w:cs="Calibri"/>
      <w:sz w:val="21"/><w:szCs w:val="21"/><w:lang w:val="id-ID"/>
    </w:rPr></w:rPrDefault>
    <w:pPrDefault><w:pPr><w:spacing w:after="120" w:line="276" w:lineRule="auto"/></w:pPr></w:pPrDefault>
  </w:docDefaults>
  <w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/><w:qFormat/></w:style>
  <w:style w:type="paragraph" w:styleId="Heading1">
    <w:name w:val="heading 1"/><w:basedOn w:val="Normal"/><w:next w:val="Normal"/><w:qFormat/>
    <w:pPr><w:keepNext/><w:spacing w:before="240" w:after="160"/><w:outlineLvl w:val="0"/>
      <w:pBdr><w:bottom w:val="single" w:sz="8" w:space="4" w:color="2563EB"/></w:pBdr></w:pPr>
    <w:rPr><w:b/><w:sz w:val="32"/><w:color w:val="0F172A"/></w:rPr>
  </w:style>
  <w:style w:type="paragraph" w:styleId="Heading2">
    <w:name w:val="heading 2"/><w:basedOn w:val="Normal"/><w:next w:val="Normal"/><w:qFormat/>
    <w:pPr><w:keepNext/><w:spacing w:before="200" w:after="100"/><w:outlineLvl w:val="1"/></w:pPr>
    <w:rPr><w:b/><w:sz w:val="26"/><w:color w:val="1D4ED8"/></w:rPr>
  </w:style>
  <w:style w:type="paragraph" w:styleId="Heading3">
    <w:name w:val="heading 3"/><w:basedOn w:val="Normal"/><w:next w:val="Normal"/><w:qFormat/>
    <w:pPr><w:keepNext/><w:spacing w:before="160" w:after="80"/><w:outlineLvl w:val="2"/></w:pPr>
    <w:rPr><w:b/><w:sz w:val="23"/><w:color w:val="334155"/></w:rPr>
  </w:style>
</w:styles>
XML;

    $footer = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<w:ftr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
        . '<w:p><w:pPr><w:jc w:val="center"/></w:pPr>'
        . '<w:r><w:rPr><w:sz w:val="17"/><w:color w:val="64748B"/></w:rPr><w:t xml:space="preserve">'
        . xml_escape((string) ($meta['identity'] ?? 'FixPoint Service')) . ' - Halaman </w:t></w:r>'
        . '<w:r><w:fldChar w:fldCharType="begin"/></w:r>'
        . '<w:r><w:instrText xml:space="preserve"> PAGE </w:instrText></w:r>'
        . '<w:r><w:fldChar w:fldCharType="separate"/></w:r>'
        . '<w:r><w:t>1</w:t></w:r><w:r><w:fldChar w:fldCharType="end"/></w:r>'
        . '<w:r><w:rPr><w:sz w:val="17"/><w:color w:val="64748B"/></w:rPr><w:t xml:space="preserve"> dari </w:t></w:r>'
        . '<w:r><w:fldChar w:fldCharType="begin"/></w:r>'
        . '<w:r><w:instrText xml:space="preserve"> NUMPAGES </w:instrText></w:r>'
        . '<w:r><w:fldChar w:fldCharType="separate"/></w:r>'
        . '<w:r><w:t>1</w:t></w:r><w:r><w:fldChar w:fldCharType="end"/></w:r>'
        . '</w:p></w:ftr>';

    $settings = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<w:settings xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
        . '<w:updateFields w:val="true"/><w:defaultTabStop w:val="708"/>'
        . '<w:compat><w:compatSetting w:name="compatibilityMode" w:uri="http://schemas.microsoft.com/office/word" w:val="15"/></w:compat>'
        . '</w:settings>';

    $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml" ContentType="application/xml"/>'
        . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
        . '<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
        . '<Override PartName="/word/settings.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.settings+xml"/>'
        . '<Override PartName="/word/footer1.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"/>'
        . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
        . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
        . '</Types>';

    $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
        . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
        . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
        . '</Relationships>';

    $docRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
        . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/settings" Target="settings.xml"/>'
        . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/footer" Target="footer1.xml"/>'
        . '</Relationships>';

    $core = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" '
        . 'xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" '
        . 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
        . '<dc:title>' . xml_escape((string) ($meta['title'] ?? 'Dokumen')) . '</dc:title>'
        . '<dc:subject>' . xml_escape((string) ($meta['subtitle'] ?? '')) . '</dc:subject>'
        . '<dc:creator>FixPoint Service</dc:creator>'
        . '<cp:lastModifiedBy>FixPoint Service</cp:lastModifiedBy>'
        . '</cp:coreProperties>';

    $app = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties">'
        . '<Application>FixPoint Service Document Builder</Application></Properties>';

    if (file_exists($path)) {
        unlink($path);
    }

    $zip = new ZipArchive();
    if ($zip->open($path, ZipArchive::CREATE) !== true) {
        throw new RuntimeException('Tidak dapat membuat file DOCX: ' . $path);
    }

    $zip->addFromString('[Content_Types].xml', $contentTypes);
    $zip->addFromString('_rels/.rels', $rootRels);
    $zip->addFromString('word/document.xml', $document);
    $zip->addFromString('word/styles.xml', $styles);
    $zip->addFromString('word/settings.xml', $settings);
    $zip->addFromString('word/footer1.xml', $footer);
    $zip->addFromString('word/_rels/document.xml.rels', $docRels);
    $zip->addFromString('docProps/core.xml', $core);
    $zip->addFromString('docProps/app.xml', $app);
    $zip->close();
}

/* -------------------------------------------------------------------------
 | PDF (dompdf)
 | ---------------------------------------------------------------------- */

function pdf_html_shell(string $title, string $inner): string
{
    return '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><title>' . htmlspecialchars($title) . '</title>'
        . '<style>'
        . 'body{font-family:"DejaVu Sans",sans-serif;font-size:10pt;color:#0f172a;line-height:1.55;margin:0}'
        . 'h1{font-size:18pt;color:#0f172a;border-bottom:2px solid #2563eb;padding-bottom:5px;margin:0 0 12px}'
        . 'h2{font-size:13.5pt;color:#1d4ed8;margin:16px 0 7px;page-break-after:avoid}'
        . 'h3{font-size:11.5pt;color:#334155;margin:12px 0 5px;page-break-after:avoid}'
        . 'p{margin:0 0 8px;text-align:justify}'
        . 'ul,ol{margin:0 0 8px 16px;padding:0}li{margin-bottom:3px}'
        . 'table{width:100%;border-collapse:collapse;margin:6px 0 12px;font-size:8.4pt}'
        . 'th{background:#e4ecf7;border:1px solid #b7c4d6;padding:5px;text-align:left;font-weight:bold}'
        . 'td{border:1px solid #b7c4d6;padding:5px;vertical-align:top}'
        . 'code{font-family:"DejaVu Sans Mono",monospace;font-size:8.6pt;color:#1f3a5f}'
        . 'pre{background:#f4f6f9;border-left:3px solid #94a3b8;padding:6px 8px;font-family:"DejaVu Sans Mono",monospace;'
        . 'font-size:8.2pt;white-space:pre-wrap;margin:6px 0 10px;color:#1f3a5f}'
        . '.note{background:#eff6ff;border-left:4px solid #2563eb;padding:7px 9px;color:#1e40af;font-size:9pt;margin:6px 0 12px}'
        . '.pagebreak{page-break-after:always}'
        . '.cover{page-break-after:always;text-align:center}'
        . '.cover .doctype{font-size:9.5pt;letter-spacing:2px;color:#2563eb;font-weight:bold;margin-top:150px}'
        . '.cover .title{font-size:26pt;font-weight:bold;margin:14px 0 6px;line-height:1.25;border:0}'
        . '.cover .subtitle{font-size:11.5pt;color:#475569;margin-bottom:26px}'
        . '.cover .meta{font-size:10pt;color:#334155;margin:3px 0}'
        . '.toc-title{text-align:center;font-size:15pt;font-weight:bold;margin-bottom:14px}'
        . '.toc a{color:#0f172a;text-decoration:none}'
        . '.toc-row{display:block;font-size:10pt;margin:0 0 3px}'
        . '.toc-row .num{float:right;color:#475569}'
        . '.toc-row.lvl2{margin-left:16px;font-size:9.5pt;color:#334155}'
        . '.caption{font-size:8.6pt;color:#64748b;font-style:italic;margin-top:-6px;margin-bottom:12px}'
        . '</style></head><body>' . $inner . '</body></html>';
}

function pdf_blocks_html(array $blocks): string
{
    $html = '';

    foreach ($blocks as $block) {
        switch ($block['type']) {
            case 'heading':
                $tag = 'h' . min(3, max(1, (int) $block['level']));
                $id = 'sec' . substr(md5($block['text']), 0, 8);
                $html .= '<' . $tag . ' id="' . $id . '">' . htmlspecialchars($block['text']) . '</' . $tag . '>';
                break;

            case 'paragraph':
                $html .= '<p>' . inline_html($block['text']) . '</p>';
                break;

            case 'bullets':
                $html .= '<ul>';
                foreach ($block['items'] as $item) {
                    $html .= '<li>' . inline_html($item) . '</li>';
                }
                $html .= '</ul>';
                break;

            case 'numbers':
                $html .= '<ol start="' . (int) $block['start'] . '">';
                foreach ($block['items'] as $item) {
                    $html .= '<li>' . inline_html($item) . '</li>';
                }
                $html .= '</ol>';
                break;

            case 'table':
                $html .= '<table><thead><tr>';
                foreach ($block['header'] as $cell) {
                    $html .= '<th>' . inline_html($cell) . '</th>';
                }
                $html .= '</tr></thead><tbody>';
                foreach ($block['rows'] as $row) {
                    $html .= '<tr>';
                    foreach ($block['header'] as $index => $_) {
                        $html .= '<td>' . inline_html($row[$index] ?? '') . '</td>';
                    }
                    $html .= '</tr>';
                }
                $html .= '</tbody></table>';
                break;

            case 'code':
                $html .= '<pre>' . htmlspecialchars($block['text']) . '</pre>';
                break;

            case 'note':
                $html .= '<div class="note">' . inline_html($block['text']) . '</div>';
                break;

            case 'pagebreak':
                $html .= '<div class="pagebreak"></div>';
                break;
        }
    }

    return $html;
}

function inline_html(string $text): string
{
    $escaped = htmlspecialchars($text);

    $escaped = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $escaped);
    $escaped = preg_replace('/`([^`]+)`/', '<code>$1</code>', $escaped);

    return $escaped;
}

/**
 * Membagi blok menjadi bab berdasarkan heading level 1.
 *
 * @return array<int, array{title: string, blocks: array<int, array<string, mixed>>}>
 */
function split_chapters(array $blocks): array
{
    $chapters = [];
    $current = ['title' => '', 'blocks' => []];

    foreach ($blocks as $block) {
        if ($block['type'] === 'heading' && (int) $block['level'] === 1) {
            if ($current['title'] !== '' || $current['blocks'] !== []) {
                $chapters[] = $current;
            }
            $current = ['title' => $block['text'], 'blocks' => [$block]];
            continue;
        }
        $current['blocks'][] = $block;
    }

    if ($current['title'] !== '' || $current['blocks'] !== []) {
        $chapters[] = $current;
    }

    return $chapters;
}

function render_pdf(string $html, string $outputPath, string $footerLabel, bool $withFooter = true): int
{
    $options = new Options();
    $options->set('isRemoteEnabled', false);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans');
    $options->set('chroot', dirname(__DIR__, 2));

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    if ($withFooter) {
        $canvas = $dompdf->getCanvas();
        $canvas->page_text(400, 812, $footerLabel . ' - Halaman {PAGE_NUM} dari {PAGE_COUNT}', 'DejaVu Sans', 7.5, [0.42, 0.45, 0.50]);
    }

    file_put_contents($outputPath, $dompdf->output());

    return $dompdf->getCanvas()->get_page_count();
}

function count_pdf_pages(string $html): int
{
    $options = new Options();
    $options->set('isRemoteEnabled', false);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans');
    $options->set('chroot', dirname(__DIR__, 2));

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    return $dompdf->getCanvas()->get_page_count();
}

function build_pdf(array $meta, array $blocks, string $path): array
{
    $chapters = split_chapters($blocks);
    $coverHtml = '<div class="cover">'
        . '<div class="doctype">' . htmlspecialchars(strtoupper((string) ($meta['doc_type'] ?? 'DOKUMEN'))) . '</div>'
        . '<div class="title">' . htmlspecialchars((string) ($meta['title'] ?? '')) . '</div>'
        . '<div class="subtitle">' . htmlspecialchars((string) ($meta['subtitle'] ?? '')) . '</div>'
        . (!empty($meta['project']) ? '<div class="meta">Proyek: ' . htmlspecialchars($meta['project']) . '</div>' : '')
        . (!empty($meta['identity']) ? '<div class="meta">' . htmlspecialchars($meta['identity']) . '</div>' : '')
        . (!empty($meta['version']) ? '<div class="meta">Versi: ' . htmlspecialchars($meta['version']) . '</div>' : '')
        . (!empty($meta['date']) ? '<div class="meta">Tanggal: ' . htmlspecialchars($meta['date']) . '</div>' : '')
        . '</div>';

    $footerLabel = (string) ($meta['identity'] ?? 'FixPoint Service');

    // Jumlah halaman tiap bab dihitung dengan merender bab secara terpisah.
    // Setiap bab selalu dimulai pada halaman baru sehingga posisinya deterministik.
    $chapterPages = [];
    foreach ($chapters as $index => $chapter) {
        $chapterPages[$index] = count_pdf_pages(
            pdf_html_shell((string) ($meta['title'] ?? ''), pdf_blocks_html($chapter['blocks']))
        );
    }

    $tocRows = function (array $startPages) use ($chapters): string {
        $html = '<div class="toc-title">DAFTAR ISI</div>';
        foreach ($chapters as $index => $chapter) {
            $html .= '<div class="toc-row"><span class="num">' . ($startPages[$index] ?? '') . '</span>'
                . htmlspecialchars($chapter['title']) . '</div>';

            foreach ($chapter['blocks'] as $block) {
                if ($block['type'] === 'heading' && (int) $block['level'] === 2) {
                    $html .= '<div class="toc-row lvl2">' . htmlspecialchars($block['text']) . '</div>';
                }
            }
        }

        return $html . '<div class="pagebreak"></div>';
    };

    // Tahap 1: ukur berapa halaman yang dipakai daftar isi (angka sementara).
    $tocPages = count_pdf_pages(pdf_html_shell((string) ($meta['title'] ?? ''), $coverHtml . $tocRows([]))) - 1;
    $tocPages = max(1, $tocPages);

    // Tahap 2: hitung halaman awal setiap bab setelah cover dan daftar isi.
    $startPages = [];
    $cursor = 1 + $tocPages;
    foreach ($chapters as $index => $chapter) {
        $cursor++;
        $startPages[$index] = $cursor;
    }

    $body = $coverHtml . $tocRows($startPages);
    foreach ($chapters as $chapter) {
        $body .= pdf_blocks_html($chapter['blocks']) . '<div class="pagebreak"></div>';
    }

    $pages = render_pdf(pdf_html_shell((string) ($meta['title'] ?? ''), $body), $path, $footerLabel);
    $expected = 1 + $tocPages + array_sum($chapterPages);

    return ['pages' => $pages, 'expected' => $expected, 'toc_pages' => $tocPages, 'chapters' => count($chapters)];
}

/* -------------------------------------------------------------------------
 | Main
 | ---------------------------------------------------------------------- */

$source = $argv[1] ?? null;
$target = $argv[2] ?? null;

if ($source === null || $target === null) {
    fwrite(STDERR, "Pemakaian: php tools/docs/build.php <sumber.md> <target-tanpa-ekstensi>\n");
    exit(1);
}

if (!is_file($source)) {
    fwrite(STDERR, "Sumber tidak ditemukan: {$source}\n");
    exit(1);
}

[$meta, $markdown] = parse_front_matter((string) file_get_contents($source));
$blocks = parse_blocks($markdown);

$docxPath = $target . '.docx';
$pdfPath = $target . '.pdf';

$directory = dirname($docxPath);
if (!is_dir($directory)) {
    mkdir($directory, 0777, true);
}

build_docx($meta, $blocks, $docxPath);
$pdfInfo = build_pdf($meta, $blocks, $pdfPath);

$headings = count(array_filter($blocks, fn ($block) => $block['type'] === 'heading'));
$tables = count(array_filter($blocks, fn ($block) => $block['type'] === 'table'));

printf("DOCX : %s (%d KB, %d heading, %d tabel)\n", $docxPath, (int) round(filesize($docxPath) / 1024), $headings, $tables);
printf("PDF  : %s (%d KB, %d halaman, perkiraan %d halaman, daftar isi %d halaman)\n", $pdfPath, (int) round(filesize($pdfPath) / 1024), $pdfInfo['pages'], $pdfInfo['expected'], $pdfInfo['toc_pages']);

if ($pdfInfo['pages'] !== $pdfInfo['expected']) {
    fwrite(STDERR, "PERINGATAN: jumlah halaman PDF tidak sama dengan perkiraan sehingga nomor halaman pada daftar isi dapat bergeser.\n");
    exit(2);
}
