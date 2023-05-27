<?php

	function outputJson($result,array $debugArray = NULL,int $statusCode = 200,string $statusComment = NULL){
		$output = array();
		$output['result']=$result;
		$output['statusCode']=$statusCode;
		if($debugArray != NULL){
			$output['debug']=$debugArray;
		}
		if($statusComment != NULL){
			$output['statusComment']=$statusComment;
		}
		echo json_encode($output,JSON_PRETTY_PRINT);
	}

	function getJsonFromUrl($targetUrl){
		changeLogIndentation(true,__FUNCTION__);
		createLog("Getting JSON from URL: ".$targetUrl);
		$result = json_decode(fetchRemoteData($targetUrl),true);
		createLog("Got JSON.");
		changeLogIndentation(false,__FUNCTION__);
		return $result;
	}
?>