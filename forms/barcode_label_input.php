<?php
session_start();
if (isset($_SESSION['saved_form']))
  $saved_form = $_SESSION['saved_form'];
//Clear session here
//session_unset();
$label_start_options = 8;
if (!isset($saved_form)) {
    $saved_form = array(
        "barcodes" => array(
            "A14403408420", "A14403408420", "A14403408420", "A14403408420"
        ),

        "print_pocket_label" => array(
            0, 1, 1, 0
        ),

        "label_start" => 1
    );
}

?>

<form name="barcode_scan_form" id="barcode_scan_form" action="forms/barcode_label_output.php" method="POST">
    <div class="barcodes_forms_container">

        <h1 class="form_header">Enter Barcode Number</h1>

        <div id="barcodes_container">

            <table id="output_columns_table" class="output_columns_table">
                <tr>
                    <td>Which label field do you wish to start printing on?</td>
                    <td>
                        <select id="label_start" name="label_start">
                            <?php
                            $options = array();
                            if (isset($saved_form)){
                                for ($i = 0; $i < $label_start_options; $i++){
                                    if($i == ($saved_form["label_start"] - 1)) {
                                        $options[] = "<option value=\"".$saved_form["label_start"]."\" selected=\"selected\">".$saved_form["label_start"]."</option>";
                                    }
                                    else {
                                        $opt_no = $i+1;
                                        $options[] = "<option value=\"".$opt_no."\">".$opt_no."</option>";
                                    }

                                }

                            }
                            else {
                                for ($i = 0; $i < $label_start_options; $i++) {
                                    if($i == 0) {
                                        $options[] = "<option value=\"1\" selected=\"selected\">1</option>";
                                    }
                                    else {
                                        $opt_no = $i+1;
                                        $options[] = "<option value=\"".$opt_no."\">".$opt_no."</option>";
                                    }

                                }
                            }
                            print implode($options);
                            ?>
                        </select>
                    </td>
                </tr>
            </table>

            <table id="barcodes_table" class="barcodes_table">
                <tr>
                    <th>Barcode No.</th><th>Pocket Label?</th>
                </tr>
                <!-- generated rows of barcode inputs go here -->

                <!--
                First we use PHP to generate the elements that may (or may not) already exist
                (in the form of an 'edit' or 'back' submission from barcode_label_output.php
                -->
                <?php
                if (isset($saved_form['barcodes'])){
                    $count = count($saved_form['barcodes']);
                    $initial_barcode_table = array();
                    for ($i = 0; $i < $count; $i++) {
                        $barcode = $saved_form['barcodes'][$i];
                        if (empty($barcode) || $barcode == "")
                          continue;
                        $print_pocket_label = $saved_form['print_pocket_label'][$i];
                        $print_pocket_label_checkbox = $print_pocket_label == 0 ? "no" : "yes";
                        $checked = $print_pocket_label == 0 ? "" : "checked";
                        $initial_barcode_table[] =
                        "<tr>
                            <td>
                                <input type=\"text\" name=\"barcodes[]\" class=\"barcode_input\" value=\"".$saved_form['barcodes'][$i]."\">
                            </td>
                            <td>
                                <input type=\"checkbox\" name=\"print_pocket_label_cb\" class=\"print_pocket_box\" value=\"".$print_pocket_label_checkbox."\"  ".$checked." >
                                <input type=\"hidden\" value=\"".$saved_form['print_pocket_label'][$i]."\" name=\"print_pocket_label[]\">
                            </td>
                        </tr>";
                    }
                }
                $tmp = implode($initial_barcode_table);
                print $tmp;
                ?>

            </table>

            <script>

                /* Initial call to addBarcodeInput to add first barcode and apply style. */
                addBarcodeInput();
                init();

            </script>

            <div id="select_all_checkbox">
                <br>
                Select/Unselect All  <input type="checkbox" onClick="toggleSelected(this)" /><br/>
            </div>

        </div>

        <div class="button_container">
            <input class="btn_add input_center" type="button" value="+1" onClick="addBarcodeInput()">
            <input class="btn_submit input_center" type="submit" value="Make Labels">
        </div>
    </div>
</form>