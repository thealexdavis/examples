<?php
namespace Concrete\Package\SaltwaterDynamicContent\Src\Service;
defined('C5_EXECUTE') or die("Access Denied.");

use Core;
class Marketo
{
	public function errorCheck(){
        $settings = Core::make('saltwater_dynamic_content/helper/tools')->getSettingsArray();
        $error = true;
        if ($settings['marketoAPIURL'] && $settings['marketoClientID'] && $settings['marketoClientSecret'] && strlen($settings['marketoAPIURL']) > 0 && strlen($settings['marketoClientID']) > 0 && strlen($settings['marketoClientSecret']) > 0 ){
	        $error = false;
	    }
	    return $error;
    }
    public function testConnection($url, $id, $secret){
        $ch = curl_init($url ."/identity/oauth/token?grant_type=client_credentials&client_id=". $id ."&client_secret=". $secret);
        curl_setopt($ch,  CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('accept: application/json',));
        $response = json_decode(curl_exec($ch));
        curl_close($ch);
        $token = $response->access_token;
        if($token){
            return true;
        } else{
            return false;
        }
    }

    private function getMarketoToken($settings){
        if(!isset($settings['marketoToken']) || !isset($settings['marketoTokenExpire']) || $settings['marketoTokenExpire'] < time()){
            \Log::addInfo('Retrieving Marketo Token');
            $ch = curl_init($settings["marketoAPIURL"] ."/identity/oauth/token?grant_type=client_credentials&client_id=". $settings['marketoClientID'] ."&client_secret=". $settings['marketoClientSecret']);
            curl_setopt($ch,  CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('accept: application/json',));
            $response = json_decode(curl_exec($ch));
            curl_close($ch);
            $token = $response->access_token;
            $expire = ($response->expires_in - 60) + time();

            $db = \Database::connection();
            $db->executeQuery('REPLACE INTO SaltwaterDynamicContent_Settings (name, value) VALUES ("marketoToken", ?)', [$token]);
            $db->executeQuery('REPLACE INTO SaltwaterDynamicContent_Settings (name, value) VALUES ("marketoTokenExpire", ?)', [$expire]);
        
        }else{
            $token = $settings['marketoToken'];
        }
        return $token;        
    }

    private function getMarketoCookie(){
        if($_COOKIE["_mkto_trk"]){
            $cookie = $_COOKIE["_mkto_trk"];
        } else{
            $cookie = Core::make('saltwater_dynamic_content/helper/tools')->getSettingsArray('marketoDefaultCookie');
        }
        $cookie = str_replace("&", "%26", $cookie);
        return urlencode($cookie);
    }

    public function getMarketoLeadData(){
        $settings = Core::make('saltwater_dynamic_content/helper/tools')->getSettingsArray();
        $token = $this->getMarketoToken($settings);
        $marketoCookie = $this->getMarketoCookie();
        $marketoCookie = str_replace("%3A", ":", $marketoCookie);
        $marketoCookie = str_replace("%25", "%", $marketoCookie);
		$marketoCookie = urldecode($marketoCookie);
        if($marketoCookie != null){
            $leadDataPoints = json_decode($settings['marketoTrackedLeadData']);
            $fields = [];
            if(count($leadDataPoints) > 0){
                foreach ($leadDataPoints as $leadDataPoint) {

                    $fields[] = explode('|',$leadDataPoint)[0];
                }
                $leadData = implode(',',$fields);
                $url = $settings['marketoAPIURL'] . "/rest/v1/leads.json?access_token=" . $token . "&filterType=cookies&filterValues=" . $marketoCookie . "&fields=" . $leadData ;
                $ch = curl_init($url);
                curl_setopt($ch,  CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('accept: application/json',));
                $response = curl_exec($ch);
                curl_close($ch);
                $response = json_decode($response);
                if($response->success == 1){
                    $filterData = $response->result[0];
                } else{
                    $this->LogMarketoErrors($response);
                }
            }
        }
        return $filterData;
    }


    public function describeMarketoLead($settings){
        $token = $this->getMarketoToken($settings);

        $url = $settings['marketoAPIURL'] . "/rest/v1/leads/describe.json?access_token=" . $token;
        $ch = curl_init($url);
        
        curl_setopt($ch,  CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('accept: application/json',));
        $response = json_decode(curl_exec($ch));
        curl_close($ch);
        if($response->success == 1){
            $leadDataPoints = [];
            foreach($response->result as $leadDataPoint){
                $dataPoint['name'] = $leadDataPoint->displayName;
                $dataPoint['id'] = $leadDataPoint->rest->name;
                if(!empty($dataPoint['id'])){
                    $leadDataPoints[] = $dataPoint;
                }
            }
        } else {
            $this->LogMarketoErrors($response);
        }
        return $leadDataPoints;
    }

    private function logMarketoErrors($response){
        if(count($response->errors) > 0){
            foreach($response->errors as $error){
                \Log::addError('Marketo Error: (' . $error->code . ')' . $error->message);
            }
        } else {
            \Log::addError('Marketo Error');
        }
    }

    public function getPossibleValues($fieldName){
        //To be built out if you want to populate inputs with values from marketo (select field)
        $populateData =[
            //'firstName'
        ];
        if(in_array($fieldName, $populateData)){
            //Pull values from marketo, put into array
            //$options = ['1', '2', '3'];
        } else {
            $options = null;
        }
        return $options;
    }
}