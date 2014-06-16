<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
<script src="../jquery/jquery-ui-1.10.4/js/jquery-ui-1.10.4.custom.min.js"></script>
<script src="../jquery/printarea/jquery.PrintArea.js" type="text/JavaScript" language="javascript"></script>
<script src="../scripts/js_functions.js" language="JavaScript"></script>
<link rel="stylesheet" href="../css/wms.css">
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


if (isset($_REQUEST["label_start"]) && is_numeric($_REQUEST["label_start"]) &&  $_REQUEST["label_start"] > 0 && $_REQUEST["label_start"] < 9) {
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
        $table_row .= "<tr>\n";
        $table_row .= "<td class=\"cnum\">{$print_array[$x][0]}</td>\n<td class=\"pocket\">{$print_array[$x][1]}</td>\n";
    if (isset($output_columns) && $output_columns == "2" && array_key_exists($x + 1, $print_array)) {
 //       print_r("3");
        $x++;
        $table_row .= "<td class=\"spacer_cell\">&nbsp;</td>\n";
        $table_row .= "<td class=\"cnum\">{$print_array[$x][0]}</td>\n<td class=\"pocket\">{$print_array[$x][1]}</td>\n";
    }
    $table_row .= "</tr>\n";
    $table_row .= "<tr>\n<td class=\"spacer_cell_vert\" colspan=\"5\">&nbsp;</td>\n</tr>\n";
    // Test whether we have eight labels for 2-col printing, or are on the last label
    if ((($x + 1) % 8 == 0) || $x + 1 == count($print_array)) {
        $table .= "<table class=\"label_table\">\n{$table_row}</table>\n";
        $table_row = "";
    }
}



?>

<div id="link-area" class="print_label_button">
    <div id="link-print"><div class="button_left_div"<a id="print_button" href="#print"><img src="../images/icon-print.png" /><br/>Print Labels</a></div><div class="button_right_div"><input type="radio" name="printer" id="printer" value="dot_matrix" /> Okidata Dot Matrix<br /><input type="radio" name="printer" id="printer" value="laser" /> Laser Printer<br /><input type="radio" name="printer" id="printer" value="dymo" /> Dymo Printer</div></div>
</div>

<?php
    print "$table";
?>

<script>
    $("a#print_button").click(function(e){
        e.preventDefault();
        $(".label_table").printArea( { mode: "iframe" } );

    });
</script>