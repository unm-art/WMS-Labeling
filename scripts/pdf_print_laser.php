<?php
require_once('../../tcpdf/tcpdf.php');

//Load the config file for appropriate label stock
if (isset($_GET['config'])) {
    $labelConfig = $_GET['config'];
} else {
    $labelConfig = 'laser';
}
require_once($labelConfig . '.config.php');

//This class extends TCPDF class and is used so that we can 
//disable 'Fit to Scale' by default when printing.
class TCPDFE extends TCPDF {
    /**
     * @author Brian Wendt (http://ontodevelopment.blogspot.com/)
     * @link http://www.tcpdf.org/doc/classTCPDF.html#ad09b32089f8e68c9d8424a2777627c21
     * @param string $preference key of preference 
     * @param mixed $value
     */
    function setViewerPreference($preference, $value){
        $this->viewer_preferences[$preference] = $value;
    }
}

//Grab labels from session stored in label output screen
session_start();
$printArray = $_SESSION['printArray'];
$cnumVal = $_SESSION['cnumVal'];
//Create TCPDF object
$pdf = new TCPDFE($pageOrientation, $pageRuling, $pageFormat, true, 'UTF-8');
//Remove 'Print Scaling' option so labels aren't resized
$pdf->setViewerPreference('PrintScaling', 'None');
//Set default font
$pdf->SetFont($fontFamily, $fontStyle, $fontSize);
//Set margins
$pdf->SetMargins($marginLeft, $marginTop, $marginRight); //in mm
// remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
// set auto page breaks
$pdf->SetAutoPageBreak(false);

//Add first page
$pdf->AddPage();

//Position to move after creating cell - 0(right), 1(new line)
$mvPos = 0;

// Build labels using divs.
$printCount = count($printArray);
for ($x = 0; $x < $printCount; $x++) {
    //Spine label
    $cnumArray = array();
    //Replace <br /> with line breaks, or make label blank if just &nbsp;
    if (isset($printArray[$x][0]) === true && $printArray[$x][0] !== '&nbsp;') {
        $cnumVal = preg_replace('#<br\s*/?>#i', "\n", $printArray[$x][0]);
        //$_SESSION['cnumVal'] = $cnumVal;
        $strCount = substr_count($cnumVal, "\n");
    } else {
        $cnumVal = '';
    }
    
    //Output spine to pdf
    $pdf->MultiCell($spineWidth, $spineHeight, $_SESSION['cnumVal'], 0, 'L', false, 0, NULL, NULL, true, 0, false, false, $spineHeight, 'T', true);

    //Gap between spine and pocket-label
    $pdf->Cell($spacingLabel, 0, "", 0, 0);
    
    //Pocket label
    //Replace <br /> with line breaks, or make label blank if just &nbsp;
    if (isset($printArray[$x][1]) === true && $printArray[$x][1] !== '&nbsp;') {
        $pocketVal = preg_replace('#<br\s*/?>#i', "\n",$printArray[$x][1]);
        $strCount = substr_count($pocketVal, "\n");
    } else {
        $pocketVal = '';
    }

    //Check if on left or ride side of label sheet
    if (($x % 2) === 0) {
      //Make next label start to right
      $mvPos = 0;
    } else {
      //Make next label start below
      $mvPos = 1;
    }
    //Output pocket label to pdf
    $pdf->MultiCell($pocketWidth, $pocketHeight, $pocketVal, 0, 'L', false, $mvPos, NULL, NULL, true, 0, false, false, $pocketHeight, 'T', true);
    
    //Add middle padding if moving to right side
    if (($x % 2) === 0) {
      $pdf->Cell($spacingVert, 0, '', 0, 0);
    }
    
    //Padding for horizontal space between cells.
    if (($x % 2) !== 0 && ($x + 1) % $labelsPerPage !== 0) {
      $pdf->MultiCell(0, $spacingHorz, '', 0, 'L', false, 1);
    }

    //Add a page if reached labelsPerPage and this is not last label
    if ((($x + 1) % $labelsPerPage === 0) && count($printArray) > ($x + 1)) {
      $pdf->AddPage();
    }
}//end for

$pdf->Output();