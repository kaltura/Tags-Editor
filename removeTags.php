<?php
require_once("kalturaConfig.php");
//Retrieves the array of tags from a cached file created by getTagList.php
$tagArray = unserialize(file_get_contents(TAG_CACHE));
//Includes the client library and starts a Kaltura session to access the API
//More informatation about this process can be found at
//http://knowledge.kaltura.com/introduction-kaltura-client-libraries
require_once('lib/php5/KalturaClient.php');
$config = new KalturaConfiguration(PARTNER_ID);
$config->serviceUrl = 'http://www.kaltura.com/';
$client = new KalturaClient($config);
$ks = $client->generateSession(ADMIN_SECRET, USER_ID, KalturaSessionType::ADMIN, PARTNER_ID);
$client->setKs($ks);
//Formats the tags correctly
$tags = $_REQUEST['tags'];
//Removes the tags from the cache
foreach($tags as $deleteTag)
	unset($tagArray[$deleteTag]);

$tagString = implode(',', $tags);
$pager = new KalturaFilterPager();
$pageSize = 500;
$pager->pageSize = $pageSize;
$lastCreatedAt = 0;
$lastEntryIds = "";
$cont = true;
while($cont) {
	//Instead of using a page index, the entries are retrieved by creation date
	//This is the only way to ensure that the server retrieves all of the entries
	$filter = new KalturaMediaEntryFilter();
	$filter->orderBy = "-createdAt";
	$filter->tagsMultiLikeOr = $tagString;
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
	//For each entry in the server, the requested tags are removed
	foreach($results->objects as $place => $entry) {
		//Retrieves the old tags
		$oldTags = explode(',', $entry->tags);
		foreach($oldTags as $index => $tag) {
			$oldTags[$index] = trim($tag);
		}
		//Deletes the appropriate tags
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
file_put_contents(TAG_CACHE, serialize($tagArray));