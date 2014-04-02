<?php
require_once('./lib/rss/rss_php.php');
require_once('./lib/dom/simple_html_dom.php');

/*
    initialize mysql
 */
$mysqli = new mysqli("localhost", "rssparser", "YndeLMGbvBZGTdsL", "rssparser");

if (mysqli_connect_errno()) {
	printf("Connect failed: %s\n", mysqli_connect_error());
	exit();
}

if (!$mysqli->set_charset("UTF8")) {
	printf("Error loading character set utf8: %s\n", $mysqli->error);
	exit();
}

$query = "INSERT INTO `rsslist`(`title`, `link`, `pub_date`, `category`, `guid`) VALUES (?,?,?,null,?)";
$stmt = $mysqli->prepare($query);

/*
	prevent from blocking your ip address
*/
$ip = rand(1,255).'.'.rand(1,255).'.'.rand(1,255).'.'.rand(1,255);
$opts = array(
	'http'=>array(
		'method'=>"GET",
		'header'=>
			"Client-IP: ".$ip."\r\n" . 
			"User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:9.0.1) Gecko/20100101 Firefox/9.0.1\r\n" .
			"Referer: http://cafe.naver.com\r\n"
	)
);
$context = stream_context_create($opts);

/*
	printer
*/
$url = "http://cafe.naver.com/joonggonara.cafe/ArticleList.nhn?search.clubid=10050146&search.menuid=388&search.boardtype=L&search.clubid=10050146";
$findme = "2130";


$CP949_Document = file_get_contents($url, false, $context);
$UTF8_Document = iconv("CP949", "UTF-8", $CP949_Document);
$DOM_Document = str_get_html($UTF8_Document);

/*
	<div class="article-board m-tcol-c">
*/
foreach ($DOM_Document->find('div[class=article-board m-tcol-c]') as $div_element) {
	foreach ($div_element->find('tr[align=center]') as $tr_element) {
		//file_put_contents('test2.txt', $tr_element->plaintext, FILE_APPEND);
		
		/*
			title: <a class="m-tcol-c">
			link : 'http://cafe.naver.com/joonggonara/' + <span class="m-tcol-c list-count">
			date : <td class="view-count m-tcol-c">
				this date format should be transformed into 'Sat, 03 Aug 2013 01:44:14 +0900'
			guid : 'http://cafe.naver.com/joonggonara/' + <span class="m-tcol-c list-count">
		*/
		$title = $tr_element->find('a[class=m-tcol-c]', 0)->innertext;
		$link = $tr_element->find('span[class=m-tcol-c list-count]', 0)->innertext;
		$link = 'http://cafe.naver.com/joonggonara/' . $link;
		$guid = $tr_element->find('span[class=m-tcol-c list-count]', 0)->innertext;
		$guid = 'http://cafe.naver.com/joonggonara/' . $guid;
		$date = $tr_element->find('td[class=view-count m-tcol-c]', 0)->innertext;
		
		$date_format = 'D, d M Y H:i:s O';
		$timezone = new DateTimeZone('Asia/Seoul');

		/*
			IF   '2013.08.02'
			THEN '2013-08-02' --> 'Fri, 02 Aug 2013 00:00:00 +0900'
			IF   '08:02'
			THEN 'Sun, 04 Aug 2013 08:02:00 +0900'
		*/
		if (strlen($date) > 5) {
			$date = str_replace('.', '-', $date);
			$DATE_TIME = new DateTime($date, $timezone);
			$date = $DATE_TIME->format($date_format);
		} else {
			$DATE_TIME = new DateTime($date, $timezone);
			$date = $DATE_TIME->format($date_format);
		}

		/* insert into database */
		$stmt->bind_param('ssss', $title, $link, $date, $guid);
		$stmt->execute();

		/*
			if informatino you want to find matches with the titie,
			this's gonna send you an email.
		*/
		if ($stmt->affected_rows > 0 &&
			strpos($title, $findme) !== false) {
			$to      = 'jason.heetae.kim@me.com';
			$subject = $title;
			$message = $link;
			$headers = 'From: jason@xs.p.ht' . "\r\n" .
			'Reply-To: jason@xs.p.ht' . "\r\n" .
		    'X-Mailer: PHP/' . phpversion();
			mail($to, $subject, $message, $headers);
		}
	}
	$log = '[' . date($date_format) . '] ' . '[succeeded]' . "\r\n";
	file_put_contents(date("Ymd") . '_log.txt', $log, FILE_APPEND);
}

$stmt->close();
$mysqli->close();
?>