
<script>
    /* Initial call to addBarcodeInput to add first barcode and apply style. */
    addBarcodeInput();
</script>

<form name="barcode_scan_form" action="forms/label_display.php">
    <div class="barcodes_forms_container">

        <h1 class="form_header">Enter Barcode Number</h1>

        <div class="output_columns_radio_container">
            <p>Would you like to print to <b>one</b> or <b>two</b> column label stock?</p>
            <input type="radio" name="output_columns" value="1" checked="checked">One
            <input type="radio" name="output_columns" value="2">Two
        </div>

        <div id="barcodes_container">

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