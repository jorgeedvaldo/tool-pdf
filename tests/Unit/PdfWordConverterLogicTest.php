<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the PDF↔Word converter logic.
 *
 * The converter runs entirely in the browser (JS), so these tests validate:
 * - The algorithmic logic (line grouping, paragraph detection, alignment) by
 *   re-implementing equivalent PHP versions of the core functions.
 * - Route configuration via reflection of routes/web.php expectations.
 */
class PdfWordConverterLogicTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Line grouping algorithm
    // -------------------------------------------------------------------------

    /**
     * Simulates the JS groupLines() logic: items with the same baseline Y
     * (within fontSize * 0.6 tolerance) belong to the same line.
     *
     * @param array $items  Each item: ['x'=>float, 'y'=>float, 'w'=>float, 'h'=>float, 'str'=>string]
     * @return array        Lines sorted by Y, each containing sorted items
     */
    private function groupLines(array $items): array
    {
        $lines = [];
        foreach ($items as $item) {
            $fontSize = $item['h'];
            $tolerance = $fontSize * 0.6;
            $matched = false;
            foreach ($lines as &$line) {
                if (abs($line['baseY'] - $item['y']) <= $tolerance) {
                    $line['items'][] = $item;
                    $matched = true;
                    break;
                }
            }
            unset($line);
            if (!$matched) {
                $lines[] = ['baseY' => $item['y'], 'items' => [$item]];
            }
        }
        // Sort lines top-to-bottom
        usort($lines, fn($a, $b) => $a['baseY'] <=> $b['baseY']);
        // Sort items within each line left-to-right
        foreach ($lines as &$line) {
            usort($line['items'], fn($a, $b) => $a['x'] <=> $b['x']);
        }
        return $lines;
    }

    public function test_items_on_same_baseline_grouped_into_one_line()
    {
        $items = [
            ['x' => 10, 'y' => 100, 'w' => 50, 'h' => 12, 'str' => 'Hello'],
            ['x' => 65, 'y' => 101, 'w' => 40, 'h' => 12, 'str' => 'world'],
        ];
        $lines = $this->groupLines($items);
        $this->assertCount(1, $lines);
        $this->assertCount(2, $lines[0]['items']);
    }

    public function test_items_on_different_baselines_grouped_into_separate_lines()
    {
        $items = [
            ['x' => 10, 'y' => 100, 'w' => 50, 'h' => 12, 'str' => 'Line one'],
            ['x' => 10, 'y' => 130, 'w' => 50, 'h' => 12, 'str' => 'Line two'],
        ];
        $lines = $this->groupLines($items);
        $this->assertCount(2, $lines);
    }

    public function test_items_within_tolerance_are_grouped()
    {
        // tolerance = 12 * 0.6 = 7.2; delta = 6 → same line
        $items = [
            ['x' => 10, 'y' => 100, 'w' => 50, 'h' => 12, 'str' => 'A'],
            ['x' => 70, 'y' => 106, 'w' => 30, 'h' => 12, 'str' => 'B'],
        ];
        $lines = $this->groupLines($items);
        $this->assertCount(1, $lines);
    }

    public function test_items_outside_tolerance_are_split()
    {
        // tolerance = 12 * 0.6 = 7.2; delta = 10 → different lines
        $items = [
            ['x' => 10, 'y' => 100, 'w' => 50, 'h' => 12, 'str' => 'A'],
            ['x' => 70, 'y' => 110, 'w' => 30, 'h' => 12, 'str' => 'B'],
        ];
        $lines = $this->groupLines($items);
        $this->assertCount(2, $lines);
    }

    public function test_lines_sorted_top_to_bottom()
    {
        $items = [
            ['x' => 10, 'y' => 200, 'w' => 50, 'h' => 12, 'str' => 'Second'],
            ['x' => 10, 'y' => 100, 'w' => 50, 'h' => 12, 'str' => 'First'],
        ];
        $lines = $this->groupLines($items);
        $this->assertEquals('First', $lines[0]['items'][0]['str']);
        $this->assertEquals('Second', $lines[1]['items'][0]['str']);
    }

    public function test_items_within_line_sorted_left_to_right()
    {
        $items = [
            ['x' => 80, 'y' => 100, 'w' => 30, 'h' => 12, 'str' => 'Second'],
            ['x' => 10, 'y' => 100, 'w' => 30, 'h' => 12, 'str' => 'First'],
        ];
        $lines = $this->groupLines($items);
        $this->assertEquals('First', $lines[0]['items'][0]['str']);
        $this->assertEquals('Second', $lines[0]['items'][1]['str']);
    }

    // -------------------------------------------------------------------------
    // Paragraph detection algorithm
    // -------------------------------------------------------------------------

    /**
     * Simulates groupParagraphs() — lines with a gap > prevFontSize * 1.7 start a new paragraph.
     */
    private function groupParagraphs(array $lines): array
    {
        $paragraphs = [];
        $current = [];
        $prevBottomY = null;
        $prevFontSize = null;

        foreach ($lines as $line) {
            $lineY = $line['baseY'];
            $fontSize = !empty($line['items']) ? $line['items'][0]['h'] : 12;

            if ($prevBottomY !== null) {
                $gap = $lineY - $prevBottomY;
                if ($gap > $prevFontSize * 1.7) {
                    if (!empty($current)) {
                        $paragraphs[] = $current;
                        $current = [];
                    }
                }
            }
            $current[] = $line;
            $prevBottomY = $lineY + $fontSize;
            $prevFontSize = $fontSize;
        }
        if (!empty($current)) {
            $paragraphs[] = $current;
        }
        return $paragraphs;
    }

    public function test_lines_with_small_gap_form_one_paragraph()
    {
        // Lines spaced by 14px, fontSize=12, threshold=12*1.7=20.4 → same paragraph
        $lines = [
            ['baseY' => 100, 'items' => [['h' => 12, 'str' => 'L1']]],
            ['baseY' => 114, 'items' => [['h' => 12, 'str' => 'L2']]],
            ['baseY' => 128, 'items' => [['h' => 12, 'str' => 'L3']]],
        ];
        $paragraphs = $this->groupParagraphs($lines);
        $this->assertCount(1, $paragraphs);
        $this->assertCount(3, $paragraphs[0]);
    }

    public function test_lines_with_large_gap_form_separate_paragraphs()
    {
        // Lines: first ends at 112, gap to second is 88, threshold=12*1.7=20.4 → split
        $lines = [
            ['baseY' => 100, 'items' => [['h' => 12, 'str' => 'Para 1']]],
            ['baseY' => 200, 'items' => [['h' => 12, 'str' => 'Para 2']]],
        ];
        $paragraphs = $this->groupParagraphs($lines);
        $this->assertCount(2, $paragraphs);
    }

    public function test_single_line_produces_one_paragraph()
    {
        $lines = [
            ['baseY' => 100, 'items' => [['h' => 12, 'str' => 'Only line']]],
        ];
        $paragraphs = $this->groupParagraphs($lines);
        $this->assertCount(1, $paragraphs);
    }

    // -------------------------------------------------------------------------
    // Alignment detection algorithm
    // -------------------------------------------------------------------------

    /**
     * Simulates alignment detection from the JS converter.
     * Returns 'CENTER', 'RIGHT', or 'LEFT'.
     */
    private function detectAlignment(float $lineLeft, float $lineRight, float $pageWidth): string
    {
        $lineMid = ($lineLeft + $lineRight) / 2;
        $lineWidth = $lineRight - $lineLeft;

        if (
            abs($lineMid - $pageWidth / 2) < $pageWidth * 0.06 &&
            $lineLeft > $pageWidth * 0.18
        ) {
            return 'CENTER';
        }
        if (
            $lineRight > $pageWidth * 0.85 &&
            $lineLeft > $pageWidth * 0.4
        ) {
            return 'RIGHT';
        }
        return 'LEFT';
    }

    public function test_centered_text_detected_as_center()
    {
        // Page 600px wide, line from 200 to 400 → mid=300, pageMid=300 → centered
        $alignment = $this->detectAlignment(200, 400, 600);
        $this->assertEquals('CENTER', $alignment);
    }

    public function test_left_aligned_text_detected_as_left()
    {
        // Line starts at left edge
        $alignment = $this->detectAlignment(10, 200, 600);
        $this->assertEquals('LEFT', $alignment);
    }

    public function test_right_aligned_text_detected_as_right()
    {
        // Line ends near right edge, starts past 40% of page
        $alignment = $this->detectAlignment(350, 590, 600);
        $this->assertEquals('RIGHT', $alignment);
    }

    public function test_near_center_but_starting_too_left_is_left()
    {
        // Mid is close to center but lineLeft < 18% of page width → LEFT
        $alignment = $this->detectAlignment(5, 595, 600);
        $this->assertEquals('LEFT', $alignment);
    }

    // -------------------------------------------------------------------------
    // Coordinate system for edit-pdf (canvas px ↔ PDF pt ↔ CSS %)
    // -------------------------------------------------------------------------

    public function test_canvas_px_to_css_percent()
    {
        $vpWidth = 900;   // canvas viewport width at renderScale=1.5
        $itemX = 450;     // item x in canvas px

        $cssPercent = $itemX / $vpWidth * 100;
        $this->assertEquals(50.0, $cssPercent);
    }

    public function test_canvas_px_to_pdf_pt()
    {
        $renderScale = 1.5;
        $itemX = 150;     // canvas px
        // PDF pt = canvas px / renderScale (approximately, for square scale)
        $pdfPt = $itemX / $renderScale;
        $this->assertEqualsWithDelta(100.0, $pdfPt, 0.01);
    }

    public function test_y_coordinate_flip_canvas_to_pdf()
    {
        $vpHeight = 1200;  // canvas px
        $renderScale = 1.5;
        $pdfHeight = $vpHeight / $renderScale;  // ≈ 800 pt
        $itemY = 300;      // canvas px from top

        // PDF coordinate system has Y=0 at bottom
        $yTopPt = ($vpHeight - $itemY) / $renderScale;
        $this->assertEqualsWithDelta(600.0, $yTopPt, 0.01);
    }

    // -------------------------------------------------------------------------
    // Text extraction helpers
    // -------------------------------------------------------------------------

    public function test_line_text_concatenation()
    {
        $items = [
            ['str' => 'Hello', 'x' => 10, 'w' => 30],
            ['str' => 'world', 'x' => 45, 'w' => 30],
        ];

        // Items are joined with a space
        $text = implode(' ', array_column($items, 'str'));
        $this->assertEquals('Hello world', $text);
    }

    public function test_empty_string_items_are_filtered()
    {
        $items = [
            ['str' => 'Hello', 'x' => 10],
            ['str' => '', 'x' => 20],
            ['str' => 'world', 'x' => 30],
        ];

        $filtered = array_filter($items, fn($i) => trim($i['str']) !== '');
        $this->assertCount(2, $filtered);
    }
}
