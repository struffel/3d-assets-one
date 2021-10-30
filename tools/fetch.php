<?php
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/init.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/tools/log.php';

	function fetchRemoteData($url){
		changeLogIndentation(true,__FUNCTION__);
		createLog("Fetching URL: $url");

		$client = new GuzzleHttp\Client();
		try{
			$result = $client->request('GET',$url,[]);
			$content = $result->getBody();
			createLog("Request successful!");
		}catch(GuzzleHttp\Exception\ClientException $e){
			createLog("Request error, Status code: ".$e->getResponse()->getBody()->getContents(),"HTTP-ERROR");
			$content = "";
		}
		
		createLog("Content length: ".strlen($content)."");
		changeLogIndentation(false,__FUNCTION__);
		return $content;
	}

?>