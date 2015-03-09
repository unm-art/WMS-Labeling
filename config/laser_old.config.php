<?php
/* CONFIG VARIABLES */
//http://www.tcpdf.org/doc/code/classTCPDF.html#a134232ae3ad1ec186ed45046f94b7755
$pageFormat = "Letter";
$pageOrientation = "P";
$pageRuling = "mm";

$labelsPerPage = 8; //Spine and pocket count as one label.
$pocketWidth = 73;
$pocketHeight = 34.1313;
$spineWidth = 20;
$spineHeight = 34.1313;

$spacingLabel = 3; //Spacing between spine and pocket.
$spacingHorz = 34.1313; //Spacing below set of labels.
$spacingVert = 8; //Spacing in middle of page.

//Page Margin Settings
$marginLeft = 13;
$marginRight = 11.1125;
$marginTop = 3.96875;

//Font settings
//http://www.tcpdf.org/doc/code/classTCPDF.html#afd56e360c43553830d543323e81bc045
$fontFamily = 'helveticaB';
$fontSize = '10';
$fontStyle = 'B';