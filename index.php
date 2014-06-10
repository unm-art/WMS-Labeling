<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>University Libraries WMS Acquisitions Extension</title>
<link rel="stylesheet" href="jquery/jquery-ui-1.10.4/css/blitzer/jquery-ui-1.10.4.custom.css" />
<link rel="stylesheet" href="css/wms.css">
<link rel="stylesheet" href="css/invoice.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
<script src="jquery/jquery-ui-1.10.4/js/jquery-ui-1.10.4.custom.min.js"></script>
<script src="jquery/printarea/jquery.PrintArea.js" type="text/JavaScript" language="javascript"></script>
<script>
$(document).ready(function() {
  //Load page if user goes to hash page on first browse
  pageChoose(getLocationHash());
  
  $('#menu .menu_list li a').click(function(e) {
    pageChoose(getLocationHash());
    $(".selected").removeClass('selected');
    $(this).addClass('selected');
  });
});

$(document).on("submit", "#invoiceForm", function(e) {
    e.preventDefault();
    pageLoad("lib/invoice.php", $("#invoiceForm").serialize());
  });

$(window).bind('hashchange', function(e) {
  pageChoose(getLocationHash());
});

function pageChoose(loc) {
  switch (loc) {
    case "invoice_number":
      pageLoad("forms/invoice_number.php");
      break;
    case "invoice_date_range":
      pageLoad("forms/invoice_date_range.php");
      break;
    case "invoice_fund_number":
      pageLoad("forms/invoice_fund_number.php");
      break;
    case "invoice_vendor_name":
      pageLoad("forms/invoice_vendor_name.php");
      break;
  }
}

function pageLoad(page, params) {
  if (!params) {
    $.get(page, function(data) {
      $("#main_content").html(data);
    })
    .fail(function(data, status, jqXHR) {
      $("#main_content").text(status + ": " + jqXHR);
    });
  } else {
    $('#main_content').html('<img class="loading_gif" src="images/loading.gif"/>');
    $.post(page, params, function(data) {
      $("#main_content").html(data);
    })
    .fail(function(data, status, jqXHR) {
      $("#main_content").text(status + ": " + jqXHR);
    })
    .always(function() {
      $('#loadingScreen').hide();
    });
  }
}

function getLocationHash () {
  return window.location.hash.substring(1);
}
</script>
</head>
<body>
<div id="banner">
  <img src="/ilsacq/images/ullogo_acq.png" alt="UL Organizational Statistics Application" /><p style="clear: none; float: left; font-size: 50px; font-style: italic; margin-bottom: 0px; margin-left: 50px; margin-top: 30px;">&beta;eta</p>
</div>
<div class="clear"></div>
<div id="container">
  <div id="menu">
    <div class="menu_header">Invoices By</div>
    <ul class="menu_list">
      <li><a href="#invoice_number">Invoice Number</a></li>
      <li><a href="#invoice_date_range">Date/Date Range</a></li>
      <li><a href="#invoice_fund_number">Fund Number</a></li>
      <li><a href="#invoice_vendor_name">Vendor Name</a></li>
    </ul>
  </div>
  <div id="main_content">
  </div> <!-- end #main_content -->
</div> <!-- end #container -->
</body>
</html>