<?php

/*
 * (C) Copyright 2014 Bill Hubauer <bill@hubauer.com>
 * 
 * This file is part of Cricket  https://github.com/bhubauer/cricket
 * 
 * This library is free software; you can redistribute it and/or modify it under the terms of the 
 * GNU Lesser General Public License as published by the Free Software Foundation; either 
 * version 2.1 of the License, or (at your option) any later version.
 * 
 * This library is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
 * See the GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License along with this library; 
 * if not, visit http://www.gnu.org/licenses/lgpl-2.1.html or write to the 
 * Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */


namespace cricket\core;

use cricket\utils\Translator;

/**
 * Page class
 *
 */

abstract class Page extends Container {
    const   MODE_STATELESS = 'stateless';
    const   MODE_RELOAD = 'reload';
    const   MODE_PRESERVE = 'preserve';
    
    static public $SESSION_MODE = self::MODE_RELOAD;
    static public $SESSION_PAGE_VERSION = 1;
    
    
    /** @var RequestContext */
    private $_request;    // transient
    
    /** @var ResponseContext */
    private $_response;   // transient
    
    /** @var AjaxResponseManager */
    private $_ajax;       // transient
    
    public $_instanceID;
    public $_loaded = false;
    
    /** @var cricket\core\Module */
    private $_module;

    public $_mcReceivers = array(); // array of MessageReceiver

    

    public function __construct() {
        parent::__construct(null);
    }

    /**
     * If not loaded, load.  Otherwise, pull from cache _loaded
     * 
     * @return void
     */
    public function load() {
        if(!$this->_loaded) {
            $this->_loaded = true;
            $this->_instanceID = $this->generateInstanceID();
            $this->init();
        }
    }
    
    /**
     * Overridden in subclasses to add components
     * 
     * @return void
     */
    protected function init() {
        
    }
    
    /**
     * Render
     * 
     * @return void
     */
    abstract public function render();
    
    /**
     * Determine if the page is stated
     * 
     * @return boolean
     */
    public function hasState() {
        $class = get_class($this);
        return $class::$SESSION_MODE != self::MODE_STATELESS;
    }
    
    /**
     * Return the RequestContext
     *  
     * @return RequestContext 
     */
    public function getRequest() {
        return $this->_request;
    }
    
    /**
     *  Return the RepsonseContext
     *  
     *  @return ResponseContext 
     */
    public function getResponse() {
        return $this->_response;
    }
    
    /**
     * Set the Page's AjaxResponseManager
     * 
     * @see getAjaxManager()
     * 
     * @param AjaxResponseManager $aj
     * 
     * @return void
     */
    public function setAjaxManager(AjaxResponseManager $aj) {
        $this->_ajax = $aj;
    }
    
    /**
     *  Get the Page's AjaxResponseManager
     *  
     *  @see setAjaxManager()
     *  
     *  @return AjaxResponseManager 
     */
    public function getAjaxManager() {
        return $this->_ajax;
    }
    
    /**
     * Determine if call is AJAX
     * 
     * @return boolean
     */
    public function isAjax() {
        return $this->_ajax !== null;
    }
    
    /**
     * Begin the request
     * 
     * @param RequestContext $req
     * @param ResponseContext $resp
     * 
     * @return void
     */
    public function beginRequest(RequestContext $req,ResponseContext $resp) {
        $this->_request = $req;
        $this->_response = $resp;
    }
    
    /**
     * Terminate the request by nullifying request and response
     * 
     * @return void
     */
    public function endRequest() {
        $this->_request = null;
        $this->_response = null;
        $this->_ajax = null;
    }
    
    /**
     * Post to Render
     * 
     * @return void
     */
    public function post() {
        $this->render();
    }
    
    /**
     * Return the Page's instance ID
     * 
     * @return string
     */
    public function getInstanceID() {
        return $this->_instanceID;
    }
    
	/**
	 * Cause a component to render
	 * 
	 * @param string $inID Component ID
	 */    
    public function renderComponent($inID) {
        /* @var $c Component */
        $c = $this->findComponent($inID);
        if($c !== null) {
            $cDivID = $c->getDivId();
            $cDivClass = $c->getDivClass();
            if($cDivClass) {
                $cDivClass = " class='$cDivClass'";
            }
            echo "<div id = '$cDivID'{$cDivClass}>";
            $this->_request->pushContext();
            $c->render();
            $this->_request->popContext();
            echo "</div>";
        }else{
            echo "<div style='color:white;background-color:red;'>MISSING COMPONENT: " . $inID . "</div>";
        }
    }
    
    /**
     * Cause a static component to render
     * 
     * @param string $inID Component ID
     * @param string $inRenderID Component'd div ID
     * @param mixed $inData
     * 
     * @return void
     */
    public function renderStaticComponent($inID,$inRenderID,$inData) {
        /* @var $c StaticComponent */
        $c = $this->findComponent($inID);
        if($c !== null) {
            $c->setRenderID($inRenderID);
            $cDivID = $c->getDivId();
            $cDivClass = $c->getDivClass();
            if($cDivClass) {
                $cDivClass = " class='$cDivClass'";
            }
            echo "<div id = '$cDivID'{$cDivClass}>";
            $this->_request->pushContext();
            $c->renderStatic($inData);
            $this->_request->popContext();
            echo "</div>";
        }else{
            echo "<div style='color:white;background-color:red;'>MISSING COMPONENT: " . $inID . "</div>";
        }
    }
    
	/**
	 * Returns leaf name of Page class
	 * 
	 * @return string
	 */
    public function getPageClassName() {
    	return Translator::getPageClassName($this, $this->getModule()->getPageSearchPaths());
    }
    
    /**
     * Returns the module
     *  
     * @return cricket\core\Module 
     */
    public function getModule() {
        if(!isset($this->_module)) {
            list($module,$class) = Application::getInstance()->pageClass2ModuleAndPageID(get_class($this));
            $this->_module = $module;
        }
        
        return $this->_module;
    }

	/**
	 * Create an action URL
	 * 
	 * @see \cricket\core\Container::getActionUrl($inActionID)
	 * 
	 * @return URL
	 */
    public function getActionUrl($inActionID,$inPageClassName = null) {
        $module = null;
        $instanceID = $this->getInstanceID();
        if($inPageClassName === null) {
            $inPageClassName = get_class($this);
            $module = $this->getModule();
        }else{
            list($module,$class) = Application::getInstance()->pageClass2ModuleAndPageID($inPageClassName);
        }
        
        if($module == null) {
            throw new \Exception("Can't locate module for class: $inPageClassName");
        }
        return $module->assembleURL($this->getRequest(),$inPageClassName,$inActionID,$instanceID);
    }
    
    /**
     * Get the URL of the page class
     * 
     * @param string $inPageClassName
     * 
     * @return \cricket\core\URL
     */
    public function getPageUrl($inPageClassName = null) {
        return $this->getActionUrl(null,$inPageClassName);
    }
    
    /**
     * Generate an Instance ID for the page
     * 
     * @return string
     */
    public function generateInstanceID() {
        return uniqid(null,true);
    }
    
    /**
     * Invalidate a component
     * 
     * @see AjaxResponseManager::invalidate()
     * 
     * @param Component $aThis
     * 
     * @return void
     */
    public function invalidateComponent(Component $aThis) {
        if($this->_ajax !== null) {
            $this->_ajax->invalidate($aThis);
        }
    }
    
    /**
     * Force a component to render
     * 
     * @see AjaxResponse::renderNow()
     * 
     * @param Component $aThis
     * 
     * @return void
     */
    public function renderComponentNow(Component $aThis) {
        if($this->_ajax !== null) {
            $this->_ajax->renderNow($aThis);
        } 
    }
	
    /**
     * Add the Page's contributions to the head
     * 
     * @param Page $page
     * 
     * @return string
     */
    static public function contributeToHead($page) {
        $cricketJS = Container::resolveResourceUrl($page, get_class($page), "cricket/js/cricket.js");
        $pageID = $page->getInstanceID();
        return <<<END
            <script type="text/javascript" src = "$cricketJS"></script>
            <script type="text/javascript">
                _CRICKET_PAGE_INSTANCE_ = '$pageID';
            </script>
END;
    }
    
    /**
     * Get the Page's Component's contributions to the head
     * 
     * @return string
     */
    public function getHeadContributions() {
        $added = array();
        $results = array();
                
        $this->contributeComponentsToHead($added,$results);
        return implode("\n", $results);
    }
    
    
    /**
     * Dispatch the request
     * 
     * @see receiveActionRequest()
     * 
     * @param array $parts
     * 
     * @return boolean
     */
    public function dispatchRequest($parts) {
        if(count($parts) == 0) {
            if($this->getRequest()->getMethod() == 'POST') {
                $this->post();
            }else{
                $this->render();
            }
            
            return true;
        }
                
        return $this->receiveActionRequest($parts);
    }
    
    /**
     * Receive the action request
     * 
     * @see \cricket\core\Container::receiveActionRequest($parts)
     * 
     * @param array $inParts
     * 
     * @return boolean
     */
    protected function receiveActionRequest($parts) {
        $next = array_shift($parts);
        if(count($parts) == 0) {
            // page action
            $actionMethod = "action_$next";
            if(method_exists($this,$actionMethod)) {
                $this->$actionMethod();
                return true;
            }
        }else{
            $component = $this->findComponent($next);
            if($component !== null) {
                return $component->receiveActionRequest($parts);
            }
        }
        
        return false;
    }
    
    
	/**
	 * Register message receiver 
	 * 
	 * @param MessageReceiver $inReceiver
	 * 
	 * @return void
	 */
    public function mcRegisterReceiver(MessageReceiver $inReceiver) {
        $this->_mcReceivers[] = $inReceiver;
    }
    
    /**
     * Remove message receiver
     *
     * @param MessageReceiver $inReceiver
     *
     * @return void
     */
    public function mcRemoveReceiver(MessageReceiver $inReceiver) {
        $p = array_search($inReceiver,$this->_mcReceivers);
        if($p !== false) {
            unset($this->_mcReceivers[$p]);
            $this->_mcReceivers = array_values($this->_mcReceivers);
        }
    }
    // END PRIVATE MESSAGE RECEIVER METHODS
    
    
}