<?php
/* CONFIG VARIABLES */
//http://www.tcpdf.org/doc/code/classTCPDF.html#a134232ae3ad1ec186ed45046f94b7755
$pageFormat = "Letter";
$pageOrientation = "P";
$pageRuling = "mm";

$labelsPerPage = 12; //Spine and pocket count as one label.
$pocketWidth = 73;
$pocketHeight = 40;
$spineWidth = 22;
$spineHeight = 40;

$spacingLabel = 3.5; //Spacing between spine and pocket.
$spacingHorz = 3; //Spacing below set of labels.
$spacingVert = 5; //Spacing in middle of page.

//Page Margin Settings
$marginLeft = 8;
$marginRight = 6;
$marginTop = 8;

//Font settings
//http://www.tcpdf.org/doc/code/classTCPDF.html#afd56e360c43553830d543323e81bc045
$fontFamily = 'helveticaB';
$fontSize = '10';
$fontStyle = 'B';