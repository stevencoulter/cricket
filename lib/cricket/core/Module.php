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


class Module {
    
    private $dispatchURL;
    
    public function __construct($inDispatchURL) {
        $this->dispatchURL = $inDispatchURL;
    }
    
    public function getDispatchURL() {
        return $this->dispatchURL;
    }
    
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
    
    public function getID() {
        if(preg_match('/.+\/(.+)$/',$this->dispatchURL,$matches)) {
            return $matches[1];
        }
        
        return "";
    }
        
    public function getPageSearchPaths() {
        $result = array();
        
        $rf = new \ReflectionClass($this);
        
        while($rf !== false) {
            $result[] = $rf->getNamespaceName() . "\\{$rf->getShortName()}\\pages";
            $rf = $rf->getParentClass();
        }
        
        return $result;
    }
    
    
    public function getPageClassPrefix() {
        return "Page";
    }

    // pageID is leaf URL pattern.   returns leaf page class name
    // [TEMP NOTE] at the moment, this is only called from resolvePageID below
    public function getPageClassNameFromPageID($inPageID) {
        $parts = explode("/",$inPageID);
        $last = $parts[count($parts) - 1];
        $className = $this->getPageClassPrefix() . ucfirst($last);
        $parts[count($parts) - 1] = $className;
        return implode("\\",$parts);
    }
    
    
    // pages leaf page class and attempts to resolve it to
    // a fully qualified class name by searching within this modules classes
    // if its already a fully qualified class name then return the class name 
    // if it exists in this module.
    
    // [TEMP NOTE]  Called from resolvePageID below, which will only ever be a leaf class
    // [TEMP NOTE]  Also called from application pageClass2ModuleAndClass which might be a Leaf Class, or fully qualified class
    public function resolvePageClass($inPageClass) {
        $searchPaths = $this->getPageSearchPaths();
        
        foreach($searchPaths as $thisPath) {
            if(strpos($inPageClass, $thisPath) === 0) {
                return $inPageClass;
            }
        }

        foreach($searchPaths as $thisTestNS) {
            $thisTestClass = "$thisTestNS\\$inPageClass";
            $thisTestPath = str_replace("\\","/",$thisTestClass) . ".php";
            if(stream_resolve_include_path($thisTestPath) !== false) {
                return $thisTestClass;
            }
        }
        
        
        return null;
    }
    
    
    // resolves a URL leaf pattern directly to a fully qualified class name
    // [TEMP NOTE]  Only called from dispatcher
    public function resolvePageID($inPageID) {
        return $this->resolvePageClass($this->getPageClassNameFromPageID($inPageID));
    }
    
    
    // given a fully qualified or leaf class name, return the URL leaf pattern
    // that will map back to the same class
    // [TEMP NOTE]  Only called from application which is only called from Page for creating URL
    public function getPageIDFromPageClassName($inPageClassName) {
        $fullClass = $this->resolvePageClass($inPageClassName);
        
        $longest = "";
        $searchPaths = $this->getPageSearchPaths();
        foreach($searchPaths as $thisPath) {
            if(strpos($fullClass, $thisPath) === 0) {
                if(strlen($thisPath) > $longest) {
                    $longest = $thisPath;
                }
            }
        }
        
        $fragment = substr($fullClass,strlen($longest) + 1);
        $parts = explode("\\",$fragment);
        $last = $parts[count($parts) - 1];

        $prefix = $this->getPageClassPrefix();
        $regex = "/^{$prefix}/";
        $last = preg_replace($regex,'',$last);
        $parts[count($parts) - 1] = strtolower($last);
        
        return implode("/",$parts);
    }
        
    
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

    
    public function assembleURL(RequestContext $inRequest,$inPageClassName,$inPagePathInfo,$inInstanceID = null,$inMutable = true) {
        $pageID = $this->getPageIDFromPageClassName($inPageClassName);
        
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