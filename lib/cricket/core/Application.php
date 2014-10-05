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

use cricket\core\page_store\PageStoreHandler;
use cricket\core\page_store\FSPageStoreHandler;


class Application {
    ////////////// CONFIG KEYS //////////////////
    const CONFIG_SESSION = 'session_mode';
    const CONFIG_DB_SESSION = 'db_session_config';      // value is standard DB config for Connection class
    const CONFIG_PHP_SESSION = 'php_session_config';    // array(self::PHP_SESSION_NAME => "value", self::PHP_SESSION_PATH => "value", self::PHP_SESSION_EXPIRE => 0);
    const CONFIG_APP_URI = 'app_uri';
    const CONFIG_AUTO_PAGE_GC_AGE = 'auto_page_gc_age';  // in seconds (pages untouched older than this will be deleted)
    const CONFIG_APP_ID = 'app_id';
    
    /////////////   PHP SESSION KEYS ////////////
    const PHP_SESSION_NAME = 'name';
    const PHP_SESSION_PATH = 'path';
    const PHP_SESSION_EXPIRE = 'expire';
    
    
    static private $sInstance = null;
    static private $sSessionStarted = false;
    static private $sSessionCount = 0;
    private $sMutable = true;
    
    
    /** @return Application */
    static public function getInstance() {
        return self::$sInstance;
    }
    /////////////////////////////////////////
 
    const NO_SESSION = 'no_session';
    const PHP_SESSION = 'php_session';
    const DB_SESSION = 'db_session';
    
    private $config;
    private $sessionMode = self::PHP_SESSION;
    private $dbSessionConfig = array();
    private $modules;           // module namespace => instance
    private $moduleOrder;       // array of modules in order of searching
    /** @var Module */
    private $activeModule;      // Module that recieved the dispatch
    
    private $killSessionOnExit;
    
    /** @var PageStoreHandler */
    private $pageStoreHandler;
    
    
    static public function createApplication() {
        global $APPLICATION;
        
        if($APPLICATION == null) {
            $APPLICATION = new \app\Application();
        }
        
        return $APPLICATION;
    }
    
    public function __construct($inConfig = array()) {
        self::$sInstance = $this;

        $this->config = array(
            self::CONFIG_SESSION => self::PHP_SESSION,
            self::CONFIG_DB_SESSION => array(),
            self::CONFIG_PHP_SESSION => array(),
            self::CONFIG_APP_URI => "/",
            self::CONFIG_AUTO_PAGE_GC_AGE => null,
            self::CONFIG_APP_ID => "unset",
        );
        
        foreach($inConfig as $k => $v) {
            $this->config[$k] = $v;
        }
                
        $this->modules = array();
        $this->moduleOrder = array();
        
        self::$sSessionStarted = false;
        
        // lazy start
        //$this->startSession();
    }
    
    public function setMutable($inMutable) {
     	$this->sMutable = $inMutable;
    }
    
   	public function isMutable() {
   		return $this->sMutable;
   	}
    
    public function getDefaultModuleClass() {
        return "cricket\\core\\DefaultModule";
    }
    
    protected function getDefaultDispatchScriptName($inUsePHPExtension) {
        if($inUsePHPExtension) {
            return "page.php";
        }else{
            return "page";
        }
    }
    
    protected function constructDefaultModule($inContextRootURL,$inUsePHPExtension) {
        $qName = $this->getDefaultModuleClass();
        return new $qName($inContextRootURL . "/" . $this->getDefaultDispatchScriptName($inUsePHPExtension));
    }
    
    public function initializeModules($inContextRootURL,$inActiveModuleClass,$inUsePHPExtension) {
        $theModules = $this->registerModules($inContextRootURL,$inUsePHPExtension);
        
        $defaultModule = $this->constructDefaultModule($inContextRootURL,$inUsePHPExtension);
        $this->modules[get_class($defaultModule)] = $defaultModule;
        $this->moduleOrder[] = $defaultModule;
        
        /* @var $thisModule cricket\core\Module */
        foreach($theModules as $thisModule) {
            $this->modules[get_class($thisModule)] = $thisModule; 
            $this->moduleOrder[] = $thisModule;
        }
        
        $this->setActiveModule($this->modules[$inActiveModuleClass]);
    }
    
    protected function registerModules($inContextRootURL,$inUsePHPExtension) {
        return array();
    }
    
    /** @return cricket\core\Module */
    public function getModule($inModuleClass) {
        return $this->modules[$inModuleClass];
    }
    
    public function setActiveModule($inModule) {
        $this->activeModule = $inModule;
    }
    
    /** @return cricket\core\Module */
    public function getActiveModule() {
        return $this->activeModule;
    }
    
    
    // returns array($module,$fullyQualifiedPageClass)
    protected function pageClass2ModuleAndClass($inPageClass) {
        if($this->activeModule) {
            $theClass = $this->activeModule->resolvePageClass($inPageClass);
            if($theClass !== null) {
                return array($this->activeModule,$theClass);
            }
        }
        
        foreach($this->moduleOrder as $thisModule) {
            if($thisModule != $this->activeModule) {
                $theClass = $thisModule->resolvePageClass($inPageClass);
                if($theClass !== null) {
                    return array($thisModule,$theClass);
                }
            }
        }
        
        return array(null,null);
    }
    
    // returns array($module,$pageID)
    // [TEMP NOTE]  Called from Page to determine its module, and to create the action URL
    public function pageClass2ModuleAndPageID($inPageClass) {
        list($module,$theClass) = $this->pageClass2ModuleAndClass($inPageClass);
        if($theClass) {
            return array($module,$module->getPageIDFromPageClassName($theClass));
        }
        
        return array(null,null);
    }
    
    
    // THESE THREE SESSION FUNCTIONS PROVIDE APPLICATION GLOBAL NAMESPACE FOR SESSION ATTRIBUTES.
    // SIMPLE PREFIX IS USED.
    
    public function getSessionAttribute(RequestContext $inReq,$inName) {
        return $inReq->getSessionAttribute("APP_{$inName}");
    }
    
    public function setSessionAttribute(RequestContext $inReq,$inName,$inValue) {
        $inReq->setSessionAttribute("APP_{$inName}", $inValue);
    }
    
    public function clearSessionAttribute(RequestContext $inReq,$inName) {
        $inReq->clearSessionAttribute("APP_{$inName}");
    }
    
    public function ensureSession() {
    	if (!$this->isMutable()) {
    		if (self::$sSessionCount == 0 && !self::$sSessionStarted)
    			$this->startSession();
    		self::$sSessionCount += 1;
    	} else {
	        if(!self::$sSessionStarted) {
	            $this->startSession();
	        }
    	}
    }
    
    public function attemptSessionClose() {
    	if (!$this->isMutable()) {
    		self::$sSessionCount -= 1;
    		if (self::$sSessionCount == 0)
    			$this->closeSession();
    	}
    }
    
    public function startSession() {
        if($this->config[self::CONFIG_SESSION] == self::PHP_SESSION){
            self::$sSessionStarted = true;

            $phpSession = $this->config[self::CONFIG_PHP_SESSION];
            
            if(!empty($phpSession[self::PHP_SESSION_NAME])) {
                session_name($phpSession[self::PHP_SESSION_NAME]);
            }
            
            if(!empty($phpSession[self::PHP_SESSION_PATH])) {
                $expire = empty($phpSession[self::PHP_SESSION_EXPIRE]) ? 0 : $phpSession[self::PHP_SESSION_EXPIRE];
                
                session_set_cookie_params($expire, $phpSession[self::PHP_SESSION_PATH]);
            }
            
            session_start();
        }
    }
    
    public function closeSession() {
        if(self::$sSessionStarted) {
            if($this->config[self::CONFIG_SESSION] == self::PHP_SESSION) {
                session_write_close();
            }
        }
    }
    
    public function destroySessionOnExit() {
        $this->killSessionOnExit = true;
    }
    
    public function enterApplication() {
        $this->killSessionOnExit = false;
    }
    
    public function exitApplication() {
        if($this->killSessionOnExit) {
            $this->destroySessionNow();
        }
    }
    
    public function destroySessionNow() {
    	if (!$this->isMutable()) {
    		if(self::$sSessionCount > 0) {
    			$_SESSION = array();
    			session_destroy();
    			self::$sSessionCount = 0;
    		}
    	} else {
	        if(self::$sSessionStarted) {
	            $_SESSION = array();
	            session_destroy();
	            self::$sSessionStarted = false;            
	        }
    	}
    }
    
    
    public function getConfigValue($inKey) {
        return $this->config[$inKey];
    }
    
    public function getPageSearchPaths() {
        return array("app\\pages");
    }
    
    public function getPageClassPrefix() {
        return "Page";
    }
    
    public function getPageClassNameFromPageID($inPageID) {
        $parts = explode("/",$inPageID);
        $last = $parts[count($parts) - 1];
        $className = $this->getPageClassPrefix() . ucfirst($last);
        $parts[count($parts) - 1] = $className;
        return implode("\\",$parts);
    }
    
    // $inPageClassName must be fully qualified class
    public function getPageIDFromPageClassName($inPageClassName) {        
        $longest = "";
        $searchPaths = $this->getPageSearchPaths();
        foreach($searchPaths as $thisPath) {
            if(strpos($inPageClassName, $thisPath) === 0) {
                if(strlen($thisPath) > $longest) {
                    $longest = $thisPath;
                }
            }
        }
        
        $fragment = substr($inPageClassName,strlen($longest) + 1);
        $parts = explode("\\",$fragment);
        $last = $parts[count($parts) - 1];

        $prefix = $this->getPageClassPrefix();
        $regex = "/^{$prefix}/";
        $last = preg_replace($regex,'',$last);
        $parts[count($parts) - 1] = strtolower($last);
        
        return implode("/",$parts);
    }
    
    public function getUniqueId() {
    	$this->ensureSession();
    	$unique_id = session_id();
    	$this->attemptSessionClose();
    	return $unique_id;
    }
  
    /** @return Page */
    public function loadPageFromStorage($inInstanceID,$inPageVersion) {
        return $this->getPageStoreHandler()->load($this->getUniqueId(),$inInstanceID,$inPageVersion);
    }
    
    public function savePageToStorage(Page $inPage,$inSaveAsInstanceID = null) {
        $this->getPageStoreHandler()->save($this->getUniqueId(),$inPage,$inSaveAsInstanceID);
    }
    
    /** @return PageStoreHandler */
    public function getPageStoreHandler() {
        if(!$this->pageStoreHandler) {
            $appID = $this->config[self::CONFIG_APP_ID];
            if(!$appID) {
                throw new \Exception("Cricket Application Configuration Error:  Application::CONFIG_APP_ID must be set");
            }
            $this->pageStoreHandler = $this->createPageStoreHandler($appID);
            if($this->config[self::CONFIG_AUTO_PAGE_GC_AGE] !== null) {
                $this->pageStoreHandler->gc($this->config[self::CONFIG_AUTO_PAGE_GC_AGE]);
            }
        }
        
        return $this->pageStoreHandler;
    }
    
    // Override this method
    protected function createPageStoreHandler($appID) {
        $storePath = \sys_get_temp_dir() . "/cricket_page_store/$appID";
        return new FSPageStoreHandler($storePath);
    }
    
}