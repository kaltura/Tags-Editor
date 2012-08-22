<?php
//Framework for grabbing synonyms from the server
//The lookup variable is an array of words that have been entered into the "Add Tags" field
//getSynonyms must echo a JSON 2-dimensional array
//You can achieve this by calling json_encode($yourArray) 
//The format of the array is as follows:
//$yourArray[$wordYouLookedUp][$j] where every index of $yourArray[$wordYouLookedUp]
//is another synonym for $wordYouLookedUp
//If the user have typed in this in the add tags field (minus the quotes): "financing, learning"
//The response two-dimensional array would look as such:
//[["funding","finance"],["acquisition","eruditeness","erudition","learnedness","scholarship","encyclopedism","encyclopaedism","basic cognitive process","education"]]
//You may look over either bigHugeThesaurus.php or phpAiksaurus.php to see how these arrays are generated
require_once("kalturaConfig.php");
require_once(THESAURUS);
getSynonyms($_REQUEST['lookup']);