<?php
require_once("kalturaConfig.php");
$cachetime = 5 * 60; //Cache time is 5 minutes
//Only updates the Existing Tags list every time the cache time expires
if (!file_exists(TAG_CACHE) ||	(time() - $cachetime > filemtime(TAG_CACHE))) {
	require_once('php5/KalturaClient.php');
	$config = new KalturaConfiguration(PARTNER_ID);
	$config->serviceUrl = 'http://www.kaltura.com/';
	$client = new KalturaClient($config);
	$ks = $client->generateSession(ADMIN_SECRET, USER_ID, KalturaSessionType::ADMIN, PARTNER_ID);
	$client->setKs($ks);
	
	$pager = new KalturaFilterPager();
	$pageSize = 500;
	$pager->pageSize = $pageSize;
	$lastCreatedAt = 0;
	$lastEntryIds = "";
	$cont = true;
	while($cont) {
		$filter = new KalturaMediaEntryFilter();
		$filter->orderBy = "-createdAt";
		if($lastCreatedAt != 0)
			$filter->createdAtLessThanOrEqual = $lastCreatedAt;
		if($lastEntryIds != "")
				$filter->idNotIn = $lastEntryIds;
		$results = $client->media->listAction($filter, $pager);
		if(count($results->objects) == 0) {
			$cont = false;
		}
		$entryIds = "";
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
//If the cache time has not passed yet, do not recreate the tag array
//Instead use the array that has been stored
else {
	$tagString = file_get_contents(TAG_CACHE);
	$tagArray = unserialize($tagString);
	//Creates a string of every tag and its respective frequeny and returns it
	$tagString = "";
	foreach($tagArray as $tag => $tagCount) $tagString .= $tag.' ('.$tagCount.'), ';
	echo substr($tagString, 0, -2);
}