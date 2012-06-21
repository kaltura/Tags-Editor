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

//Filters the entries so that they are ordered by descending creation order
//In other words, the newer videos show up on the front page
$filter = new KalturaMediaEntryFilter();
$filter->orderBy = KalturaPlayableEntryOrderBy::CREATED_AT_DESC;
$pager = new KalturaFilterPager();
//Displays 20 entries per page
$pageSize = 20;
$page = 1;
//Retrieves the correct page number
if(array_key_exists('pagenum', $_REQUEST))
	$page = $_REQUEST['pagenum'];
//If a search has been made, display only the entries that match the search terms
$search = trim($_REQUEST['search']);
function escapeChar($input)
{
	$input = '\\'.$input[0];
	return $input;
}
$search = preg_replace_callback('|[#-/]|','escapeChar',$search);
$search = preg_replace_callback('|!|','escapeChar',$search);
$search = preg_replace_callback('|"|','escapeChar',$search);
$search = preg_replace_callback('|-|','escapeChar',$search);
$search = preg_replace_callback('|\\/|','escapeChar',$search);
$filter->freeText = $search;
$pager->pageSize = $pageSize;
$pager->pageIndex = $page;
$results = $client->media->listAction($filter, $pager);
$count = $results->totalCount;

//Creates an array that lists the tags for each entry
$tagsList = array();
$j = 0;
foreach ($results->objects as $entry) {
	$tagsList[$j] = $entry->tags;
	$j++;
}
	
//This function creates a link to other entry pages
function create_gallery_pager  ($pageNumber, $current_page, $pageSize, $count, $js_callback_paging_clicked) {
	$search = trim($_REQUEST['search']);
	$pageNumber = (int)$pageNumber;
	$b = (($pageNumber+1) * $pageSize) ;
	$b = min ( $b , $count ); // don't let the page-end be bigger than the total count
	$a = min($pageNumber * $pageSize + 1,$count - ($count % $pageSize) + 1);
	$veryLastPage = (int)($count / $pageSize);
	$veryLastPage += ($count % $pageSize == 0) ? 0 : 1;
	if($pageNumber == $veryLastPage) {
		$pageToGoTo = $pageNumber;
		$pageToGoTo += (($pageNumber + 1) * $pageSize > $count) ? 0 : 1;
	}
	else
		$pageToGoTo = $pageNumber + 1;
	if ($pageToGoTo == $current_page)
		$str = "[<a title='{$pageToGoTo}' href='javascript:{$js_callback_paging_clicked} ($pageToGoTo, \"$search\")'>{$a}-{$b}</a>] ";
	else
		$str =  "<a title='{$pageToGoTo}' href='javascript:{$js_callback_paging_clicked} ($pageToGoTo, \"$search\")'>{$a}-{$b}</a> ";
	return $str;
}
//The server may pull entries up to the hard limit. This number should not exceed 10000.
$hardLimit = 2000;
$pagerString = "";
$startPage = max(1, $page - 5);
$veryLastPage = (int)($count / $pageSize);
$veryLastPage += ($count % $pageSize == 0) ? 0 : 1;
$veryLastPage = min((int)($hardLimit / $pageSize), $veryLastPage);
$endPage = min($veryLastPage, $startPage + 10);
//Iterates to create several page links
for ($pageNumber = $startPage; $pageNumber < $endPage; ++$pageNumber) {
	$pagerString .= create_gallery_pager ($pageNumber , $page  , $pageSize , $count , "pagerClicked");
}

$beforePageString = "";
$afterPageString = "";
$prevPage = $page - 1;
if($page > 1) $beforePageString .= "<a title='{$prevPage}' href='javascript:pagerClicked ($prevPage, \"$search\")'>Previous</a> ";
// add page 0 if not in list
if($startPage == 1 && $count > 0) $beforePageString .= create_gallery_pager(0, $page, $pageSize, $count, "pagerClicked");
$nextPage = $page + 1;
if ($page < $veryLastPage) $afterPageString .= "<a title='{$nextPage}' href='javascript:pagerClicked ($nextPage, \"$search\")'>Next</a> ";
$pagerString = "<span style=\"color:#ccc;\">Total (" . $count . ") </span>" . $beforePageString . $pagerString . $afterPageString;

echo '<div class="pagerDiv">'.$pagerString.'</div>';
//Uses a counter to keep track of each entry on the page
//Many elements such as id's and name's rely on this counter
$count = 0;
//Loops through every entry on your current page
foreach ($results->objects as $result) {
	//Creates a thumbnail that can be clicked to view the content
	$name = $result->name;
	$type = $result->mediaType;
	$id = $result->id;
	$display =  $result->thumbnailUrl ? "<img width='120' height='90' id='thumb$count' src='".$result->thumbnailUrl."' title='".$id." ".$name."' >" : "<div>".$id." ".$name."</div>";
	$thumbnail = "<a class='thumblink' rel='{$result->id}' href=\"#\">{$display}</a>";
	echo '<div id="entry'.$count.'">';
	echo '<div class="float1">';
		echo '<img src="lib/loading.gif" style="display: none; position: absolute;" id="loading_image'.$count.'">'.$thumbnail;
	echo '</div>';
	echo '<div class="float2">';
		echo '<select class="czntags" id="slct'.$count.'" name="'.$count.'[]" data-placeholder="Choose your tags" style="width:350px;" multiple="multiple">';
    		foreach($tagArray as $tag => $tagCount) {
        		echo '<option value="'.$tag.'"';
            		//Checks to see if the particular entry has any tags already
            		//Any tags found are pre-selected in the multiselect field
 					$pos = strpos($tagsList[$count],$tag);
 					if($pos === false) {}
 					else {
 						echo " selected";
 					}
					echo '>'.$tag;
					echo '</option>';
			}
	    echo '</select>';
	    echo '<button id="btn'.$count.'" class="btnClass" type="button" onclick="tagSubmit('."'".$id."'".','.$count.')">Submit</button>';
	echo '</div>';
    echo '</div>';
    echo '<div class="clear"></div>';
	++$count;
}