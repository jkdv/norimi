<?php
//Sat, 03 Aug 2013 15:44:14 +0900
$DATE_TIME = new DateTime('08:02', new DateTimeZone('Asia/Seoul'));
echo $DATE_TIME->format('D, d M Y H:i:s O');

$date = str_replace('.', '-', '2013.08.02');
$DATE_TIME = new DateTime($date, new DateTimeZone('Asia/Seoul'));
echo $DATE_TIME->format('D, d M Y H:i:s O');

echo date("Ymd");
?>