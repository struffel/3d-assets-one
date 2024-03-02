<?php

class FetchLogic{
	public static function fetchRemoteData(string $url, array $headers = []) : string{
		LogLogic::stepIn(__FUNCTION__);
		LogLogic::write("Fetching URL: $url");

		if(!isset($headers['User-Agent'])){
			$headers['User-Agent'] = "3dassets.one / Fetching";
		}

		$client = new GuzzleHttp\Client();
		try{
			$options = ['headers' => $headers]; 				// GPT: Add headers to the request options
			$result = $client->request('GET',$url,$options);
			$content = $result->getBody();
			LogLogic::write("Request successful!");
		}catch(GuzzleHttp\Exception\ClientException $e){
			LogLogic::write("Request error, Status code: ".$e->getResponse()->getStatusCode(),"HTTP-ERROR");
			$content = "";
		}
		
		LogLogic::write("Content length: ".strlen($content)."");
		LogLogic::stepOut(__FUNCTION__);
		return $content;
	}

	public static function fetchRemoteJson(string $url,array $headers = []) : mixed{
		LogLogic::stepIn(__FUNCTION__);
		$result = json_decode(FetchLogic::fetchRemoteData($url,$headers),true);
		LogLogic::write("Received and parsed JSON.");
		LogLogic::stepOut(__FUNCTION__);
		return $result;
	}

	public static function fetchRemoteCommaSeparatedList(string $url, array $headers = []) : array{
		LogLogic::stepIn(__FUNCTION__);
		$content = FetchLogic::fetchRemoteData($url,$headers);
		$content = str_replace("\n","",$content);

		$contentArray = explode(",",$content);
		$contentArray = array_filter($contentArray);
		$contentArray = array_map('trim', $contentArray);
		LogLogic::stepOut(__FUNCTION__);
		return $contentArray;
	}
}
	

?>