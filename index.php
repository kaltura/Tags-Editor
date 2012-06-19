<!DOCTYPE HTML>
<?php
require_once("kalturaConfig.php");
//Retrieves the array of tags from a cached file created by getTagList.php
$tagString = file_get_contents(TAG_CACHE);
$tagArray = unserialize($tagString);
//Includes the client library and starts a Kaltura session to access the API
//More informatation about this process can be found at 
//http://knowledge.kaltura.com/introduction-kaltura-client-libraries
require_once('lib/php5/KalturaClient.php');
$config = new KalturaConfiguration(PARTNER_ID);
$config->serviceUrl = 'http://www.kaltura.com/';
$client = new KalturaClient($config);
$ks = $client->generateSession(ADMIN_SECRET, USER_ID, KalturaSessionType::ADMIN, PARTNER_ID);
$client->setKs($ks);

?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>Tags Editor</title>
	<link rel="stylesheet" href="lib/chosen/chosen.css" />
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
    <script src="lib/chosen/chosen.jquery.js" type="text/javascript"></script>
	<link href="entriesLayout.css" media="screen" rel="stylesheet" type="text/css" />
	<link href="lib/facebox.css" media="screen" rel="stylesheet" type="text/css" />
	<script src="lib/facebox.js" type="text/javascript"></script>
	<script src="http://cdnbakmi.kaltura.com/html5/html5lib/v1.6.12.16/mwEmbedLoader.php" type="text/javascript"></script>
	<script type="text/javascript" src="lib/loadmask/jquery.loadmask.min.js"></script>
	<link href="lib/loadmask/jquery.loadmask.css" rel="stylesheet" type="text/css" />
	<script type="text/javascript">
		//Keeps track of the page being viewed
		var currentPage = 1;
		
		$(document).ready(function($) {
			$.facebox.settings.closeImage = './lib/closelabel.png';
			$.facebox.settings.loadingImage = './lib/loading.gif';

			//When the page loads, show the tag list, the entries, and the remove tags multiselect
			updateTagList();
			showEntries(1);
			reloadRemoveTags();

			jQuery('.czntags').chosen({search_contains: true});
		});
		
		//Responds to the page number index that is clicked
		function pagerClicked (pageNumber, search)	{
			currentPage = pageNumber;
			showEntries(pageNumber, search);
		}

		//Called whenever the user submits new tags for an entry
		function tagSubmit(id, entryCount) {
			//Masks the entry until it is successfully updated
			$('#entry'+entryCount).mask("Loading...");
			$.ajax({
			  type: "POST",
			  url: "updateEntry.php",
			  data: {entryId: id, tags: $('#slct'+entryCount).val()}
			}).done(function(msg) {
				$('#loading_image'+entryCount).hide();
				$("#entry"+entryCount).unmask();
				$('#tagDiv').hide();
				updateTagList();
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
				$('#tagDiv').show();
				$('#tagDiv').text(msg);
			});
		}

		//Adds tags to the list
		function addTags() {
			$('#tagDiv').hide();
			$('#loadBar').show();
			$('#userTags').mask();
			$.ajax({
				type: "POST",
				url: "addNewTags.php",
				data: {tags: $('#addTagsInput').val()}
			}).done(function(msg) {
				$('#userTags').unmask();
				$('#addTagsInput').val('');
				$('#loadBar').hide();
				updateTagList();
				if($('#searchBar').val() == "")
					showEntries(currentPage);
				else
					showEntries();
				reloadRemoveTags();
			});
		}

		//Remove tags from the list and the entries
		function removeTags() {
			$('#loadBar').show();
			$('#tagDiv').hide();
			$('#userTags').mask();
			$.ajax({
				type: "POST",
				url: "removeTags.php",
				data: {tags: $('#removeTagsSelect').val()}
			}).done(function(msg) {
				$('#userTags').unmask();
				if(msg !== "null") {
					reloadRemoveTags();
					$('#loadBar').hide();
					updateTagList();
					if($('#searchBar').val() == "")
						showEntries(currentPage);
					else
						showEntries();
				}
			});
		}

		//Show all the entries for a given page based on search terms or lack thereof
		function showEntries(page, terms) {
			if(terms == "")
				$('#searchBar').val('');
			$('#entryLoadBar').show();
			$('#entryList').hide();
			$.ajax({
				type: "POST",
				url: "reloadEntries.php",
				data: {pagenum: page, search: $('#searchBar').val()}
			}).done(function(msg) {
					$('#entryLoadBar').hide();
					$('#entryList').show();
					$('#entryList').html(msg);
					$(".thumblink").click(function () {
						$.facebox({ajax:'player.php?entryid=' + $(this).attr('rel')});
				    });
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
</head>
<body>
	<div id="wrapper">
		<div><h1>Existing tags:</h1></div>
		<div><img src="lib/loadBar.gif" style="display: none;" id="loadBar"></div>
		<div id="tagDiv"></div>
		<div id="userTags">
			<div class="addTagsDiv">Add tags (seperated by commas): 
				<input type="text" id="addTagsInput" value="">
				<button id="addTagsButton" class="addTagsButtonClass" type="button" onclick="addTags()">Submit</button>
			</div>
			<div class="removeTagsDiv"><div class="removeTagTextDiv">Remove tags: </div>
				<div class="removeTagSelectDiv" id="removeSelect"></div>
				<button id="removeTagsButton" class="removeTagsButtonClass" type="button" onclick="removeTags()">Submit</button>
			</div>
		</div>
		<div>
			<h1>List of entries:</h1>
			<p>Enter the tags for a media entry and click submit to update those tags.</p>
		</div>
		<div class="searchDiv">
			Search by name, description, or tags: <input type="text" id="searchBar" autofocus="autofocus">
			<button id="searchButton" class="searchButtonClass" type="button" onclick="showEntries()">Search</button>
			<button id="showButton" type="button" onclick="showEntries(1, '')">Show All</button>
		</div>
	</div>
	<div class="capsule">
		<img src="lib/loadBar.gif" style="display: none;" id="entryLoadBar">
		<div id="entryList"></div>
	</div>
</body>
</html>