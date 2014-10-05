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

class Dispatcher {
    const INSTANCE_ID = "_CRICKET_PAGE_INSTANCE_";
    const ERROR_404 = "404 Not Found";
    
    
    public $req;
    public $resp;
    public $defaultPageClass;
    
    private $moduleClass;
    
    static public $sInstance = null;
    static public function getInstance() {
        return self::$sInstance;
    }
    
    /** @return RequestContext */
    public function getRequest() {
        return $this->req;
    }
    
    /** @return ResponseContext */
    public function getResponse() {
        return $this->resp;
    }
    
    // these must match alias in apache config
    // $externalResourcePaths = array( 'alias' => 'full-system-path', ...);
    public function __construct($inUsePHPExtension = null,$inContextRootUrl = null,$inContextRootPath = null,$externalResourcePaths = array(),$inModuleClass=null,$inDefaultPageClass = null) {        
        self::$sInstance = $this;
        
        // try to guess
        if($inUsePHPExtension === null) {
            
            // if the SCRIPT_NAME is a subset of the REQUEST_URI, then we are using the extension, otherwise
            // we must be using mod_rewrite
            
            if(strpos($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME']) === 0) {
                $inUsePHPExtension = true;
            }else{
                $inUsePHPExtension = false;
            }
        }
        
        if($inContextRootUrl == null) {
            $inContextRootUrl = $this->determineContextRootUrl();
        }
        
        if($inContextRootPath == null) {
            $inContextRootPath = $this->determineContextRootPath();
        }
        
        $contextUri = $this->determineContextUri();
        
        $dispatchPathInfo = pathinfo($_SERVER['SCRIPT_FILENAME']);
        $dispatchUri = $inUsePHPExtension ? "{$dispatchPathInfo['filename']}.{$dispatchPathInfo['extension']}" : $dispatchPathInfo['filename'];
        
        $this->defaultPageClass = $inDefaultPageClass;

        $this->req = new RequestContext($inContextRootUrl,$inContextRootPath,$contextUri,$dispatchUri,$externalResourcePaths);
        $this->resp = new ResponseContext();
        
        
        /* @var $app Application */
        $app = Application::createApplication();
        if($inModuleClass == null) {
            $inModuleClass = $app->getDefaultModuleClass();
        }
        $this->moduleClass = $inModuleClass;
        $app->initializeModules($inContextRootUrl,$inModuleClass,$inUsePHPExtension);
    }
    
    protected function determineContextUri() {
        $path = $_SERVER['SCRIPT_NAME'];
        $pathInfo = pathinfo($path);
        return $pathInfo['dirname'];
    }
    
    protected function determineContextRootPath() {
        $script = $_SERVER['SCRIPT_FILENAME'];
        $pathInfo = pathinfo($script);
        return $pathInfo['dirname'];
    }
    
    protected function determineContextRootUrl() {
        $path = $_SERVER['SCRIPT_NAME'];
        $pathInfo = pathinfo($path);
        $host = $_SERVER['HTTP_HOST'];
        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 'https' : 'http';
        $port = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : '80';
        if($port == '80') {
            $port = "";
        }else{
            $port = ":$port";
        }
        
        return "{$scheme}://{$host}{$port}{$pathInfo['dirname']}";
    }
    
    protected function response_expired($inAjax) {
        $message = "Your current session with this application has expired.  Please refresh your browser window to continue.";
        if($inAjax) {
            $ajax = new AjaxResponse();
            $ajax->setMessage($message);
            echo json_encode($ajax->m);
        }else{
            echo $message;
        }
    }
    
    public function redirectToPage($inPageClass) {
        $app = Application::getInstance();
        $module = $app->getModule($this->moduleClass);
        $url = $module->assembleURL($this->req,$inPageClass,"");
        
        $this->resp->sendRedirect($url);
    }
    
    public function dispatchRequest() {
        
        // GET PATH INFO AND EXPLODE INTO PARTS, THEN REMOVE FIRST EMPTY PART
        
        $pathInfo = $this->req->getPathInfo();
        $parts = explode("/",$pathInfo);

        array_shift($parts);
        
        if(count($parts) == 1 && empty($parts[0])) {
            $parts = array();
        }
        
        if(count($parts) == 0 && $this->defaultPageClass) {
            $this->redirectToPage($this->defaultPageClass);
            return;
        }

        Application::getInstance()->enterApplication();

        // WE SHOULD HAVE AT LEAST ONE ENTRY HERE (THE PAGE ID), IF NOT, THEN SEND A 404 BELOW (ON THE ELSE)
        
        if(count($parts) > 0) {
            
            // ASK CURRENT MODULE TO RESOLVE THE PAGE ID TO THE FULLY QUALIFIED PAGE CLASS ($qName)
            // IF WE CAN'T GET A PAGE CLASS FROM THE PAGE ID, THEN REPORT 404
            
            $app = Application::getInstance();
            $module = $app->getModule($this->moduleClass);

            // GRAB THE PAGE ID AND THEN MAKE NOTE IF THERE IS ANY "SUB ITEMS" ($pageTop)
            
            list($pageID,$instanceID,$parts,$mutableCall) = $module->parseURIPartsToClass($parts);

            $pageTop = count($parts) == 0;
            $qName = $module->resolvePageID($pageID);

            $mutable = ($qName::$SESSION_MODE == Page::MODE_STATELESS) ? false : $mutableCall;
            
            Application::getInstance()->setMutable($mutable);
            
            if($qName === null) {
                $this->resp->sendError(self::ERROR_404);
            }else{
                
                // MAKE NOTE OF REQUEST TYPE (POST, CRICKET_AJAX, ETC.)
                // GRAB INSTANCE ID IF WE HAVE ONE AND MAKE NOTE IF THE INSTANCE ID IS REQUIRED
                // IF INSTANCE ID IS REQUIRED AND WE DON'T HAVE ONE, THEN THROW AN ERROR SINCE THIS IS A PROGRAMMING ERROR
                // AND NOT A RUNTIME USER ERROR
            	
                $isPost = $this->req->getMethod() == 'POST';
                $isCricketAjax = $this->req->getHeader("x-cricket-ajax") !== null;
                $postedInstanceID = isset($_REQUEST[self::INSTANCE_ID]) ? $_REQUEST[self::INSTANCE_ID] : null;
                if($postedInstanceID) {
                    $instanceID = $postedInstanceID;
                }
                $instanceIDRequired = false;
                if($qName::$SESSION_MODE != Page::MODE_STATELESS) {
                    $instanceIDRequired = $isPost || !$pageTop;
                }else{
                    $instanceID = null;
                }

                if($instanceIDRequired && !$instanceID) {
                    throw new \Exception(self::INSTANCE_ID . " parameter is required. Most likely cause of this error is forgetting to add &lt;?= \$cricket->form_instance_id() ?> in your form.");
                }


                // FOR LEGACY APPS THAT USE MODE_PRESERVE, WE WILL LOAD AND SAVE
                // THIS PAGE UNDER A SINGLE INSTANCE OF ID
                
                $loadID = $instanceID;
                $saveID = $instanceID;
                if($qName::$SESSION_MODE == Page::MODE_PRESERVE) {
                    $loadID = "p_{$module->getID()}_{$pageID}";
                    $saveID = $loadID;
                    
                    // ESCAPE HATCH FOR MODE_PRESERVE PAGES TO FORCE A RELOAD
                    
                    if($_SERVER['QUERY_STRING'] == 'reload') {
                        $instanceID = null;
                        $loadID = null;
                    }
                }

                // START OUT WITH NO PAGE AND FLAG THAT SAYS WE SHOULD CONTINUE TRYING TO PROCESS
                // THIS REQUEST
                
                $thisPage = null;
                $process = true;
                
                // OK SO FIRST, IF WE HAVE AN INSTANCE ID LETS TRY TO LOAD THE PAGE (USING $loadID INSTEAD OF INSTANCE ID
                // SEE COMMENT ABOVE ABOUT LEGACY APPS
                
                if($loadID) {
                    $thisPage = $app->loadPageFromStorage($loadID,$qName::$SESSION_PAGE_VERSION);
                    if($thisPage) {
                        
                        // IF WE GOT A PAGE, THE ONLY REASON THAT THE FOLLOWING SAFETY CHECK IS NEEDED IS FOR THE MODE_PRESERVE PAGE TYPES
                        if($instanceID) {
                            if($instanceID != $thisPage->getInstanceID()) {
                                error_log("INSTANCE_ID MISMATCH: page = " . $thisPage->getInstanceID() . ", POST = $instanceID");
                                $this->response_expired($isCricketAjax);
                                $process = false;
                            }
                        }
                    }
                }else{
                    // TODO:  This shouldn't be here, but I need to make sure that there is a session before output starts so that I can get 
                    // the session key.    I need to remove the dependancy on the session key
                    
                	#if($qName::$SESSION_MODE != Page::MODE_STATELESS) {
                    #    $app->ensureSession();
                    #}
                }

                if($process) {
                    
                    //  OK, SO IF WE DIDN'T GET A PAGE FROM ABOVE, THEN JUST CONSTRUCT A NEW ONE
                    //  AND DISPATCH THE REQUEST TO IT.
                    
                    if($thisPage == null) {
                        $thisPage = new $qName();
                    }
                    
                    MessageCenter::registerPage($thisPage);

                    $this->req->setAttribute("pageID", $pageID);
                    $this->req->setAttribute("resourceUrl",$this->req->getAttribute("contextUrl") . "/resources");
                    $thisPage->beginRequest($this->req,$this->resp);
                    try {
                        $thisPage->load();

                        $debugFile = null;$debugLine = null;
                        $process = !headers_sent($debugFile,$debugLine);

                        if($process) {
                            $cricketContext = new CricketContext($this->req);
                            $cricketContext->setPage($thisPage);
                            $this->req->setAttribute("cricket",$cricketContext);


                            /* @var $ajax AjaxResponseManager */
                            $ajax = null;
                            if($isCricketAjax) {
                                $this->resp->setContentType("text/json");
                                $ajax = new AjaxResponseManager();
                                $thisPage->setAjaxManager($ajax);
                            }else{
                                $this->resp->setContentType("text/html");
                            }
                            
                            $process = $thisPage->dispatchRequest($parts);
                            if(!$process) {
                                $this->resp->sendError(self::ERROR_404);
                            }

                            if($process && (!headers_sent()) && $ajax !== null) {
                                $ajax->renderInvalidComponents();
                                $ajax->writeToResponse($this->resp);
                            }
                        }

                        $thisPage->endRequest();
                    }catch(Exception $e) {
                        $thisPage->endRequest();
                        throw $e;
                    }

                    //  IF THE PAGE HAS STATE AND WAS LOADED, THEN
                    //  SAVE IT BACK TO THE STORAGE (AGAIN USING THE $saveID IF SET, OR IT DEFAULTS TO THE PAGE INSTANCE ID
                    
                    if($thisPage->hasState() && $thisPage->_loaded && $mutable) {
                        $app->savePageToStorage($thisPage, $saveID);
                    }
                }
            }

        }else{
            $this->resp->sendError(self::ERROR_404);
        }
        
        Application::getInstance()->exitApplication();
    }
}