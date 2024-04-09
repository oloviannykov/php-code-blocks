<?php
namespace App\Models\tools;

/**
 * Page Tool Kit
 */
class Ptk
{
    private static
    $pagination_interval = 15,
    $pagination_page_no = 1,
    $pagination_action = '',
    $pagination_filter = [],
    $pagination_controller = '',
    $pagination_found_qty = 0;

    public static function val($value, $multiline = false): string
    {
        if (!is_string($value) && !is_numeric($value)) {
            return htmlspecialchars(json_encode($value, JSON_UNESCAPED_UNICODE));
        }
        $value = htmlspecialchars($value, ENT_QUOTES, null, false);
        if ($multiline) {
            return nl2br($value);
        }
        return $value;
    }

    public static function rows($rowsArray = [])
    {
        return nl2br(self::val(implode("\n", $rowsArray)));
    }

    public static function tag($name, $body = '', $attributes = [])
    {
        $attr = [];
        foreach ($attributes as $attrName => $attrValue) {
            $attr[] = $attrName . '="' . self::val($attrValue) . '"';
        }
        return "<$name" . ($attr ? ' ' . implode(" ", $attr) : '') . ">" . self::val($body) . "</$name>";
    }

    public static function mtag($name, $list)
    {
        $result = '';
        foreach ($list as $item) {
            $val = empty($item[0]) ? '' : $item[0];
            $attrs = empty($item[1]) ? '' : ' ' . $item[1];
            $notEscape = !empty($item[2]);
            $result .= "<$name$attrs>" . ($notEscape ? $val : self::val($val)) . "</$name>\n";
        }
        return $result;
    }

    public static function link($caption, $admin_page, $get = [], $class = '', $extraArgs = [])
    {
        foreach ($extraArgs as $argName => &$argValue) {
            $argValue = " $argName=\"" . htmlspecialchars($argValue) . '"';
        }
        unset($argValue);
        return '<a href="' . self::href($admin_page, $get) . '"'
            . ($class ? ' class="' . $class . '"' : '')
            . ($extraArgs ? ' ' . implode('', $extraArgs) : '')
            . '>' . self::val($caption) . '</a>';
    }
    public static function linkAway($caption, $admin_page, $get = [], $class = '', $args = [])
    {
        $args['target'] = '_blank';
        return self::link($caption, $admin_page, $get, $class, $args);
    }

    public static function href($page, $get = [], $controller = 'admin')
    {
        return '/' . $controller . '/' . $page
            . ($get ? '?' . self::val(http_build_query($get)) : '');
    }

    public static function tagml($name, $lines = [], $attributes = [])
    {
        $attr = [];
        foreach ($attributes as $attrName => $attrValue) {
            $attr[] = $attrName . '="' . self::val($attrValue) . '"';
        }
        return "<$name" . ($attr ? ' ' . implode(" ", $attr) : '') . ">"
            . self::val(implode("\n", $lines), true) //--> "line1<br/>line2<br/>..."
            . "</$name>";
    }

    public static function tags($name, $htmlbody = '', $attributes = [])
    {
        $attr = [];
        foreach ($attributes as $attrName => $attrValue) {
            $attr[] = $attrName . '="' . self::val($attrValue) . '"';
        }
        return "<$name " . implode(" ", $attr) . ">$htmlbody</$name>";
    }

    public static function icon($name, $spaceAfter = true)
    {
        return '<span class="glyphicon glyphicon-' . $name . '"></span>'
            . ($spaceAfter ? ' ' : '');
    }

    public static function text($text): string
    {
        return nl2br(htmlspecialchars($text, ENT_NOQUOTES, null, false));
    }

    public static function btn(
        $caption,
        $classes,
        $admin_page,
        $getParams,
        $icon = '',
        $newWindow = false,
        $style = 'margin-bottom: 5px'
    ): string {
        $args = '';
        if ($newWindow) {
            $args .= ' target="_blank"';
        }
        if ($style) {
            $args .= ' style="' . self::val($style) . '"';
        }
        return '<a href="' . self::href($admin_page, $getParams) . '"'
            . ' class="btn btn-' . $classes . '"' . ($args ? $args : '') . '>'
            . ($icon ? self::icon($icon) : '') . self::val($caption)
            . '</a>';
    }

    public static function btnAway($caption, $classes, $admin_page, $getParams = [], $icon = '', $style = 'margin-bottom: 5px'): string
    {
        return self::btn($caption, $classes, $admin_page, $getParams, $icon, true, $style);
    }

    public static function btnSubmit($title, $classesStr, $icon = '', $attrStr = '', $style = 'margin-bottom: 5px'): string
    {
        /* <button type="submit" class="btn btn-danger action-apply">
            <span class="glyphicon glyphicon-remove"></span> Test
        </button> */
        $caption = is_string($title) ? self::val($title) : json_encode($title);
        return '<button type="submit" class="btn btn-' . $classesStr . '" ' . $attrStr
            . ($style ? ' style="' . $style . '"' : '')
            . '>' . ($icon ? self::icon($icon) : '') . self::val($caption)
            . '</button>';
    }

    public static function tplFill($tpl, $values)
    {
        //use [...] to skip escaping
        $idx = 0;
        foreach ($values as $val) {
            $idx++;
            if (is_array($val)) {
                $val = empty($val) || !isset($val[0]) ? '' : $val[0];
            } else {
                $val = self::val($val);
            }
            $tpl = str_replace('%' . $idx . '%', $val, $tpl);
        }
        return $tpl;
    }

    public static function panelStart($title, $icon = '', $panelClass = '', $bodyClass = '', $bodyAttrStr = '')
    {
        $iconTag = $icon ? self::icon($icon) : '';
        return '<div class="panel' . ($panelClass ? " $panelClass" : '') . '">'
            . '<div class="panel-heading">' . $iconTag . self::val($title) . '</div>'
            . '<div class="panel-body' . ($bodyClass ? " $bodyClass" : '') . '"'
            . ($bodyAttrStr ? " $bodyAttrStr" : '')
            . '>' . "\n";
    }
    public static function panelStartMinimizable($title, $icon = '', $minimized = true, $bodyClass = '', $bodyAttr = '')
    {
        $miniClass = 'panel-minimizable' . ($minimized ? ' panel-minimized' : '');
        return self::panelStart($title, $icon, $miniClass, $bodyClass, $bodyAttr);
    }

    public static function panelHeader($title, $icon = '', $classStr = '', $attrStr = '')
    {
        $iconTag = $icon ? self::icon($icon) : '';
        return '<div class="panel' . ($classStr ? " $classStr" : '') . '"'
            . ($attrStr ? " $attrStr" : '') . ">\n"
            . '<div class="panel-heading">' . $iconTag . self::val($title) . '</div>' . "\n";
    }
    public static function panelBodyStart($classStr = '', $attrStr = '')
    {
        return '<div class="panel-body' . ($classStr ? " $classStr" : '') . '"'
            . ($attrStr ? " $attrStr" : '') . '>' . "\n";
    }

    public static function panelEnd()
    {
        return "</div></div>\n";
    }

    //generate query offset and limit + store parameters
    public static function paginationGetFrame($action, $filter, $records_per_page_qty = 15, $controller = 'admin'): array
    {
        self::$pagination_action = $action;
        self::$pagination_interval = $records_per_page_qty;
        self::$pagination_controller = $controller;
        if (empty($filter['page'])) {
            self::$pagination_page_no = 1;
        } else {
            self::$pagination_page_no = intval($filter['page']);
            unset($filter['page']);
        }
        self::$pagination_filter = $filter;
        return [
            'offset' => (self::$pagination_page_no - 1) * $records_per_page_qty,
            'limit' => self::$pagination_page_no * $records_per_page_qty + 1,
            //example: interval=15, page=2, offset=15, limit=31, show_qty=30,
            //.....if found 31 then show link to page #3 in paginationGetHtml()
        ];
    }

    public static function paginationGetInterval(): int
    {
        return self::$pagination_interval;
    }
    public static function paginationGetPage(): int
    {
        return self::$pagination_page_no;
    }
    public static function paginationSetFound($qty): void
    {
        self::$pagination_found_qty = (int) $qty;
    }

    public static function paginationGetHtml(): string
    {
        $hasMoreRecords = self::$pagination_found_qty > self::$pagination_interval;
        if (self::$pagination_page_no === 1 && !$hasMoreRecords) {
            return ''; //no pagination required
        }
        $items = [];
        $params = (self::$pagination_filter ? http_build_query(self::$pagination_filter) . '&' : '') . 'page=';
        for ($i = 1; $i <= self::$pagination_page_no; $i++) {
            if ($i < self::$pagination_page_no) {
                $href = '/' . self::$pagination_controller . '/' . self::$pagination_action . "?{$params}{$i}";
                $content = '<a href="' . self::val($href) . '">' . $i . '</a>';
                $item_class = '';
            } else {
                $content = "<b>$i</b>";
                $item_class = ' class="pagination_current"';
            }
            $items[] = "<li{$item_class}>{$content}</li>";
        }
        if ($hasMoreRecords) {
            $next_page_no = self::$pagination_page_no + 1;
            $href = '/' . self::$pagination_controller . '/' . self::$pagination_action . "?{$params}{$next_page_no}";
            $content = '<a href="' . self::val($href) . '">' . self::val('>>') . '</a>';
            $items[] = "<li>{$content}</li>";
        }
        return "\n<ul class=\"pagination_list\">\n" . implode("\n", $items) . "\n</ul>\n";
    }
}
