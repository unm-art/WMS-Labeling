<?php
require_once('vendor/autoload.php');
require_once('config/constants.php');
require_once('lib/acq_objects.php');


if (isset($_POST['inv_no'])) {
  //Grab invoice ids straight from post
  $ids = explode(',', $_POST['inv_no']);
} elseif (isset($_POST['inv_date_start'])) {
  //Search db for invoices by date range
  $dateArray = array($_POST['inv_date_start'], $_POST['inv_date_end']);
  $ids = getInvoiceIds($dateArray, "date");
} elseif (isset($_POST['fund_no'])) {
  //Search db for invoices with matching fund number
  $ids = getInvoiceIds($_POST['fund_no'], "fund");
} elseif (isset($_POST['vendor_no'])) {
  //Search db for invoices with matching vendor number/id
  $ids = getInvoiceIds($_POST['vendor_no'], "vendor");
} else {
  die("No information received. Could not perform search.");
}

$invoices = array();
$errors = array();
if (!empty($ids)) {
  foreach ($ids as $id) {
    $invoices[$id] = createInvoiceByDB(trim($id));
  }
  session_start();
  $_SESSION['invoices'] = $invoices;

  ?>
  <div id="errorDisplay" class="printhidden">
  <?php 
  foreach ($errors as $err) {
    echo $err . "<br/>"; 
  }
  ?>
  </div>
  <div id="link-area" class="printhidden">
    <div id ="link-csv"><a href="lib/invoiceCSV.php"><img src="images/icon-csv.png" /><br/>CSV</a></div>
    <div id="link-print"><a id="print_button" href="#print"><img src="images/icon-print.png" /><br/>Print</a></div>
  </div>
  <?php 
  $pageNum = 1;
  foreach ($invoices as $invoice) {
    if (!empty($invoice)) {
  ?>
  <div id="<?php echo $invoice['vendorInvoiceNumber']; ?>" class="page-wrap">
    <div class="header">
      <div class="invoice-logo">
        <img src="images/invoice-logo.png"/>
        <h2>University Libraries <br/> Budget and Cost Management</h2>
      </div>
    </div>
    
    <div class="downloadInvoiceCSV printhidden"><?php echo '<a href="invoiceCSV.php?id='. $invoice['invoiceNumber'] .'">Download CSV</a>' ?></div>
    
    <div class="meta">
      <div><div class="descText">Vendor:</div><?php echo $invoice['vendorName']; ?></div>
      <div><div class="descText">Vendor #:</div><textarea><?php echo $invoice['localIdentifier']; ?></textarea></div>
      <div><div class="descText">Invoice #:</div><?php echo $invoice['vendorInvoiceNumber']; ?></div>
      <div><div class="descText">Date:</div><?php echo formatDate($invoice['datePaid']); ?></div>
      <div><div class="descText">Amount:</div>$<?php echo $invoice['grandTotal']; ?></div>
    </div>  

    <div class="account">
      <h3>Account #'s</h3>
      <table>
        <tr><td><textarea>177000&mdash;</textarea></td><td><textarea>$</textarea></td></tr>
        <tr><td><textarea></textarea></td><td><textarea>$</textarea></td></tr>
        <tr><td><textarea></textarea></td><td><textarea>$</textarea></td></tr>
        <tr><td><textarea></textarea></td><td><textarea>$</textarea></td></tr>
        <tr><td><textarea></textarea></td><td><textarea>$</textarea></td></tr>
        <tr><td><textarea></textarea></td><td><textarea>$</textarea></td></tr>
      </table>
    </div>
    
    <div class="specialFunds">
      <h3>University Libraries Use only:</h3>
      <h3>Special Funds</h3>
      <table>
        <tr><td colspan=2><textarea>Acct # </textarea></td></tr>
        <tr><td><textarea></textarea></td><td><textarea></textarea></td></tr>
        <tr><td><textarea></textarea></td><td><textarea></textarea></td></tr>
      </table>
      <table>
        <tr><td colspan=2><textarea>Acct # </textarea></td></tr>
        <tr><td><textarea></textarea></td><td><textarea></textarea></td></tr>
        <tr><td><textarea></textarea></td><td><textarea></textarea></td></tr>
      </table>
      <table>
        <tr><td colspan=2><textarea>Acct # </textarea></td></tr>
        <tr><td><textarea></textarea></td><td><textarea></textarea></td></tr>
        <tr><td><textarea></textarea></td><td><textarea></textarea></td></tr>
      </table>
      <table>
        <tr><td colspan=2><textarea>Acct # </textarea></td></tr>
        <tr><td><textarea></textarea></td><td><textarea></textarea></td></tr>
        <tr><td><textarea></textarea></td><td><textarea></textarea></td></tr>
      </table>
    </div>
    
    <br style="clear:both;"/>
    
    <div class="batch"><textarea></textarea><br/>Batch #</div>
    
    <div class="voucher">
      <div style="border-bottom: 1px solid black;"><?php echo $invoice['invoiceNumber']; ?></div>
      <div>Voucher #</div>
    </div>
    
    <br style="clear:both;"/>
  <?php 
  if (!empty($invoice['items'])) {
  ?>
  <table class="items">
    <thead>
      <tr>
        <td class="table-meta" colspan="4">
          <div><?php echo $invoice['vendorName'] . " " . ($invoice['localIdentifier'] != "" ? "({$invoice['localIdentifier']})" : ""); ?></div>
          <div><?php echo "Invoice#: " . $invoice['vendorInvoiceNumber']; ?></div>
          <div><?php echo "Voucher#: " . $invoice['invoiceNumber']; ?></div>
        </td>
      </tr>
    <tr>
        <th>Item #</th>
        <th>Fund</th>
        <th>Subtotal</th>
        <th>User</th>
    </tr>
    </thead>
    <tbody>
    <!-- Run item loop here -->
    <?php
    foreach($invoice['items'] as $item) {
      echo '<tr class="item-row">';
      echo "<td>{$item['orderLineItem']}</td>";
      echo "<td>{$item['fundName']}</td>";
      echo "<td>{$item['grandTotal']}</td>";
      echo "<td>{$item['lastModifiedBy']}</td>";
      echo '</tr>';
    }
    ?>
    <tr>
      <td colspan="2" rowspan="2" class="blank"></td>
      <th class="total-line">Total:</th>
      <td class="total-value"><?php echo $invoice['grandTotal'] ?></td>
    </tr>
    <tr>
      <th class="total-line">Paid Date:</th>
      <td class="total-value"><?php echo $invoice['datePaid'] ?></td>
    </tr>
    </tbody>
  </table>
  <?php 
  } else {
    echo '<div class="printhidden" style="text-align: center;">No items found for this invoice.</div>';
  }
  ?>
  </div> <!-- end Page wrapper -->
  <?php
    } //end if empty invoice statement
  } //end foreach invoice
} else {
  //No ids found
  echo "No matching invoices found.";
}
?>
<script>
    $("a#print_button").click(function(e){
        e.preventDefault();
        $(".page-wrap").printArea( { mode: "iframe" } );
    
});
</script>
