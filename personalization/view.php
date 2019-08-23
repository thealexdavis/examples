<?php

namespace Concrete\Package\SaltwaterDynamicContent\Block\SaltwaterDynamicContent;

use Concrete\Core\Block\BlockController;
use Core;
use StackList;
use Stack;
use Block;
use \Concrete\Package\SaltwaterDynamicContent\Src\PackageServiceProvider;

class Controller extends BlockController
{
    public $helpers = array('form');
    public $btFieldsRequired = array('sID');
    protected $btExportFileColumns = array();
    protected $btTable = 'btSaltwaterDynamicContent';
    protected $btInterfaceWidth = 400;
    protected $btInterfaceHeight = 500;
    protected $btIgnorePageThemeGridFrameworkContainer = false;
    protected $btCacheBlockRecord = false;
    protected $btCacheBlockOutput = false;
    protected $btCacheBlockOutputOnPost = false;
    protected $btCacheBlockOutputForRegisteredUsers = false;
    protected $btCacheBlockOutputLifetime = 0;
    protected $pkg = false;
    
    public function getBlockTypeDescription()
    {
        return t("Show content based on configured rules.");
    }

    public function getBlockTypeName()
    {
        return t("Dynamic Content");
    }

    public function getSearchableContent()
    {
        $content = array();
        $content[] = $this->sID;
        return implode(" ", $content);
    }

    public function on_start()
    {
        $app = Core::getFacadeApplication();
        $sp = new PackageServiceProvider($app);
        $sp->register();
    }

    public function view()
    {
        $version = Core::make('saltwater_dynamic_content/helper/tools')->getSettingsArray('cookieVersion');
        if($_COOKIE["swdc_mkt"]){
            $userData = json_decode($_COOKIE["swdc_mkt"]);
        }
        //If cookie doesn't exist or the version of the cookie doesn't match our current configuration, get user data from marketo
        if(!$_COOKIE["swdc_mkt"] || $userData->v != $version){
            if (Core::make('saltwater_dynamic_content/helper/marketo')->errorCheck() === false ){
                $userData = Core::make('saltwater_dynamic_content/helper/marketo')->getMarketoLeadData();
                if($userData){
                    $userData = (object) array_merge( (array)$userData, array( 'v' => $version ) );
                    unset($userData->cookies);
                     setcookie("swdc_mkt", json_encode($userData), time()+3600,'/');
                }
            }
        }
        
        $thisStack = Stack::getByID($this->sID);
        if($thisStack) $this->set('stackName',$thisStack->getCollectionName());
        
        $db = \Database::connection();
        $blocks = $db->fetchAll('SELECT bID FROM SaltwaterDynamicContent_Blocks WHERE sID = ? ORDER BY displayOrder', [$this->sID]);
        
        $bIDs = [];
        foreach($blocks as $block){
            $bIDs[] = $block['bID'];
        }
        if(count($bIDs) > 0){
            $ruleData = Core::make('saltwater_dynamic_content/helper/tools')->getRulesArray($bIDs);
        }

        $this->set('block',null);
        $shortCodes = $db->fetchAll('SELECT * FROM SaltwaterDynamicContent_Shortcodes');
        $this->set('shortcodes', $shortCodes);

        foreach($bIDs as $bID){
            if(!isset($ruleData[$bID])){
                //if no rules are defined, then display this block
                $passed = true;
            } else {
                foreach($ruleData[$bID] as $rule){

                    $comparison = $rule['comparison'];
                    
                    //If rule is based on Marketo Cookied UTM data
                    if(substr($rule['x'], 0, 4) === 'utm_'){
	                	$x = $_COOKIE[$rule['x']];
	                	$x = strtolower($x);
                    //If rule is based on URL Parametered UTM Data
                    } else if(substr($rule['x'], 0, 4) === 'url_'){
	                    $url_param = str_replace("url_", "", $rule['x']);
	                	$x = $_GET[$url_param];
                    	$x = strtolower($x);
                    //If rule is based on User activity
                    } else if(substr($rule['x'], 0, 4) === 'trk_'){
                        $pbID = ltrim($rule['x'], 'trk_');
                        $userData = $_COOKIE['swdc_mkt'];
                        $userData = json_decode(urldecode($userData));
                        $x = $userData->buckets->{$pbID};

                    //If rule is based on Marketo Data
                    } else if(substr($rule['x'], 0, 4) === 'mkt_'){
                        $xRule = str_replace("mkt_", "", $rule['x']);
                        $x = trim(strtolower($userData->{$xRule}));
                    }
                    $y = trim(strtolower($rule['y']));
                    $passed = false;
                    
					$passx = $rule['x'];
                    $passy = $rule['y'];
                    $passcompare = $rule['comparison'];
                    switch ($comparison) {
                        case "equals":
                            if($x == $y){
                                $passed = true;
                            }
                            break;
                        case "doesNotEqual":
                            if($x != $y){
                                $passed = true;
                            }
                            break;
                        case "greaterThan":
                            if($x > $y){
                                $passed = true;
                            }
                            break;
                        case "lessThan":
                            if($x < $y){
                                $passed = true;
                            }
                            break;
                        case "isEmpty":
                            if(empty($x)){
                                $passed = true;
                            }
                            break;
                        case "notEmpty":
                            if(!empty($x)){
                                $passed = true;
                            }
                            break;
                        case "default":
                            $passed = true;
                            break;
                    }
                    if(!$passed){
                        //if rule was not passed, get out of looping through the rest of the rules for this block. They are all configured as "ands" so if one fails, the whole set fails.
                        $rulespass = "NO_PASS";
                        break;
                    }
                }
            }
            if($passed){
	            if (end($bIDs) !== $bID){
		            $rulespass = $passx." ".$passcompare." ".$passy;
	            } else {
		            $rulespass = "DEFAULT";
	            }
	            $this->set('rulespass',$rulespass);
                $displayedBlock = Block::getByID($bID);
                $this->set('block',$displayedBlock);
                $this->set('userData', $userData);
                break;
            }
        }
    }

    public function add()
    {
        $this->addEdit();
    }

    public function edit()
    {
        $this->addEdit();
    }

    protected function addEdit()
    {
        $this->set('btFieldsRequired', $this->btFieldsRequired);

        $db = \Database::connection();
        $dynamicStacks = $db->fetchAll('SELECT DISTINCT sID FROM SaltwaterDynamicContent_Blocks');

        $stacks= new StackList();
        $stacks->filterByUserAdded();
        $stacks = $stacks->getResults();
        $dynamicStacksData = [];
        
        if(count($stacks) > 0){   
            foreach ($stacks as $stack) {
                $thisStack = Stack::getByID($stack->cID);
                $stackData = array(
                    "title" => $thisStack->getCollectionName(),
                    "id"    => $thisStack->getCollectionID()
                );
                $isDynamic = array_search($thisStack->getCollectionID(), array_column($dynamicStacks, 'sID'));
                if($isDynamic !== false){
                    $dynamicStacksData[] = $stackData;
                }
            }
        }

        $this->set('stacks', $dynamicStacksData);
    }

    public function validate($args)
    {
        $e = Core::make("helper/validation/error");
        if (in_array("sID", $this->btFieldsRequired) && (trim($args["sID"]) == "")) {
            $e->add(t("The %s field is required.", t("Stack ID")));
        }
        return $e;
    }

    public function composer()
    {
        $this->edit();
    }
}