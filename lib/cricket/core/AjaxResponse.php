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
 * The PHP representation of a jQuery ajax call.  See cricket.js.
 */

class AjaxResponse {
    public $m;      // map<String,Object>
    
    /**
     * Construct an empty jQuery ajax repsponse.
     *
     * @todo Add support for component based preserved scrolling
     * @todo Allow component to contribute to jQuery selector for div to preserve
     *
     * @return void
     */
    public function __construct() {
        $this->m = array(
            'message' => "",
            'redirect' => "",
            'scripts_pre' => array(), // List<String>
            'updates' => array(),  // Map<String,String>
            'replacements' => array(),
            'append' => array(),
            'scripts_post' => array(), // List<String>
            'modal' => null,
            'sounds' => array(),
        );
    }
    
    
    /**
     * open jQuery dialog box
     *
     * jQuery("<div id='" + data.dialog.id + "'></div>").dialog(
     * 		jQuery.extend(
     *      	data.dialog.options, {
     *          	close: function(e,ui) {
     *              	cricket_ajax(data.dialog.closeUrl,{});
     *              	jQuery("#" + data.dialog.id).dialog('destroy').remove();
     *          	}
     *      	}
     *  	)
     * );
     * 
     * @param string $inID
     * @param string $inCloseUrl
     * @param mixed $inOptions
     *
     * @return void
     */
    public function openDialog($inID,$inCloseUrl,$inOptions) {
        $params = array(
            'content' => '',
            'id' => $inID,
            'options' => $inOptions,
            'closeUrl' => $inCloseUrl
        );
        
        $this->m["dialog"] = $params;
    }
    
    /**
     * Play sound in javascript Audio object
     *
     * @param string $inSoundHref URL
     *
     * @return void
     */
    public function playSound($inSoundHref) {
        $this->m['sounds'][] = $inSoundHref;
    }
    
    /**
     * Set document.location.href to the desired redirect
     *
     * @param string $s URL
     *
     * @return void
     */
    public function setRedirect($s) {
        $this->m['redirect'] = $s;
    }

    /**
     * Add javascript to execute before updates have been pushed.
     * @link AjaxResponse::addPostScript()
     *
     * @param string $s Javascript
     *
     * @return void
     */
    public function addPreScript($s) {
        $this->m['scripts_pre'][] = $s;
    }
    
    /**
     * Add javascript to execute after updates have been pushed.
     * @link AjaxResponse::addPreScript()
     *
     * @param string $s Javascript
     *
     * @return void
     */
    public function addPostScript($s) {
        $this->m['scripts_post'][] = $s;
    } 

    /**
     * $inContent will be pushed to the div specified by $inID.
     * @link AjaxResponse::setAppendTo()
     *
     * @param string $inID
     * @param mixed $inContent
     *
     * @return void
     */
    public function setUpdate($inID, $inContent, $replace = false) {
        $this->m['updates'][$inID] = $inContent;
        if($replace)
            $this->m['replacements'][$inID] = true;
    }
    
    /**
     * $inContent will be pushed to the div specified by $inID.
     * @link AjaxResponse::setUpdate()
     *
     * @param string $inID
     * @param mixed $inContent
     *
     * @return void
     */
    public function setAppendTo($inID,$inContent) {
        $this->m['append'][$inID] = $inContent;
    }
    
    /**
     * Set the message.
     *
     * @param string $inMessage
     *
     * @return void
     */
    public function setMessage($inMessage) {
        $this->m['message'] = $inMessage;
    }
}