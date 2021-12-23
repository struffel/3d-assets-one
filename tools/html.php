<?php
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/init.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/database.php';

	function readMetatagsFromHtmlString($htmlString){
		$output = [];
    	$document = domObjectFromHtmlString($htmlString);

    	$metaTags = $document->getElementsByTagName('meta');
    	foreach ($metaTags as $tag) {
			if($tag->getAttribute('name')??"" != ""){
				$output[$tag->getAttribute('name')] = $tag->getAttribute('content');
			} elseif ($tag->getAttribute('property')??"" != "") {
				$output[$tag->getAttribute('property')] = $tag->getAttribute('content');
			}
		}
		return $output;
	}

	function domObjectFromHtmlString($htmlString){
		$document = new DOMDocument();
    	@$document->loadHTML($htmlString);
		return $document;
	}

?>