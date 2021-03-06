<?php 
require_once("kalturaConfig.php");
//Includes the client library and starts a Kaltura session to access the API
//More informatation about this process can be found at
//http://knowledge.kaltura.com/introduction-kaltura-client-libraries
require_once('lib/php5/KalturaClient.php');
$config = new KalturaConfiguration(PARTNER_ID);
$config->serviceUrl = 'http://www.kaltura.com/';
$client = new KalturaClient($config);
$ks = $client->generateSession(ADMIN_SECRET, USER_ID, KalturaSessionType::ADMIN, PARTNER_ID);
$client->setKs($ks);
//Retrieves the array of tags from a cached file created by getTagList.php
$tagArray = unserialize(file_get_contents(TAG_CACHE));
$tags = explode(',', $_REQUEST["tags"]);
//Adds each new tag to the tags cache
foreach($tags as $tag) {
	$tag = strtolower(trim($tag));
	if(!array_key_exists($tag, $tagArray) && $tag !== "") {
		$tagArray[$tag] = 0;
	}
}
//Stores the new array of tags back into the cache
file_put_contents(TAG_CACHE, serialize($tagArray));