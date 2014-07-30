<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/tcpdf/tcpdf.php');


/* Config variables here */
//TODO


//This class is used so that we can disable 'Fit to Scale' by default when printing.
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

$pdf = new TCPDFE('P', 'mm', 'Letter');
//Remove 'Print Scaling' option so labels aren't resized
$pdf->setViewerPreference('PrintScaling', 'None');

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
$pdf->SetFont("helveticaB", "B", 12);
$pdf->SetMargins(13, 3.96875, 11.1125); //in mm

// remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// set auto page breaks
$pdf->SetAutoPageBreak(false);

//Add first page
$pdf->AddPage();

$printCount = count($printArray);

//Position to move after creating cell - 0(right), 1(next line), 2(below)
$mvPos = 0;

// Build labels using divs.
for ($x = 0; $x < $printCount; $x++) {
    $cnumArray = array();
    if (isset($printArray[$x][0]) === true && $printArray[$x][0] !== '&nbsp;') {
        $cnumVal = preg_replace('#<br\s*/?>#i', "\n", $printArray[$x][0]);
        $strCount = substr_count($cnumVal, "\n");
    } else {
        $cnumVal = '';
    }
    
    //Spine label
    $pdf->MultiCell(20, 67.4688, $cnumVal, 0, 'L', false, 0, NULL, NULL, true, 1, false, false, 67.4688, 'T', true);

    if (isset($printArray[$x][1]) === true && $printArray[$x][1] !== '&nbsp;') {
        $pocketVal = preg_replace('#<br\s*/?>#i', "\n",$printArray[$x][1]);
        $strCount = substr_count($pocketVal, "\n");
    } else {
        $pocketVal = '';
    }
    
    //Gap between spine and pocket-label
    $pdf->Cell(3, 0, "", 0, 0);
    
    //Check if on left or ride side of label sheet
    if (($x % 2) === 0) {
      //Make next label start to right
      $mvPos = 0;
    } else {
      //Make next label start below
      $mvPos = 1;
    }
    
    //Pocket label
    $pdf->MultiCell(73, 67.4688, $pocketVal, 0, 'L', false, $mvPos, NULL, NULL, true, 0, false, false, 67.4688, 'T', true);
    
    //Add middle padding if moving to right side
    if (($x % 2) === 0) {
      $pdf->Cell(7, 0, "", 0, 0);
    }

    // Test whether we have eight labels and add new page if we have more
    if ((($x + 1) % 8 === 0) && count($printArray) > ($x + 1)) {
      $pdf->AddPage();
    }
}//end for

$pdf->Output();
?>