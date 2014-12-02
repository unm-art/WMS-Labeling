<?php

session_start();

if (isset($_SESSION['saved_form']) === true) {
    $savedForm = $_SESSION['saved_form'];
}

 /*
     * This variable defines the number of individual
     * labels on your sheet of two column label stock.
     *  This is here so you can prevent waste on partially
     * used label stock.
 */

$labelStartOptions = 8;


?>
<link href="jquery/jeditable/jquery.jeditable.js" />
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
if (isset($savedForm) === true) {
    for ($i = 0; $i < $labelStartOptions; $i++) {
        if ($i === ($savedForm['label_start'] - 1)) {
            $options[] = '<option
                            value="'.$savedForm['label_start'].'"
                            selected="selected">'.$savedForm['label_start'].'</option>';
        } else {
            $optNum    = ($i + 1);
            $options[] = '<option value="'.$optNum.'">'.$optNum.'</option>';
        }
    }
} else {
    for ($i = 0; $i < $labelStartOptions; $i++) {
        if ($i === 0) {
            $options[] = '<option value="1" selected="selected">1</option>';
        } else {
            $optNum    = ($i + 1);
            $options[] = '<option value="'.$optNum.'">'.$optNum.'</option>';
        }
    }
}//end if
echo implode($options);
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
if (isset($savedForm['barcodes']) === true) {
    $count = count($savedForm['barcodes']);
    $initialBarcodeTable = array();
    for ($i = 0; $i < $count; $i++) {
        $barcode = $savedForm['barcodes'][$i];
        if (empty($barcode) === true || $barcode === '') {
            continue;
        }

        $printPocketLabel = $savedForm['print_pocket_label'][$i];
        if ($printPocketLabel === '0') {
            $printPocketLabelCheckbox = 'no';
            $checked = '';
        } else {
            $printPocketLabelCheckbox = 'yes';
            $checked = 'checked';
        }

        $initialBarcodeTable[]
            = '<tr>
                <td>
                    <input
                        type="text"
                        name="barcodes[]"
                        class="barcode_input"
                        value="'.$savedForm['barcodes'][$i].'">
                </td>
                <td>
                    <input
                        type="checkbox"
                        name="print_pocket_label_cb"
                        class="print_pocket_box"
                        value="'.$printPocketLabelCheckbox.'"
                        '.$checked.'>
                    <input
                        type="hidden"
                        value="'.$savedForm['print_pocket_label'][$i].'"
                        name="print_pocket_label[]">
                </td>
             </tr>';
    }//end for
}//end if
if (empty($initialBarcodeTable) === false) {
    echo implode($initialBarcodeTable);
}
?>

            </table>

            <script>

                /* Initial call to addBarcodeInput to add first barcode. */
                addBarcodeInput();
                /* Init to apply javascript event handlers */
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