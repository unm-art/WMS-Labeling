<?php
session_start();
if (isset($_POST['id'])) {
    $split = explode('_', $_POST['id']);
    //Will be 'cnum' or 'pocket'
    $type = $split[0];
    //Position in the label array
    $labelNum = $split[1];

    if ($type == 'cnum') {
        $labelType = 0;
    }
    else
        $labelType = 1;
    //Re-convert edited text to html
    $value = str_replace( "\n", '<br />', $_POST['value']);
    //Save value in existing session for printing functions
    $_SESSION['printArray'][$labelNum][$labelType] = $value;
    //Returns value to the html output. Add divs to keep padding for readability
    print '<div>'. $value . '</div>';
}


?>