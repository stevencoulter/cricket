<?php

/*
 * (C) Copyright 2014 Steven Coulter <steven.coulter@gmail.com>
 * 
 * This file is part of Cricket  https://github.com/scoulter/cricket
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


namespace cricket\utils;

/**
 *  The Translator class translates between page class names and page IDs.  It can traverses page search paths to look for the page. 
 *
 */

class Translator {
	
	/**
	 * Get the Page class name
	 * 
	 * @param Page $inPage
	 * @param array $inPageSearchPaths
	 * 
	 * @return string
	 */
	public static function getPageClassName($inPage, $inPageSearchPaths) {
		$pageSearchPaths = $inPageSearchPaths;
		$thisClass = get_class($inPage);
	
		$longestMatch = "";
		foreach($pageSearchPaths as $thisPath) {
			if(strpos($thisClass,$thisPath) === 0) {
				if(strlen($thisPath) > strlen($longestMatch)) {
					$longestMatch = $thisPath;
				}
			}
		}
	
		return substr($thisClass, strlen($longestMatch)+1);
	}
	
	/**
	 * 
	 * @param string $inPageID
	 * @param string $inPageClassPrefix
	 * 
	 * @return string
	 */
	public static function getPageClassNameFromPageID($inPageID, $inPageClassPrefix) {
		$parts = explode("/",$inPageID);
		$last = $parts[count($parts) - 1];
		$className = $inPageClassPrefix . ucfirst($last);
		$parts[count($parts) - 1] = $className;
		return implode("\\",$parts);
	}
    
	/**
	 * Return the PageID given a Page class name
	 *
	 * @param $inPageClassName fully qualified
	 *
	 * @return array
	 */
	public static function getPageIDFromPageClassName($inPageClassName, $inPageClassPrefix, $inPageSearchPaths) {
		$longest = "";
		foreach($inPageSearchPaths as $thisPath) {
			if(strpos($inPageClassName, $thisPath) === 0) {
				if(strlen($thisPath) > $longest) {
					$longest = $thisPath;
				}
			}
		}
	
		$fragment = substr($inPageClassName,strlen($longest) + 1);
		$parts = explode("\\",$fragment);
		$last = $parts[count($parts) - 1];
	
		$prefix = $inPageClassPrefix;
		$regex = "/^{$prefix}/";
		$last = preg_replace($regex,'',$last);
		$parts[count($parts) - 1] = $last;
	
		return implode("/",$parts);
	}
	
	/**
	 * Translate to Page class and resolve
	 * 
	 * @param string $inPageID
	 * @param string $inPageClassPrefix
	 * @param array $inPageSearchPaths
	 * 
	 * @return string PageClass
	 */
	public static function resolvePageID($inPageID, $inPageClassPrefix, $inPageSearchPaths) {
		$pageClass = Translator::getPageClassNameFromPageID($inPageID, $inPageClassPrefix);
		return Translator::resolvePageClass($pageClass,$inPageSearchPaths);
	}
	
	/**
	 * Traverse page search paths to look for page class
	 * 
	 * @param string $inPageClass
	 * @param array $inPageSearchPaths
	 * 
	 * @return string
	 */
	public static function resolvePageClass($inPageClass, $inPageSearchPaths) {		
		foreach($inPageSearchPaths as $thisPath) {
			if(strpos($inPageClass, $thisPath) === 0) {
				return $inPageClass;
			}
		}
		
		foreach($inPageSearchPaths as $thisTestNS) {
			$thisTestClass = "$thisTestNS\\$inPageClass";
			$thisTestPath = str_replace("\\",DIRECTORY_SEPARATOR,$thisTestClass) . ".php";
			if(stream_resolve_include_path($thisTestPath) !== false) {
				return $thisTestClass;
			}
		}
		
		
		return null;
	}
}
