<?php
session_start();
require_once('../config/config.php');
?>
<link rel="stylesheet" href="../css/labeling.css">
<script src="../js/vendor/printarea/jquery.PrintArea.js" type="text/JavaScript" language="javascript"></script>
<script src="../js/vendor/printElement/jquery.printElement.js" type="text/JavaScript" language="javascript"></script>
<script src="http://code.jquery.com/jquery-migrate-1.0.0.js"></script>
<script src="../js/vendor/DYMO.Label.Framework.latest.js" type="text/javascript" charset="UTF-8"> </script>
<script src="../js/DYMO_print.js"></script>
<script src="../js/vendor/jeditable/jquery.jeditable.js" type="text/JavaScript" language="javascript"></script>
<script type="text/javascript" charset="utf-8">
    $(document).ready(function() {
        $('.cnum, .pocket').css("white-space", "pre-line");
        $('.cnum, .pocket').editable('edited.php', {
            type : 'textarea',
            cancel : 'Cancel',
            submit : 'OK',
            tooltip : 'Click to edit...',
            data: function(value, settings) {
                /* Convert <br> to newline. */
                var retval = value.replace(/<br[\s\/]?>/gi, '\n').replace(/<div[\s\/]?>/gi, '').replace(/<\/div[\s\/]?>/gi, '');
                return retval;
            }
        });
    });
</script>
<?php
/**
 * barcode_label_output
 *
 * PHP version 5.3.3
 *
 * Output labels grabbed from WMS API and allow user to print.
 */
if(!empty($_POST)) {
    $_SESSION['saved_form'] = $_POST;
}
$barcodes         = '';
$barcodeCount     = '';
$labelStart       = '';
$printPocketLabel = '';
$labelStartOptions = $_SESSION['labelStartOptions'];

if (isset($_SESSION['saved_form']['barcodes']) === true && $_SESSION['saved_form']['barcodes'] !== '') {
    $barcodes     = $_SESSION['saved_form']['barcodes'];
    $barcodeCount = count($barcodes);
    $tmp          = '';

    for ($i = 0; $i < $barcodeCount; $i++) {
        $tmp .= $barcodes[$i];
    }
}

if (isset($_SESSION['saved_form']['print_pocket_label']) === true && $_SESSION['saved_form']['print_pocket_label'] !== '') {
    $printPocketLabel = $_SESSION['saved_form']['print_pocket_label'];
}

if (isset($_SESSION['saved_form']['label_start']) === true
    && is_numeric($_SESSION['saved_form']['label_start']) === true
    && $_SESSION['saved_form']['label_start'] > 0
    && $_SESSION['saved_form']['label_start'] <= $labelStartOptions
) {
    $labelStart = $_SESSION['saved_form']['label_start'];
}

require_once 'quicklabels.php';

$printArray = array();
if (!empty($_POST)) {
// Store all spine labels and pocket label counterparts in new, combined array.
    for ($x = 0; $x < $barcodeCount; $x++) {
        if ($barcodes[$x] !== '') {
            $printArray[] = quicklabels($barcodes[$x], $printPocketLabel[$x]);
        }
    }

// Add empty rows to print_array to start print on specified label.
    if (isset($labelStart) === true && $labelStart !== '') {
        for ($x = 1; $x < $labelStart; $x++) {
            array_unshift($printArray, array('&nbsp;', '&nbsp;'));
        }
    }
} else {
    $printArray = $_SESSION['printArray'];
}

//require 'edited.php';
    $labelRow = '';
    $labelPage = '';
    $printCount = count($printArray);
// Build labels using divs.
    for ($x = 0; $x < $printCount; $x++) {
        $labelRow .= '<div class="label_container">' . "\n";
        if (isset($printArray[$x][0]) === true && $printArray[$x][0] !== '') {
            $cnumVal = $printArray[$x][0];
        } else {
            $cnumVal = '&nbsp;';
        }

        if (isset($printArray[$x][1]) === true && $printArray[$x][1] !== '') {
            $pocketVal = $printArray[$x][1];
        } else {
            $pocketVal = '&nbsp;';
        }

        $labelRow .= '<div class="cnum" id="cnum_' . $x . '"><div>' . $cnumVal . '</div></div>' . "\n";
        $labelRow .= '<div class="pocket" id="pocket_' . $x . '"><div>' . $pocketVal . '</div></div>' . "\n";

        $labelRow .= "</div>\n";
        if (($x % 2) === 0) {
            $labelRow .= '<div class="middle_padding">&nbsp</div>';
        }

        // Test whether we have eight labels for 2-col printing, or are on the last label.
        if ((($x + 1) % $labelStartOptions === 0) || ($x + 1) >= count($printArray)) {
            $labelPage .= '<div class="label_page">' . $labelRow . '</div>' . "\n";
            $labelRow = '';
        }
    }//end for

//Store print array for FPDF to print from
$_SESSION['printArray'] = $printArray;

if ($printCount > 0) {
    ?>
    <!-- Radio list of different printers to choose from -->
    <div class="print-area">
        <div class="icon"><a id="print_button" href="#print" target="_blank"><img src="../img/icon-print.png"/><br/>Print
                Labels</a></div>
        <div>
            <input type="radio" name="printer" value="dot_matrix"/> Okidata Dot Matrix (IE Only)<br/>
            <input type="radio" name="printer" value="laser"/> Laser Printer (IE/Chrome Only)<br/>
            <input type="radio" name="printer" value="laser_old"/> Old Laser Layout (IE/Chrome Only)<br/>
            <input type="radio" name="printer" value="dymo"/> Dymo Printer
        </div>
    </div>

    <script>
        $(document).ready(function () {
            // Sets preview appearance for each printer type. This view does not affect actual printing, which is all css.
            $('input[name="printer"]').click(function () {
                $('.pocket').css("display", "block");
                switch ($(this).val()) {
                    case "dot_matrix":
                        $('.label_page').css("width", 400);
                        break;
                    case "dymo":
                        $('.label_page').css("width", 100);
                        $('.pocket').css("display", "none");
                        break;
                    default:
                        $('.label_page').css("width", 800);
                        break;
                }
            });
        });
    </script>

    <div class="edit_text">Click on any label to edit it for printing.</div>
    <?php echo $labelPage ?>

    <script>
        $("a#print_button").click(function (e) {
            printer_css = $("input[name=printer]:checked").val();
            switch(printer_css) {
                case "dymo":
                    e.preventDefault();
                    $('.cnum').each(function () {
                        //Get contents of spine label
                        var str = $(this).children('div').html();
                        var regex = /<br\s*[\/]?>/gi;
                        var labelStr = str.replace(regex, "\n");
                        printDymoSpine(labelStr);
                    });
                    break;
                case "dot_matrix":
                    $(".label_page").printArea({mode: "popup", retainAttr: [], extraCss: 'css/' + printer_css + '.css'});
                    break;
                case undefined:
                case "":
                    e.preventDefault();
                    alert("Type of printer must be selected.");
                    break;
                default:
                    $(this).attr('href', '../inc/pdf_print_laser.php?config=' + printer_css);
                    break;
            }
        });
    </script>
<?php
} //End if printCount
?>