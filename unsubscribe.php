<?php
date_default_timezone_set("America/Chicago");
$emailparm = "";
$orderparm = "";
if (!empty($_GET["shipemail"])) $emailparm = $_GET["shipemail"];
if (!empty($_GET["orderid"])) $orderparm = $_GET["orderid"];
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
<title>Unsubscribe</title>
</head>
<body>
<table align="center" border="0" cellpadding="0" cellspacing="0" style="width:600px; font-family:Verdana, Arial, Helvetica, sans-serif; font-size:10pt">
<tr>
  <td>
  <img src="http://www.onehourproducts.com/mm5/graphics/logos/logo.jpg" id="null" alt="OneHourProducts.com" border="0">
  </td>
</tr>
<tr height="50px"><td>&nbsp;</td></tr>
<tr>
  <td align="center">
  <?php 
  if ((!empty($emailparm)) && (!empty($orderparm))) {
    echo "Your request to be removed from the Filter Change Reminder subscription has been processed for the email and order listed below.<br><br><b>Thank You!</b><br><br>";
    }
  else {
    echo "In order to change your subscription a valid email and order ID must be provided. Please click the unsubscribe link in your email reminder.<br><br>";
  }
  ?>
  </td>
</tr>
<tr>
<td align="center">
<?php
if (!empty($_GET["shipemail"])) {
  echo "Email address <b>" . $_GET["shipemail"] . "</b>, For order <b>";
  }
else {
  echo "Email address <b>?</b>, For order <b>";
}
if (!empty($_GET["orderid"])) {
  echo $_GET["orderid"] . "</b><br>";
  }
else {
  echo "?</b><br>";
}
$qry = "UPDATE s02_QFI_Reminders 
  SET optin=0 
  WHERE orderid = " . $orderparm;
$svr = "localhost:3306";
$uid = "ReadUsr";
$pwd = "sfmg9G";
$dbname = "onehourp_mm5";
// connect to host if data to update
if ((!empty($emailparm)) && (!empty($orderparm))) {
  $link = mysql_connect($svr, $uid, $pwd);
  if (!$link) {
    die('Could not connect: ' . mysql_error());
    }
  $db_selected = mysql_select_db($dbname, $link);
  if (!$db_selected) {
    die ('Can\'t use ' . $dbname . mysql_error());
    }
  $result = mysql_query($qry, $link);
  if (($result) && (mysql_affected_rows($link) > 0)) {
    echo '<br>Update Complete<br>';
    }
  if (($result) && (mysql_affected_rows($link) == 0)) {
    echo '<br>The email address or Order ID you provided is not valid<br>';
  }
mysql_close($link);
}
?>
</td> 
</tr>
<tr height="50px" ><td><hr width="100%"></td></tr>
</table>
</body>
</html>
