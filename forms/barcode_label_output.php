<script src="jquery/printarea/jquery.PrintArea.js" type="text/JavaScript" language="javascript"></script>
<script src="jquery/printElement/jquery.printElement.js" type="text/JavaScript" language="javascript"></script>
<script src="http://code.jquery.com/jquery-migrate-1.0.0.js"></script>
<script src="scripts/DYMO.Label.Framework.latest.js"
        type="text/javascript" charset="UTF-8"> </script>
<script src="scripts/DYMO_print.js"></script>
<?php
/**
 * barcode_label_output
 *
 * PHP version 5.3.3
 *
 * Output labels grabbed from WMS API and allow user to print.
 */
session_start();
$barcodes         = '';
$barcodeCount     = '';
$labelStart       = '';
$printPocketLabel = '';

if (isset($_REQUEST['barcodes']) === true && $_REQUEST['barcodes'] !== '') {
    $barcodes     = $_REQUEST['barcodes'];
    $barcodeCount = count($barcodes);
    $tmp          = '';

    for ($i = 0; $i < $barcodeCount; $i++) {
        $tmp .= $barcodes[$i];
    }
}

if (isset($_REQUEST['print_pocket_label']) === true && $_REQUEST['print_pocket_label'] !== '') {
    $printPocketLabel = $_REQUEST['print_pocket_label'];
}

if (isset($_REQUEST['label_start']) === true
    && is_numeric($_REQUEST['label_start']) === true
    && $_REQUEST['label_start'] > 0
    && $_REQUEST['label_start'] < 9
) {
    $labelStart = $_REQUEST['label_start'];
}

$_SESSION['saved_form'] = $_REQUEST;

$printArray = array();

require_once '../lib/quicklabels.php';

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

$labelRow   = '';
$labelPage  = '';
$printCount = count($printArray);
// Build labels using divs.
for ($x = 0; $x < $printCount; $x++) {
    $labelRow .= '<div class="label_container">'."\n";
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

    $labelRow .= '<div class="cnum"><div>'.$cnumVal.'</div></div>'."\n";
    $labelRow .= '<div class="pocket"><div>'.$pocketVal.'</div></div>'."\n";

    $labelRow .= "</div>\n";
    if (($x % 2) === 0) {
        $labelRow .= '<div class="middle_padding">&nbsp</div>';
    }

    // Test whether we have eight labels for 2-col printing, or are on the last label.
    if ((($x + 1) % 8 === 0) || ($x + 1) >= count($printArray)) {
        $labelPage .= '<div class="label_page">'.$labelRow.'</div>'."\n";
        $labelRow   = '';
    }
}//end for

//Store print array for FPDF to print from
$_SESSION['printArray'] = $printArray;

?>
<!-- Radio list of different printers to choose from -->
<div id="link-area" class="print_label_button">
    <div id="link-print">
        <div class="button_left_div"><a id="print_button" href="#print"><img src="images/icon-print.png" /><br/>Print Labels</a>
        </div>
        <div class="button_right_div">
            <input type="radio" name="printer" value="dot_matrix" /> Okidata Dot Matrix (IE Only)<br />
            <input type="radio" name="printer" value="laser" /> Laser Printer (IE/Chrome Only)<br />
            <input type="radio" name="printer" value="laser_old" /> Old Laser Layout (IE/Chrome Only)<br />
            <input type="radio" name="printer" value="dymo" /> Dymo Printer
        </div>
        <div class="clear"></div>
    </div>
</div>

<script>
$(document).ready(function(){
  // Sets preview appearance for each printer type. This view does not affect actual printing, which is all css.
  $('input[name="printer"]').click(function(){
  $('.pocket').css("display", "block");
  switch($(this).val()) {
    case "dot_matrix":
      $('#table_div').css("width", 400);
      break;
    case "laser":
      $('#table_div').css("width", 800);
      break;
    case "dymo":
      $('#table_div').css("width", 100);
      $('.pocket').css("display", "none");
      break;
  }
  });
});
</script>

<?php
echo "<div id=\"table_div\">$labelPage</div>";
?>

<script>
    $("a#print_button").click(function(e){
        e.preventDefault();
        printer_css = $("input[name=printer]:checked").val();
        if (typeof printer_css !== "undefined") {
          if (printer_css == "laser") {
            //Run TCPDF script
            window.location.href = 'scripts/pdf_print_laser.php?config=laser';
          }
          else if (printer_css == "laser_old") {
            window.location.href = 'scripts/pdf_print_laser.php?config=laser_old';
          /*
           //Detect browser and load appropriate css
           if (navigator.userAgent.match(/trident/i))
           printer_css += "_ie";
           else if (navigator.userAgent.match(/firefox/i))
           printer_css += "_firefox";
           else if (navigator.userAgent.match(/chrome/i))
           printer_css += "_chrome";
           */
          }
          else if (printer_css == "dymo") {
            $('.cnum').each(function() {
              //Get contents of spine label
              var str = $(this).children('div').html();
              var regex = /<br\s*[\/]?>/gi;
              var labelStr = str.replace(regex, "\n");
              printDymoSpine(labelStr);
            });
          }
          else {
            //Dot Matrix
            $("#table_div").printArea( { mode: "popup", retainAttr: [], extraCss: 'css/'+printer_css+'.css' } );
          }

        } else {
          alert("Type of printer must be selected.");
        }

    });
</script>