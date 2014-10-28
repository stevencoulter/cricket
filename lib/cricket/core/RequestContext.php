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

/**
 * Request
 *
 */

class RequestContext {
    private $mContextStack;
    
    private $contextUrl;
    private $contextRoot;
    private $contextUri;
    
    private $extResourcePaths;
    private $dispatcherUri;
    
    /**
     * 
     * @param string $inContextUrl
     * @param string $inContextRoot
     * @param string $inContextUri
     * @param string $dispatcherUri
     * @param array $externalResourcePaths
     * 
     * @return void
     */
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
    
    /**
     * Push array to context stack
     * 
     * @todo Not yet implemented?
     * 
     * @return null
     */
    public function pushContext() {
        $this->mContextStack[] = array();
    }
    
    /**
     * Return top of context stack
     * 
     * @return array
     */
    public function popContext() {
        array_pop($this->mContextStack);
    }
    
    /**
     * Search context stack for attribute
     * 
     * @param string $name
     * 
     * @return mixed
     */
    public function getAttribute($name) {
        for($z = count($this->mContextStack) - 1 ; $z >= 0 ; $z--) {
            if(isset($this->mContextStack[$z][$name])) {
                return $this->mContextStack[$z][$name];
            }
        }
        return null;
    }
    
    /**
     * Set context attribute to top of stack
     * 
     * @param string $name
     * @param mixed $o
     * 
     * @return void
     */
    public function setAttribute($name,$o) {
        $this->mContextStack[count($this->mContextStack) - 1][$name] = $o;
    }
    
    /**
     * Get server's path info
     * 
     * @return string
     */
    public function getPathInfo() {
        return isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : "";
    }
    
    /**
     * Get server's request method
     * 
     * @return string
     */
    public function getMethod() {
        return $_SERVER["REQUEST_METHOD"];
    }
    
    /**
     * Return server's request URI
     *
     * @todo This may be too apache specific
     * 
     * @return string
     */
    public function getRequestURI() {
        return $_SERVER['REQUEST_URI']; 
    }
    
    /**
     * Get server's key from HTTP header
     * 
     * @param string $inHeader
     * 
     * @return string
     */
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
    
    /**
     * Retrieve key value pair from session
     * 
     * @param string $inKey
     * 
     * @return mixed
     */
    public function getSessionAttribute($inKey) {
        Application::getInstance()->ensureSession();
        $result = (isset($_SESSION[$inKey])) ? $_SESSION[$inKey] : null;
        Application::getInstance()->attemptSessionClose();
        return $result;
    }
    
    /**
     * Insert key value pair into session
     *
     * @param string $inKey
     * @param mixed $inValue
     * 
     * @return void
     */
    public function setSessionAttribute($inKey,$value) {
        Application::getInstance()->ensureSession();
        $_SESSION[$inKey] = $value;
        Application::getInstance()->attemptSessionClose();
    }
    
    /**
     * Clear key value pair from session
     *
     * @param string $inKey
     *
     * @return void
     */
    public function clearSessionAttribute($inKey) {
        unset($_SESSION[$inKey]);
    }
    
    /**
     * Return the dispatcher URI
     *
     * @return string
     */
    public function getDispatchUrl() {
        return $this->getAttribute("contextUrl") . $this->dispatcherUri;
    }
    
    /**
     * Returns context stack as flat array
     *
     * @return array
     */
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
    
    /**
     * Return the context URL
     *
     * @return string
     */    
    public function getContextUrl() {
        return $this->contextUrl;
    }
    
    /**
     * Return the Context URI
     *
     * @return string
     */
    public function getContextUri() {
        return $this->contextUri;
    }
    
    /**
     * Return the context root
     *
     * @return string
     */
    public function getContextRoot() {
        return $this->contextRoot;
    }
    
    
    /**
     * Translates a path into url
     * 
     * @param $fsPath
     *
     * @return URL
     */
    public function translatePath($fsPath) {
        $result = str_replace($this->getContextRoot(), "", $fsPath);
        if($result != $fsPath) {
            return $this->getContextUrl() . $result;
        }else{
            foreach($this->extResourcePaths as $alias => $path) {
                if(substr($path, 0, 1) != '/') {
                    $path = realpath("{$this->getContextRoot()}/{$path}");
                }
                
                if (is_link($path)) {
                	$path = substr(readlink($path),0,-1);
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