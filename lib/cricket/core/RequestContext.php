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

class RequestContext {
    private $mContextStack;
    
    private $contextUrl;
    private $contextRoot;
    private $contextUri;
    
    private $extResourcePaths;
    private $dispatcherUri;
    
    public function __construct($inContextUrl,$inContextRoot,$inContextUri,$dispatcherUri,$externalResourcePaths) {
        $this->mContextStack = array(array());
        $this->contextUrl = $inContextUrl;
        $this->contextUri = $inContextUri;
        $this->contextRoot = $inContextRoot;
        $this->setAttribute("contextUrl",$inContextUrl);
        $this->setAttribute("contextPath",$inContextRoot);
        $this->extResourcePaths = $externalResourcePaths;
        $this->dispatcherUri = $dispatcherUri;
    }
    
    
    public function pushContext() {
        $this->mContextStack[] = array();
    }
    
    public function popContext() {
        array_pop($this->mContextStack);
    }
    
    public function getAttribute($name) {
        for($z = count($this->mContextStack) - 1 ; $z >= 0 ; $z--) {
            if(isset($this->mContextStack[$z][$name])) {
                return $this->mContextStack[$z][$name];
            }
        }
        return null;
    }
    
    public function setAttribute($name,$o) {
        $this->mContextStack[count($this->mContextStack) - 1][$name] = $o;
    }
    
    
    public function getPathInfo() {
        return isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : "";
    }
    
    public function getMethod() {
        return $_SERVER["REQUEST_METHOD"];
    }
    
    public function getRequestURI() {
        return $_SERVER['REQUEST_URI']; // TODO: this may be apache specific if we ever really care about any other servers
    }
    
    public function getHeader($inHeader) {
        $inHeader = strtoupper($inHeader);
        $inHeader = str_replace("-", "_", $inHeader);
        $key = "HTTP_$inHeader";
        if(isset($_SERVER[$key])) {
            return $key;
        }else{
            return null;
        }
    }
    
    public function getSessionAttribute($inKey) {
        Application::getInstance()->ensureSession();
        $result = (isset($_SESSION[$inKey])) ? $_SESSION[$inKey] : null;
        Application::getInstance()->attemptSessionClose();
        return $result;
    }
    
    public function setSessionAttribute($inKey,$value) {
        Application::getInstance()->ensureSession();
        $_SESSION[$inKey] = $value;
        Application::getInstance()->attemptSessionClose();
    }
    
    public function clearSessionAttribute($inKey) {
        unset($_SESSION[$inKey]);
    }
    
    
    public function getDispatchUrl() {
        return $this->getAttribute("contextUrl") . $this->dispatcherUri;
    }
    
    
    public function getFlattenedMap() {
        $result = null;
        foreach($this->mContextStack as $layer) {
            if($result === null) {
                $result = $layer;
            }else{
                foreach($layer as $k => $v) {
                    $result[$k] = $v;
                }
            }
        }
        
        return $result;
    }
    
    
    public function getContextUrl() {
        return $this->contextUrl;
    }
    
    public function getContextUri() {
        return $this->contextUri;
    }
    
    public function getContextRoot() {
        return $this->contextRoot;
    }
    
    
    // returns URL
    public function translatePath($fsPath) {
        $result = str_replace($this->getContextRoot(), "", $fsPath);
        if($result != $fsPath) {
            return $this->getContextUrl() . $result;
        }else{
            foreach($this->extResourcePaths as $alias => $path) {
                if(substr($path, 0, 1) != '/') {
                    $path = realpath("{$this->getContextRoot()}/{$path}");
                }
                
                $result = str_replace($path,"",$fsPath);
                if($result != $fsPath) {
                    return $this->getContextUrl() . "/$alias$result";
                }
            }
        }
        
        return "UNTRANSLATABLE_PATH/$fsPath";
    }
    

}