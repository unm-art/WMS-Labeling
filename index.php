<style>
    td {
        padding-left: 5px;
        width: 75px;
    }
    .cnum {
        border: 1px solid black;
        width: 75px;
    }
    .pocket {
        border: 1px solid black;
        width: 150px;
    }
</style>
<?php

if (isset($_POST["nums"]) && $_POST["nums"] != "") {
    $num_array = explode(",", $_POST["nums"]);
    $print_array = array();
    include_once('quicklabels.php');
    foreach($num_array as $val) {
        $print_array[] = quicklabels($val, "s", "y");
    }
//    print_r($print_array);
    $table_row = "";
    foreach($print_array as $val => $key) {
        $table_row .= "<tr><td class=\"cnum\">$key[0]</td><td class=\"pocket\">$key[1]</td></tr>\n";
    }
    $table = "<table>\n$table_row</table>\n";
    print "$table";
} else { 

?>

<form name="bcodes_form" id="bcodes_form" action="" method="post">
    Enter barcodes separated by commas:
    <textarea id="nums" name="nums"></textarea>
    <input type="submit" value="Submit" />
</form>

<?php
}