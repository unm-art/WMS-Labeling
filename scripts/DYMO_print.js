//DYMO SDK printing functions
function printDymoSpine (labelText) {

  //Assign xml to string. This can be built in Dymo Label Software and saved as a layout.
  var labelXml = ' \
    <DieCutLabel Version="8.0" Units="twips"> \
      <PaperOrientation>Portrait</PaperOrientation> \
      <Id>Small30347</Id> \
      <PaperName>30347 1 in x 1-1/2 in</PaperName> \
      <DrawCommands> \
        <RoundRectangle X="0" Y="0" Width="1440" Height="2160" Rx="180" Ry="180" /> \
      </DrawCommands> \
      <ObjectInfo> \
        <TextObject> \
          <Name>TEXT</Name> \
          <ForeColor Alpha="255" Red="0" Green="0" Blue="0" /> \
          <BackColor Alpha="0" Red="255" Green="255" Blue="255" /> \
          <LinkedObjectName></LinkedObjectName> \
          <Rotation>Rotation0</Rotation> \
          <IsMirrored>False</IsMirrored> \
          <IsVariable>True</IsVariable> \
          <HorizontalAlignment>Left</HorizontalAlignment> \
          <VerticalAlignment>Top</VerticalAlignment> \
          <TextFitMode>ShrinkToFit</TextFitMode> \
          <UseFullFontHeight>True</UseFullFontHeight> \
          <Verticalized>False</Verticalized> \
          <StyledText> \
            <Element> \
              <String>ZIM \
    PN1998 \
    A3 \
    A64 \
    1969</String> \
              <Attributes> \
                <Font Family="Tahoma" Size="14" Bold="True" Italic="False" Underline="False" Strikeout="False" /> \
                <ForeColor Alpha="255" Red="0" Green="0" Blue="0" /> \
              </Attributes> \
            </Element> \
          </StyledText> \
        </TextObject> \
        <Bounds X="82" Y="326" Width="1301" Height="1747" /> \
      </ObjectInfo> \
    </DieCutLabel>';
    
    //Assign xml to label
    var label = dymo.label.framework.openLabelXml(labelXml);
    
    //Put new text in label
    label.setObjectText("TEXT", labelText);
    
    var printers = dymo.label.framework.getPrinters();
    if (printers.length == 0)
        throw "No DYMO printers are installed. Install DYMO printers.";
    /*
    //Chooses first DYMO Printer found
    var printerName = "";
    for (var i = 0; i < printers.length; ++i)
    {
        var printer = printers[i];
        if (printer.printerType == "LabelWriterPrinter")
        {
            printerName = printer.name;
            break;
        }
    }
    */
    
    //Print
    //label.print(printerName);
    label.print("DYMO LabelWriter 450 Turbo");
}