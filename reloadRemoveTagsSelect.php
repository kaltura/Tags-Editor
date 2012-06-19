<?php
require_once("kalturaConfig.php");
//Retrieves the array of tags from a cached file created by getTagList.php
$tagArray = unserialize(file_get_contents(TAG_CACHE));
//Creates the multiselect for selecting which tags the user would like to remove
echo '<select class="czntags" id="removeTagsSelect" data-placeholder="Select tags" style="width:350px;" multiple="multiple">';
	foreach($tagArray as $tag => $tagCount) {
       	echo '<option value="'.$tag.'">'.$tag.'</option>';
   	}
echo '</select>';