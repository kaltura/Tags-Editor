<?php
//This calls the PHP Aiksaurus application to retrieve the synonyms
//This application must be installed on your server for this to work

function getSynonyms($array) {
	$response = array();
	$count = 0;
	foreach($array as $word) {
	        if($word != "") {
	        	$ret = exec("aiksaurus \"$word\"", $out);
		        if ($out[0] == "*** No synonyms known. ***") {
		                $response[$count] = array();
		        }
		        else {
		        	$response[$count] = array();
	                for($i = 0; $i < count($out); ++$i) {
	                        if (ereg("^===", $out[$i])) {
	                                continue;
	                        }
	                        else if ($out[$i] == "") {
	                                continue;
	                        }
	                        else {
	                        	$syns = explode(',', $out[$i]);
	                        	foreach ($syns as $syn) {
	                                $response[$count][] = trim($syn);
	                        	}
	                        }
	                }
		        }
		        unset($out);
			}
			else
				$response[$count] = array();
			++$count;
	}
	echo json_encode($response);
}