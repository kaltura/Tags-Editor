<?php
require_once("kalturaConfig.php");
$tagArray = unserialize(file_get_contents(TAG_CACHE));
echo '<select class="czntags" id="removeTagsSelect" data-placeholder="Select tags" style="width:350px;" multiple="multiple">';
	foreach($tagArray as $tag => $tagCount) {
       	echo '<option value="'.$tag.'">'.$tag.'</option>';
   	}
echo '</select>';