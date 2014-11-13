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


// I've studied both PSR-0 and PSR-4 both baffle me.  
// PHP already has the include_path.   Just map the namespace to
// a file system path that would be relative the "include_path" and 
// let it rip.
//
// I'm not a fan of PSR-0 because of the legacy _ mapping issues
// I'm not a fan of PSR-4 because I can't know for sure what class is loading based on the
// namespace...  plus it it doesn't seem to help the "standards" since it doesn't define
// a method of mapping vendor prefixes to directories.
//
// I'm happy to consider using PSR-4 if someone can explain the win to me.
// Having said all that, I believe that this autoloader should play nicely with others.

/**
 * Autoload class by mapping the namespace to a file system path
 *
 * @param string $className The class name to load
 *
 * @return boolean Returns true if the class exists.  The path is added to the php include path
 */
function __cricket_autoload($className) {
    $path = str_replace("\\", DIRECTORY_SEPARATOR, $className) . ".php";
    if(stream_resolve_include_path($path) !== false) {
        include_once($path);
        return class_exists($className,false);
    }
    
    return false;
}

spl_autoload_register("__cricket_autoload");
