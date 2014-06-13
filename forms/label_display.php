<?php

if (isset($_POST["col_num"]) && $_POST["col_num"] == "2") {
    $col_num = "2";
} else {
    $col_num = "1";
}

if (isset($_POST["nums"]) && $_POST["nums"] != "") {
    $num_array = $_POST["nums"];
    if (isset($_POST["label_start"]) && is_numeric($_POST["label_start"]) && $_POST["label_start"] < count($num_array) && $_POST["label_start"] > 0 && $_POST["label_start"] < 9) {
        $print_position = $_POST["label_start"];
    } else {
        $print_position = "0";
    }
    $print_array = array();
    include_once('quicklabels.php');
    for($x = 0; $x < count($num_array); $x++) {
        $print_array[] = quicklabels($num_array[$x][0], $num_array[$x][1]);
    }
    $table_row = "";
    for ($x = 0; $x < $print_position; $x++) {
        $table_row .= "<tr>\n";
        $table_row .= "<td class=\"cnum\">&nbsp;</td>\n<td class=\"pocket\">&nbsp;</td>\n";
        if (isset($col_num) && $col_num == "2" && array_key_exists($x + 1, $print_array)) {
            $x++;
            $table_row .= "<td class=\"spacer_cell\">&nbsp;</td>\n";
            $table_row .= "<td class=\"cnum\">&nbsp;</td>\n<td class=\"pocket\">&nbsp;</td>\n";
        }
        if (isset($col_num) && $col_num == "2" && $x % 2 != 0) {
            $table_row .= "</tr>\n";
            $table_row .= "<tr>\n<td class=\"spacer_cell_vert\" colspan=\"5\">&nbsp;</td>\n</tr>\n";
        }
        
    }
    for ($x = $print_position; $x < count($print_array); $x++) {
        if (isset($col_num) && $col_num == "2" && $x % 2 != 0) {
            $table_row .= "<tr>\n";
            $table_row .= "<td class=\"cnum\">{$print_array[$x][0]}</td>\n<td class=\"pocket\">{$print_array[$x][1]}</td>\n";
        }
        if (isset($col_num) && $col_num == "2" && array_key_exists($x + 1, $print_array)) {
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
    <div id="link-print"><a id="print_button" href="#print"><img src="images/icon-print.png" /><br/>Print Labels</a></div>
  </div>

<?php

    print "$table";
}

?>

<script>
    $("a#print_button").click(function(e){
        e.preventDefault();
        $(".label_table").printArea( { mode: "iframe" } );
    
});
</script>