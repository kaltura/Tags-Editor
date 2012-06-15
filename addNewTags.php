<?php 
require_once("kalturaConfig.php");
require_once('php5/KalturaClient.php');
$config = new KalturaConfiguration(PARTNER_ID);
$config->serviceUrl = 'http://www.kaltura.com/';
$client = new KalturaClient($config);
$ks = $client->generateSession(ADMIN_SECRET, USER_ID, KalturaSessionType::ADMIN, PARTNER_ID);
$client->setKs($ks);
$tagArray = unserialize(file_get_contents(TAG_CACHE));
$tags = explode(',', $_REQUEST["tags"]);
//Adds each new tag to the tags cache
foreach($tags as $tag) {
	$tag = strtolower(trim($tag));
	if(!array_key_exists($tag, $tagArray) && $tag !== "") {
		$tagArray[$tag] = 0;
	}
}
file_put_contents(TAG_CACHE, serialize($tagArray));