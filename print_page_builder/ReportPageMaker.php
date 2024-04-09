<?php
namespace App\Models\entities;
use App\Translations\FieldDictionary;

class ReportPageMaker
{
    const
        PAGE_ORIENTATION_PORTRAIT = 'portrait',
        PAGE_ORIENTATION_LANDSCAPE = 'landscape',
        PAGE_FORMAT_A4 = 'A4', //A4: 210 x 297mm, 8.3 x 11.7 inch
        A4_PORTRAIT_HEIGHT_MM = 296,
        A4_PORTRAIT_WIDTH_MM = 210,
        A4_LANDSCAPE_HEIGHT_MM = 209,
        A4_LANDSCAPE_WIDTH_MM = 297,
        PADDING_TOP_MM = 15,
        PADDING_RIGHT_MM = 10,
        PADDING_BOTTOM_MM = 15,
        PADDING_LEFT_MM = 20,
        HEADER_HEIGHT_MM = 6,
        HEADER_LOCATION_TOP = 'top',
        HEADER_LOCATION_BOTTOM = 'bottom',
        LINE_HEIGHT_DEFAULT_MM = 5
    ;

    private
        $orientation = self::PAGE_ORIENTATION_PORTRAIT,
        $content_max_height = self::A4_PORTRAIT_HEIGHT_MM - 30,
        $content_max_width = self::A4_PORTRAIT_WIDTH_MM - 30,
        $header_location = self::HEADER_LOCATION_TOP,
        $content_length = 0,
        $title = 'Untitled',
        $page_no = 0,
        $line_height = self::LINE_HEIGHT_DEFAULT_MM,
        $table_header = '',
        $table_header_height = 0,
        $log = []
    ;

    public function __construct($page_orientation='', $header_location='')
    {
        FieldDictionary::load();
        $this->orientation = empty($page_orientation) ? self::PAGE_ORIENTATION_PORTRAIT : $page_orientation;

        $page_height = $this->orientation === self::PAGE_ORIENTATION_PORTRAIT ? self::A4_PORTRAIT_HEIGHT_MM : self::A4_LANDSCAPE_HEIGHT_MM;
        $page_width = $this->orientation === self::PAGE_ORIENTATION_PORTRAIT ? self::A4_PORTRAIT_WIDTH_MM : self::A4_LANDSCAPE_WIDTH_MM;

        $this->content_max_height = $page_height - self::PADDING_TOP_MM - self::PADDING_BOTTOM_MM - self::HEADER_HEIGHT_MM;
        $this->content_max_width = $page_width - self::PADDING_LEFT_MM - self::PADDING_RIGHT_MM;

        $this->header_location = empty($header_location) ? self::HEADER_LOCATION_TOP : $header_location;

        $this->line_height = self::LINE_HEIGHT_DEFAULT_MM;

        $this->log[] = json_encode([
            'orientation' => $this->orientation,
            'page_height' => $page_height,
            'page_width' => $page_width,
        ], JSON_UNESCAPED_UNICODE);

        $this->log[] = json_encode([
            'content_max_height' => $this->content_max_height,
            'content_max_width' => $this->content_max_width,
            'header_location' => $this->header_location,
            'line_height' => $this->line_height,
        ], JSON_UNESCAPED_UNICODE);
    }

    public function set_line_height($height_mm): void
    {
        $this->line_height = $height_mm;
    }

    public function table_start($columns=[], $fit_lines_qty=1): void
    {
        $this->table_header_height = $this->line_height * $fit_lines_qty;
        $header = [];

        $td_style = 'height: ' . $this->table_header_height . 'mm; overflow: hidden;';
        foreach ($columns as $column_name => $settings) {
            $cell_style = empty($settings['cell_style']) ? '' : $settings['cell_style'];
            $line_style = empty($settings['line_style']) ? '' : $settings['line_style'];
            $line_height = empty($settings['line_height']) ? $this->line_height : $settings['line_height'];
            $colspan = empty($settings['colspan']) ? '' : 'colspan="' . $settings['colspan'] . '"';
            $header[] = '<td ' . $colspan . ' style="' . $td_style . $cell_style . '">'
                    . $this->table_cell_wrap_lines([$column_name], $line_height, $line_style)
                . '</td>';
        }

        $result = '<table style="table-layout: fixed;"><thead><tr>'
                . implode("\n", $header) . '</tr></thead><tbody>';
        $this->table_header = $result;
        $this->content_length += $this->table_header_height;
        echo $result;
    }

    private function table_cell_wrap_lines($lines, $height_mm, $line_style=''): string
    {
        $line_style = 'style="height: ' .(int)$height_mm. 'mm; padding: 0 1mm;'
            . ' overflow: hidden; white-space: nowrap; ' . $line_style
            . ' font-size: '.(int)$height_mm. 'mm;"';
        $result = [];
        foreach($lines as $line) {
            $result[] = "<div $line_style>" . $line . '</div>';
        }
        return implode('', $result);
    }

    public function table_row($cells=[]): void
    {
        $td_array = [];
        $max_lines_qty = 1;
        foreach($cells as $one_cell) {
            if(is_array($one_cell)) {
                $lines_qty = empty($one_cell['lines']) ? 0 : count($one_cell['lines']);
            } else {
                $lines_qty = 1;
            }
            $max_lines_qty = $max_lines_qty < $lines_qty ? $lines_qty : $max_lines_qty;
        }
        $cell_height_mm = $max_lines_qty * $this->line_height;
        foreach($cells as $one_cell) {
            if(is_array($one_cell)) {
                $colspan = empty($one_cell['colspan']) ? '' : 'colspan="' . $one_cell['colspan'] . '"';
                $lines = empty($one_cell['lines']) ? [] : $one_cell['lines'];
                $line_style = empty($one_cell['style']) ? '' : $one_cell['style'];
                $line_height = empty($one_cell['height']) ? $this->line_height : $one_cell['height'];
                $content = $this->table_cell_wrap_lines($lines, $line_height, $line_style);
            } else {
                $colspan = '';
                $content = $this->table_cell_wrap_lines([$one_cell], $this->line_height);
            }
            $td_array[] = "<td style=\"height: {$cell_height_mm}mm;\" {$colspan}>"
                . $content . '</td>';
        }
        $result = '<tr>' . implode("\n", $td_array) . "</tr>\n";
        $this->append_content($result, $cell_height_mm);
    }

    public function table_break(): void
    {
        if($this->table_header) {
            echo '</tbody></table>';
        }
    }

    public function table_continue(): void
    {
        if ($this->table_header) {
            $this->content_length += $this->table_header_height + 10;
            echo $this->table_header;
        }
    }

    public function table_close(): void
    {
        $this->table_header = '';
        $this->table_header_height = 0;
        echo '</tbody></table>';
    }

    public function heading($text, $align_center=true, $lines_qty=1): void
    {
        $this->paragraph($text, $lines_qty, true, 8,
                $align_center ? 'center' : 'left',
                2);
    }

    public function paragraph(
        $text, $lines_qty=1, $bold=false, $font_size_mm=5, $align='left', $margin_mm=1
    ): void {
        $p_line_height = $font_size_mm;// + ($font_size_mm > 5 ? 2 : 1);
        $p_inner_height_mm = $lines_qty * $p_line_height;
        $p_outer_height_mm = $p_inner_height_mm + ($margin_mm * 2);

        $p_style = "overflow: hidden;"
            . " height: {$p_inner_height_mm}mm;"
            . " line-height: {$p_line_height}mm;"
            . ($bold ? " font-weight: bold;" : '')
            . " font-size: {$font_size_mm}mm;"
            . " margin: {$margin_mm}mm;"
            . " text-align: {$align};"
        ;
        $this->append_content(
            "<div style=\"{$p_style}\">{$text}</div>\n",
            $p_outer_height_mm
        );
    }

    public function text_with_line_breaks($text, $font_size_mm=5): void
    {
        foreach (explode("\n", $text) as $p) {
            $p = trim($p);
            if (empty($p)) {
                $this->interval(2);
                continue;
            }
            $lines_qty = ceil(mb_strlen($p) / (17 * $font_size_mm));
            $this->paragraph($p, $lines_qty, false, $font_size_mm);
        }
    }

    public function printing_date($size_mm=4): void
    {
        $this->paragraph(
            FieldDictionary::$was_printed.' '.date('Y-m-d H:i'),
            1, false, $size_mm, 'right', 2
        );
    }

    public function interval($size_mm=10): void
    {
        if ($this->content_length + $size_mm + 5 > $this->content_max_height) {
            $this->page_break();
        } else {
            echo '<div style="height: ' . (int)$size_mm . 'mm;"><span></span></div>';
            $this->content_length += $size_mm;
        }
    }

    public function page_break(): void
    {
        $this->page_close();
        $this->page_open();
    }

    public function get_log(): string
    {
        $log = implode("\n", $this->log);
        $this->log = [];
        return $log;
    }

    public function append_content($content, $content_length=0): void
    {
        $content_length = $content_length ? $content_length : $this->line_height;
        if($this->content_length + $content_length + 5 > $this->content_max_height) {
            //if inside table then close table
            $this->page_close();
            $this->page_open();
            //if inside table then open table and insert header
        } else {
            $this->content_length += $content_length;
        }
        echo $content;
    }

    private function getDocumentId($code): string
    {
        return $code . '/' . date('Y-m-d') . '/' . uniqid();
    }

    public function report_start($title, $code=''): void
    {
        $this->title = $this->getDocumentId($code ? $code : APP_TITLE);
        //heare need set paper size if use not A4
        $orient = $this->orientation === self::PAGE_ORIENTATION_PORTRAIT ? '' : ' landscape';
        echo '<body class="A4' . $orient . '">' ."\n";
        $this->page_open();
        $this->heading($title, true, mb_strlen($title)>45 ? 2 : 1);
    }

    public function report_end(): void
    {
        $this->page_close();
        echo '</body>';
    }

    public function page_open(): void
    {
        $this->content_length = 0;
        $this->page_no++;
        echo "\n" . '<section class="sheet">' . "\n";
        if ($this->header_location === self::HEADER_LOCATION_TOP) {
            $this->page_header(true);
        }
        $this->table_continue();
    }

    public function page_close(): void
    {
        $this->table_break();
        if ($this->header_location === self::HEADER_LOCATION_BOTTOM) {
            $this->page_header(false);
        }
        $this->content_length = 0;
        echo "</section>\n";
    }

    public function page_header($location_top=true): void
    {
        $title = $this->title;
        $content_height = self::HEADER_HEIGHT_MM - 1;
        $span_style = 'display: inline-block; vertical-align: top;'
                . ' font-size: ' . ($content_height-1) . 'mm;';
        $div_style = 'height: ' . $content_height . 'mm; overflow: hidden;';
        if ($location_top) {
            $div_style .= ' margin-bottom: 1mm; border-bottom: 1px solid black;';
        } else {
            $margin = $this->content_max_height - $this->content_length + 1;
            $div_style .= ' margin-top: ' . $margin . 'mm; border-top: 1px solid black;';
        }
        ?>
        <div style="<?= $div_style ?>">
            <span style="<?= $span_style . 'width:' . ($this->content_max_width - 20) . 'mm;' ?>">
                <?= $title ?>
            </span><span
                style="<?= $span_style ?> width:20mm; text-align: right;">
                <?= FieldDictionary::$page . ' ' . $this->page_no ?>
            </span>
        </div>
        <?php
    }

}
