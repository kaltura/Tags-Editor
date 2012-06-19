Kaltura Tag Editor
==================
An efficient way to edit the tags for all the media entries in your account. 

Files
-----

* addNewTags.php - Adds new tags to the cache for efficient applying of tags to entries
* entriesLayout.css - The styling for the front page
* getTagList.php - Generates the tag cache for your entries and displays them
* index.php - The front page for all the entries and calling the scripts
* kalturaConfig.php - Stores all the constants such as the cache file and your authorization information
* player.php - Used to display the Kaltura player for the entries
* reloadEntries.php - Displays the current page of entries
* reloadRemoveTagsSelect.php - Displays an up to date multiple select to delete tags
* removeTags.php - Deletes any tags requested from the tag cache and removes them from entries
* tagCaching.txt - Where the array of tags is stored for efficient loading
* updateEntry.php - Updates an entry whenever tags are added or removed

Folders
-------

* lib - Contains scripts and images needed for displaying the page and player
* lib/chosen - Contains the Chosen javascript plugin
	(http://harvesthq.github.com/chosen/)
* lib/loadmask - Contains the loadmask jQuery plugin
	(http://code.google.com/p/jquery-loadmask/)
* lib/php5 - Contains the Kaltura PHP5 client library
	(http://www.kaltura.com/api_v3/testme/client-libs.php)