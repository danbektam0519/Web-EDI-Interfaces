<?php

// Include PDF Generator
include ( '/var/www/vhosts/onehourproducts.com/httpdocs/mpdf/mpdf.php');
$mpdf=new mPDF('','', 0, '', 15, 15, 30, 16, 6, 9, 'P');
$mpdf->AddPage('P');

// Include PHPMailer and setup mail headers
//require '/var/www/vhosts/onehourproducts.com/httpdocs/phpMailer/PHPMailerAutoload.php';
//$mail = new PHPMailer;
//$mail->From = 'noreply@onehourproducts.com';
//$mail->FromName = 'OneHourProducts.Com EDI Mailer';
//$mail->addAddress('russell@qualityfilters.com', 'Russell Dennis');  // Add a recipient
//$mail->addReplyTo('noreply@onehourproducts.com', 'NoReply');
//$mail->Subject = 'OneHourProducts.Com EDI Summary';
//$mail->WordWrap = 50; 
//$mail->isHTML(true);

//Connect to DB and Query EDI Data
date_default_timezone_set("America/Chicago");
echo("Opening database.\n");
$svr = "localhost:3306";
$uid = "ReadUsr";
$pwd = "sfmg9G";
$dbname = "onehourp_mm5";
$qry = "SELECT cus.login as Login, ord.id as OrderID, ord.pay_data as MemberPO, date_format(FROM_UNIXTIME(ord.orderdate), '%m/%d/%Y') as OrderDate,
        concat(ord.ship_fname, ' ', ord.ship_lname) as Name, ord.ship_addr as Address, ord.ship_city as City, ord.ship_state as State,
        ord.ship_zip as Zip, oi.code as Item, ifnull(oo.data,'') as CustPart, oi.quantity as Qty,
        ifnull(pv.value,4) as CaseQty, ifnull(oo2.data,'') as CustFiltCost, ifnull(oo3.data,'') as CustCaseCost, ifnull(catfields.SalesUOM, 4) as SalesUnits, 
        ifnull(pv1.value, '') as BuyMaxPart, oi.name as ItemDesc, oo4.data as CustomDesc
        FROM s02_Orders ord
        LEFT JOIN s02_Customers cus on cus.id = ord.cust_id
        LEFT JOIN s02_OrderItems oi ON oi.order_id = ord.id
        LEFT JOIN s02_OrderOptions oo on oo.order_id = ord.id and oo.line_id = oi.line_id and oo.attr_code = 'partno' 
        LEFT JOIN s02_OrderOptions oo2 on oo2.order_id = ord.id and oo2.line_id = oi.line_id and oo2.attr_code = 'costprft' 
        LEFT JOIN s02_OrderOptions oo3 on oo3.order_id = ord.id and oo3.line_id = oi.line_id and oo3.attr_code = 'costprcs'
        LEFT JOIN s02_OrderOptions oo4 on oo4.order_id = ord.id and oo4.line_id = oi.line_id and oo4.attr_code = 'Size'
        LEFT JOIN s02_CategoryXProduct catx on catx.product_id = oi.product_id
        LEFT JOIN s02_SS_CATFIELDS_data catfields on catfields.id = catx.cat_id
        LEFT JOIN s02_Products pr on pr.id = oi.product_id
        LEFT JOIN s02_CFM_ProdFields pf on pf.code = 'FPC'
        LEFT JOIN s02_CFM_ProdValues pv on pv.product_id = oi.product_id and pv.field_id = pf.id
        LEFT JOIN s02_CFM_ProdFields pf1 on pf1.code = 'BuyMaxPart'
        LEFT JOIN s02_CFM_ProdValues pv1 on pv1.product_id = oi.product_id and pv1.field_id = pf1.id
        WHERE date(from_unixtime(ord.orderdate)) >= '2014-02-01' 
        order by cus.login, ord.id, oi.line_id";
// connect to Host Server 
$link = mysql_connect($svr, $uid, $pwd);
if (!$link) {
    die('Could not connect: ' . mysql_error());
}
echo "Connected successfully\n";
$db_selected = mysql_select_db($dbname, $link);
if (!$db_selected) {
    die ('Can\'t use ' . $dbname . mysql_error());
}

echo "Executing query\n";
$result = mysql_query($qry, $link);

$pdffilename = 'EDIDoc.pdf';

// Begin Batch
echo "Begin \n";
$ordcnt = 0;
$ediFile = 0;
$row = 0;
$ordlist = '';
$prevord = '';
$prevmem = '';
$linecnt = 0;
if (mysql_num_rows($result) > 0) {
  $row = mysql_fetch_object($result);
  }
else {
  echo "No Records to process\n";
  exit;
}

$orderdetail = '';
while ($row) {
  // Process Member
  $prevmem = $row->Login;
  while ($row->Login == $prevmem) {
    //Process Order and Lines
    $ordcnt = $ordcnt + 1;
    if ($ordcnt % 2 != 0) {
      $orderdetail .= '<tr><td bgcolor="#EEEEEE"><table class="style1" width="100%" border="0" cellpadding="0">' . PHP_EOL;
      }
    else {
      $orderdetail .= '<tr><td><table class="style1" width="100%" border="0" cellpadding="0">' . PHP_EOL;
    }
    $linenum = 1;
    $prevord = $row->OrderID;
    while (( $row->OrderID == $prevord ) &&  ($row->Login == $prevmem)) {
      //Process Order Lines
      if ($row->Item == 'custom') {
        $webitem = $row->CustPart;
        $buymaxitem = $row->CustPart;
      }
      else {
        $webitem = $row->Item;
        if ($row->BuyMaxPart != '') {
          $buymaxitem = $row->BuyMaxPart;
        }
        else {
          $buymaxitem = $row->Item;
        }
      }
      if (substr($row->MemberPO, 0, 3) == ':PO') {
        list($polbl, $poval) = split('=', $row->MemberPO, 2);
        $poval = urldecode($poval);
        $poval = str_replace(',', ' ', $poval);
      }
      else {
        $poval = '';
      }
      $orderdetail .= '<tr>' . PHP_EOL;
      if ($linenum == 1) {
        $orderdetail .= '<td align="center" width="12%">' . $row->Login . '</td>' . PHP_EOL;
        $orderdetail .= '<td align="center" width="19%">' . $row->OrderID . '</td>' . PHP_EOL;
        }
      else {
        $orderdetail .= '<td align="center" width="12%">&nbsp;</td>' . PHP_EOL;
        $orderdetail .= '<td align="center" width="19%">&nbsp;</td>' . PHP_EOL;
      }
      $orderdetail .= '<td align="center" width="20%">' . $poval . '</td>' . PHP_EOL;
      $orderdetail .= '<td align="center" width="38%">' . $buymaxitem . '</td>' . PHP_EOL;
      $orderdetail .= '<td align="center" width="11%">' . $row->Qty . '</td>' . PHP_EOL;
      $orderdetail .= '</tr>' . PHP_EOL;
      $linecnt = $linecnt + 1;
      $linenum = $linenum + 1;
      $row = mysql_fetch_object($result);
    }
    $orderdetail .= '</table></td>' . PHP_EOL . '</tr>' . PHP_EOL;  
  }
}

echo "End \n";
mysql_close($link);

//$mail->addAttachment($ediFileName);         // Add attachments

$mpdf->SetHTMLHeader('<table width="100%" border="0" cellpadding="0" cellspacing="0" class="style1">
  <tr>
    <th >OneHourProducts.Com EDI Order Detail</th>
  </tr>
  <tr>
    <th><hr /></th>
  </tr>
  <tr>
    <td><table class="style1" width="100%" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <th width="12%">Member ID </th>
        <th width="19%">Site Order<br>ID </th>
        <th width="20%">Member PO </th>
        <th width="38%">Item</th>
        <th width="11%">Qty</th>
      </tr>
    </table></td>
  </tr>
  <tr>
    <th><hr /></th>
  </tr>
</table>');
$mpdf->SetHTMLFooter('
<table width="100%" style="vertical-align: bottom; font-family: verdana; font-size: 8pt; color: #000000; font-weight: bold; font-style: italic;">
<tr>
<td width="33%"><span style="font-weight: bold; font-style: italic;">{DATE m-j-Y}</span></td>
<td width="34%" align="center" style="font-weight: bold; font-style: italic;">{PAGENO}/{nbpg}</td>
<td width="33%" style="text-align: right; ">My document</td>
</tr></table>');

// Start buffer capture and build HTML
ob_start(); //Turn on output buffering
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Untitled Document</title>
<style type="text/css">
<!--
.style1 {
	font-family: Verdana, Arial, Helvetica, sans-serif;
	font-size: 12px;
}
-->
</style></head>

<body>
<table width="100%" border="0" cellpadding="0" class="style1">
<?php echo $orderdetail; ?>
  <tr>
    <td>&nbsp;<br />Orders Processed: <?php echo $ordcnt; ?><br />Lines Processed: <?php echo $linecnt; ?></td>
  </tr>
</table>
</body>
</html>

<?
//copy current buffer contents into $report variable and delete current output buffer
$report = ob_get_clean();
//$mail->Body    = $report;

//$mail->AltBody = 'EDI File attached for processing.';

$mpdf->WriteHTML($report);
//$mail->addStringAttachment($pdfcontent, $pdffilename);         // Add attachments

//if(!$mail->send()) {
//   echo 'Message could not be sent.';
//   echo 'Mailer Error: ' . $mail->ErrorInfo;
//}
//else {
//  echo 'Mail has been sent';
//}
$mpdf->Output();

?>