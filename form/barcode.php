<?php
include('../config/config.php');
include('../inc/header.php') ;
?>
    <script src="../js/barcodes.js"></script>
    <div class="form_container">
        <h1>Enter Barcode(s)</h1>
        <div class="minmax_icon"></div>

        <form name="barcode_scan_form" id="barcode_scan_form" action="#" method="POST">
            <div class="singlecol" style="max-width:500px;">
                <div>
                    <label>Which label field do you wish to start printing on?</label>

                    <select id="label_start" name="label_start">
                        <?php
                        $options = array();
                        if (isset($savedForm) === true) {
                            for ($i = 0; $i < $labelStartOptions; $i++) {
                                $optNum    = $i + 1;
                                $options[] = '<option value="'.$optNum.'">'.$optNum.'</option>';
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
                </div>
                <div>
                    <table id="barcodes_table">
                        <thead>
                        <tr>
                            <th>Barcode No.</th><th>Pocket Label?</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        for ($i = 0; $i < $labelStartOptions; $i++) {
                            ?>
                            <tr>
                                <td>
                                    <input type="text" name="barcodes[]" class="barcode_input" value="">
                                </td>
                                <td>
                                    <input type="checkbox" name="print_pocket_label_cb" class="print_pocket_box">
                                    <input type="hidden" value="0" name="print_pocket_label[]">
                                </td>
                            </tr>
                        <?php
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
                <div style="overflow: auto;">
                    <input type="button" class="btn_submit" style="float:left;" value="Add More" onClick="addBarcodeInput()">
                    <a href="#" id="clearForm">Clear Form</a>
                    <input id="selectAll" style="float:right;" type="checkbox" onClick="toggleSelected(this)" /><label for="selectAll" style="float:right;">Select All/None</label>
                </div>
                <input class="btn_submit" type="submit" value="Make Labels">
            </div>
        </form>
    </div>

    <div class="loading_gif"><img src="/ilsarchive/img/loading.gif" ></div>
    <div id="results"><!-- content will be loaded here --></div>
<?php include('../inc/footer.php') ?>