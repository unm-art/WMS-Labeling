<?php
use League\OAuth2\Client\OptionProvider\HttpBasicAuthOptionProvider;
use League\OAuth2\Client\Provider\GenericProvider;
session_start();
require_once(__DIR__ . '/../config/config.php');
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
$_SESSION['labelStartOptions'] = $labelStartOptions;

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
    $setup_options = [
    'clientId'                => WSKEY,
    'clientSecret'            => SECRET,
    'urlAuthorize'            => 'https://oauth.oclc.org/auth',
    'urlAccessToken'          => 'https://oauth.oclc.org/token',
    'urlResourceOwnerDetails' => ''
    ];
    
    
    $basicAuth_provider = new HttpBasicAuthOptionProvider();
    $provider = new GenericProvider($setup_options, ['optionProvider' => $basicAuth_provider]);
    
    try {
        
        // Try to get an access token using the client credentials grant.
        $accessToken = $provider->getAccessToken('client_credentials', ['scope' => 'WMS_COLLECTION_MANAGEMENT']);
        for ($x = 0; $x < $barcodeCount; $x++) {
            if ($barcodes[$x] !== '') {
                $printArray[] = quicklabels($barcodes[$x], $printPocketLabel[$x], $accessToken);
            }
        }
    
    // Add empty rows to print_array to start print on specified label.
        if (isset($labelStart) === true && $labelStart !== '') {
            for ($x = 1; $x < $labelStart; $x++) {
                array_unshift($printArray, array('&nbsp;', '&nbsp;'));
            }
        }
    } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
        $printArray[] = array("&nbsp;", "Failed to get access token");
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
    <form id="print_area_form">
        <div class="print-area">
            <div class="icon"><button id="print_button" type="submit"><img src="../img/icon-print.png"/><br/>Print
                    Labels</button></div>
            <div>
                <label><input type="radio" name="printer" value="dot_matrix" id="dot_matrix"/> Okidata Dot Matrix (IE Only)</label><br/>
                <label><input type="radio" name="printer" value="laser" id="laser"/> Laser Printer (IE/Chrome Only)</label><br/>
                <label><input type="radio" name="printer" value="laser_old" id="laser_old"/> Old Laser Layout (IE/Chrome Only)</label><br/>
                <label><input type="radio" name="printer" value="dymo" id="dymo"/> Dymo Printer (Firefox/IE/Chrome Only)</label>
            </div>
        </div>
    </form>


    <script>
        $(document).ready(function () {
            function setPrinterDisplay(printer) {
                switch (printer) {
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
            }

            // Sets preview appearance for each printer type. This view does not affect actual printing, which is all css.
            $('input[name="printer"]').click(function () {
                $('.pocket').css("display", "block");
                setPrinterDisplay($(this).val());
            });

            // Pre-select the preferred printer
            prefPrinter = document.cookie.match('prefLabelPrinter=([^;]*)[;|$]');
            if(prefPrinter) {
                setPrinterDisplay(prefPrinter[1]);
                $("input[name=printer]").val([prefPrinter[1]]);
                $('#'+prefPrinter[1]).focus();
            }
        });
    </script>

    <div class="edit_text">Click on any label to edit it for printing.</div>
    <?php echo $labelPage ?>

    <script>
        var printfunc = function(){
            printer_css = $("input[name=printer]:checked").val();

            // save the printer to set as preferred
            document.cookie = "prefLabelPrinter="+printer_css+"; expires=Fri, 31 Dec 9999 23:59:59 GMT;"

            switch(printer_css) {
                case "dymo":
                    $('.cnum').each(function () {
                        //Get contents of spine label
                        var str = $(this).children('div').html();
                        var regex = /<br\s*[\/]?>/gi;
                        var labelStr = str.replace(regex, "\n");
                        printDymoSpine(labelStr.replace(/&amp;/gi, '&'));
                    });
                    break;
                case "dot_matrix":
                    $(".label_page").printArea({mode: "popup", popTitle: "Dot Matrix Print", retainAttr: [], extraCss: 'css/' + printer_css + '.css'});
                    break;
                case undefined:
                case "":
                    alert("Type of printer must be selected.");
                    break;
                default:
                    window.open('../inc/pdf_print_laser.php?config=' + printer_css, 'print_window');
                    break;
            }

            // Expose a clean entry form leaving the previous work below.
            var f = $('#barcode_scan_form').stop().slideDown().get(0);
            f.reset();
            $('input:hidden',f).val(0);
            $('input:text', f).first().focus();
        };

        $("#print_button").click(function (e) {
            e.preventDefault();
            printfunc();
        });
    </script>
<?php
} //End if printCount
?>