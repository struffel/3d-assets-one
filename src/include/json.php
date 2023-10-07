<?php

class JsonLogic{
	public static function output($result,array $debugArray = NULL,int $statusCode = 200,string $statusComment = NULL){
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

	public static function getFromUrl(string $url) : string{
		LogLogic::stepIn(__FUNCTION__);
		LogLogic::write("Getting JSON from URL: ".$url);
		$result = json_decode(fetchRemoteData($url),true);
		LogLogic::write("Got JSON.");
		LogLogic::stepOut(__FUNCTION__);
		return $result;
	}
}
	
?>