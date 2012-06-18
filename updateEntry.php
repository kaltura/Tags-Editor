<?php
require_once("kalturaConfig.php");
require_once('lib/php5/KalturaClient.php');
$config = new KalturaConfiguration(PARTNER_ID);
$config->serviceUrl = 'http://www.kaltura.com/';
$client = new KalturaClient($config);
$ks = $client->generateSession(ADMIN_SECRET, USER_ID, KalturaSessionType::ADMIN, PARTNER_ID);
$client->setKs($ks);
//Retrives the correct entry
$entryId = $_REQUEST["entryId"];
$version = null;
$result = $client->media->get($entryId, $version);
//Gets the tags for the entry before the update occurs
$oldTags = explode(',', $result->tags);
$tagArray = unserialize(file_get_contents(TAG_CACHE));
//Decreases the frequency for tags being removed
foreach($oldTags as $index => $tag) {
	if ($tag == "") continue;
	$tag = trim($tag);
	$oldTags[$index] = $tag;
	//If an entry used to have a tag but it has been removed, decrease the frequency
	if(is_array($_REQUEST["tags"])) {
		if(!in_array($tag, $_REQUEST["tags"])) {
			--$tagArray[$tag];
		}
	}
	else {
		--$tagArray[$tag];
	}
}

//Stores all the tags for an entry that is being updated
if(is_array($_REQUEST["tags"])) {
		foreach($_REQUEST["tags"] as $tag) {
			//If a tag has been added that the entry did not previously possess, increase the frequency
			if(!in_array($tag, $oldTags)) {
				if (isset($tagArray[$tag]))
					++$tagArray[$tag];
				else
					$tagArray[$tag] = 1;
			}
		}	
		$joinedTags = implode(', ', $_REQUEST["tags"]);
		$mediaEntry = new KalturaMediaEntry();
		$mediaEntry->tags = $joinedTags;
		$updateResults = $client->media->update($entryId, $mediaEntry);
}
//If no tags are entered then the entry's tags are cleared
else {
	$mediaEntry = new KalturaMediaEntry();
	$mediaEntry->tags = "";
	$updateResults = $client->media->update($entryId, $mediaEntry);
}
//Updates the cache with the most recent frequency array of tags
file_put_contents(TAG_CACHE, serialize($tagArray));
//Returns the the update was successful
print "Tags updated";