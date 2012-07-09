<!DOCTYPE HTML>
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
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>Tags Editor</title>
	<!-- Style Includes -->
	<link href="entriesLayout.css" media="screen" rel="stylesheet" type="text/css" />
	<link href="lib/facebox.css" media="screen" rel="stylesheet" type="text/css" />
	<link rel="stylesheet" href="lib/chosen/chosen.css" />
	<link href="lib/loadmask/jquery.loadmask.css" rel="stylesheet" type="text/css" />
	<!-- Script Includes -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
	<script src="lib/chosen/chosen.jquery.js" type="text/javascript"></script>
	<script src="lib/facebox.js" type="text/javascript"></script>
	<script src="http://cdnbakmi.kaltura.com/html5/html5lib/v1.6.12.16/mwEmbedLoader.php" type="text/javascript"></script>
	<script type="text/javascript" src="lib/loadmask/jquery.loadmask.min.js"></script>
	<script type="text/javascript" src="lib/jquery.json-2.3.min.js"></script>
	
	<!-- Page Scripts -->
	<script type="text/javascript">
		//Tells the synonym script which API to use
		var useBigHugeLabs = true;	
		//Local copy of the tag list
		var tagArray = [];
		//are we loading the page or just calling ajax triggerd by user interaction?
		var firstload = true;
		//Keeps track of the page being viewed
		var currentPage = 1;
		$(document).ready(function($) {
			$.facebox.settings.closeImage = './lib/closelabel.png';
			$.facebox.settings.loadingImage = './lib/loading.gif';

			if(<?php echo '"'.ADMIN_SECRET.'"'; ?> == 'xxxx' || <?php echo '"'.PARTNER_ID.'"'; ?> == 000) {
				$('#addTagsButton').attr('disabled', 'disabled');
				$('#removeTagsButton').attr('disabled', 'disabled');
				$('#searchButton').attr('disabled', 'disabled');
				$('#showButton').attr('disabled', 'disabled');
				$('#searchBar').attr('disabled', 'disabled');
				$('#addTagsInput').attr('disabled', 'disabled');
				$('#removeTagsSelect').attr('disabled', 'disabled');
			}
			else {
				$('.notep').hide();
				//When the page loads, show the tag list, the entries, and the remove tags multiselect
				updateTagList();
				$('#searchBar').keyup(function(event) {
					if(event.which == 13)
						showEntries();
				});
				$('#addTagsInput').keyup(function(event) {
					if(event.which == 13)
						addTags();
					else
						findWords();
				});
				jQuery('.czntags').chosen({search_contains: true});
			}
		});

		//Every time a keystroke is recorded, this function scans the tags list
		//and creates a list of synonyms to highlight any tags "similar" or synonymous
		//to the new tags being entered
		function findWords() {
			var background = $('#tagDiv').css('background-color');
			var newTags = $('#addTagsInput').val().split(/,\s*/gi);
			//Calls the script that handles synonym retrieval
			if(useBigHugeLabs) {
				var synonyms = [];
				$.ajax({
					type: "POST",
					url: "getSynonyms.php",
					data: {lookup: $.toJSON(newTags)}
				}).done(function(msg) {
					var synonymsList = $.evalJSON(msg);
					console.log(synonymsList);
					for(var i = 0; i < synonymsList.length; ++i) {
						words = "";
						for(var field in synonymsList[i]) {
							for(var syn in synonymsList[i][field]) {
								if(syn = 'syn')
									words += synonymsList[i][field][syn];
							}
						}
						synonyms[i] = words.split(/,\s*/gi);
					}
					//If a tag already on the server matches the string of a new tag,
					//or is synonymous with a new tag, it is highlighted yellow
					for(var i = 0; i < tagArray.length; ++i) {
						var tagFound = false;
						for(var j = 0; j < newTags.length; ++j) {
							if(newTags[j].length > 1 && tagArray[i].search(newTags[j]) != -1 || jQuery.inArray(tagArray[i], synonyms[j]) != -1)
								$("#tagDiv span").eq(i).css("background-color","yellow");
							else
								$("#tagDiv span").eq(i).css("background-color", background);
						}
					}
				});
			}
			else {
				$.ajax({
					  type: "POST",
					  url: <?php echo PHP_AIKSAURUS; ?>,
					  data: {lookup: $.toJSON(newTags)}
					}).done(function(msg) {
						var synonyms = $.evalJSON(msg);
						//Creates a parallel array of synonyms for the tags being added
						for(var i = 0; i < synonyms.length; ++i)
							synonyms[i] = synonyms[i].split(/,\s*/gi);
						//If a tag already on the server matches the string of a new tag,
						//or is synonymous with a new tag, it is highlighted yellow
						for(var i = 0; i < tagArray.length; ++i) {
							var tagFound = false;
							for(var j = 0; j < newTags.length; ++j) {
								if(newTags[j].length > 1 && tagArray[i].search(newTags[j]) != -1 || jQuery.inArray(tagArray[i], synonyms[j]) != -1)
									$("#tagDiv span").eq(i).css("background-color","yellow");
								else
									$("#tagDiv span").eq(i).css("background-color", background);
							}
						}
				});
			}
		}
		
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
				//Seperates the tags by span so that the appropriate
				//tags can be highlighted if matched
				var tagList = msg.split(', ').sort();
				var tags = tagList.join("</span>, <span>");
				$('#tagDiv').html("<span>" + tags + "</span>");
				tagArray = msg.replace(/ \([0-9]+\)/gi, "").split(', ').sort();
				if (firstload) {
					showEntries();
					reloadRemoveTags();
					firstload = false;
				}
			});
		}

		//Adds tags to the list
		function addTags() {
			if($('#addTagsInput').val() != "") {
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
		}

		//Remove tags from the list and the entries
		function removeTags() {
			if($('#removeTagsSelect').val() != null) {
				$('#loadBar').show();
				$('#tagDiv').hide();
				$('#userTags').mask();
				$.ajax({
					type: "POST",
					url: "removeTags.php",
					data: {tags: $('#removeTagsSelect').val()}
				}).done(function(msg) {
					$('#userTags').unmask();
						reloadRemoveTags();
						$('#loadBar').hide();
						updateTagList();
						if($('#searchBar').val() == "")
							showEntries(currentPage);
						else
							showEntries();
				});
			}
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
	<a href="https://github.com/kaltura/Tags-Editor"><img style="position: absolute; top: 0; left: 0; border: 0;" src="https://s3.amazonaws.com/github/ribbons/forkme_left_darkblue_121621.png" alt="Fork me on GitHub"></a>
	<div id="wrapper">
		<div><h1>Existing tags:</h1></div>
		<div class="notep">NOTE: Make sure to set your partner id and admin secret in kalturaConfig.php</div>
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
			<div class="clear"></div>
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