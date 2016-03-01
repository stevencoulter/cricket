<?php

namespace cricket\core;

use cricket\core\AjaxResponse;
use cricket\core\ResponseContext;
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


use \cricket\components\DialogComponent;
use cricket\components\DialogWrapper;

/**
 * The core class of a cricket object
 */

abstract class Container implements MessageReceiver {
    // these are all public so that the default serialize implementation will work
    
    public $_components;     // Map<String,Component>
    public $_id;
    public $_localID;
    /** @var Container */
    public $_parent;
    
    public function __sleep() {
        $vars = array_keys(get_object_vars($this));
        $transients = $this->getTransientVariables();
        return array_diff($vars,$transients);
    }
    
    /**
     * Return transient variables
     *
     * @todo Not yet implemented
     *
     * @return array
     */
    protected function getTransientVariables() {
        return array();
    }
    
    /**
     * Return parent container
     *
     * @return Container
     */
    public function getParent() {
        return $this->_parent;
    }
    
    /**
     * 
     * @return string
     */
    public function getId() {
        return $this->_id;
    }
    
    /**
     * Return local id
     * 
     * @return string 
     */
    public function getLocalId() {
        return $this->_localID;
    }
    
    /**
     * Return div id of container
     * 
     * @return string
     */
    public function getDivId() {
        return "component_" . $this->_id;
    }
    
    /**
     * Return class of container's div
     * 
     * @return string
     */
    public function getDivClass() {
        return "";
    }
    
    /**
     * 
     */
    public function __construct($inID) {
        $this->_components = array();
        $this->_localID = $inID;
        $this->_id = null;
        $this->_parent = null;
    }
    
    /**
     * Set a redirect, discovering if it should be done through ajax
     * 
     * @link AjaxResponse
     * @link ResponseContext::setRedirect()
     * 
     * @param string $inUrl
     * 
     * @return void
     */
    public function setRedirect($inUrl) {
        /* @var $thisPage Page */
        $thisPage = $this->getPage();
        if($thisPage->isAjax()) {
            $thisPage->getAjaxResponse()->setRedirect($inUrl);
        }else{
            $this->getResponse()->sendRedirect($inUrl);
        }
    }
    
    /**
     * Broadcast a message
     * 
     * @link MessageCenter::broadcastMessage
     * 
     * @param string $inMessage
     * @param mixed $inData
     * 
     * @return void
     */
    public function broadcastMessage($inMessage,$inData = null) {
        MessageCenter::broadcastMessage($inMessage, $inData, $this);
    }
    
    /**
     * Broadcast a message to the component tree
     * 
     * @link broadcastMessageToTree
     * @link receiveMessage
     * @link messageReceived
     * 
     * @param string $inMessage
     * @param mixed $inData
     * @param Container $inSender
     * 
     * @return void
     */
    public function broadcastMessageToTree($inMessage,$inData,$inSender) {
        if($inSender instanceof Container) {
            $this->receiveMessage($inMessage,$inData,$inSender);
        }else{
            $this->messageReceived($inMessage,$inData,$inSender);
        }
        
        foreach($this->_components as $id => $c) {
            $c->broadcastMessageToTree($inMessage,$inData,$inSender);
        }
    }
    
	/**
	 * Used to receive message from non-containers
	 * 
	 * @link \cricket\core\MessageReceiver::messageReceived()
	 * 
	 * @return void
	 */
    public function messageReceived($inMessage,$inData,$inSender) {
        
    }
    
    /**
     * Receive a broadcast message
     * 
     * @param string $inMessage
     * @param mixed $inData
     * @param Container $inSender
     * 
     * @return void
     */
    protected function receiveMessage($inMessage,$inData,Container $inSender) {
        // override if need to receive message
    }
    
    /**
     * Resolve a child
     * 
     * @param string $inLocalChildID
     * 
     * @return string
     */
    public function resolveChildID($inLocalChildID) {
        if($this->_id === null) {
            return $inLocalChildID;
        }
        return $this->_id . "_" . $inLocalChildID;
    }
    
    /**
     * Recursively set child ids
     * 
     * @link resolveChildId()
     * 
     * @return void
     */
    protected function setChildIDs() {
        foreach($this->_components as $id => $c) {
            if($c->_id === null) {
                $c->_id = $this->resolveChildID($c->_localID);
                $c->setChildIDs();
            }
        }
    }
    
    /**
     * Perform the requested action method on the closest component that has it
     * 
     * @param array $parts
     * 
     * @return bool Should fail silently or throw 404
     */
    protected function receiveActionRequest($parts) {
        $next = array_shift($parts);
        if(count($parts) == 0) {
            // action
            
            $currentTarget = $this;
            while($currentTarget) {
                $actionMethod = "action_{$next}";
                if(method_exists($currentTarget, $actionMethod)) {
                    $currentTarget->$actionMethod();
                    return true;
                }else{
                    $currentTarget = $currentTarget->_parent;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Add a child component
     * 
     * @param Component $newComponent
     * 
     * @return void
     */
    public function addComponent(Component $newComponent) {
        $this->_components[$newComponent->_localID] = $newComponent;
        $newComponent->_parent = $this;
        if($this->_id !== null) {
            $newComponent->_id = $this->resolveChildID($newComponent->_localID);
            $newComponent->setChildIDs();
        }else{
            if($this instanceof Page) {
                $newComponent->_id = $newComponent->_localID;
                $newComponent->setChildIDs();
            }else{
                $newComponent->_id = null;
            }
        }
        
        $newComponent->addedToParent();
    }
    
    /**
     * Execute when component has been added to a parent
     * 
     * @return void
     */
    protected function addedToParent() {
        
    }
    
    /**
     * Remove a child component
     *
     * @param Component $newComponent
     *
     * @return void
     */
    public function removeComponent(Component $c) {
        unset($this->_components[$c->_localID]);
        $c->_parent = null;
        $c->_id = null;
    }
    
    /**
     * Remove all child component
     *
     * @return void
     */
    public function removeAllComponents() {
        $this->_components = array();
    }
    
    /**
     * Add a DialogComponent and call it's open method
     * 
     * @link DialogComponent::openDialog()
     *
     * @param DialogComponent $newComponent
     *
     * @return void
     */
    public function addDialogComponent(DialogComponent $newComponent) {
        if($this->getPage()->isAjax()) {
            $this->addComponent($newComponent);
            $newComponent->invalidate();
            $closeUrl = $newComponent->getActionUrl("closeDialog");
            $newComponent->openDialog();
            $this->getAjaxResponse()->openDialog($newComponent->getDivId(),$closeUrl,$newComponent->getDialogOptions());
        }
    }
    
    /**
     * Add a DialogComponent and call it's open method
     *
     * @link DialogComponent::openDialog()
     *
     * @param DialogComponent $newComponent
     *
     * @return void
     * 
     *     public function __construct($inID,$inTitle,$inWidth,Component $inComponent, $inFocus = '') {

     */
    public function addComponentAsModal($inTitle, $inWidth, Component $inComponent, $inFocus = '') {
    	if($this->getPage()->isAjax()) {
    		$dialog = new DialogWrapper(rand(), $inTitle, $inWidth, $inComponent,$inFocus);
			$this->addDialogComponent($dialog);
    	}
    }
    
    /**
     * Get a component specified by its local ID
     * 
     * @param string $localID
     * 
     * @return Component
     */
    public function getComponent($localID) {
        if(isset($this->_components[$localID])) {
            return $this->_components[$localID];            
        }
        return null;
    }
    
	/**
	 * Return the Page the component exists on
	 * 
	 * @return Page
	 */
    public function getPage() {
        $result = null;
        
        if($this->_parent !== null) {
            $result = $this->_parent;
            while($result->_parent !== null) {
                $result = $result->_parent;
            }
        }else{
            $result = $this;
        }
        
        if($result instanceof Page) {
            return $result;
        }else{
            return null;
        }
    }
    
    /**
     * Get a component specified by its ID.  Traverses the component tree.
     *
     * @param string $inID
     *
     * @return Component
     */
    public function findComponent($inID) {
        foreach($this->_components as $id => $thisComp) {
            if($thisComp->_id == $inID) {
                return $thisComp;
            }else{
                $thisComp = $thisComp->findComponent($inID);
                if($thisComp !== null) {
                    return $thisComp;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Return the action URL
     *
     * @param string $inActionID
     */
    abstract public function getActionUrl($inActionID);
    
    
    /**
     * Translate a resource into a file system path
     * 
     * @param Page $page
     * @param string $class
     * @param string $path
     * 
     * @return string
     */
    static public function resolveResourceUrl($page,$class,$path) {
        $result = null;
        $a = array();
        $fsPath = Container::resolveTemplatePath($page,$class,$path,$a,false);
        if($fsPath !== null) {
            $result = $page->getRequest()->translatePath($fsPath);
        }
        
        return $result;
    }
    
    /**
     * Resolve the file system path of a template
     * 
     * @param Page $page
     * @param string $class
     * @param string $path
     * @param array $inParams
     * @param boolean $showMissingTemplate
     * 
     * @return string
     */
    static public function resolveTemplatePath($page,$class,$path,&$inParams,$showMissingTemplate = true) {
        // if starts with a slash, then its a full path from the contextRoot
        // that means there is no wayt to do a file system full path...  thats probably ok
        
        if(preg_match("/^\//",$path)) {
            $result = Application::getInstance()->getActiveModule()->resolveResourcePath($path,$page->getRequest()->getAttribute("contextPath"));
            if($result) {
                return $result;
            }else{
                $inParams['templatePath'] = $path;
                $a = array();
                return Container::resolveTemplatePath($page,$class, "missing_template.php", $a);
            }
        }
        
        /* First search for overridden namespace paths in the root directory of the namespace */
        $override_path = str_replace("\\",DIRECTORY_SEPARATOR, $class) . DIRECTORY_SEPARATOR . $path;
        $reflector = new \ReflectionClass(get_class(Application::getInstance()));        
        $start_path = str_replace(str_replace("\\",DIRECTORY_SEPARATOR,$reflector->getName()).".php", '', $reflector->getFileName());
        $override_path = $start_path . $override_path;
        if(file_exists($override_path)) {
        	return $override_path;
        }
        
        $iter = new SearchPathIterator($class);
        while($iter->hasNext()) {
            $testPath = $iter->next() . DIRECTORY_SEPARATOR . $path;
            if(file_exists($testPath)) {
                return $testPath;
            }
        }
        
        // if didn't find it and we aren't the Page class, then allow the page to be involved
        if($class != get_class($page)) {
            return self::resolveTemplatePath($page, get_class($page), $path, $inParams, $showMissingTemplate);
        }else{
            if($showMissingTemplate) {
                $inParams['templatePath'] = $path;
                return Container::resolveTemplatePath($page,$class, "missing_template.php", $a);
            }else{
                return null;
            }
        }
    }
    
    /**
     * Return the URL of a resource
     * 
     * @link resolveResourceUrl()
     * 
     * @param string $inPath
     * 
     * @return string
     */
    public function resourceUrl($inPath) {
        return Container::resolveResourceUrl($this->getPage(), get_class($this), $inPath);
    }
    
    /**
     * Return the path of a resource
     * 
     * @link resolveTemplatePath()
     * 
     * @param string $inPath
     * 
     * @return string
     */
    public function resourcePath($inPath) {
        $a = array();
        return Container::resolveTemplatePath($this->getPage(),get_class($this),$inPath,$a,false);
    }
    
    /**
     * Locate a template
     * 
     * @link resolveTemplatePath()
     * 
     * @param string $inTemplatePath
     * @param array $inParamsArray
     * 
     * @return string 
     */
    protected function locateTemplate($inTemplatePath,&$inParamsArray) {
        return Container::resolveTemplatePath($this->getPage(), get_class($this), $inTemplatePath, $inParamsArray);
    }
    
    /**
     * Render through an anonmous function rather than through the template.  Useful in the case where template is unnecessary.
     * 
     * public function render() {
     * 		$self = $this;
     * 		$this->renderFunction(function(\cricket\core\CricketContext $cricket,\cricket\core\TemplateInheritanceContext $tpl) use($self) {
     * 			...
     * 		});
     * }
     * 
     * @link cricket\core\CricketContext
     * @link TemplateInheritanceContext
     * 
     * @param function $inFunction
     * 
     * @return void
     */
    protected function renderFunction($inFunction) {
        /* @var $ctx CricketContext */
        $ctx = $this->getPage()->getRequest()->getAttribute("cricket");
        /* @var $savedComponent Container */
        $savedComponent = $ctx->getComponent();
        $ctx->setComponent($this);
        
        $tpl = new TemplateInheritanceContext($ctx);
        $inFunction($ctx,$tpl);
        $tpl->flush();
                
        $ctx->setComponent($savedComponent);
    }
    
    /**
     * Render through use of a template file.  Array keys are callable by name in the template.
     * 
     * @param string $inTemplatePath
     * @param array $inParamsArray
     * 
     * @return void
     */
    protected function renderTemplate($inTemplatePath,$inParamsArray = array()) {
        $fullTemplatePath = $this->locateTemplate($inTemplatePath,$inParamsArray);
        
        /* @var $page Page */
        $page = $this->getPage();
        
        foreach($inParamsArray as $k => $v) {
            $page->getRequest()->setAttribute($k,$v);
        }
        
        /* @var $ctx CricketContext */
        $ctx = $page->getRequest()->getAttribute("cricket");
        /* @var $savedComponent Container */
        $savedComponent = $ctx->getComponent();
        $ctx->setComponent($this);
        
        
        { // scope block -- PHP doesn't have brace level scope, right... what is the purpose of this?
            foreach($page->getRequest()->getFlattenedMap() as $k => $v) {
                $$k = $v;
            }
            $tpl = new TemplateInheritanceContext($ctx);
            if(!($this instanceof Page)) {
                echo "<!-- BEGIN TEMPLATE: $fullTemplatePath -->";
            }
            require($fullTemplatePath);
            if(!($this instanceof Page)) {
                echo "<!-- END TEMPLATE: $fullTemplatePath -->";
            }
            $tpl->flush();
        }
        
        $ctx->setComponent($savedComponent);
    }
    
    /**
     * Renders template to a string instead of to the screen.
     * 
     * @param string $inTemplatePath
     * @param array $inParamsArray
     * 
     * @return string
     */
    public function renderTemplateToString($inTemplatePath,$inParamsArray = array()) {
        $result = "";
            
        $fullTemplatePath = $this->locateTemplate($inTemplatePath,$inParamsArray);
        
        /* @var $page Page */
        $page = $this->getPage();
        
        foreach($inParamsArray as $k => $v) {
            $page->getRequest()->setAttribute($k,$v);
        }
        
        /* @var $ctx CricketContext */
        $ctx = $page->getRequest()->getAttribute("cricket");
        /* @var $savedComponent Container */
        $savedComponent = $ctx->getComponent();
        $ctx->setComponent($this);
        
        
        { // scope block -- PHP doesn't have brace level scope, right... what is the purpose of this?
            foreach($page->getRequest()->getFlattenedMap() as $k => $v) {
                $$k = $v;
            }
            $tpl = new TemplateInheritanceContext($ctx);
            ob_start();
            require($fullTemplatePath);
            $tpl->flush();
            $result = ob_get_clean();
        }
        
        $ctx->setComponent($savedComponent);
        
        return $result;
    }
    
    /**
     * Render as JSON
     * 
     * @param array $inArray
     * 
     * @return string
     */
    protected function renderJSON($inArray) {
    	echo json_encode($inArray);
    }
	
    /**
     * Call contributeToHead on each class in the hierarchy
     * 
     * @param array $added
     * @param array $results
     * 
     * @return void
     */
    public function contributeClassHierarchyToHead(&$added,&$results) {
		$this->contributeClassHierarchyToHeadFromClass(get_class($this),$this->getPage(),$added,$results);
    }
    
    /**
     * Add this component's contributions to the head
     * 
     * @param string $class
     * @param Page $page
     * @param array $added
     * @param array $results
     * 
     * @return void
     */
    public function contributeClassHierarchyToHeadFromClass($class,$page,&$added,&$results) {
        $classes = array();
        $ref = new \ReflectionClass($class);
        while($ref !== false) {
            if(!isset($added[$ref->getName()])) {
                array_unshift($classes, $ref);
                $added[$ref->getName()] = 1;
            }
            $ref = $ref->getParentClass();
        }
        
        foreach($classes as $thisClass) {
            if($thisClass->hasMethod("contributeToHead")) {
                /* @var $thisMethod ReflectionMethod */
                $thisMethod = $thisClass->getMethod("contributeToHead");
                if($thisMethod->getDeclaringClass()->getName() == $thisClass->getName()) {
                    $results[] = $thisMethod->invoke(null,$page);
                }
            }
        }    
    }

    /**
     * 
     * @param array $added
     * @param array $results
     * 
     * @return void
     */
    public function contributeComponentsToHead(&$added,&$results) {
        $this->contributeClassHierarchyToHead($added,$results);
        foreach($this->_components as $id => $c) {
            $c->contributeComponentsToHead($added,$results);
        }
    }
    
    
    
    /** 
     * Return the request
     * 
     * @return RequestContext 
     */
    public function getRequest() {
        return $this->getPage()->getRequest();
    }
    
    /** 
     * Return the response
     * 
     * @return ResponseContext 
     */
    public function getResponse() {
        return $this->getPage()->getResponse();
    }
    
    /**
     * Return all items of $_REQUEST
     * 
     * @return array
     */
    public function getParams() {
        return $_REQUEST;
    }

    /**
     * Return all items of $_FILES
     * 
     * @return array
     */
    public function getFiles() {
        return $_FILES;
    }
    
    /**
     * Return a parameter from $_REQUEST
     * 
     * @link getIntParameter
     * @link getBoolParameter
     * 
     * @param string $inName
     * @param mixed $inDefaultValue
     * 
     * @return mixed
     */
    public function getParameter($inName,$inDefaultValue = null) {
        if(isset($_REQUEST[$inName])) {
            return $_REQUEST[$inName];
        }else{
            return $inDefaultValue;
        }
    }
    
    /**
     * Return an integer parameter from $_REQUEST
     * 
     * @link getParameter
     * @link getBoolParameter
     * 
     * @param string $inName
     * @param integer $inDefaultValue
     * 
     * @return integer
     */
    public function getIntParameter($inName,$inDefaultValue = null) {
        $result = $this->getParameter($inName);
        if($result !== null) {
            $result = filter_var($result, FILTER_SANITIZE_NUMBER_INT);
            if (filter_var($result, FILTER_VALIDATE_INT) === false) {
                return $inDefaultValue;
            } else {
                return intval($result);
            }
        }
        
        return $inDefaultValue;
    }
    
    /**
     * Return a boolean parameter from $_REQUEST
     *
     * @link getParameter
     * @link getIntParameter
     *
     * @param string $inName
     * @param bool $inDefaultValue
     *
     * @return bool
     */
    public function getBoolParameter($inName,$inDefaultValue = null) {
        $result = $this->getParameter($inName);
        if($result !== null) {
            return filter_var($result, FILTER_VALIDATE_BOOLEAN);
        }
        
        return $inDefaultValue;
    }
    
    
    /** 
     * @return AjaxResponse 
     */
    public function getAjaxResponse() {
        /* @var $p Page */
        $p = $this->getPage();
        if($p->getAjaxManager() !== null) {
            return $p->getAjaxManager()->getResponse();
        }
        return null;
    }
    
    const DYNAMIC_REPLACE = 'replace';  // replaces contenst of $inContainerElementID
    const DYNAMIC_APPEND = 'append';    // appends to children of $inContainerElementID
    
    /**
     * Render a component into the ahax response.  Useful when you don't want to invalidate the parent component 
     * 
     * @param string $inContainerElementID
     * @param Component $inNewComponent
     * @param string $inMode
     * 
     * @return void
     */
    public function addDynamicAjaxComponent($inContainerElementID,$inNewComponent,$inMode = self::DYNAMIC_REPLACE) {
        $this->addComponent($inNewComponent);
        ob_start();
        $this->getPage()->renderComponent($inNewComponent->_id);
        $data = ob_get_clean();
        if($inMode == self::DYNAMIC_REPLACE) {
            $this->getAjaxResponse()->setUpdate($inContainerElementID, $data);
        }else{
            $this->getAjaxResponse()->setAppendTo($inContainerElementID,$data);
        }
    }
    
    /**
     * Render a StaticComponent dynamically
     * 
     * @link Page::renderStaticComponent
     * 
     * @param string $inContainerElementID
     * @param string $inComponentID
     * @param string $inRenderID
     * @param mixed $inRenderData
     * @param string $inMode
     * 
     * @throws \Exception
     * 
     * @return void
     */
    public function renderDynamicStaticComponent($inContainerElementID,$inComponentID,$inRenderID,$inRenderData,$inMode = self::DYNAMIC_REPLACE) {
        $c = $this->getComponent($inComponentID);
        if($c) {
            ob_start();
            $this->getPage()->renderStaticComponent($c->_id, $inRenderID, $inRenderData);
            $data = ob_get_clean();
            if($inMode == self::DYNAMIC_REPLACE) {
                $this->getAjaxResponse()->setUpdate($inContainerElementID, $data);
            }else{
                $this->getAjaxResponse()->setAppendTo($inContainerElementID,$data);
            }
        }else{
            throw new \Exception("Unknown component: $inComponentID");
        }
    }
    
    /**
     * Insert a key value pair into the session
     * 
     * @link getSession()
     * 
     * @param string $key
     * @param mixed $value
     * 
     * @return void
     */
    public function putSession($key,$value) {
        $this->getRequest()->setSessionAttribute($key,$value);
    }
    
    /**
     * Retrieve a key value pair from the session
     * 
     * @link putSession()
     * 
     * @param string $key
     * 
     * @return mixed
     */
    public function getSession($key) {
        return $this->getRequest()->getSessionAttribute($key);
    }
    
    /**
     * Place key value pair into the session
     * 
     * @param string $key
     * @param mixed $value
     * 
     * @return void
     */
    public function putAppSession($key,$value) {
        Application::getInstance()->setSessionAttribute($this->getRequest(),$key, $value);
    }
    
    /**
     * Retrieve key value pair into the session
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getAppSession($key) {
        return Application::getInstance()->getSessionAttribute($this->getRequest(),$key);
    }
    
    /**
     * Clear a key value pair from the session
     * 
     * @param string $key
     * 
     * @return void
     */
    public function clearAppSession($key) {
        Application::getInstance()->clearSessionAttribute($this->getRequest(),$key);
    }
    
}