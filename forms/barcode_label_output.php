<script src="jquery/printarea/jquery.PrintArea.js" type="text/JavaScript" language="javascript"></script>
<script src="jquery/printElement/jquery.printElement.js" type="text/JavaScript" language="javascript"></script>
<script src="http://code.jquery.com/jquery-migrate-1.0.0.js"></script>
<?php

//var_dump($_REQUEST);

if(isset($_REQUEST["output_columns"]) && $_REQUEST["output_columns"] == "2") {
    $output_columns = "2";
}
else {
    $output_columns = "1";
}

if(isset($_REQUEST["barcodes"]) && $_REQUEST["barcodes"] != "") {
    // array()
    $barcodes = $_REQUEST["barcodes"];
    $tmp = "";
    for($i = 0; $i < count($barcodes); $i++) {
        $tmp .= $barcodes[$i];
    }
    //var_dump(count($barcodes[0]));
    //var_dump($barcodes[0]);
}
else {
    // Error
}

if(isset($_REQUEST["print_pocket_label"]) && $_REQUEST["print_pocket_label"] != "") {
    // array()
    $print_pocket_label = $_REQUEST["print_pocket_label"];
}
else {
    // Error
}

if (isset($_REQUEST["label_start"]) && is_numeric($_REQUEST["label_start"]) && $_REQUEST["label_start"] > 0 && $_REQUEST["label_start"] < 9 && $output_columns == "2") {
    $label_start = $_REQUEST["label_start"];
}

$barcode_count = count($barcodes);


$print_array = array();

include_once('../lib/quicklabels.php');

//var_dump(quicklabels($barcodes[0], "0"));

for($x = 0; $x < $barcode_count; $x++) {
    if ($barcodes[$x] != "") {
        $print_array[] = quicklabels($barcodes[$x], $print_pocket_label[$x]);
    }
//    var_dump($print_array[$x]);
}

// Add empty rows to print_array to start print on specified label
if (isset($label_start) && $label_start != "") {
    for ($x = 1; $x < $label_start; $x++) {
        array_unshift($print_array, array("&nbsp;", "&nbsp;"));
    }
}

$table_row = "";
$table = "";

for ($x = 0; $x < count($print_array); $x++) {
//    print_r("1");
//    var_dump($output_columns);
        $table_row .= "<tr>\n<td class=\"left_spacer_cell\">&nbsp;</td>\n";
        $table_row .= "<td class=\"cnum\">{$print_array[$x][0]}</td>\n<td class=\"pocket\">{$print_array[$x][1]}</td>\n";
    if (isset($output_columns) && $output_columns == "2") {
 //       print_r("3");
        $x++;
        if (isset($print_array[$x][0]) && $print_array[$x][0] != "") {
            $cnumval = $print_array[$x][0];
        } else {
            $cnumval = "&nbsp;";
        }
        if (isset($print_array[$x][1]) && $print_array[$x][1] != "") {
            $pocketval = $print_array[$x][1];
        } else {
            $pocketval = "&nbsp;";
        }
        $table_row .= "<td class=\"center_spacer_cell\">&nbsp;</td>\n";
        $table_row .= "<td class=\"cnum\">{$cnumval}</td>\n<td class=\"pocket\">{$pocketval}</td>\n";
    }
    $table_row .= "</tr>\n";
    $table_row .= "<tr>\n<td class=\"spacer_cell_vert\" colspan=\"5\">&nbsp;</td>\n</tr>\n";
    // Test whether we have eight labels for 2-col printing, or are on the last label
    if ((($x + 1) % 8 == 0) || $x + 1 >= count($print_array)) {
        $table .= "<table class=\"label_table\">\n{$table_row}</table>\n";
        $table_row = "";
    }
}

if (isset($output_columns) && $output_columns == "1") {
    reset($print_array);
    $text = "";
    for ($x = 0; $x < count($print_array); $x++) {
        $call_num_array = explode("<br />", $print_array[$x][0]);
        $title_array = makeTitleArray($print_array[$x][1]);
        for ($y = 0; $y < 10; $y++) {
            if (isset($call_num_array[$y]) && $call_num_array[$y] != "") {
                $call_num_right_pad = str_pad($call_num_array[$y], 12, " ", STR_PAD_RIGHT);
                $text .= "$call_num_right_pad";
            }
            if (isset($title_array[$y]) && $title_array[$y] != "") {
                $text .= $title_array[$y] . "<br/>\n";
            } else {
                $text .= "<br/>\n";
            }
        }
    }
    $text = str_replace(" ", "&nbsp;", $text);
    $oki_text = "\n<div class=\"invisible\" id=\"textPrint\">\n{$text}</div>\n";
}

function makeTitleArray($input) {
    $title = explode("<br />", $input);
    $return = array();
    for ($x = 0; $x < count($title); $x++) {
        if (isset($title[$x]) && $title[$x] != "") {
            $title_split = explode("\n", wordwrap($title[$x], 30, "\n"));
            $return = array_merge($return, $title_split);
        }
    }
    return $return;
}


?>

<div id="link-area" class="print_label_button">
    <div id="link-print">
        <div class="button_left_div"><a id="print_button" href="#print"><img src="images/icon-print.png" /><br/>Print Labels</a>
        </div>
        <div class="button_right_div">
            <input type="radio" name="printer" id="printer1" value="dot_matrix" /> Okidata Dot Matrix<br />
            <a href="#" id="okiprint">Print</a><br />
            <input type="radio" name="printer" id="printer2" value="laser" /> Laser Printer<br />
            <input type="radio" name="printer" id="printer3" value="dymo" /> Dymo Printer
        </div>
        <div class="clear"></div>
    </div>
</div>

<?php
    print "<div id=\"table_div\">$table</div>";
    print "$oki_text";
?>

<script>
    $("a#okiprint").click(function(e) {
        e.preventDefault();
        $("div.invisible").printElement({ overrideElementCSS: true, styleToAdd: "font-family:'Courier New';font-size: 13px;" });
    });

    $("a#print_button").click(function(e){
        e.preventDefault();
        printer_css = $("input[name=printer]:checked").val();
        if ($("div.invisible").html()) {
            $(".invisible").printArea( { mode: "popup", retainAttr: [] } );
        } else if (typeof printer_css !== "undefined") {
            $("#table_div").printArea( { mode: "popup", extraCss: 'css/'+printer_css+'.css' } );
        } else {
            alert("Type of printer must be selected.");
        }

    });
</script>