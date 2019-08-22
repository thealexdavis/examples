<?php
namespace Application\Src;

use Core;
use Page;
use Request;

class Uberflip {
	
	public function getItem($id){
		$expensiveCache = \Core::make('cache/expensive');
		$cacheObject = $expensiveCache->getItem('uberflipItem'.$id);
		if ($cacheObject->isMiss()) {
			$cacheObject->lock();
			$itemInfo = $this->uberflipSendoff(
				'https://v2.api.uberflip.com/items/'.$id.'',
			    array(
				    	'grant_type' => 'client_credentials', 
				    	'client_id' => Core::make('uberflip/helper/tools')->getSettingsArray('id'),
				    	'client_secret' => Core::make('uberflip/helper/tools')->getSettingsArray('secret')),
				    'GET',
			    array('Authorization: Bearer '.$this->getToken().'')
			);
			$cacheObject->set($itemInfo)->expiresAfter(60 * 240)->save();
		} else {
			$itemInfo = $cacheObject->get();
		}
		return $itemInfo;
	}
	
	public function getItemAlt($id){
		$expensiveCache = \Core::make('cache/expensive');
		$cacheObject = $expensiveCache->getItem('uberflipItemalt'.$id);
		if ($cacheObject->isMiss()) {
			$cacheObject->lock();
			$itemInfo = $this->uberflipSendoff(
				'https://api.uberflip.com/?APIKey=XXX=&Signature=XXX==&Version=0.1&Method=GetHubItemData&ItemId='.$id.'&ResponseType=JSON',
			    array(),
				    'GET',
			    array('Authorization: Bearer '.$this->getToken().'')
			);
			$cacheObject->set($itemInfo)->expiresAfter(60 * 240)->save();
		} else {
			$itemInfo = $cacheObject->get();
		}
		return $itemInfo;
	}
	
	public function getHubStreams($id){
		$expensiveCache = \Core::make('cache/expensive');
		$cacheObject = $expensiveCache->getItem('uberflipHubStreams'.$id);
		if ($cacheObject->isMiss()) {
			$cacheObject->lock();
			$hubStreams = $this->uberflipSendoff(
				'https://v2.api.uberflip.com/hubs/'.$id.'/streams?limit=100&sort=-modified_at',
			    array(
				    	'grant_type' => 'client_credentials', 
				    	'client_id' => Core::make('uberflip/helper/tools')->getSettingsArray('id'),
				    	'client_secret' => Core::make('uberflip/helper/tools')->getSettingsArray('secret')),
				    'GET',
			    array('Authorization: Bearer '.$this->getToken().'')
			);
			$cacheObject->set($hubStreams)->expiresAfter(60 * 240)->save();
		} else {
			$hubStreams = $cacheObject->get();
		}
		return $hubStreams;
	}
	
	
	public function getStreamItems($id){
		$expensiveCache = \Core::make('cache/expensive');
		$cacheObject = $expensiveCache->getItem('uberflipStreamItems'.$id);
		if ($cacheObject->isMiss()) {
			$cacheObject->lock();
			$streamInfo = $this->uberflipSendoff(
				'https://v2.api.uberflip.com/streams/'.$id.'/items?limit=100&sort=-modified_at',
			    array(
				    	'grant_type' => 'client_credentials', 
				    	'client_id' => Core::make('uberflip/helper/tools')->getSettingsArray('id'),
				    	'client_secret' => Core::make('uberflip/helper/tools')->getSettingsArray('secret')),
				    'GET',
			    array('Authorization: Bearer '.$this->getToken().'')
			);
			$cacheObject->set($streamInfo)->expiresAfter(60 * 240)->save();
		} else {
			$streamInfo = $cacheObject->get();
		}
		return $streamInfo;
	}
	
	public function getHubs(){
		$expensiveCache = \Core::make('cache/expensive');
		$cacheObject = $expensiveCache->getItem('uberflipHubs99');
		if ($cacheObject->isMiss()) {
			$cacheObject->lock();
			$hubList = $this->uberflipSendoff(
				'https://v2.api.uberflip.com/hubs?limit=100&sort=name',
			    array(
				    	'grant_type' => 'client_credentials', 
				    	'client_id' => Core::make('uberflip/helper/tools')->getSettingsArray('id'),
				    	'client_secret' => Core::make('uberflip/helper/tools')->getSettingsArray('secret')),
				    'GET',
			    array('Authorization: Bearer '.$this->getToken().'')
			);
			$cacheObject->set($hubList)->expiresAfter(60 * 60)->save();
		} else {
			$hubList = $cacheObject->get();
		}
		return $hubList;
	}
	
	public function getHubInfo($id){
		$expensiveCache = \Core::make('cache/expensive');
		$cacheObject = $expensiveCache->getItem('uberflipHubInfo'.$id);
		if ($cacheObject->isMiss()) {
			$cacheObject->lock();
			$hubInfo = $this->uberflipSendoff(
				'https://v2.api.uberflip.com/hubs/'.$id.'?limit=100&sort=name',
			    array(
				    	'grant_type' => 'client_credentials', 
				    	'client_id' => Core::make('uberflip/helper/tools')->getSettingsArray('id'),
				    	'client_secret' => Core::make('uberflip/helper/tools')->getSettingsArray('secret')),
				    'GET',
			    array('Authorization: Bearer '.$this->getToken().'')
			);
			$cacheObject->set($hubInfo)->expiresAfter(60 * 60)->save();
		} else {
			$hubInfo = $cacheObject->get();
		}
		return $hubInfo;
	}
	
	public function getStreamInfo($id){
		$expensiveCache = \Core::make('cache/expensive');
		$cacheObject = $expensiveCache->getItem('uberflipStreamInfo'.$id);
		if ($cacheObject->isMiss()) {
			$cacheObject->lock();
			$streamInfo = $this->uberflipSendoff(
				'https://v2.api.uberflip.com/streams/'.$id.'',
			    array(),
				    'GET',
			    array('Authorization: Bearer '.$this->getToken().'')
			);
			$cacheObject->set($streamInfo)->expiresAfter(60 * 60)->save();
		} else {
			$streamInfo = $cacheObject->get();
		}
		return $streamInfo;
	}
	
	public function getToken(){
		$expensiveCache = \Core::make('cache/expensive');
		$cacheObject = $expensiveCache->getItem('uberflipAccessToken'.$id);
		if ($cacheObject->isMiss()) {
			$cacheObject->lock();
			$tokenstring = $this->uberflipSendoff(
				'https://v2.api.uberflip.com/authorize',
			    array(
				    	'grant_type' => 'client_credentials', 
				    	'client_id' => Core::make('uberflip/helper/tools')->getSettingsArray('id'),
				    	'client_secret' => Core::make('uberflip/helper/tools')->getSettingsArray('secret')),
				    'POST',
			    array('Content-Type:application/json')
			);
			$accesstoken = $tokenstring->access_token;
			$cacheObject->set($accesstoken)->expiresAfter(60 * 60)->save();
		} else {
			$accesstoken = $cacheObject->get();
		}
		return $accesstoken;
	}
	
	public function uberflipSendoff($apicall, $data, $method, $header){
		if ($method == "POST"){
			$ch = curl_init( $apicall );
			$payload = json_encode( $data );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
			curl_setopt( $ch, CURLOPT_HTTPHEADER, $header);
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			$result = curl_exec($ch);
			curl_close($ch);
			$result = json_decode($result);
		}
		if ($method == "GET"){
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $apicall);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt( $ch, CURLOPT_HTTPHEADER, $header);
			$result = curl_exec($ch);
			curl_close($ch);
			$result = json_decode($result);
		}
		return $result;
	}
	
}