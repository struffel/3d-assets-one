<?php
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/init.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/log.php';

	function fetchRemoteData($url){
		changeLogIndentation(true,__FUNCTION__);
		createLog("Fetching URL: $url");
		$content = file_get_contents($url);
		createLog("Content length: ".strlen($content)."");
		changeLogIndentation(false,__FUNCTION__);
		return $content;
	}

?>