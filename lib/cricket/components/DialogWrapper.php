<?php

/*
 * (C) Copyright 2014 Steven Coulter <scoulter@assetgenie.com>
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


namespace cricket\components;

/**
 * A component that renders in a dialog box
 * 
 * See jQuery dialog
 *
 */
class DialogWrapper extends ModalComponent {
    
    public function __construct($inID,$inTitle,$inWidth,\cricket\core\Component $inComponent, $inFocus = '') {
        parent::__construct($inID, $inTitle, $inWidth, $inFocus);
		$this->addComponent($inComponent);
		$this->_component_id = $inComponent->_localID;
    }

    public function render() {
    	$this->renderFunction(function($ctx,$tpx) {
    		$ctx->component($this->_component_id);
    	});
    }
}