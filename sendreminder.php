<?php
$svr = "localhost:3306";
$uid = "ReadUsr";
$pwd = "sfmg9G";
$dbname = "onehourp_mm5";
$link = mysql_connect($svr, $uid, $pwd);
if (!$link) {
    die('Could not connect: ' . mysql_error());
}
echo 'Sending Reminders for ' . date("M d, Y") . PHP_EOL . PHP_EOL;
//echo 'Sending Reminders for Jun 17, 2014' . PHP_EOL;
$db_selected = mysql_select_db($dbname, $link);
if (!$db_selected) {
    die ('Can\'t use ' . $dbname . mysql_error());
}
$qry = "SELECT r1.ship_email, orderdate, orderid, shipname, nextreminder, freq, annualrenewal, member_comp, c1.bill_city, c1.bill_state, c1.bill_phone 
FROM s02_QFI_Reminders r1 
left join s02_Customers c1 on c1.login = memberid 
where nextreminder = curdate() and optin = 1 and expired = 0 and r1.ship_email <> '' 
order by orderid ";
//echo 'Executing query: ' . $qry . "<br><br>";
$result = mysql_query($qry, $link);
//define the receiver of the email
$to = 'russell@qualityfilters.com';
//define the subject of the email
$subject = 'Your OneHourProducts.com Reminder'; 
//create a boundary string. It must be unique 
//define the body of the message.
$sendcnt = 0;
while ($row = mysql_fetch_object($result)) {
//so we use the MD5 algorithm to generate a random hash
$random_hash = md5(date('r', time())); 
//define the headers we want passed. Note that they are separated with \r\n
$headers = "From: noreply@onehourproducts.com\r\nReply-To: noreply@onehourproducts.com";
//add boundary string and mime type specification
$headers .= "\r\nContent-Type: multipart/alternative; boundary=\"PHP-alt-".$random_hash."\""; 
ob_start(); //Turn on output buffering
?>
--PHP-alt-<?php echo $random_hash; ?>  
Content-Type: text/plain; charset="iso-8859-1" 
Content-Transfer-Encoding: 7bit

Dear <?php echo $row->shipname; ?>,

We are sending you a reminder to check, and change if necessary, your heating and cooling system's air filter.

--PHP-alt-<?php echo $random_hash; ?>  
Content-Type: text/html; charset="iso-8859-1" 
Content-Transfer-Encoding: 7bit

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head>
<body style="background-color:#FDCF00; color:#60513A">
<table align="center" style="width:800px; background-color:#DCEDE5; font-family:Arial,Helvetica,sans-serif; color:#60513A; font-size:10pt">
<tbody>
<tr>
<td>
<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
<tbody>
<tr>
<td align="left" bgcolor="#ffffff">
<img src="http://www.onehourproducts.com/mm5/graphics/logos/logo.jpg" id="null" alt="OneHourProducts.com" border="0"></td>
<td align="right" valign="bottom" bgcolor="#ffffff">
<font face="Verdana, Arial, Helvetica, sans-serif" size="2"><b><?php echo $row->member_comp; ?>&nbsp;</b><br />
<?php echo $row->bill_city; ?>,&nbsp;<?php echo $row->bill_state; ?>&nbsp;<br />
<?php echo $row->bill_phone; ?>&nbsp;</font><br />
</td> </tr>
</tbody>
</table>
<table width="100%" border="0" align="center" cellpadding="10" cellspacing="0">
<tbody>
<tr>
<td valign="top" bgcolor="#FFFFFF">
<hr width="100%">
Dear <b><?php echo $row->shipname; ?></b>,<br>
  <br>We are sending you this reminder to check, and change if necessary, your heating & cooling system's air filter. If it has been more than 90 days since its been
changed, it may be time to replace it.<br><br>
Order <?php echo $row->orderid; ?>, placed on <?php echo $row->orderdate; ?>.
<hr width="100%"> 
<p align="center"><big><b>Important HEALTH FACTS About Your Indoor Air</b></big>
<ul type="square">
<li>According to the <b>EPA</b> the air inside your home can be <u>2 to 5 times more polluted</u> than the air outside.</li>
<li>Cheap filters let dirt enter the system they are supposed to protect, which can add 37% more to your energy bill. Quality filtration <b>protects your heating and cooling system</b> and helps it to continue performing at its best.</li>
<li>Our products contain on an average, 15% more filter media than typical retail products. This gives you more <b>Dust-Holding Power, and Energy Savings</b>.</li>
</ul><p><hr width="100%">
<h3 align="center">Thanks for being a valued customer!</h3><br><br>
</td></tr>
</tbody>
</table>
<table border="0" align="center" cellpadding="10" cellspacing="0" width="100%" bgcolor="#FFFFFF">
<tbody>
<tr>
<td valign="top">
<table border="0" cellpadding="0" cellspacing="0" height="150" width="100%">
<tbody>
<tr align="center">
</tr>
</tbody>
</table>
</td>
</tr>
</tbody>
</table>
<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
<tbody>
<tr>
<td valign="top" style="font-size:8pt"><br>
<hr noshade="NOSHADE">
 <p>
 To stop receiving this notice, click here to <a href="http://www.onehourproducts.com/current/unsubscribe.php?shipemail=<?php echo $row->ship_email;?>&orderid=<?php echo $row->orderid;?>" target="_blank">Unsubscribe</a><br><br>
 Copyright <?php echo date("Y"); ?> CLOCKWORK IP, LLC<br>
   All rights reserved. <br>
       <br>
   Please do not reply directly to this e-mail. onehourproducts.Com will not receive any reply message. <br>
      <br>
   This communication contains proprietary information and may be confidential.&nbsp; If you are not the intended recipient, the reading, copying, disclosure or other use of the contents of this e-mail is strictly prohibited. Please delete
   this e-mail immediately.<br>
 </p>
 </td>
</tr>
<tr>
</tr>
</tbody>
</table>
</td>
</tr>
</tbody>
</table>
</body>
</html>

--PHP-alt-<?php echo $random_hash; ?>--
<?
//copy current buffer contents into $message variable and delete current output buffer
$message = ob_get_clean();
//send the email
$to = $row->ship_email;
$mail_sent = @mail( $to, $subject, $message, $headers );
//if the message is sent successfully print "Mail sent". Otherwise print "Mail failed" 
//echo $mail_sent ? "Mail sent " . $row->ship_email . ", " . $row->orderid . "<br>" : "Mail failed<br>";
if ($mail_sent) {
  $sendcnt++;
  echo "Reminder sent to " . $row->ship_email . ", Order " . $row->orderid . " on " . $row->orderdate . PHP_EOL;
  $updqry = "UPDATE s02_QFI_Reminders 
  SET lastreminder = nextreminder, nextreminder = nextreminder + interval freq month
  WHERE orderid = " . $row->orderid;
  $updresult = mysql_query($updqry, $link);
}
}
if ($sendcnt > 0) {
  echo PHP_EOL . $sendcnt . " Reminders sent" . PHP_EOL;
}
else {
  echo "No Reminders for today" . PHP_EOL;
}
?>