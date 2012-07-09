<?php
//Calls the Big Huge Thesaurus API to look up synonyms
require_once("kalturaConfig.php");
$lookup = json_decode($_REQUEST['lookup']);
$synonyms = array();
foreach($lookup as $word) {
	$response = @file_get_contents('http://words.bighugelabs.com/api/2/'.BIG_HUGE_THESAURUS_KEY.'/'.$word.'/php');
	if($response === false)
		$synonyms[] = "";
	else
		$synonyms[] = unserialize($response);
}
echo json_encode($synonyms);