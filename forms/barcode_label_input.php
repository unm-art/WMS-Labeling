
<script>

    /* Initial call to addBarcodeInput to add first barcode and apply style. */
    addBarcodeInput();
    init();

</script>

<form name="barcode_scan_form" id="barcode_scan_form" action="forms/barcode_label_output.php" method="POST">
    <div class="barcodes_forms_container">

        <h1 class="form_header">Enter Barcode Number</h1>

<!--
        <div class="output_columns_radio_container">
            <p>Would you like to print to <b>one</b> or <b>two</b> column label stock?</p>
            <input type="radio" name="output_columns" value="1" checked="checked">One
            <input type="radio" name="output_columns" value="2">Two
        </div>
-->
        <div id="barcodes_container">

            <table id="output_columns_table" class="output_columns_table">
                <tr>
                    <td>Would you like to print to <strong>one</strong> or <strong>two</strong> column label stock?</td>
                    <td>
                        <input type="radio" name="output_columns" value="1" checked="checked">One
                        <input type="radio" name="output_columns" value="2">Two
                    </td>
                </tr>
                <tr>
                    <td>Which label field do you wish to start printing on?</td>
                    <td>
                        <select id="label_start" name="label_start">
                            <option value="1" selected="selected">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                            <option value="6">6</option>
                            <option value="7">7</option>
                            <option value="8">8</option>
                        </select>
                    </td>
                </tr>
            </table>

            <table id="barcodes_table" class="barcodes_table">
                <tr>
                    <th>Barcode No.</th><th>Pocket Label?</th>
                </tr>

                <!-- generated rows of barcode inputs go here -->

            </table>

        </div>

        <div class="button_container">
            <input class="btn_add input_center" type="button" value="+1" onClick="addBarcodeInput()">
            <input class="btn_submit input_center" type="submit" value="Make Labels">
        </div>
    </div>
</form>