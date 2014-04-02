<?php
require_once('rss_php.php');

/*
    mysql
 */
$mysqli = new mysqli("mysql3.000webhost.com", "a3601557_rss", "YndeLMGbvBZGTdsL1", "a3601557_rss");

if (mysqli_connect_errno()) {
	printf("Connect failed: %s\n", mysqli_connect_error());
	exit();
}

if (!$mysqli->set_charset("UTF8")) {
	printf("Error loading character set utf8: %s\n", $mysqli->error);
	exit();
}

$query = "INSERT INTO `rsslist`(`title`, `link`, `pub_date`, `category`, `guid`) VALUES (?,?,?,?,?)";
$stmt = $mysqli->prepare($query);

/*
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
$url = "http://cafe.rss.naver.com/joonggonara";
$findme = "2130";


//for ($i=0; $i < 2; $i++) { 
while (true) {
	$UTF8_Document = file_get_contents($url, false, $context);
	$RSS_PHP = new rss_php;
	$RSS_PHP->loadRSS($UTF8_Document);

	/*
		Array
		(
		    [0] => Array
		        (
		            [title] => RSS_PHP Test News Item
		            [link] => http://rssphp.net/test-news/
		            [pubDate] => Tue, 15 Jan 2008 03:08:15 -0500
		            [dc:creator] => rssphp
		            [category] => Test News
		            [description] => A short test article to demonstrate RSS_PHP [...]
		            [guid] => http://rssphp.net/test-news/
		        )	
			[1] => . . .
	 */
	$rss_array 	= $RSS_PHP->getItems();
	$stmt->bind_param("sssss", $rss_title, $rss_link, $rss_pub_date, $rss_category, $rss_guid);

	foreach ($rss_array as $rss_data) {
		$rss_title 		= $rss_data['title'];
		$rss_link 		= $rss_data['link'];
		$rss_pub_date 	= $rss_data['pubDate'];
		$rss_category 	= $rss_data['category'];
		$rss_guid 		= $rss_data['guid'];
		$stmt->execute();

		if (strpos($rss_title, $findme) !== false) {
			$to      = 'jason.heetae.kim@me.com';
			$subject = $rss_title;
			$message = $rss_link;
			$headers = 'From: jason@xs.p.ht' . "\r\n" .
			'Reply-To: jason@xs.p.ht' . "\r\n" .
		    'X-Mailer: PHP/' . phpversion();
			mail($to, $subject, $message, $headers);
		}
	}

	echo '[' . $stmt->affected_rows . '] ' . $rss_pub_date;
	sleep(5);
}

$stmt->close();
$mysqli->close();
?>