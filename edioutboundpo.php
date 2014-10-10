<?php
date_default_timezone_set("America/Chicago");
chdir('/var/www/vhosts/homefilters.com/httpdocs/EDIOut/');
echo "Current directory is " . getcwd() . "\n";
echo("Opening a database.\n");
$svr = "localhost:3306";
$uid = "ReadUsr";
$pwd = "sfmg9G";
$dbname = "homefilter_mm5";
$qry = "SELECT ord.id as OrderID, date(FROM_UNIXTIME(ord.orderdate)) as OrderDate, ord.ship_fname as FirstName,
        ord.ship_lname as LastName, ord.ship_email as eMail, ord.ship_addr as Address, ord.ship_city as City,
        ord.ship_state as State, ord.ship_zip as Zip, ord.ship_cntry as Country, oi.code as Item, ifnull(oo.data,'') as CustPart, oi.quantity as Qty,
        pr.cost as Cost, oi.price as Price, ifnull(pv.value,1) as CaseQty, ifnull(oo2.data,'') as CustFiltCost, ifnull(oo3.data,'') as CustCaseCost, ifnull(catfields.SalesUOM, 12) as SalesUnits, 
        ifnull(custef.value, '1108') as AcctNumber, ifnull(pv1.value,'') as MfgPartNumber
        FROM s02_Orders ord
        LEFT JOIN s02_NBECF_customervalues custef on custef.cust_id = ord.cust_id and custef.fieldname = 'erpacct'
        LEFT JOIN s02_OrderItems oi ON oi.order_id = ord.id
        LEFT JOIN s02_OrderOptions oo on oo.order_id = ord.id and oo.line_id = oi.line_id and oo.attr_code = 'partno' 
        LEFT JOIN s02_OrderOptions oo2 on oo2.order_id = ord.id and oo2.line_id = oi.line_id and oo2.attr_code = 'costprft' 
        LEFT JOIN s02_OrderOptions oo3 on oo3.order_id = ord.id and oo3.line_id = oi.line_id and oo3.attr_code = 'costprcs'
        LEFT JOIN s02_CategoryXProduct catx on catx.product_id = oi.product_id
        LEFT JOIN s02_SS_CATFIELDS_data catfields on catfields.id = catx.cat_id
        LEFT JOIN s02_Products pr on pr.id = oi.product_id
        LEFT JOIN s02_CFM_ProdFields pf on pf.code = 'FPC'
        LEFT JOIN s02_CFM_ProdValues pv on pv.product_id = oi.product_id and pv.field_id = pf.id
        LEFT JOIN s02_CFM_ProdFields pf1 on pf1.code = 'MfgPart'
        LEFT JOIN s02_CFM_ProdValues pv1 on pv1.product_id = oi.product_id and pv1.field_id = pf1.id
        WHERE ord.processed = 0 and (pf.code = 'FPC' or oi.code = 'Custom') order by ord.id, oi.line_id";
// connect to Host Server and port 3306 (default port)
$link = mysql_connect($svr, $uid, $pwd);
if (!$link) {
    die('Could not connect: ' . mysql_error());
}
echo "Connected successfully.\n";
$db_selected = mysql_select_db($dbname, $link);
if (!$db_selected) {
    die ('Can\'t use ' . $dbname . mysql_error());
}

echo "Executing query\n";
$result = mysql_query($qry, $link);

$logFileName = "/var/www/vhosts/homefilters.com/httpdocs/EDILog/" . date('Ymd') . ".log";
$logFile = fopen($logFileName, "aw");
if (!$logFile) {
  print("Log File could not be opened.\n");
  }
else {
  fwrite($logFile, ">> Begin EDI Outbound Order Processing\n" . date("   m-d-Y H:i:s") . "\n");
  }

// Begin Batch
echo "Begin \n";
$ordcnt = 0;
$ediFile = 0;
$row = 0;
$ordlist = '';
if (mysql_num_rows($result) > 0) {
  $row = mysql_fetch_object($result);
  $ediFileName = "Homefilters_PO_" . date("YmdHis") . ".xml";
  fwrite($logFile, "   File Name: " . $ediFileName . "\n" . " + Records Processed: " . mysql_num_rows($result) . "\n");
  if(!($ediFile = fopen($ediFileName, "w")))
    {
    print("EDI Output File could not be opened.\n");
    exit;
    }
  else {
    fwrite($ediFile, "<TX_BATCH>\n");
    }
  }
else {
  fwrite($logFile, "   No Orders Found.\n");
  }

$sd = mktime(0,0,0,date("m"),date("d")+2,date("Y"));
$jd=cal_to_jd(CAL_GREGORIAN,date("m", $sd),date("d", $sd),date("Y", $sd));
if ((jddayofweek($jd, 0) == 0) or (jddayofweek($jd, 0) == 6)) {
  $sd = mktime(0,0,0,date("m"),date("d")+3,date("Y"));
  }

$prevord = "";
while ($row) {
  // Write Order Header
  $ordcnt = $ordcnt + 1;
  if ($ordcnt == 1) {
    $ordlist = $row->OrderID; 
    }
  else {
    $ordlist = $ordlist . ", " . $row->OrderID;
    }
  $linenbr = 1;
  $prevord = $row->OrderID;
  fwrite($ediFile, "<IMS_PHOENIX_TRANSACTION>\n");
  fwrite($ediFile, "<Document>\n");
  fwrite($ediFile, "<SalesOrder_PHOENIX_TRANSACTION>\n");
  fwrite($ediFile, "<Domain>SalesOrder</Domain>\n");
  fwrite($ediFile, "<Phantom>FALSE</Phantom>\n");
  fwrite($ediFile, "<Version>*NotSet*</Version>\n");
  fwrite($ediFile, "<ProcessingType>ADD</ProcessingType>\n");
  fwrite($ediFile, "<FIELDS>\n");
  if ($row->AcctNumber == '') {
    fwrite($ediFile, "<FIELD><Name>lSOM_CUS_CustomerID</Name><CurrValue>1108</CurrValue></FIELD>\n");
    }
  else {
    fwrite($ediFile, "<FIELD><Name>lSOM_CUS_CustomerID</Name><CurrValue>" . $row->AcctNumber . "</CurrValue></FIELD>\n");
    }
  fwrite($ediFile, "<FIELD><Name>SOM_CustomerPOID</Name><CurrValue>HF-" . $row->OrderID . "</CurrValue></FIELD>\n");
  fwrite($ediFile, "<FIELD><Name>SOM_SalesOrderDate</Name><CurrValue>" . date("Y/m/d") . "</CurrValue></FIELD>\n");
  fwrite($ediFile, "<FIELD><Name>SOM_DropShipFlag</Name><CurrValue>True</CurrValue></FIELD>\n");
  fwrite($ediFile, "<FIELD><Name>SOM_ShipToName</Name><CurrValue>" . $row->FirstName . " " . $row->LastName . "</CurrValue></FIELD>\n");
  fwrite($ediFile, "<FIELD><Name>SOM_ShipToAddress</Name><CurrValue>" . $row->Address . "</CurrValue></FIELD>\n");
  fwrite($ediFile, "<FIELD><Name>SOM_ShipToCity</Name><CurrValue>" . $row->City . "</CurrValue></FIELD>\n");
  fwrite($ediFile, "<FIELD><Name>SOM_ShipToState</Name><CurrValue>" . $row->State . "</CurrValue></FIELD>\n");
  fwrite($ediFile, "<FIELD><Name>SOM_ShipToPostalCode</Name><CurrValue>" . $row->Zip . "</CurrValue></FIELD>\n");
  fwrite($ediFile, "<FIELD><Name>SOM_ShipToCountry</Name><CurrValue>" . $row->Country . "</CurrValue></FIELD>\n");
  fwrite($ediFile, "<FIELD><Name>SOM_UserDef2</Name><CurrValue>" . $row->eMail . "</CurrValue></FIELD>\n");
  fwrite($ediFile, "</FIELDS>\n");

  // Begin Order Line Items Collection
  fwrite($ediFile, "<Subdomains>\n");
  fwrite($ediFile, "<SalesOrderLine_Item_PHOENIX_COLLECTION>\n");

  while ( $row->OrderID == $prevord ) {
    // Write Order Line
    fwrite($ediFile, "<SalesOrderLine_Item_PHOENIX_TRANSACTION>\n");
    fwrite($ediFile, "<Domain>SalesOrderLine</Domain>\n");
    fwrite($ediFile, "<Phantom>FALSE</Phantom>\n");
    fwrite($ediFile, "<Version>*NotSet*</Version>\n");
    fwrite($ediFile, "<ProcessingType>ADD</ProcessingType>\n");
    fwrite($ediFile, "<FIELDS>\n");
    fwrite($ediFile, "<FIELD><Name>SOI_SOLineNbr</Name><CurrValue>" . $linenbr  . "</CurrValue></FIELD>\n");
    if ($row->Item == 'custom') {
      fwrite($ediFile, "<FIELD><Name>lSOI_IMA_ItemID</Name><CurrValue>" . $row->CustPart . "</CurrValue></FIELD>\n");
      }
    else {
      if ($row->MfgPartNumber == '') {
        fwrite($ediFile, "<FIELD><Name>lSOI_IMA_ItemID</Name><CurrValue>" . $row->Item . "</CurrValue></FIELD>\n");
        }
      else {       
        fwrite($ediFile, "<FIELD><Name>lSOI_IMA_ItemID</Name><CurrValue>" . $row->MfgPartNumber . "</CurrValue></FIELD>\n");
        }
      }
    $jd=cal_to_jd(CAL_GREGORIAN,date("m", $sd),date("d", $sd),date("Y", $sd));
    fwrite($ediFile, "<FIELD><Name>SOI_ItemSpecialInst</Name><CurrValue></CurrValue></FIELD>\n");
    fwrite($ediFile, "</FIELDS>\n");

    // Write Delivery
    fwrite($ediFile, "<Subdomains>\n");
    fwrite($ediFile, "<SalesOrderDelivery_PHOENIX_COLLECTION>\n");
    fwrite($ediFile, "<SalesOrderDelivery_PHOENIX_TRANSACTION>\n");
    fwrite($ediFile, "<Domain>SalesOrderDelivery</Domain>\n");
    fwrite($ediFile, "<Phantom>FALSE</Phantom>\n");
    fwrite($ediFile, "<Version>*NotSet*</Version>\n");
    fwrite($ediFile, "<ProcessingType>ADD</ProcessingType>\n");
    fwrite($ediFile, "<FIELDS>\n");
    fwrite($ediFile, "<FIELD><Name>SOD_RequiredDate</Name><CurrValue>" . 
                     date("Y/m/d", $sd) .
                     "</CurrValue></FIELD>\n");
    if ($row->Item == 'custom') {
      fwrite($ediFile, "<FIELD><Name>SOD_RequiredQty</Name><CurrValue>" .
                       $row->Qty * ($row->CustCaseCost / $row->CustFiltCost) . "</CurrValue></FIELD>\n");
      }
    else {
      if ($row->SalesUnits == 1) {
        fwrite($ediFile, "<FIELD><Name>SOD_RequiredQty</Name><CurrValue>" . $row->Qty  . "</CurrValue></FIELD>\n");        
        }
      else {
        fwrite($ediFile, "<FIELD><Name>SOD_RequiredQty</Name><CurrValue>" . $row->Qty * $row->CaseQty  . "</CurrValue></FIELD>\n");
        }
      }
    // fwrite($ediFile, "<FIELD><Name>SOD_UnitPrice</Name><CurrValue>" . $row->Cost . "</CurrValue></FIELD>\n");
    fwrite($ediFile, "</FIELDS>\n");

    // End Delivery
    fwrite($ediFile, "</SalesOrderDelivery_PHOENIX_TRANSACTION>\n");
    fwrite($ediFile, "</SalesOrderDelivery_PHOENIX_COLLECTION>\n");
    fwrite($ediFile, "</Subdomains>\n");

    // End Order Line
    fwrite($ediFile, "</SalesOrderLine_Item_PHOENIX_TRANSACTION>\n");
    $linenbr = $linenbr + 1;
    $row = mysql_fetch_object($result);
    }

  // End Order Line Items Collection
  fwrite($ediFile, "</SalesOrderLine_Item_PHOENIX_COLLECTION>\n");
  fwrite($ediFile, "</Subdomains>\n");
  // Write Order Footer
  fwrite($ediFile, "</SalesOrder_PHOENIX_TRANSACTION>\n");
  fwrite($ediFile, "</Document>\n");
  fwrite($ediFile, "</IMS_PHOENIX_TRANSACTION>\n");
  }

// End Batch
if (mysql_num_rows($result) > 0) {
  fwrite($ediFile, "</TX_BATCH>\n");
  }
if ($logFile) {
  fwrite($logFile, " + Orders Processed: " . $ordcnt . "\n");
  }

if ($ordcnt > 0) {
  $updqry = "UPDATE s02_Orders SET processed = 1 
             WHERE id IN (" . $ordlist . ")";
  echo "Orders updated: " . $ordlist . "\n";
  $result = mysql_query($updqry, $link);
  if (!$result) {
    fwrite($logFile, " + Database error on update\n");
    fwrite($logfile, "   " . mysql_error());
    }
  else {
    fwrite($logFile, " + Database update complete\n");
    }
  }

if ($ediFile) {
  fclose($ediFile);
  }
if ($logFile) {
  fwrite($logFile, " * END\n");
  fclose($logFile);
  }
echo "End \n";
mysql_close($link);
?>
