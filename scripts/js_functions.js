/**
 * Created by bacchus on 6/11/14.
 */

function init() {

    $('.barcode_input').unbind("keyup", car_ret_add_bc);
    $('.barcode_input').keyup(car_ret_add_bc);

}

function car_ret_add_bc(e) {
    var evt = e || window.event
    // Check if next sibling has a value of "" in its textfield.  If so, set focus to that instead of adding another field.

    var row = $(this).parent().parent();

    // If return carriage is present in input (scanner done with input)...
    if(evt.keyCode == 17 || evt.keyCode == 13) {
        // Then ... if there are no empty inputs below us, add a new one
        if (row.index() == row.siblings().length) {
            addBarcodeInput();
        }
        // If there are empty inputs below us, go to the next one and set focus.
        else {
            var sib = row.next().find('.barcode_input');
            sib.focus();
        }
    }
}


function addBarcodeInput() {

    /*
     <tr>
        <td>
            <input type="text" value="TEST1">
        </td>
        <td>
            <input type="checkbox" value="0">
        </td>
     </tr>
     */

    var barcodes_tbl = document.getElementById('barcodes_table');

    var new_table_row = document.createElement('tr');

    var new_table_textbox_cell = document.createElement('td');
    var new_table_textbox_cell_input = document.createElement('input');

    var new_table_checkbox_cell = document.createElement('td');
    var new_table_checkbox_cell_checkbox = document.createElement('input');

    /* This hidden input value is neccesary to carry the value of an unchecked checkbox upon submission */
    var new_table_hidden_cb_value_input = document.createElement('input');

    new_table_textbox_cell_input.type = "text";
    new_table_textbox_cell_input.value = "";
    new_table_textbox_cell_input.name = "barcodes[]";
    new_table_textbox_cell_input.setAttribute("class", "barcode_input");

    new_table_checkbox_cell_checkbox.type = "checkbox";
    new_table_checkbox_cell_checkbox.name = "print_pocket_label_cb";
    new_table_checkbox_cell_checkbox.value = "no";
    new_table_checkbox_cell_checkbox.onclick = setCheckboxValue;

    new_table_hidden_cb_value_input.type = "hidden";
    new_table_hidden_cb_value_input.value = 0;
    new_table_hidden_cb_value_input.name = "print_pocket_label[]";

    new_table_textbox_cell.appendChild(new_table_textbox_cell_input);
    new_table_checkbox_cell.appendChild(new_table_checkbox_cell_checkbox);
    new_table_checkbox_cell.appendChild(new_table_hidden_cb_value_input);

    new_table_row.appendChild(new_table_textbox_cell);
    new_table_row.appendChild(new_table_checkbox_cell);

    barcodes_tbl.appendChild(new_table_row);

    altRows('barcodes_table');

    new_table_textbox_cell_input.focus();

    init();

}

function setCheckboxValue() {

    hidden_sibling = this.nextSibling;

    if (hidden_sibling.value == 0 || hidden_sibling.value == "") {
        this.value = "yes";
        hidden_sibling.value = 1;
    }
    else {
        this.value = "no";
        hidden_sibling.value = 0;
    }
}


function altRows(id){
    if(document.getElementsByTagName){

        var table = document.getElementById(id);
        var rows = table.getElementsByTagName("tr");

        for(i = 0; i < rows.length; i++){
            if(i % 2 == 0){
                rows[i].className = "evenrowcolor";
            }else{
                rows[i].className = "oddrowcolor";
            }
        }
    }
}