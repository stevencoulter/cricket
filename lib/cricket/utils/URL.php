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


namespace cricket\utils;

class URL {
    
    public $parts;
    public $params;
    
    public function __construct($inURLString = null) {
        $this->params = array();
        if ($inURLString) {
            $this->parts = parse_url($inURLString);
            $qs = $this->getQuery();
            if (!empty($qs)) {
                parse_str($qs, $this->params);
            }
        } else {
            $this->parts = array(
                'scheme' => 'http',
            );
        }
    }
    
    public function getScheme() {
        return $this->getPart('scheme');
    }
    
    public function setScheme($inScheme) {
        $this->parts['scheme'] = $inScheme;
    }
    
    public function getHost() {
        return $this->getPart('host');
    }
    
    public function setHost($inHost) {
        $this->parts['host'] = $inHost;
    }
    
    public function getPort() {
        return $this->getPart('port');
    }
    
    public function setPort($inPort) {
        $this->parts['port'] = $inPort;
    }
    
    public function getUser() {
        return $this->getPart('user');
    }
    
    public function setUser($inUser) {
        $this->parts['user'] = $inUser;
    }
    
    public function getPassword() {
        return $this->getPart('pass');
    }
    
    public function setPassword($inPassword) {
        $this->parts['password'] = $inPassword;
    }
    
    public function getPath() {
        return $this->getPart('path');
    }
    
    public function setPath($inPath) {
        $this->parts['path'] = $inPath;
    }
    
    public function getQuery() {
        return $this->getPart('query');
    }
    
    public function setQuery($inQuery) {
        $this->parts['query'] = $inQuery;
    }
    
    public function getFragment() {
        return $this->getPart('fragment');
    }
    
    public function setFragment($inFrag) {
        $this->parts['fragment'] = $inFrag;
    }
    
    public function getQueryParameters() {
        return $this->params;
    }
    
    public function getQueryParameter($inName) {
        return isset($this->params[$inName]) ? $this->params[$inName] : null;
    }
    
    public function setQueryParameter($inName, $inValue) {
        $this->params[$inName] = $inValue;
    }
    
    
    public function toString() {
        $scheme = $this->getScheme();
        $result = $scheme ? $scheme . '://' : '';
        $user = $this->getUser();
        $pass = $this->getPassword();

        if ($user || $pass) {
            $result .= $user;
            if ($pass) {
                $result .= ":$pass";
            }
            
            $result .= "@";
        }
        
        $result .= $this->getHost();

        $port = $this->getPort();
        if ($port) {
            $result .= ":$port";
        }
        
        $result .= $this->getPath();

        if (count($this->params)) {
            $result .= "?" . http_build_query($this->params);
        }

        $frag = $this->getFragment();
        if ($frag) {
            $result .= "#$frag";
        }

        return $result;
    }
    
    public function __toString() {
        return $this->toString();
    }
    
    private function getPart($inName) {
        return isset($this->parts[$inName]) ? $this->parts[$inName] : null;
    }
}
