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
use cricket\utils\Translator;

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
    
    /** @var array */
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
    
    /**
     *
     * @return Application
     */
    static public function createApplication() {
        global $APPLICATION;
        
        if($APPLICATION == null) {
            $APPLICATION = new \app\Application();
        }
        
        return $APPLICATION;
    }
    
    /**
     *
     * @param array $inConfig
     *
     * @return void
     */
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
    
    /**
     * Set to be mutable
     * 
     * @see isMutable()
     *
     * @param boolean $inMutable
     *
     * @return void
     */
    public function setMutable($inMutable) {
     	$this->sMutable = $inMutable;
    }
    
    /**
     * Semaphore used for asycnronous calls
     *
     * @see setMutable()
     *
     * @return boolean
     */
   	public function isMutable() {
   		return $this->sMutable;
   	}
   	
   	/**
   	 * Returns the default module class name
   	 * 
   	 * @see DefaultModule
   	 *
   	 * @return string
   	 */
    public function getDefaultModuleClass() {
        return "cricket\\core\\DefaultModule";
    }
    
    /**
     * Returns the default dispatcher script name
     *
     * @param boolean $inUsePHPExtension
     *
     * @return string
     */
    protected function getDefaultDispatchScriptName($inUsePHPExtension) {
        if($inUsePHPExtension) {
            return "page.php";
        }else{
            return "page";
        }
    }
    
    /**
     * Constrict the default module
     * 
     * @see getDefaultModuleClass()
     *
     * @param string $inContextRootURL
     * @param boolean $inUsePHPExtension
     *
     * @return Module
     */
    protected function constructDefaultModule($inContextRootURL,$inUsePHPExtension) {
        $qName = $this->getDefaultModuleClass();
        return new $qName($inContextRootURL . "/" . $this->getDefaultDispatchScriptName($inUsePHPExtension));
    }
    
    /**
     * Register any modules used in Application
     * 
     * @see registerModules()
     *
     * @param string $inContextRootURL
     * @param boolean $inActiveModuleClass
     * @param boolean $inUsePHPExtension
     *
     * @return void
     */
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
    
    /**
     * Register any modules used in the application, user specified for initializeModules
     *
     * @param string $inContextRootURL
     * @param boolean $inUsePHPExtension
     *
     * @return void
     */
    protected function registerModules($inContextRootURL,$inUsePHPExtension) {
        return array();
    }
    
    /**
     *
     * @param string $inModuleClass
     *
     * @return Module
     */    
     public function getModule($inModuleClass) {
        return $this->modules[$inModuleClass];
    }
    
    /**
     *
     * @return Module
     */
    public function getModules() {
    	return $this->modules;
    }
    
    /**
     * Set the active module
     * 
     * @see getActiveModule()
     *
     * @param Module $inModule
     *
     * @return void
     */
    public function setActiveModule($inModule) {
        $this->activeModule = $inModule;
    }
    
    /**
     * Returns the active module
     * 
     * @see setActiveModule()
     *
     * @return Module
     */
    public function getActiveModule() {
        return $this->activeModule;
    }
    
    
    /**
     * Translate Page class to Module and class
     * 
     * @see pageClass2ModuleAndClass()
     *
     * @param string $inPageClass
     *
     * @return array (module, fully qualified page class)
     */
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
    
    /**
     * Translate Page class to Module and Page ID
     * 
     * @see pageClass2ModuleAndClass()
     *
     * @param string $inPageClass
     *
     * @return array (module, page_id)
     */
    public function pageClass2ModuleAndPageID($inPageClass) {
        list($module,$theClass) = $this->pageClass2ModuleAndClass($inPageClass);
        if($theClass) {
        	$pageID = Translator::getPageIDFromPageClassName($inPageClass, $this->getPageClassPrefix(), $this->getPageSearchPaths());
            return array($module,$pageID);
        }
        
        return array(null,null);
    }
    
    
    /**
     * Retrieve a session variable
     *
     * @see Application::setSessionAttribute()
     * @see Application::clearSessionAttribute()
     *
     * @param RequestContext $inReq
     * @param string $inName
     *
     * @return mixed
     */
    public function getSessionAttribute(RequestContext $inReq,$inName) {
        return $inReq->getSessionAttribute("APP_{$inName}");
    }
    
    /**
     * Set a session variable
     *
     * @see Application::getSessionAttribute()
     * @see Application::clearSessionAttribute() 
     * 
     * @param RequestContext $inReq
     * @param string $inName
     * @param mixex $inValue
     * 
     * @return void
     */
    public function setSessionAttribute(RequestContext $inReq,$inName,$inValue) {
        $inReq->setSessionAttribute("APP_{$inName}", $inValue);
    }
    
    /**
     * Clear a session variable
     *
     * @see Application::getSessionAttribute()
     * @see Application::clearSessionAttribute() 
     *
     * @param RequestContext $inReq
     * @param string $inName 
     *
     * @return void
     */
    public function clearSessionAttribute(RequestContext $inReq,$inName) {
        $inReq->clearSessionAttribute("APP_{$inName}");
    }
    
    /**
     * Start session, or if already started, increment $sSessionCount
     *
     * @return void
     */
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
    
    /**
     * Attempt to close the session.  Reduce $sSessionCount.  Close if 0
     *
     * @return void
     */
    public function attemptSessionClose() {
    	if (!$this->isMutable()) {
    		self::$sSessionCount -= 1;
    		if (self::$sSessionCount == 0)
    			$this->closeSession();
    	}
    }
    
    /**
     * Start the session
     *
     * @return void
     */
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
    
    /**
     * Close the session, if started
     *
     * @return void
     */
    public function closeSession() {
        if(self::$sSessionStarted) {
            if($this->config[self::CONFIG_SESSION] == self::PHP_SESSION) {
                session_write_close();
            }
        }
    }
    
    /**
     * Mark to killSessionOnExit
     *
     * @return void
     */
    public function destroySessionOnExit() {
        $this->killSessionOnExit = true;
    }
    
    /**
     * Sets killSessionOnExit to false
     *
     * @return void
     */
    public function enterApplication() {
        $this->killSessionOnExit = false;
    }
    
    /**
     * Destroy session if set to kill session on exit
     *
     * @return void
     */
    public function exitApplication() {
        if($this->killSessionOnExit) {
            $this->destroySessionNow();
        }
    }
    
    /**
     * Destroy session
     *
     * @return void
     */
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
    
    /**
     * Return value from config
     * 
     * @param $inKey string
     *
     * @return mixed
     */
    public function getConfigValue($inKey) {
        return $this->config[$inKey];
    }
    
    /**
     * What namespace (folder structure) to find the page classes
     *
     * @return array
     */
    public function getPageSearchPaths() {
        return array("app\\pages");
    }
    
    /**
     * Return prefix for Page class names
     *
     * @return string
     */
    public function getPageClassPrefix() {
        return "Page";
    }
        
    /**
     * Return the session id as the unique page id
     *
     * @return string
     */
    public function getUniqueId() {
    	$this->ensureSession();
    	$unique_id = session_id();
    	$this->attemptSessionClose();
    	return $unique_id;
    }
  
    /**
     * Retrieve the page from storage
     * 
     * @see savePageToStorage()
     *
     * @param string $inInstanceID
     * @param string $inPageVersion
     *
     * @return Page
     */    
    public function loadPageFromStorage($inInstanceID,$inPageVersion) {
        return $this->getPageStoreHandler()->load($this->getUniqueId(),$inInstanceID,$inPageVersion);
    }
    
    /**
     * Save the page to storage 
     *
     * @see loadPageFromStorage()
     * 
     * @param Page $inPage
     * @param string $inSaveAsInstanceId
     *
     * @return void
     */
    public function savePageToStorage(Page $inPage,$inSaveAsInstanceID = null) {
        $this->getPageStoreHandler()->save($this->getUniqueId(),$inPage,$inSaveAsInstanceID);
    }
    
    /**
     * Return the page store handler
     * 
     * @throws Exception
     *
     * @return PageStoreHandler
     */
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
    
    /**
     *
     * @param string $appID
     *
     * @return FSPageStoreHandler
     */
    protected function createPageStoreHandler($appID) {
        $storePath = \sys_get_temp_dir() . "/cricket_page_store/$appID";
        return new FSPageStoreHandler($storePath);
    }
    
}