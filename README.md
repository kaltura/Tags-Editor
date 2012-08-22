Kaltura Tags Editor
==================
An efficient way to edit the tags for all the media entries in your account. 

Files
-----

* addNewTags.php - Adds new tags to the cache for efficient applying of tags to entries
* entriesLayout.css - The styling for the front page
* getSynonyms.php - Calls the Big Huge Thesaurus API to look up synonyms
* getTagList.php - Generates the tag cache for your entries and displays them
* index.php - The front page for all the entries and calling the scripts
* kalturaConfig.php - Stores all the constants such as the cache file and your authorization information
* player.php - Used to display the Kaltura player for the entries
* reloadEntries.php - Displays the current page of entries
* reloadRemoveTagsSelect.php - Displays an up to date multiple select to delete tags
* removeTags.php - Deletes any tags requested from the tag cache and removes them from entries
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
	
Note
----
* For faster loading, upon the first load of the page a file named tagCaching.txt is created to store the array of tags. This cache is updated when the tool is being used but any new tags that are entered elsewhere (eg., KMC or through the API) will not show up until the cache file is deleted.
* By default, the script used to find synonyms is set to use PHP Aiksaurus. To use the Big Huge Thesaurus API instead, set USE_BIG_HUGE_THESAURUS to false in kalturaConfig.php and enter your API key as well.
* If you would like to use your own synonym generating engine, modify findWords() in index.php and getSynonyms.php to properly format the data retrieval and implementation. The "synonyms" array should be 2-dimensional and the indices should be parallel to the words in the newTags array. If your engine returns that no synonyms are found, there should be an empty string at that index.