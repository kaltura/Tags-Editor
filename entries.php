<!DOCTYPE HTML>
<?php
//Retrieves the array of tags from a cached file created by getTagList.php
require_once("kalturaConfig.php");
if(file_exists(TAG_CACHE)) {
	$tagString = file_get_contents(TAG_CACHE);
	$tagArray = unserialize($tagString);
}
require_once('php5/KalturaClient.php');
$config = new KalturaConfiguration(PARTNER_ID);
$config->serviceUrl = 'http://www.kaltura.com/';
$client = new KalturaClient($config);
$ks = $client->generateSession(ADMIN_SECRET, USER_ID, KalturaSessionType::ADMIN, PARTNER_ID);
$client->setKs($ks);
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>Tag Editor</title>
	<link rel="stylesheet" href="chosen/chosen.css" />
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
    <script src="chosen/chosen.jquery.js" type="text/javascript"></script>
	<script>
		jQuery(document).ready(function () { 
		 jQuery('.czntags').chosen({search_contains: true})
		});
	</script>
	<link href="entriesLayout.css" media="screen" rel="stylesheet" type="text/css" />
	<link href="lib/facebox.css" media="screen" rel="stylesheet" type="text/css" />
	<script src="lib/facebox.js" type="text/javascript"></script>
	<script src="http://cdnbakmi.kaltura.com/html5/html5lib/v1.6.12.16/mwEmbedLoader.php" type="text/javascript"></script>
	<script type="text/javascript">
		$(document).ready(function($) {
			$.facebox.settings.closeImage = './lib/closelabel.png';
			$.facebox.settings.loadingImage = './lib/loading.gif';
		});
	</script>
	<script type="text/javascript" >
		function entryClicked (entry_id) {
			var playerurl = 'player.php?entryid=' + entry_id + '&partnerid=<?php echo PARTNER_ID; ?>';
			$.facebox({ajax:playerurl});
		}
		function pagerClicked (pageNumber, search)	{
			if(search != null)
				window.location = "?search=" + search + "&pagenum=" + pageNumber;
			else
				window.location = "?pagenum=" + pageNumber;
		}
	</script>
	<script type="text/javascript">
		//Positions a loader image on the thumbnail of the entry being updated
		function alignLoaderImage(entryCount) {
			var p = $('#thumb'+entryCount).position();
			pleft = p.left;
			ptop = p.top;
			$('#loading_image'+entryCount).css({"top": ptop+29, "left": pleft+44});
			$('#loading_image'+entryCount).show();
		}
		//Called whenever the user submits new tags for an entry
		function tagSubmit(id, entryCount) {
			alignLoaderImage(entryCount);
			$.ajax({
			  type: "POST",
			  url: "updateEntry.php",
			  data: {entryId: id, tags: $('#slct'+entryCount).val()}
			}).done(function(msg) {
				$('#loading_image'+entryCount).hide();
				$('#loadBar').show();
				updateTagList();
				//Announces that the tags have been updated
			    alert(msg);
			});
		}
		//Updates the existing tags for the entries
		function updateTagList() {
			$('#loadBar').show();
			$.ajax({
			  type: "POST",
			  url: "getTagList.php"
			}).done(function(msg) {
				$('#loadBar').hide();
				$('#tagDiv').text(msg);
			});
		}
		//Adds tags to the list
		function addTags() {
			$.ajax({
				type: "POST",
				url: "addNewTags.php",
				data: {tags: $('#addTagsInput').val()}
			}).done(function(msg) {
				$('#addTagsInput').val('');
				updateTagList();
				window.location.reload();
			});
		}
		function removeTags() {
			$.ajax({
				type: "POST",
				url: "removeTags.php",
				data: {tags: $('#removeTagsSelect').val()}
			}).done(function(msg) {
				if(msg !== "null") {
					updateTagList();
					alert(msg);
					window.location.reload();
				}
			});
		}
		function searchEntries() {
			$.ajax({
				type: "POST",
				url: "reloadEntries.php",
				data: {terms: $('#searchBar').val()}
			}).done(function(msg) {
				if(msg !== "null") {
					updateTagList();
					$('#entryList').html(msg);
					jQuery('.czntags').chosen({search_contains: true});
				}
			});
			
			//if($('#searchBar').val() != "") 
				//window.location="?search="+ $('#searchBar').val() + "&pagenum=1";
			//else
				//window.location="?pagenum=1";
		}
		function showAllEntries() {
			window.location = "?pagenum=1";
		}
	</script>
	<script>
		$(document).ready(function() {
			updateTagList();
		});
	</script>
</head>
<body>
<div id="wrapper">
<div><h1>Existing tags:</h1></div>
<div id="tagDiv"><img src="loadBar.gif" style="display: none;" id="loadBar"></div>
<?php 
	$filter = new KalturaMediaEntryFilter();
	$filter->orderBy = KalturaPlayableEntryOrderBy::CREATED_AT_DESC;
	$pager = new KalturaFilterPager();
	//Displays 20 entries per page
	$pageSize = 20;
	$page = 1;
	//Retrieves the correct page number
	if(array_key_exists('pagenum', $_REQUEST))
		$page = $_REQUEST['pagenum'];
	$pager->pageSize = $pageSize;
	$pager->pageIndex = $page;
	if(array_key_exists('search', $_REQUEST))
		$filter->freeText = $_REQUEST['search'];
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
		if ($pageToGoTo == $current_page) {
			if(array_key_exists('search', $_REQUEST)) {
				$search = $_REQUEST['search'];
				$str = "[<a title='{$pageToGoTo}' href='javascript:{$js_callback_paging_clicked} ($pageToGoTo, \"$search\")'>{$a}-{$b}</a>] ";
			}
			else
				$str = "[<a title='{$pageToGoTo}' href='javascript:{$js_callback_paging_clicked} ($pageToGoTo)'>{$a}-{$b}</a>] ";
		}
		else {
			if(array_key_exists('search', $_REQUEST)) {
				$search = $_REQUEST['search'];
				$str =  "<a title='{$pageToGoTo}' href='javascript:{$js_callback_paging_clicked} ($pageToGoTo, \"$search\")'>{$a}-{$b}</a> ";
			}
			else
				$str =  "<a title='{$pageToGoTo}' href='javascript:{$js_callback_paging_clicked} ($pageToGoTo)'>{$a}-{$b}</a> ";
		}
		return $str;
	}
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
	if($page > 1) $beforePageString .= "<a title='{$prevPage}' href='javascript:pagerClicked ($prevPage)'>Previous</a> ";
	// add page 0 if not in list
	if($startPage == 1) $beforePageString .= create_gallery_pager(0, $page, $pageSize, $count, "pagerClicked");
	$nextPage = $page + 1;
	if ($page < $veryLastPage) $afterPageString .= "<a title='{$nextPage}' href='javascript:pagerClicked ($nextPage)'>Next</a> ";
	$pagerString = "<span style=\"color:#ccc;\">Total (" . $count . ") </span>" . $beforePageString . $pagerString . $afterPageString;
?>
<div class="addTagsDiv">Add tags (seperated by commas): 
	<input type="text" id="addTagsInput" value="">
	<button id="addTagsButton" class="addTagsButtonClass" type="button" onclick="addTags()">Submit</button>
</div>
<div class="removeTagsDiv"><div class="removeTagTextDiv">Remove tags: </div>
	<div class="removeTagSelectDiv">
		<select class="czntags" id="removeTagsSelect" name="<?php print $count ?>[]" data-placeholder="Select tags" style="width:350px;" multiple="multiple">
			<?php foreach($tagArray as $tag => $tagCount) :?>
	        	<option value="<?php print $tag ?>"><?php print $tag; ?></option>
	    	<?php endforeach; ?>
	    </select>
    </div>
	<button id="removeTagsButton" class="removeTagsButtonClass" type="button" onclick="removeTags()">Submit</button>
</div>
<div><h1>List of entries:</h1>
<p>Enter the tags for a media entry and click submit to update those tags.</p></div>
<div class="searchDiv">
	Search by name, description, or tags: <input type="text" id="searchBar" autofocus="autofocus">
	<button id="searchButton" class="searchButtonClass" type="button" onclick="searchEntries()">Search</button>
	<?php 
		if(array_key_exists('search', $_REQUEST))
			print "<button id=\"showButton\" type=\"button\" onclick=\"showAllEntries()\">Show All</button>";
	?>
</div>
</div>
<div class="capsule" id="entryList">
<div class="pagerDiv"><?php echo $pagerString; ?></div>
<?php 
	//Uses a counter to keep track of each entry on the page
	//Many elements such as id's and name's rely on this counter
	$count = 0;
	//Loops through every entry on your current page
	foreach ($results->objects as $result) : 
		//Creates a thumbnail that can be clicked to view the content
		$name = $result->name;
		$type = $result->mediaType;
		$id = $result->id;
		$display =  $result->thumbnailUrl ? "<img width='120' height='90' id='thumb$count' src='".$result->thumbnailUrl."' title='".$id." ".$name."' >" : "<div>".$id." ".$name."</div>";
		$thumbnail = "<a href='javascript:entryClicked (\"$id\")'>{$display}</a>";
	?>
	<div class="float1">
		<img src="lib/loading.gif" style="display: none; position: absolute;" id="loading_image<?php echo $count ?>"><?php echo $thumbnail; ?>
	</div>
	<div class="float2">
		<select class="czntags" id="slct<?php print $count; ?>" name="<?php print $count ?>[]" data-placeholder="Choose your tags" style="width:350px;" multiple="multiple">
    		<?php foreach($tagArray as $tag => $tagCount) :?>
        		<option value="<?php print $tag ?>"
            		<?php
            			//Checks to see if the particular entry has any tags already
            			//Any tags found are pre-selected in the multiselect field
						$pos = strpos($tagsList[$count],$tag);
						if($pos === false) {}
						else {
							print " selected";
						}
					?>><?php print $tag; ?>
				</option>
       		<?php endforeach; ?>
        </select>
	</div>
    <div class="float3">
    	<button id="btn<?php print $count; ?>" class="btnClass" type="button" onclick="tagSubmit(<?php print "'".$id."'" ?>, <?php print $count; ?>)">Submit</button>
    </div>
    <div class="clear"></div>
	<?php ++$count; ?>
	<?php endforeach; ?>
</div>
</body>
</html>
