/**
 * Created by bacchus on 6/11/14.
 */
$(document).ready(function() {
    // modifies 'Pocket Label?' checkbox upon
    $('#barcodes_table').on('change', '.print_pocket_box', setCheckboxValue);
    $('#barcodes_table').on('keydown', '.barcode_input', carriageReturnAddBarcode);
});

function carriageReturnAddBarcode(e) {
    var evt = e || window.event;
    // Check if next sibling has a value of "" in its textfield.  If so, set focus to that instead of adding another field.

    var row = $(this).parent().parent();

    // If return carriage is present in input (scanner done with input)...
    if(evt.keyCode == 13) {
        evt.preventDefault();
        // Then ... if there are no empty inputs below us, add a new one
        if (row.index() == row.siblings().length) {
            addBarcodeInput();
            //return false;
        }
        // If there are empty inputs below us, go to the next one and set focus.
        else {
            var sib = row.next().find('.barcode_input');
            sib.focus();
        }
    }
}

function addBarcodeInput() {

    var barcodesTable = document.getElementById('barcodes_table');

    var barcodesTableBody = barcodesTable.getElementsByTagName('tbody')[0];

    var newTableRow = document.createElement('tr');

    var newTableTextboxCell = document.createElement('td');
    var newTableTextboxCellInput = document.createElement('input');

    var newTableCheckboxCell = document.createElement('td');
    var newTableCheckboxCellCheckbox = document.createElement('input');

    /* This hidden input value is neccesary to carry the value of an unchecked checkbox upon submission */
    var newTableHiddenCheckboxValueInput = document.createElement('input');

    newTableTextboxCellInput.type = "text";
    newTableTextboxCellInput.value = "";
    newTableTextboxCellInput.name = "barcodes[]";
    newTableTextboxCellInput.setAttribute("class", "barcode_input");

    newTableCheckboxCellCheckbox.type = "checkbox";
    newTableCheckboxCellCheckbox.name = "print_pocket_label_cb";
    newTableCheckboxCellCheckbox.value = "no";
    newTableCheckboxCellCheckbox.className = "print_pocket_box";

    newTableHiddenCheckboxValueInput.type = "hidden";
    newTableHiddenCheckboxValueInput.value = 0;
    newTableHiddenCheckboxValueInput.name = "print_pocket_label[]";

    newTableTextboxCell.appendChild(newTableTextboxCellInput);
    newTableCheckboxCell.appendChild(newTableCheckboxCellCheckbox);
    newTableCheckboxCell.appendChild(newTableHiddenCheckboxValueInput);

    newTableRow.appendChild(newTableTextboxCell);
    newTableRow.appendChild(newTableCheckboxCell);

    barcodesTableBody.appendChild(newTableRow);

    newTableTextboxCellInput.focus();
}

function setCheckboxValue() {
    var hiddenSibling = $(this).siblings('input[name="print_pocket_label[]"]');

    if (hiddenSibling.val() == 0 || hiddenSibling.val() == "") {
        this.value = "yes";
        hiddenSibling.val(1);
    }
    else {
        this.value = "no";
        hiddenSibling.val(0);
    }
}

function toggleSelected(source) {
    $('input[name="print_pocket_label_cb"]').each(function(){
        var hiddenSibling = $(this).siblings('input[name="print_pocket_label[]"]');
        $(this).prop('checked', source.checked);
        if (source.checked) {
            $(this).val('yes');
            hiddenSibling.val(1);
        } else {
            $(this).val('no');
            hiddenSibling.val(0);
        }
    });
}