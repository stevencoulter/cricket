<?php

/*
 * (C) Copyright 2014 Steven Coulter <steven.coulter@gmail.com>
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

namespace app\pages;

use cricket\core\Page;
use cricket\core\Component;
use cricket\core\Application;

class StallPanel extends Component {
    const STALL_TIME_SYNC = 1;
    const STALL_TIME_ASYNC = 1;
    
    const MODE_SYNC = 'sync';
    const MODE_ASYNC = 'async';
    
    public $finished = false;
    public $mode;
    
    public function __construct($inID, $inMode = StallPanel::MODE_SYNC) {
    	parent::__construct($inID);
    	$this->mode = $inMode;
    }
    
    protected function action_stall() {
        sleep(($this->mode == StallPanel::MODE_SYNC) ? StallPanel::STALL_TIME_SYNC : StallPanel::STALL_TIME_ASYNC);
        $this->finished=True;
        $this->invalidate();
    }
    
    public function incrementAndGetCount() {
    	$c = Application::getInstance()->getSessionAttribute($this->getPage()->getRequest(), 'COUNT');
    	$count = $c;
    	Application::getInstance()->setSessionAttribute($this->getPage()->getRequest(), 'COUNT',$count+1);
    	return $count;
    }
    
    public function render() {
        $this->renderTemplate("_stall_panel.php", array(
            'finished' => $this->finished,
        	'count' => $this->incrementAndGetCount(),
        	'sync' => $this->mode == StallPanel::MODE_SYNC,
        ));
    }
}

class AsyncStallPanel extends StallPanel {
	
	public function __construct($inID) {
		parent::__construct($inID, StallPanel::MODE_ASYNC);
	}
}

class SyncStallPanel extends StallPanel {

	public function __construct($inID) {
		parent::__construct($inID, StallPanel::MODE_SYNC);
	}
}

class PageAsync extends Page {
	const NUMBER_COMPONENTS = 10;
	
	#Will start page as 'new' every time it is reloaded
	#static public $SESSION_MODE = self::MODE_RELOAD;
	
	#Keeps the page in the session, with current state
	#static public $SESSION_MODE = self::MODE_PRESERVE;
	#static public $SESSION_PAGE_VERSION = 3;
	#Refreshing in preserve mode shows an off by one error -- each time one less async component is invalidated
	
	#Works asynchronously
	#static public $SESSION_MODE = self::MODE_STATELESS;
	#I broke this...
	
    public function init() {
    	Application::getInstance()->setSessionAttribute($this->getPage()->getRequest(), 'COUNT',0);
    	
    	for ($x=0;$x<PageAsync::NUMBER_COMPONENTS;$x++) {
    		$this->addComponent(new AsyncStallPanel("async_{$x}"));
    	}
    	for ($x=0;$x<PageAsync::NUMBER_COMPONENTS;$x++) {
    		$this->addComponent(new SyncStallPanel("sync_{$x}"));
    	}
    	
    }
    
    public function render() {
    	$class = get_class($this);
        $this->renderTemplate("async.php", array(
            'pageTitle' => "Asynchronous Example Page",
        	'mode' => $class::$SESSION_MODE,
        	'number' => self::NUMBER_COMPONENTS
        ));
    }
    
}
