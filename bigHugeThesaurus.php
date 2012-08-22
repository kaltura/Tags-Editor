<?php
//Calls the Big Huge Thesaurus API to look up synonyms

function getSynonyms($array) {
	$synonyms = array();
	foreach($array as $word) {
		$response = @file_get_contents('http://words.bighugelabs.com/api/2/'.BIG_HUGE_THESAURUS_KEY.'/'.$word.'/php');
		if($response === false)
			$synonyms[] = "";
		else
			$synonyms[] = unserialize($response);
	}
	$response = array();
	$count = 0;
	foreach($synonyms as $index) {
		if($index != "") {
			foreach($index as $type) {
				if(isset($type['syn'])) {
					foreach($type['syn'] as $word)
						$response[$count][] = $word;
				}
			}
		}
		else
			$response[$count] = array();
		++$count;
	}
	echo json_encode($response);
}