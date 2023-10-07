<?php

class FetchLogic{
	public static function fetchRemoteData(string $url, array $headers = []) : string{
		LogLogic::stepIn(__FUNCTION__);
		LogLogic::write("Fetching URL: $url");

		$client = new GuzzleHttp\Client();
		try{
			$options = ['headers' => $headers]; 				// GPT: Add headers to the request options
			$result = $client->request('GET',$url,$options);
			$content = $result->getBody();
			LogLogic::write("Request successful!");
		}catch(GuzzleHttp\Exception\ClientException $e){
			LogLogic::write("Request error, Status code: ".$e->getResponse()->getBody()->getContents(),"HTTP-ERROR");
			$content = "";
		}
		
		LogLogic::write("Content length: ".strlen($content)."");
		LogLogic::stepOut(__FUNCTION__);
		return $content;
	}
}
	

?>