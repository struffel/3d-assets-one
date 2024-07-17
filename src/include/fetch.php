<?php

class FetchLogic{
	public static function fetchRemoteData(string $url, array $headers = [], string $method = 'GET',$body = NULL) : string{
		LogLogic::stepIn(__FUNCTION__);
		LogLogic::write("Fetching URL: $url using $method with body '$body'");

		if(!isset($headers['User-Agent'])){
			$headers['User-Agent'] = "3dassets.one / Fetching";
		}

		$client = new GuzzleHttp\Client();
		try{
			$options = [
				'headers' => $headers,
				'body' => $body
			]; 				
			$result = $client->request($method,$url,$options);
			$content = $result->getBody();
			LogLogic::write("Request successful!");
		}catch(GuzzleHttp\Exception\ClientException $e){
			LogLogic::write("Request error, Status code: ".$e->getResponse()->getStatusCode(),"HTTP-ERROR");
			$content = "";
		}catch(Exception $e){
			LogLogic::write("Generic request error: ".$e->getMessage(),"HTTP-ERROR");
			$content = "";
		}
		
		LogLogic::write("Content length: ".strlen($content)."");
		LogLogic::stepOut(__FUNCTION__);
		return $content;
	}

	public static function fetchRemoteJson(string $url,array $headers = [], string $method = 'GET',$body = NULL,$jsonContentTypeHeader = false) : mixed{
		LogLogic::stepIn(__FUNCTION__);
		if($jsonContentTypeHeader){
			$headers['Content-Type'] = "application/json";
		}
		$result = json_decode(FetchLogic::fetchRemoteData(url:$url,headers:$headers,method:$method,body:$body),true);
		LogLogic::write("Received and parsed JSON.");
		LogLogic::stepOut(__FUNCTION__);
		return $result;
	}

	public static function fetchRemoteCommaSeparatedList(string $url, array $headers = [], string $method = 'GET',$body = NULL) : array{
		LogLogic::stepIn(__FUNCTION__);
		$content = FetchLogic::fetchRemoteData($url,$headers,$method,body:$body);
		$content = str_replace("\n","",$content);

		$contentArray = explode(",",$content);
		$contentArray = array_filter($contentArray);
		$contentArray = array_map('trim', $contentArray);
		LogLogic::stepOut(__FUNCTION__);
		return $contentArray;
	}
}
	

?>