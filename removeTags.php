<?php
require_once("kalturaConfig.php");
require_once('php5/KalturaClient.php');
$tagArray = unserialize(file_get_contents(TAG_CACHE));
$config = new KalturaConfiguration(PARTNER_ID);
$config->serviceUrl = 'http://www.kaltura.com/';
$client = new KalturaClient($config);
$ks = $client->generateSession(ADMIN_SECRET, USER_ID, KalturaSessionType::ADMIN, PARTNER_ID);
$client->setKs($ks);
//Formats the tags correctly
$tags = $_REQUEST['tags'];
if($_REQUEST['tags'] != "null") {
	//Removes the tags from the cache
	foreach($tags as $deleteTag) {
		if(array_key_exists($deleteTag, $tagArray)) {
			unset($tagArray[$deleteTag]);
		}
	}
	$tagString = implode(',', $tags);
	//Creates a filter to only discover entries with the necessary tags
	$filter = new KalturaMediaEntryFilter();
	$filter->orderBy = KalturaPlayableEntryOrderBy::CREATED_AT_DESC;
	$filter->tagsMultiLikeOr = $tagString;
	$pager = new KalturaFilterPager();
	$pageSize = 500;
	$pager->pageSize = $pageSize;
	$pager->pageIndex = 1;
	$results = $client->media->listAction($filter, $pager);
	if(count($results->objects) > 0) {
		$count = $results->totalCount;
		$endPage = (int)($count / $pageSize);
		$endPage += ($count % $pageSize == 0) ? 0 : 1;
		//Iterates through all the pages of entries that match the filter
		for($pageNum = 1; $pageNum <= $endPage; ++$pageNum) {
				$pager->pageIndex = $pageNum;
				if($pageNum > 1)
					$results = $client->media->listAction($filter, $pager);
			foreach($results->objects as $place => $entry) {
				//Creates a temporary array for the entry's tags
				$oldTags = explode(',', $entry->tags);
				foreach($oldTags as $index => $tag) {
					$oldTags[$index] = trim($tag);
				}
				//Removes the appropriate tags from the array
				foreach($tags as $tag) {
					if(in_array($tag, $oldTags))
						unset($oldTags[array_search($tag, $oldTags)]);
				}
				//Updates the corresponding media entry with the new list of tags
				$joinedTags = implode(', ', $oldTags);
				$mediaEntry = new KalturaMediaEntry();
				$mediaEntry->tags = $joinedTags;
				$entryId = $results->objects[$place]->id;
				$updateResults = $client->media->update($entryId, $mediaEntry);
			}
		}
	}
	file_put_contents(TAG_CACHE, serialize($tagArray));
	print "Tags removed";
}
elseif(array_key_exists('null', $tagArray)) {
	unset($tagArray['null']);
	$pager = new KalturaFilterPager();
	$pageSize = 500;
	$pager->pageSize = $pageSize;
	$lastCreatedAt = 0;
	$lastEntryIds = "";
	$cont = true;
	while($cont) {
		$filter = new KalturaMediaEntryFilter();
		$filter->orderBy = "-createdAt";
		$filter->tagsMultiLikeOr = $tagString;
		if($lastCreatedAt != 0)
			$filter->createdAtLessThanOrEqual = $lastCreatedAt;
		if($lastEntryIds != "")
			$filter->idNotIn = $lastEntryIds;
		$results = $client->media->listAction($filter, $pager);
		if(count($results->objects) == 0) {
			$cont = false;
		}
		$entryIds = "";
		foreach($results->objects as $place => $entry) {
			$oldTags = explode(',', $entry->tags);
			foreach($oldTags as $index => $tag) {
				$oldTags[$index] = trim($tag);
			}
			unset($oldTags[array_search('null', $oldTags)]);
			//Updates the corresponding media entry with the new list of tags
			$joinedTags = implode(', ', $oldTags);
			$mediaEntry = new KalturaMediaEntry();
			$mediaEntry->tags = $joinedTags;
			$entryId = $results->objects[$place]->id;
			$updateResults = $client->media->update($entryId, $mediaEntry);
			
			if($lastCreatedAt != $entry->createdAt)
				$lastEntryIds = "";
			if($lastEntryIds != "")
				$lastEntryIds .= ",";
			$lastEntryIds .= $entry->id;
			$lastCreatedAt = $entry->createdAt;
		}
	}
}
else
	print "null";