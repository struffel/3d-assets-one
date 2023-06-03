<?php
	function fetchRemoteData(string $url, array $headers = []){
		changeLogIndentation(true,__FUNCTION__);
		createLog("Fetching URL: $url");

		$client = new GuzzleHttp\Client();
		try{
			$options = ['headers' => $headers]; 				// GPT: Add headers to the request options
			$result = $client->request('GET',$url,$options);
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