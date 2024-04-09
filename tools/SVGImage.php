<?php
namespace App\Models\tools;

class SVGImage
{
    //see https://www.w3schools.com/graphics/svg_examples.asp
    //more examples: https://commons.wikimedia.org/wiki/SVG_examples
    //https://developer.mozilla.org/ru/docs/Web/SVG/Tutorial/%D0%9E%D1%81%D0%BD%D0%BE%D0%B2%D0%BD%D1%8B%D0%B5_%D0%A4%D0%B8%D0%B3%D1%83%D1%80%D1%8B

    private
    $width = 0,
    $height = 0,
    $stroke = "black",
    $stroke_width = "1",
    $stroke_opacity = "1",
    $fill = "none",
    $fill_opacity = "1",
    $current_path = '',
    $content = "",
    $group_started = false,
    $group_attributes = [];

    /* example:
    <svg xmlns="http://www.w3.org/2000/svg" version="1.1">
        <circle
            cx="100"
            cy="75"
            r="100"
            stroke="black"
            stroke-width="2"
            fill="red" />
        <circle
            cx="100"
            cy="80"
            r="40"
            stroke="black"
            stroke-width="2"
            fill="yellow" />
        <circle
            cx="300"
            cy="80"
            r="40"
            stroke="black"
            stroke-width="2"
            fill="yellow" />
    </svg>
    */
    public function __construct($width, $height)
    {
        $this->width = $width;
        $this->height = $height;
    }


    public function border($color, $width = 1, $opacity = 1)
    {
        $this->stroke = $color;
        $this->stroke_width = $width;
        $this->stroke_opacity = $opacity;
        return $this;
    }

    public function fill($color, $opacity = 1)
    {
        $this->fill = $color;
        $this->fill_opacity = $opacity;
        return $this;
    }

    private function attributes_to_string($attributes): string
    {
        $result = '';
        if (!empty($attributes) && is_array($attributes)) {
            foreach ($attributes as $attr_name => $attr_value) {
                $result .= ' ' . $attr_name . '="' . htmlspecialchars($attr_value) . '"';
            }
        }
        return $result;
    }

    private function append_node($tag, $attributes = [], $content = ''): void
    {
        $tag = htmlspecialchars($tag);
        if ($this->group_started && $this->group_attributes) {
            foreach ($attributes as $attr_name => $attr_value) {
                if (isset($this->group_attributes[$attr_name])) {
                    unset($attributes[$attr_name]);
                }
            }
        }

        $tmp = "\n" . ($this->group_started ? '  ' : '')
            . "<" . $tag . $this->attributes_to_string($attributes);

        if (empty($content)) {
            $tmp .= '/>';
        } else {
            $tmp .= '>' . htmlspecialchars($content) . "</{$tag}>";
        }
        $this->content .= $tmp;
    }

    private function open_tag($tag, $attributes)
    {
        if ($tag) {
            $this->content .= "\n<" . htmlspecialchars($tag) . $this->attributes_to_string($attributes) . ">";
        }
        return $this;
    }

    private function close_tag($tag)
    {
        $this->content .= "\n</" . htmlspecialchars($tag) . '>';
        return $this;
    }

    public function start_group($attributes = [])
    {
        //$this->open_tag('g', $this->inject_style($attributes));
        $this->open_tag('g', $attributes);
        $this->group_started = true;
        $this->group_attributes = $attributes;
        return $this;
    }
    public function end_group()
    {
        $this->close_tag('g');
        $this->group_started = false;
        $this->group_attributes = [];
        return $this;
    }

    public function start_link($href, $target_blank = false)
    {
        $attr = ['href' => $href];
        if ($target_blank) {
            $attr['target'] = "_blank";
        }
        $this->open_tag('a', $attr); // or 'xlink:href'
        return $this;
    }
    public function end_link()
    {
        $this->close_tag('a');
        return $this;
    }
    /*
    A link around a shape -->
      <a href="/docs/Web/SVG/Element/circle">
        <circle cx="50" cy="40" r="35"/>
      </a>
    A link around a text -->
        <a href="/docs/Web/SVG/Element/text">
          <text x="50" y="90" text-anchor="middle">
            &lt;circle&gt;
          </text>
        </a>
    */

    public function get_svg(): string
    {
        /*
        <?xml version="1.0" encoding="UTF-8"?>
        <!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
        */

        return '<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" '
            . '"http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">'
            . "\n" . '<svg xmlns="http://www.w3.org/2000/svg" version="1.1"'
            . ' height="' . $this->height . '"'
            . ' width="' . $this->width . '"'
            . '>'
            . $this->content
            . "\n</svg>";
    }

    public function getSvgAsHtml(): string
    {
        return '<img src="data:image/svg;base64,'
            . base64_encode($this->get_svg()) . '"/>';
    }

    private function inject_fill_style($attributes): array
    {
        /*if($this->group_started) {
            return $attributes;
        }*/
        if ($this->fill && $this->fill !== 'none') {
            $attributes['fill'] = $this->fill;
            if ($this->fill_opacity != "" && $this->fill_opacity < 1.0) {
                $attributes['fill-opacity'] = $this->fill_opacity;
            }
        } else {
            $attributes['fill'] = 'none';
        }
        return $attributes;
    }


    private function inject_stroke_style($attributes): array
    {
        /*if($this->group_started) {
            return $attributes;
        }*/
        if ($this->stroke && $this->stroke !== 'none') {
            $attributes['stroke'] = $this->stroke;
            $attributes['stroke-width'] = $this->stroke_width ? $this->stroke_width : 1;
            if ($this->stroke_opacity != "" && $this->stroke_opacity < 1.0) {
                $attributes['stroke-opacity'] = $this->stroke_opacity;
            }
        } else {
            $attributes['stroke'] = 'none';
        }
        return $attributes;
    }


    private function inject_style($attributes): array
    {
        /*if($this->group_started) {
            return $attributes;
        }*/
        return $this->inject_fill_style(
            $this->inject_stroke_style($attributes)
        );
    }

    private function points_to_string($points): string
    {
        $points_list = [];
        foreach ($points as $point) {
            $points_list[] = implode(' ', $point);
        }
        return implode(',', $points_list);
    }

    public function ellipse($x, $y, $radius_x, $radius_y)
    {
        $this->append_node('ellipse', $this->inject_style([
            'cx' => $x,
            'cy' => $y,
            'rx' => $radius_x,
            'ry' => $radius_y,
        ]));
        return $this;
    }

    public function circle($x, $y, $radius)
    {
        $this->append_node('circle', $this->inject_style([
            'cx' => $x,
            'cy' => $y,
            'r' => $radius,
        ]));
        return $this;
    }

    public function line($x1, $y1, $x2, $y2)
    {
        $attr = [
            'x1' => $x1,
            'y1' => $y1,
            'x2' => $x2,
            'y2' => $y2,
        ];
        $this->append_node('line', $this->inject_stroke_style($attr));
        //<line x1="0" y1="0" x2="200" y2="200" style="stroke:rgb(255,0,0);stroke-width:2" />
        /* dotted lines:
        <g fill="none" stroke="black" stroke-width="4">
            <path stroke-dasharray="5,5" d="M5 20 l215 0" />
            <path stroke-dasharray="10,10" d="M5 40 l215 0" />
            <path stroke-dasharray="20,10,5,5,5,10" d="M5 60 l215 0" />
        </g>
         */
        return $this;
    }

    //<polyline points="20,20 40,25 60,40 80,120 120,140 200,180" style="fill:none;stroke:black;stroke-width:3" />
    public function polyline($points = [])
    {
        $this->append_node(
            'polyline',
            $this->inject_style([
                'points' => $this->points_to_string($points),
            ])
        );
        return $this;
    }

    public function poligon($points = [])
    {
        $this->append_node(
            'poligon',
            $this->inject_style([
                'points' => $this->points_to_string($points),
            ])
        );
        //3-side poligon
        //<polygon points="200,10 250,190 160,210" style="fill:lime;stroke:purple;stroke-width:1" />
        //star
        //<polygon points="100,10 40,198 190,78 10,78 160,198" style="fill:lime;stroke:purple;stroke-width:5;fill-rule:nonzero;"/>

        //fill-rule:nonzero -- fill all (1111)
        //fill-rule:evenodd -- fill not (1010)
        return $this;
    }

    public function path_begin($x, $y)
    {
        $this->current_path = "M $x,$y";
        return $this;
    }
    public function path_move_to($x, $y, $absolute = false)
    {
        $this->current_path .= ' ' . ($absolute ? 'M' : 'm') . " $x,$y";
        return $this;
    }
    public function path_hline($x, $absolute = false)
    {
        $this->current_path .= ' ' . ($absolute ? 'H' : 'h') . " $x";
        return $this;
    }
    public function path_vline($y, $absolute = false)
    {
        $this->current_path .= ' ' . ($absolute ? 'V' : 'v') . " $y";
        return $this;
    }
    public function path_line_to($x, $y, $absolute = false)
    {
        $this->current_path .= ' ' . ($absolute ? 'L' : 'l') . " $x,$y";
        return $this;
    }
    public function path_line_to_begin()
    {
        $this->current_path .= ' z';
        return $this;
    }
    public function path_end()
    {
        $this->append_node(
            'path',
            $this->inject_style([
                'd' => $this->current_path,
            ])
        );
        $this->current_path = '';
        //<path d="M150 0 L75 200 L225 200 Z" />
        //<path id="lineAB" d="M 100 350 l 150 -300" stroke="red" stroke-width="3" fill="none" />
        return $this;
    }
    /*
    d: команды, объединённые вместе в одну строку и определяющие путь, который нужно нарисовать.
    Каждая команда состоит из буквы, следующей за некоторыми числами, которые представляют параметры команды.
    SVG определяет 6 типов команд пути для всех 20 команд:
    MoveTo: M, m
    LineTo: L, l, H, h, V, v
    Cubic Bézier Curve: C, c, S, s
    Quadratic Bézier Curve: Q, q, T, t
    Elliptical Arc Curve: A, a
    ClosePath: Z, z

    Команды чувствительны к регистру; команда верхнего регистра указывает свои аргументы как абсолютные позиции,
    тогда как команда нижнего регистра указывает точки относительно текущей позиции.

    Всегда можно указать отрицательное значение в качестве аргумента для команды:
     * отрицательные углы будут вращаться против часовой стрелки,
     * абсолютные позиции x и y будут приниматься за отрицательные координаты,
     * отрицательные относительные значения x будут перемещаться влево,
     * а отрицательные относительные значения y будут двигаться вверх.
     * *  */

    public function rectangle($x, $y, $w, $h, $rounded_x = null, $rounded_y = null)
    {
        $attributes = [
            'x' => $x,
            'y' => $y,
            'width' => $w,
            'height' => $h,
        ];
        if ($rounded_x) {
            $attributes['rx'] = $rounded_x;
        }
        if ($rounded_y) {
            $attributes['ry'] = $rounded_y;
        }
        $this->append_node(
            'rect',
            $this->inject_style($attributes)
        );
        /*
        <svg width="400" height="180">
          <rect x="50" y="20" rx="20" ry="60" width="150" height="150" style="fill:red;stroke:black;stroke-width:5;opacity:0.5" />
        </svg>
        */
        return $this;
    }

    public function text($x, $y, $text, $fsize = 15, $ffamily = 'sans-serif', $anchor = '')
    {
        $attr = [
            'x' => $x,
            'y' => $y,
            'font-size' => $fsize,
            'stroke' => 'none',
        ];
        if ($ffamily) {
            $attr['font-family'] = $ffamily;
        }
        if ($anchor) {
            $attr['text-anchor'] = $anchor; //inside link, ex.: 'middle'
        }

        $this->append_node(
            'text',
            $this->inject_fill_style($attr),
            $text
        );
        /*
        <text x="0" y="15" fill="red">I love SVG!</text>
        <text x="0" y="15" fill="red" transform="rotate(30 20,40)">I love SVG</text>
        <text x="10" y="20" style="fill:red;">Several lines:
            <tspan x="10" y="45">First line.</tspan>
            <tspan x="10" y="70">Second line.</tspan>
        </text>
        */
        return $this;
    }

    public function bold_text($x, $y, $text, $fsize = 20, $ffamily = 'sans-serif', $anchor = '')
    {
        $attr = [
            'x' => $x,
            'y' => $y,
            'font-size' => $fsize,
            'stroke' => $this->fill,
            'stroke-width' => 1,
        ];

        if ($this->fill_opacity != "" && $this->stroke_opacity < 1.0) {
            $attr['stroke-opacity'] = $this->fill_opacity;
        }
        if ($ffamily) {
            $attr['font-family'] = $ffamily;
        }
        if ($anchor) {
            $attr['text-anchor'] = $anchor; //inside link, ex.: 'middle'
        }

        $this->append_node(
            'text',
            $this->inject_fill_style($attr),
            $text
        );
        /*
        <text x="0" y="15" fill="red">I love SVG!</text>
        <text x="0" y="15" fill="red" transform="rotate(30 20,40)">I love SVG</text>
        <text x="10" y="20" style="fill:red;">Several lines:
            <tspan x="10" y="45">First line.</tspan>
            <tspan x="10" y="70">Second line.</tspan>
        </text>
        */
        return $this;
    }

    /*
    text as link:
    <a xlink:href="https://www.w3schools.com/graphics/" target="_blank">
        <text x="0" y="15" fill="red">I love SVG!</text>
    </a>
     *
    group of 3 points:
    <g stroke="black" stroke-width="3" fill="black">
        <circle id="pointA" cx="100" cy="350" r="3" />
        <circle id="pointB" cx="250" cy="50" r="3" />
        <circle id="pointC" cx="400" cy="350" r="3" />
    </g>

    group of text labels:
    <g font-size="30" font-family="sans-serif" fill="black" stroke="none" text-anchor="middle">
        <text x="100" y="350" dx="-30">A</text>
        <text x="250" y="50" dy="-10">B</text>
        <text x="400" y="350" dx="30">C</text>
    </g>
    */

    public function shape_to_path($x, $y, $shape, $points_per_meters = 30)
    {
        $this->path_begin($x * $points_per_meters, $y * $points_per_meters);
        foreach ($shape as $point) {
            if (!isset($point['x'])) {
                $this->path_vline($point['y'] * $points_per_meters);
            } elseif (!isset($point['y'])) {
                $this->path_hline($point['x'] * $points_per_meters);
            } else {
                $this->path_line_to(
                    $point['x'] * $points_per_meters,
                    $point['y'] * $points_per_meters
                );
            }
        }
        $this->path_line_to_begin()->path_end();
        return $this;
    }

    public function shape_to_3dpath($x, $y, $shape, $points_per_meters = 30, $z_index = 0)
    {
        $x *= $points_per_meters;
        $y *= $points_per_meters;
        //todo correct start point
        $this->path_begin($x, $y);
        foreach ($shape as $point) {
            $dx = isset($point['x']) ? $point['x'] : 0;
            $dy = isset($point['y']) ? $point['y'] : 0;
            $dx *= $points_per_meters;
            $dy *= $points_per_meters;
            //todo correct point
            $this->path_line_to($dx, $dy);
        }
        $this->path_line_to_begin()->path_end();
        return $this;
    }

}

/*/test
$svg = (new SVGImage(200, 200))->border('black', 2)
        ->fill('none')->circle(100, 100, 50)
        ->fill('yellow', 0.9)->ellipse(120, 100, 50, 70)
        ->fill('blue')->text(10, 70, 'JHGJGJGIUhkhkjh', 30)
        ->border('red')->line(0, 0, 200, 200)
        ->border('green', 2)->line(0, 10, 190, 200)
        ->border('grey', 3)->line(0, 20, 180, 200)
    ->get_svg();
file_put_contents(__DIR__ . '/test.svg', $svg);
*/
