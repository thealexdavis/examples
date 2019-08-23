<?php
namespace Application\Src;

use Core;
use Loader;
use Page;
use Request;

class Pardot
{
	
	public function getProspectId($formid, $key)
    {
		$prospectidGet = $this->getPardotinfoAuth(
			'https://pi.pardot.com/api/visitor/version/4/do/read/id/'.$formid,
		    array(
		        
		    ),
		    $key,
		    $this->getPardotcreds("userkey"),
		    'GET',
		    array('Authorization: Pardot api_key='.$key.', user_key='.$this->getPardotcreds("userkey"))
		);
		$prospectidLoad = simplexml_load_string($prospectidGet);
		$prospectid = $prospectidLoad->visitor->prospect->id;
		return $prospectid;
    }
    
    public function getProspectInfo($formid)
    {
	    $apiKey = $this->getPardotkey();
		$prospectInfoGet = $this->getPardotinfoAuth(
			'https://pi.pardot.com/api/prospect/version/4/do/read/id/'.$this->getProspectId($formid, $apiKey),
		    array(
		        'api_key' => $apiKey,
		        'user_key' => $this->getPardotcreds("userkey")
		    ),
		    $apiKey,
		    $this->getPardotcreds("userkey"),
		    'GET',
		    array('Authorization: Pardot api_key='.$apiKey.', user_key='.$this->getPardotcreds("userkey"))
		);
		$getProspectInfo = simplexml_load_string($prospectInfoGet);
		return $getProspectInfo;
    }
	
    public function getCampaign()
    {
		$campaignString = $this->getPardotinfo(
			'https://pi.pardot.com/api/campaign/version/4/do/query',
		    array(
		        'api_key' => $this->getPardotkey(),
		        'user_key' => $this->getPardotcreds("userkey")
		    ),
		    'GET'
		);
		$getCampaigns = simplexml_load_string($campaignString);
		return $getCampaigns;
    }
    
    private function getPardotkey(){
	    $apistring = $this->getPardotinfo(
			'https://pi.pardot.com/api/login/version/4',
		    array(
		        'email' => 'XXX',
		        'password' => 'XXX',
		        'user_key' => $this->getPardotcreds("userkey")
		    ),
		    'POST'
		);
		$getapikey = simplexml_load_string($apistring);
		return $getapikey->api_key;
    }
    
    private function getPardotcreds($type){
	    $db = Loader::db();
	    $sql = "SELECT * FROM SaltwaterDynamicContent_Settings;";
        $savedData = $db->GetArray($sql);

        foreach($savedData as $data){
            if ($data['name'] == $type){
	            return $data['value'];
            }
        };
    }
    
    //USED TO RETREIVE KEY, THIS CALL ONLY USED FOR A FEW LEGACY CALLS FROM PARDOT
    private function getPardotinfo($apicall, $data, $method){
	    $apidata = http_build_query($data, null, '&');
	    $url = $apicall."?".$apidata;
	    $ch = curl_init($url);
	    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
	    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	    curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    if ($method == "POST") {
	        curl_setopt($ch, CURLOPT_POST, true);
	    } else if ($method == "GET") {
	        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
	    }
	    $pardotApiResponse = curl_exec($ch);
	    if ($pardotApiResponse === false) {
	        echo 'Curl error: ' . curl_error($ch);
	    }
	    curl_close($ch);
	    return $pardotApiResponse;
    }
    
    //USED TO GET DATA, THIS IS USED FOR ALL UPDATED AND FUTURE CALLS
    private function getPardotinfoAuth($apicall, $data, $apikey, $userkey, $method, $header){
	    $apidata = http_build_query($data, null, '&');
	    $url = $apicall;
	    $headers = array(
		    'Content-Type: application/x-www-form-urlencoded',
		    'Authorization: Pardot api_key='.$apikey.', user_key='.$this->getPardotcreds("userkey")
		);
	  
	    $ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers);
		$result = curl_exec($ch);
	    if ($result === false) {
	        echo 'Curl error: ' . curl_error($ch);
	    }
	    curl_close($ch);
	    return $result;
    }
    
}