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
 * Module 
 *
 */

class Module {
    
	/** @var string */
    private $dispatchURL;
    
    /**
     * Construct the module
     * 
     * @param string $inDispatchURL
     * 
     * @return void
     */
    public function __construct($inDispatchURL) {
        $this->dispatchURL = $inDispatchURL;
    }

    /**
     * Get the dispatch URL
     * 
     * @return string
     */
    public function getDispatchURL() {
        return $this->dispatchURL;
    }
    
    /**
     * Resolve a resource's path
     * 
     * @param string $inResource
     * @param string $inApplicationContextPath
     * 
     * @return string
     */
    public function resolveResourcePath($inResource,$inApplicationContextPath) {
        
        if(strpos($inResource, '/') === 0) {
            return $inApplicationContextPath . $inResource;
        }
        
        
        $iter = new SearchPathIterator(get_class($this));
        while($iter->hasNext()) {
            $testPath = $iter->next() . "/" . $inResource;
            if(file_exists($testPath)) {
                return $testPath;
            }
        }
        
        return null;
    }
    
    /**
     * Match an ID in the dispatch URL
     * 
     * @return string
     */
    public function getID() {
        if(preg_match('/.+\/(.+)$/',$this->dispatchURL,$matches)) {
            return $matches[1];
        }
        
        return "";
    }
        
    /**
     * Get Page search paths
     * 
     * @return string
     */
    public function getPageSearchPaths() {
        $result = array();
        
        $rf = new \ReflectionClass($this);
        
        while($rf !== false) {
            $result[] = $rf->getNamespaceName() . "\\{$rf->getShortName()}\\pages";
            $rf = $rf->getParentClass();
        }
        
        return $result;
    }
    
    /**
     * Get Page class prefix
     * 
     * @return string
     */
    public function getPageClassPrefix() {
        return "Page";
    }

    /**
     * Resolve page class to fully qualified class name
     * 
     * @param string $inPageClass
     * 
     * @return string
     */
    public function resolvePageClass($inPageClass) {
    	return Translator::resolvePageClass($inPageClass, $this->getPageSearchPaths());
    }
    
	/**
	 * Resolve page ID into class name
	 * 
	 * @param string $inPageID
	 * 
	 * @return string
	 */
    public function resolvePageID($inPageID) {
    	return Translator::resolvePageID($inPageID, $this->getPageClassPrefix(), $this->getPageSearchPaths());
    }
    
    /**
     * Determine the proper class from URI.  Determine if mutable or not by use of @ or !
     * 
     * @param array $parts
     * 
     * @return multitype: string string array boolean Path, Instance ID, Parts, Mutable
     */
    public function parseURIPartsToClass($parts) {
        $results = array();
        $instanceID = null;
        $mutable = true;
        
        while(count($parts)) {
            $thisPart = array_shift($parts);
            if(substr($thisPart, 0, 1) == '@' || substr($thisPart, 0, 1) == '!') {
				if (substr($thisPart, 0, 1) == '!')
					$mutable = false;
            	$instanceID = substr($thisPart,1);
                break;
            }
            $results[] = $thisPart;
        }
        
        return array(implode("/",$results),$instanceID,$parts, $mutable);
    }

    /**
     * Assemble a URL
     * 
     * @param RequestContext $inRequest
     * @param string $inPageClassName
     * @param string $inPagePathInfo
     * @param string $inInstanceID
     * @param string $inMutable
     * 
     * @throws \Exception
     * 
     * @return string the assembled URL
     */
    public function assembleURL(RequestContext $inRequest,$inPageClassName,$inPagePathInfo,$inInstanceID = null,$inMutable = true) {
    	$fullClass = $this->resolvePageClass($inPageClassName);
    	$pageID = Translator::getPageIDFromPageClassName($fullClass, $this->getPageClassPrefix(), $this->getPageSearchPaths());
        if(empty($pageID)) {
            throw new \Exception("Unable to create URL for page class: $inPageClassName [MODULE ID: {$this->getID()}]");
        }
        
        $result = $this->dispatchURL . "/{$pageID}";
        if(!empty($inPagePathInfo)) {
            $sep = ($inMutable) ? "/@" : "/!";
            if($inInstanceID) {
                $sep .= $inInstanceID;
            }
            
            $result .= "{$sep}/{$inPagePathInfo}";
        }
        
        return $result;
    }
    
}