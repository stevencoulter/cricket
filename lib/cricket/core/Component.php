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

use cricket\components\DialogComponent;
abstract class Component extends Container {
    
    public $overrideOuterDiv = false; // Only set this to true from within a template...please
    
    /**
     * @param string $inID
     * 
     * @return void
     */
    public function __construct($inID) {
        parent::__construct($inID);
    }
    
    /**
     * Assemble an action URL
     * 
     * @link Module::assembleURL()
     * 
     * @param string $inActionId
     * @param boolean $inMutable
     *
     * @return string Assembled action URL
     */
    public function getActionUrl($inActionID, $inMutable = true) {
        $pathInfo = "{$this->getId()}/{$inActionID}";
        
        $page = $this->getPage();
        return $page->getModule()->assembleURL($this->getRequest(),$page->getPageClassName(),$pathInfo,$page->getInstanceID(), $inMutable);
    }
    
    /**
     * @return void
     */
    public abstract function render();
    
    /**
     * Invalidate the page
     * 
     * @link Page::invalidateComponent()
     * 
     * @return void
     */
    public function invalidate() {
        $this->getPage()->invalidateComponent($this);
    }
    
    /**
     * Call renderComponentNow method on page for this component.  Add additional request paramaters
     * 
     * @link RequestContext
     * @link RequestContext::pushContext()
     * @link RequestContext::setAttribute()
     * @link Page::renderComponentNow()
     * @link RequestContext::popContext()
     * 
     * @param array $inParams
     *
     * @return void
     */
    public function renderNow($inParams = null) {
        $thisPage = $this->getPage();
        $thisRequest = $thisPage->getRequest();
        
        $thisRequest->pushContext();
        if($inParams !== null) {
            foreach($inParams as $k => $v) {
                $thisRequest->setAttribute($k,$v);
            }          
        }
        $thisPage->renderComponentNow($this);
        $thisRequest->popContext();
    }
    
    /**
     * Detach component from parent
     *
     * @link getParent()
     * @link removeComponent()
     *
     * @return void
     */
    public function removeFromParent() {
        if($this->getParent() !== null) {
            $this->getParent()->removeComponent($this);
        }
    }

    /**
     * Returns 
     *
     * @return Component instance || undefined
     */
    protected function getDialogParent($obj) {
        $parent = $obj->getParent($obj);

        if(!$parent)
            return null;
        else if ($parent instanceof DialogComponent)
            return $parent;
        else
            return $this->getDialogParent($parent);
    }
    
}