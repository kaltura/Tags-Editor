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

$page = 1;
if(array_key_exists('pagenum', $_REQUEST))
	$page = $_REQUEST['pagenum'];
$search = "";
if(array_key_exists('search', $_REQUEST))
	$search = $_REQUEST['search'];
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
				window.location = "?pagenum=" + pageNumber + "&search=" + search;
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
				if($('#searchBar').val() == "") {
					showAllEntries("<?php echo $page;?>", "<?php echo $search; ?>");
				}
				else {
					searchEntries();
				}
				reloadRemoveTags();
			});
		}
		//Remove tags from the list and the entries
		function removeTags() {
			$.ajax({
				type: "POST",
				url: "removeTags.php",
				data: {tags: $('#removeTagsSelect').val()}
			}).done(function(msg) {
				if(msg !== "null") {
					reloadRemoveTags();
					updateTagList();
					alert(msg);
					if($('#searchBar').val() == "") {
						showAllEntries("<?php echo $page;?>", "<?php echo $search; ?>");
					}
					else {
						searchEntries();
					}
				}
			});
		}
		//Shows the entires that result from the search terms
		function searchEntries() {
			$.ajax({
				type: "POST",
				url: "reloadEntries.php",
				data: {search: $('#searchBar').val()}
			}).done(function(msg) {
					updateTagList();
					$('#entryList').html(msg);
					jQuery('.czntags').chosen({search_contains: true});
			});
		}
		//Show all the entries
		function showAllEntries(page, terms) {
			$('#entryLoadBar').show();
			$.ajax({
				type: "POST",
				url: "reloadEntries.php",
				data: {search: terms, pagenum: page}
			}).done(function(msg) {
					$('#entryLoadBar').hide();
					$('#entryList').html(msg);
					$('#searchBar').val('');
					jQuery('.czntags').chosen({search_contains: true});
			});
		}
		//Refreshes the multiselect bar for removing tags
		function reloadRemoveTags() {
			$.ajax({
				url: "reloadRemoveTagsSelect.php"
			}).done(function(msg) {
				$('#removeSelect').html(msg);
				jQuery('.czntags').chosen({search_contains: true});
			});
		}
	</script>
	<script>
		//When the page loads, show the tag list, the entries, and the remove tags multiselect
		$(document).ready(function() {
			updateTagList();
			showAllEntries("<?php echo $page; ?>", "<?php echo $search; ?>");
			reloadRemoveTags();
		});
	</script>
</head>
<body>
<div id="wrapper">
	<div><h1>Existing tags:</h1></div>
	<div id="tagDiv"><img src="loadBar.gif" style="display: none;" id="loadBar"></div>
	<div class="addTagsDiv">Add tags (seperated by commas): 
		<input type="text" id="addTagsInput" value="">
		<button id="addTagsButton" class="addTagsButtonClass" type="button" onclick="addTags()">Submit</button>
	</div>
	<div class="removeTagsDiv"><div class="removeTagTextDiv">Remove tags: </div>
		<div class="removeTagSelectDiv" id="removeSelect"></div>
		<button id="removeTagsButton" class="removeTagsButtonClass" type="button" onclick="removeTags()">Submit</button>
	</div>
	<div><h1>List of entries:</h1>
	<p>Enter the tags for a media entry and click submit to update those tags.</p></div>
	<div class="searchDiv">
		Search by name, description, or tags: <input type="text" id="searchBar" autofocus="autofocus">
		<button id="searchButton" class="searchButtonClass" type="button" onclick="searchEntries()">Search</button>
		<?php 
			echo '<button id="showButton" type="button" onclick="showAllEntries(1)">Show All</button>';
		?>
	</div>
</div>
<div class="capsule" id="entryList"><img src="loadBar.gif" style="display: none;" id="entryLoadBar"></div>
</body>
</html>