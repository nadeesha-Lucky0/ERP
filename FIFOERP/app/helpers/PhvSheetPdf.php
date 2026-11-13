<?php
namespace App\Helpers;

/**
 * PhvSheetPdf - Pure PHP PDF generator for Board of Survey Physical Verification Count Sheets
 * Exactly 21 items per A4 sheet with official headers, grid, and auditor signature blocks.
 */
class PhvSheetPdf {
    public static function generateSheet(int $sheetNum, int $totalSheets, array $items, string $station = 'Victoria Hydro Station', string $costCenter = 'Mechanical', string $warehouse = 'Central Main Store', string $date = ''): string {
        if (empty($date)) $date = date('Y-m-d');
        
        $buffer = "%PDF-1.4\n";
        $offsets = [];
        
        $newObj = function() use (&$buffer, &$offsets) {
            $id = count($offsets) + 1;
            $offsets[$id] = strlen($buffer);
            $buffer .= "$id 0 obj\n";
            return $id;
        };

        // 1: Catalog
        $catId = $newObj();
        $buffer .= "<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        // 2: Pages
        $pagesId = $newObj();
        $buffer .= "<< /Type /Pages /Kids [6 0 R] /Count 1 >>\nendobj\n";

        // Fonts: Helvetica & Helvetica-Bold
        $fontId = $newObj();
        $buffer .= "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
        $fontBoldId = $newObj();
        $buffer .= "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>\nendobj\n";

        // Stream content
        $c = "";
        
        // Header Banner Box
        $c .= "0.95 0.95 0.97 rg\n";
        $c .= "30 750 535 60 re f\n"; // top box
        $c .= "0.2 0.3 0.4 RG 1.2 w\n";
        $c .= "30 750 535 60 re s\n";

        // Company Title & Form Name
        $c .= "0.05 0.1 0.2 rg\n";
        $c .= "BT\n/F2 12 Tf\n";
        $c .= "40 792 Td (ELECTRICITY GENERATION LANKA - BOARD OF SURVEY COUNT SHEET) Tj\n";
        $c .= "ET\n";

        $c .= "0.3 0.35 0.4 rg\n";
        $c .= "BT\n/F1 8.5 Tf\n";
        $c .= "40 777 Td (Station: " . self::cleanStr($station) . " | Warehouse: " . self::cleanStr($warehouse) . ") Tj\n";
        $c .= "40 762 Td (Cost Center: " . self::cleanStr($costCenter) . " | Verification Date: " . self::cleanStr($date) . ") Tj\n";
        $c .= "ET\n";

        // Sheet Badge (Top Right)
        $c .= "0.05 0.58 0.53 rg\n";
        $c .= "440 760 115 38 re f\n";
        $c .= "1 1 1 rg\n";
        $c .= "BT\n/F2 11 Tf\n";
        $c .= "452 780 Td (SHEET " . sprintf('%02d', $sheetNum) . " / " . sprintf('%02d', $totalSheets) . ") Tj\n";
        $c .= "ET\n";
        $c .= "BT\n/F1 8 Tf\n";
        $c .= "455 768 Td (" . count($items) . " ITEMS / SHEET) Tj\n";
        $c .= "ET\n";

        // Table Header Row
        $c .= "0.15 0.2 0.28 rg\n";
        $c .= "30 726 535 18 re f\n"; // header fill
        $c .= "1 1 1 rg\n";
        $c .= "BT\n/F2 8.5 Tf\n";
        $c .= "34 731 Td (#) Tj\n";
        $c .= "22 0 Td (ITEM CODE) Tj\n";
        $c .= "80 0 Td (ITEM DESCRIPTION & SPECIFICATION) Tj\n";
        $c .= "200 0 Td (BIN) Tj\n";
        $c .= "48 0 Td (BOOK) Tj\n";
        $c .= "45 0 Td (PHYSICAL COUNT) Tj\n";
        $c .= "76 0 Td (INITIAL) Tj\n";
        $c .= "ET\n";

        // Table Rows (Exactly 21 items)
        $y = 708;
        $rowH = 26; // 26 pt per row * 21 = 546 pt
        $rowNum = 1;

        for ($idx = 0; $idx < 21; $idx++) {
            $item = $items[$idx] ?? null;

            // Row background (alternate)
            if ($idx % 2 === 1) {
                $c .= "0.97 0.98 0.99 rg\n";
                $c .= "30 " . ($y - 2) . " 535 " . ($rowH) . " re f\n";
            }

            // Grid border lines
            $c .= "0.85 0.88 0.92 RG 0.5 w\n";
            $c .= "30 " . ($y - 2) . " 535 " . ($rowH) . " re s\n";

            // Vertical column dividers
            $c .= "54 " . ($y - 2) . " m 54 " . ($y + $rowH - 2) . " l S\n";
            $c .= "134 " . ($y - 2) . " m 134 " . ($y + $rowH - 2) . " l S\n";
            $c .= "334 " . ($y - 2) . " m 334 " . ($y + $rowH - 2) . " l S\n";
            $c .= "382 " . ($y - 2) . " m 382 " . ($y + $rowH - 2) . " l S\n";
            $c .= "427 " . ($y - 2) . " m 427 " . ($y + $rowH - 2) . " l S\n";
            $c .= "503 " . ($y - 2) . " m 503 " . ($y + $rowH - 2) . " l S\n";

            if ($item) {
                $sku = self::cleanStr($item['sku'] ?? '');
                $name = self::cleanStr($item['item_name'] ?? '');
                if (strlen($name) > 42) $name = substr($name, 0, 40) . '..';
                $bin = self::cleanStr($item['location_bin'] ?? '-');
                $book = self::cleanStr($item['book_quantity'] ?? $item['remaining_quantity'] ?? '0');
                $globalIdx = (($sheetNum - 1) * 21) + ($idx + 1);

                $c .= "0.1 0.15 0.25 rg\n";
                $c .= "BT\n/F1 8 Tf\n";
                $c .= "35 " . ($y + 8) . " Td (" . sprintf('%02d', $globalIdx) . ") Tj\n";
                $c .= "22 0 Td ($sku) Tj\n";
                $c .= "80 0 Td ($name) Tj\n";
                $c .= "202 0 Td ($bin) Tj\n";
                $c .= "49 0 Td ($book) Tj\n";
                $c .= "ET\n";

                // Physical Count Blank Box
                $c .= "0.7 0.75 0.8 RG 0.8 w\n";
                $c .= "436 " . ($y + 2) . " 58 18 re s\n";

                // Initial line
                $c .= "512 " . ($y + 6) . " m 555 " . ($y + 6) . " l S\n";
            }

            $y -= $rowH;
        }

        // Bottom Signature Section
        $sigY = 135;
        $c .= "0.96 0.96 0.98 rg\n";
        $c .= "30 50 535 75 re f\n";
        $c .= "0.8 0.85 0.9 RG 0.8 w\n";
        $c .= "30 50 535 75 re s\n";

        // Three Signature Columns
        $c .= "0.2 0.25 0.35 rg\n";
        $c .= "BT\n/F2 8 Tf\n";
        $c .= "45 105 Td (1. PHYSICAL STOCK VERIFIED BY:) Tj\n";
        $c .= "220 0 Td (2. SUPERVISED & CHECKED BY:) Tj\n";
        $c .= "195 0 Td (3. BOARD OF SURVEY AUDITOR:) Tj\n";
        $c .= "ET\n";

        $c .= "0.4 0.45 0.5 rg\n";
        $c .= "BT\n/F1 7.5 Tf\n";
        $c .= "45 92 Td (Name: _________________________) Tj\n";
        $c .= "220 0 Td (Name: _________________________) Tj\n";
        $c .= "195 0 Td (Name: _________________________) Tj\n";
        $c .= "ET\n";

        $c .= "BT\n/F1 7.5 Tf\n";
        $c .= "45 78 Td (Designation / EPF: ____________) Tj\n";
        $c .= "220 0 Td (Designation / EPF: ____________) Tj\n";
        $c .= "195 0 Td (Designation / EPF: ____________) Tj\n";
        $c .= "ET\n";

        $c .= "BT\n/F1 7.5 Tf\n";
        $c .= "45 64 Td (Signature & Date: _____________) Tj\n";
        $c .= "220 0 Td (Signature & Date: _____________) Tj\n";
        $c .= "195 0 Td (Signature & Date: _____________) Tj\n";
        $c .= "ET\n";

        // Page footer note
        $c .= "0.55 0.6 0.65 rg\n";
        $c .= "BT\n/F1 7 Tf\n";
        $c .= "30 38 Td (Form EGL-ST-PHV-01 | Standard 21 Items Stock Count Tally Sheet | Generated on $date) Tj\n";
        $c .= "430 0 Td (Page $sheetNum of $totalSheets) Tj\n";
        $c .= "ET\n";

        // Content object
        $contentId = $newObj();
        $len = strlen($c);
        $buffer .= "<< /Length $len >>\nstream\n$c\nendstream\nendobj\n";

        // Page object (6)
        $pageId = $newObj();
        $buffer .= "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 $fontId 0 R /F2 $fontBoldId 0 R >> >> /Contents $contentId 0 R >>\nendobj\n";

        // Xref table
        $xrefOffset = strlen($buffer);
        $totalObjs = count($offsets) + 1;
        $buffer .= "xref\n0 $totalObjs\n";
        $buffer .= "0000000000 65535 f \n";
        for ($i = 1; $i < $totalObjs; $i++) {
            $buffer .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $buffer .= "trailer\n<< /Size $totalObjs /Root 1 0 R >>\nstartxref\n$xrefOffset\n%%EOF\n";

        return $buffer;
    }

    private static function cleanStr(string $str): string {
        $str = iconv('UTF-8', 'windows-1252//IGNORE', $str);
        return str_replace(['(', ')', '\\'], ['[', ']', '/'], $str);
    }
}

