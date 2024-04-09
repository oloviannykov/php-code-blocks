<?php
use ReportPageMaker;

//todo: set the variables from parameters
$template_path = '...';
$report_title = '...';
?>
<!doctype html>
<html lang="en" class="no-js">

<head>
    <meta charset="utf-8">
    <title><?= $report_title ?></title>
    <style>
        @page {
            size: A4;
            margin: 0;
        }

        body {
            margin: 0;
            padding: 0;
            max-width: 500mm;
            min-width: 100mm;
            box-sizing: border-box;
            margin: 0;
            border: none;
        }

        a::after {
            /* browser does this: content: " (" attr(href) ")";*/
            content: "" !important;
        }

        /*

The CSS3 properties break-before and break-after allow you control how page, column,
or region breaks behave before and after an element. Support is excellent, but older
browsers may use the similar page-break-before and page-break-after properties.
---
The following break-before and break-after values can be used:
auto: the default - a break is permitted but not forced
avoid: avoid a break on the page, column or region
avoid-page: avoid a page break
page: force a page break
always: an alias of page
left: force page breaks so the next is a left page
right: force page breaks so the next is a right page
---
The break-inside (and older page-break-inside) property specifies whether a page break
is permitted inside an element. The commonly supported values:
auto: the default - a break is permitted but not forced
avoid: avoid an inner break where possible
avoid-page: avoid an inner page break where possible
This can be preferable to specifying page breaks, since you can use as little paper
as possible while avoiding page breaks within grouped data such as tables or images:
table, img, svg {
  break-inside: avoid;
}
---
The widows property specifies the minimum number of lines in a block that must be shown
at the top of a page. Imagine a block with five lines of text. The browser wants to make
a page break after line four so the last line appears at the top of the next page.
Setting widows: 3; breaks on or before line two so at least three lines carry over to the next page.
---
The box-decoration-break property controls element borders across pages.
When an element with a border has an inner page break:
slice: the default, splits the layout. The top border is shown on the first page
    and the bottom border is shown on the second page.
clone: replicates the margin, padding, and border. All four borders are shown on both pages.
*/

        .sheet {
            margin: 0;
            overflow: hidden;
            position: relative;
            box-sizing: border-box;
            page-break-after: always;
            padding:
                <?= ReportPageMaker::PADDING_TOP_MM ?>
                mm
                <?= ReportPageMaker::PADDING_RIGHT_MM ?>
                mm
                <?= ReportPageMaker::PADDING_BOTTOM_MM ?>
                mm
                <?= ReportPageMaker::PADDING_LEFT_MM ?>
                mm;
            width: 100%;
        }

        /** Paper sizes **/
        body.A4 .sheet {
            width:
                <?= ReportPageMaker::A4_PORTRAIT_WIDTH_MM ?>
                mm;
            height:
                <?= ReportPageMaker::A4_PORTRAIT_HEIGHT_MM ?>
                mm
        }

        body.A4.landscape .sheet {
            width:
                <?= ReportPageMaker::A4_LANDSCAPE_WIDTH_MM ?>
                mm;
            height:
                <?= ReportPageMaker::A4_LANDSCAPE_HEIGHT_MM ?>
                mm
        }

        /*
body.A3               .sheet { width: 297mm; height: 419mm }
body.A3.landscape     .sheet { width: 420mm; height: 296mm }
body.A5               .sheet { width: 148mm; height: 209mm }
body.A5.landscape     .sheet { width: 210mm; height: 147mm }
body.letter           .sheet { width: 216mm; height: 279mm }
body.letter.landscape .sheet { width: 280mm; height: 215mm }
body.legal            .sheet { width: 216mm; height: 356mm }
body.legal.landscape  .sheet { width: 357mm; height: 215mm }
*/

        /** For screen preview **/
        @media screen {
            body {
                background: #e0e0e0
            }

            .sheet {
                background: white;
                box-shadow: 0 .5mm 2mm rgba(0, 0, 0, .3);
                margin: 5mm auto;
            }
        }

        /** Fix for Chrome issue #273306 - resolved
@media print {
  .sheet {
    width: 100%;
    border: 1px solid grey;
  }
  body.A4, body.A5.landscape { width: 210mm }
  body.A3, body.A4.landscape { width: 297mm }
           body.A3.landscape { width: 420mm }
  body.A5                    { width: 148mm }
  body.letter, body.legal    { width: 216mm }
  body.letter.landscape      { width: 280mm }
  body.legal.landscape       { width: 357mm }
  }*/

        table {
            width: 180mm;
            border-collapse: collapse;
        }

        body.landscape table {
            width: 267mm;
            border-collapse: collapse;
        }

        td {
            padding: 0px;
            vertical-align: top;
            min-width: 20mm;
            min-height: 5mm;
        }

        thead {
            font-weight: bold;
        }

        table,
        th,
        td {
            border: 1px solid black;
        }

        .page_no {
            text-align: right;
            margin-top: 10px;
            font-weight: bold;
        }

        #money_type_summary .money_summary_category_name {
            font-size: 13px;
            font-family: arial, serif;
            max-height: 15px;
            white-space: nowrap;
            overflow: hidden !important;
        }

        #money_type_summary_detailed .money_subtype_name_amount_pair {
            font-size: 10px;
            font-family: arial, serif;
            height: 12px;
            white-space: nowrap;
            overflow: hidden;
        }
    </style>
</head>

<?php include ($template_path); ?>

<script>window.print();</script>

</html>
