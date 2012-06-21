<?php
require_once("kalturaConfig.php");
//We'll cache the tags for a day to avoid long waiting with large media libraries
$cachetime = 24 * 60 * 60; 
//If the cache time has not passed yet, do not recreate the tag array
//Instead use the array that has been stored
if (file_exists(TAG_CACHE) && (time() - $cachetime < filemtime(TAG_CACHE))) {
	$tagString = @file_get_contents(TAG_CACHE);
	$tagArray = unserialize($tagString);
	//Creates a string of every tag and its respective frequeny and returns it
	$tagString = "";
	foreach($tagArray as $tag => $tagCount) $tagString .= $tag.' ('.$tagCount.'), ';
	echo substr($tagString, 0, -2);
}
//Only updates the tags list from the Kaltura API (media.list) when the cache has expired
else {
	//Includes the client library and starts a Kaltura session to access the API
	//More informatation about this process can be found at:
	//http://knowledge.kaltura.com/introduction-kaltura-client-libraries
	require_once('lib/php5/KalturaClient.php');
	$config = new KalturaConfiguration(PARTNER_ID);
	$config->serviceUrl = 'http://www.kaltura.com/';
	$client = new KalturaClient($config);
	$ks = $client->generateSession(ADMIN_SECRET, USER_ID, KalturaSessionType::ADMIN, PARTNER_ID);
	$client->setKs($ks);
	//Creates a pager that can parse the entries (API's limit is 500 entries per request, so we get the maximum)
	$pager = new KalturaFilterPager();
	$pageSize = 500;
	$pager->pageSize = $pageSize;
	$lastCreatedAt = 0;
	$lastEntryIds = "";
	$cont = true;
	$tagArray = array();
	while($cont) {
		//Instead of using a page index, the entries are retrieved by creation date
		//This is the only way to ensure that the server retrieves all of the entries
		$filter = new KalturaMediaEntryFilter();
		$filter->orderBy = "-createdAt";
		//Ignores entries that have already been parsed
		if($lastCreatedAt != 0)
			$filter->createdAtLessThanOrEqual = $lastCreatedAt;
		if($lastEntryIds != "")
				$filter->idNotIn = $lastEntryIds;
		$results = $client->media->listAction($filter, $pager);
		//If no entries are retrieved the loop may end
		if(count($results->objects) == 0) {
			$cont = false;
		}
		//For each entry retrieved, the tags are counted and added to the array
		foreach($results->objects as $entry) {			
			$tags = explode(',', $entry->tags);
			foreach ($tags as $tag) {
				$tag = trim($tag);
				if ($tag == "") continue;
				//If the tag has already been discovered, increment its frequency
				if (isset($tagArray[$tag]))
					++$tagArray[$tag];
				//Otherwise, add the new tag to the array
				else
					$tagArray[$tag] = 1;
			}
			//Keeps a tally of which creation dates were examined
			//and which entry ids have already been seen
			if($lastCreatedAt != $entry->createdAt)
				$lastEntryIds = "";
			if($lastEntryIds != "")
				$lastEntryIds .= ",";
			$lastEntryIds .= $entry->id;
			$lastCreatedAt = $entry->createdAt;
		}
	}
	//Stores the array of tags in a file that can be retrieved immediately for faster loading time
	$tagArrayString = serialize($tagArray);
	file_put_contents(TAG_CACHE, $tagArrayString);
	//Creates a string of every tag and its respective frequeny and returns it
	$tagString = "";
	foreach($tagArray as $tag => $tagCount) $tagString .= $tag.' ('.$tagCount.'), ';
	echo substr($tagString, 0, -2);
}