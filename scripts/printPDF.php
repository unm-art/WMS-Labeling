<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/tcpdf/tcpdf.php');

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

session_start();
$printArray = $_SESSION['printArray'];

$pdf = new TCPDFE('P', 'mm', 'Letter');
//Remove 'Print Scaling' option so labels aren't resized
$pdf->setViewerPreference('PrintScaling', 'None');

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
$pdf->SetMargins(12, 3.96875, 11.1125); //in mm

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
    if (isset($printArray[$x][0]) === true && $printArray[$x][0] !== '') {
        $cnumVal = preg_replace('#<br\s*/?>#i', "\n", $printArray[$x][0]);
        $strCount = substr_count($cnumVal, "\n");
    } else {
        $cnumVal = '';
    }
    
    //Spine label
    $pdf->MultiCell(20.6375, 68, $cnumVal, 0, 'L', false, 0, NULL, NULL, true, 0, false, false, 67.4688);

    if (isset($printArray[$x][1]) === true && $printArray[$x][1] !== '') {
        $pocketVal = preg_replace('#<br\s*/?>#i', "\n",$printArray[$x][1]);
        $strCount = substr_count($pocketVal, "\n");
    } else {
        $pocketVal = '';
    }
    
    //Gap between spine and pocket-label
    $pdf->Cell(3, 0, "", 0, 0);

    if (($x % 2) === 0) {
      //Pocket label
      $pdf->MultiCell(73.8187, 68, $pocketVal, 0, 'L', false, 0, NULL, NULL, true, 0, false, false, 67.4688);
      $pdf->Cell(7, 0, "", 0, 0);
    } else {
      $pdf->MultiCell(73.8187, 68, $pocketVal, 0, 'L', false, 1, NULL, NULL, true, 0, false, false, 67.4688);
    }

    // Test whether we have eight labels for 2-col printing, or are on the last label.
    if ((($x + 1) % 8 === 0) && count($printArray) > ($x + 1)) {
      //May be automatic
      //TODO
      $pdf->AddPage();
    }
}//end for

$pdf->Output();
?>