<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
<script src="../jquery/jquery-ui-1.10.4/js/jquery-ui-1.10.4.custom.min.js"></script>
<script src="../jquery/printarea/jquery.PrintArea.js" type="text/JavaScript" language="javascript"></script>
<script src="../scripts/js_functions.js" language="JavaScript"></script>
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

$barcode_count = count($barcodes);

if (isset($_REQUEST["label_start"]) && is_numeric($_REQUEST["label_start"]) && $_REQUEST["label_start"] < count($num_array) && $_REQUEST["label_start"] > 0 && $_REQUEST["label_start"] < 9) {
    $print_position = $_REQUEST["label_start"];
} else {
    $print_position = "0";
}

$print_array = array();

include_once('../lib/quicklabels.php');

//var_dump(quicklabels($barcodes[0], "0"));

for($x = 0; $x < $barcode_count; $x++) {
    $print_array[] = quicklabels($barcodes[$x], $print_pocket_label[$x]);
    //var_dump($print_array[$x]);
}

$table_row = "";

for ($x = 0; $x < $print_position; $x++) {
    $table_row .= "<tr>\n";
    $table_row .= "<td class=\"cnum\">&nbsp;</td>\n<td class=\"pocket\">&nbsp;</td>\n";
    if (isset($output_columns) && $output_columns == "2" && array_key_exists($x + 1, $print_array)) {
        $x++;
        $table_row .= "<td class=\"spacer_cell\">&nbsp;</td>\n";
        $table_row .= "<td class=\"cnum\">&nbsp;</td>\n<td class=\"pocket\">&nbsp;</td>\n";
    }
    if (isset($output_columns) && $output_columns == "2" && $x % 2 != 0) {
        $table_row .= "</tr>\n";
        $table_row .= "<tr>\n<td class=\"spacer_cell_vert\" colspan=\"5\">&nbsp;</td>\n</tr>\n";
    }

}


for ($x = $print_position; $x < count($print_array); $x++) {
    print_r("1");
    var_dump($output_columns);
    if (isset($output_columns) && $output_columns == "2" && $x % 2 != 0) {
        print_r("2");
        $table_row .= "<tr>\n";
        $table_row .= "<td class=\"cnum\">{$print_array[$x][0]}</td>\n<td class=\"pocket\">{$print_array[$x][1]}</td>\n";
    }
    if (isset($output_columns) && $output_columns == "2" && array_key_exists($x + 1, $print_array)) {
        print_r("3");
        $x++;
        $table_row .= "<td class=\"spacer_cell\">&nbsp;</td>\n";
        $table_row .= "<td class=\"cnum\">{$print_array[$x][0]}</td>\n<td class=\"pocket\">{$print_array[$x][1]}</td>\n";
    }
    $table_row .= "</tr>\n";
    $table_row .= "<tr>\n<td class=\"spacer_cell_vert\" colspan=\"5\">&nbsp;</td>\n</tr>\n";
}

$table = "<table class=\"label_table\">\n{$table_row}</table>\n";

?>

<div id="link-area" class="printhidden">
    <div id="link-print"><a id="print_button" href="#print"><img src="../images/icon-print.png" /><br/>Print Labels</a></div>
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