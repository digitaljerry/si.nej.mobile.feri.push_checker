<?php

$username = 'pusher';
$password = 'password';
$url = 'http://www.feri.uni-mb.si/rss/novice.xml';
$app_key = 'PilRZcKOrnAFPXt6DnCtePJC2rONvR0W';
$cloud_login_url = 'https://api.cloud.appcelerator.com/v1/users/login.json?key=' . $app_key;
$cloud_notify_url = 'https://api.cloud.appcelerator.com/v1/push_notification/notify.json?key=' . $app_key;
$file = "sentNotifications.ser";
$cookieFileLocation = 'cookie.txt';
define('MAGPIE_CACHE_AGE', 60);

$skipWelzer = false;
$sendPush = true;
$newData = false;
$alreadyLoggedIn = false;
chdir(dirname(__FILE__));

$fh = fopen($file, 'r');
$sent = unserialize(fread($fh, filesize($file)));
fclose($fh);

require('./magpierss-0.72/rss_fetch.inc');
$rss = fetch_rss($url);

echo "Reading ... ", $rss->channel['title'], PHP_EOL;
foreach ($rss->items as $item ) {
	$title = $item['title'];
	$url   = $item['link'];
	
	// testting
	if ( $skipWelzer == true && $title == 'Govorilne ure T.Welzer' )
		continue;
	
	$already_sent = false;
	foreach ($sent as $sent_item ) {
		if ( $sent_item == $url )
			$already_sent = true;
	}
	
	if ( $already_sent == false ) {
		$sent[] = $url;
		$newData = true;
		
		echo 'New notification: ' . $title . PHP_EOL;
		
		$loc = strpos($url, 'oce=');
		$category = substr($url, $loc+4);
		
		// do the sending
		if ($sendPush == true) {
			
			if ( $alreadyLoggedIn == false ) {
				$login = array(
					'login' => $username,
					'password' => $password
				);

				$ch = curl_init(); 
				curl_setopt($ch, CURLOPT_URL, $cloud_login_url);
				curl_setopt($ch, CURLOPT_HEADER, TRUE); 
				curl_setopt($ch, CURLOPT_NOBODY, TRUE); // remove body 
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
				curl_setopt($ch,CURLOPT_COOKIEJAR,$cookieFileLocation); 
				curl_setopt($ch,CURLOPT_COOKIEFILE,$cookieFileLocation);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $login);
				$head = curl_exec($ch);
				$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				curl_close($ch);
				
				$alreadyLoggedIn = true;
			}
			
			$push = array(
				'channel' => $category,
				'payload' => '{"badge":"1","sound":"default","alert":'.htmlentities($title).'}'
			);
		
			$ch = curl_init(); 
			curl_setopt($ch, CURLOPT_URL, $cloud_notify_url);
			curl_setopt($ch, CURLOPT_HEADER, TRUE); 
			curl_setopt($ch, CURLOPT_NOBODY, TRUE); // remove body 
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
			curl_setopt($ch,CURLOPT_COOKIEJAR,$cookieFileLocation); 
			curl_setopt($ch,CURLOPT_COOKIEFILE,$cookieFileLocation);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $push);
			$head = curl_exec($ch);
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
		}
	}
}

if ($newData == true) {
	$fh = fopen($file, 'w');
	$serializedData = serialize($sent);
	
	fwrite($fh, $serializedData);
	fclose($fh);
} else {
	echo "No new unsent notificiations." . PHP_EOL;
}

?>