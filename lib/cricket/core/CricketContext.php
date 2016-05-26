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

use cricket\utils\Utils;

/**
 * Context object.  This class forms $cricket in the template files.
 *
 */
class CricketContext {

    //////////////////////////////////////////
    // header contribution
    
    /**
     * Return contributions to the head
     * 
     * @return string
     */
    public function head() {
        $result = $this->page->getHeadContributions();
        return $result;
    }
    
    
    //////////////////////////////////////////
    // component rendering
    
    /**
     * Template placeholder for the component's content
     * 
     * @param string $inID
     * 
     * @return void
     */
    public function component($inID) {
        if($this->component !== null) {
            $inID = $this->component->resolveChildID($inID);
        }
        
        $this->page->renderComponent($inID);
    }
    
    /**
     * Template placeholder for static component
     * 
     * @param string $inStaticID
     * @param string $inRenderID
     * @param mixed $inData
     * 
     * @return void
     */
    public function static_component($inStaticID, $inRenderID, $inData) {
        if($this->component !== null) {
            $inID = $this->component->resolveChildID($inStaticID);
        }
        
        $this->page->renderStaticComponent($inID, $inRenderID, $inData);
    }
    
	/**
	 * Return conponent's unique ID
	 * 
	 * @param string $inSuffix
	 * 
	 * @return string
	 */
    public function componentID($inSuffix) {
        return $this->getComponent()->getId() . $inSuffix;
    }

    /**
     * Render an indicator used in call actions
     * 
     * @param string $inID
     * @param string $extraStyles
     * 
     * @return string
     */
    public function indicator($inID, $extraStyles="") {
        $imagePath = $this->resource_url("cricket/img/indicator.gif");
        return "<img src=\"$imagePath\" border=\"0\" id=\"$inID\" style=\"visibility:hidden;$extraStyles\">";
    }
    
    
    ////////////////////////////////////
    //  URL generation
    /**
     * Genreate a Page class URL
     * 
     * @param string $pageClass
     * @param string $actionID
     * 
     * @return URL
     */
    public function page_url($pageClass = null, $actionID = null) {
        return $this->page->getActionUrl($actionID, $pageClass);
    }
    
    /**
     * Generate a Component class action URL
     * 
     * @param string $actionID
     * 
     * @return URL
     */
    public function component_url($actionID) {
        return $this->component->getActionUrl($actionID);
    }
    
    /**
     * Generate a resource URL
     * 
     * @param string $inPath
     * 
     * @return string
     */
    public function resource_url($inPath, $useMinified = false) {
        $result = null;
        
        // Add ".min" before file extension
        if($useMinified) {
            $pattern = "/(.*)(\..*)$/";
            $replacement = '$1.min$2';
            $inPath = preg_replace($pattern, $replacement, $inPath);
        }
        
        if($this->component !== null) {
            $result = $this->component->resolveResourceUrl($this->page,get_class($this->component),$inPath);
        }else{
            $result = $this->component->resolveResourceUrl($this->page,get_class($this->page),$inPath);
        }
        
        if($result === null) {
            error_log("UNRESOLVED_RESOURCE_URL/$inPath");
            return "UNRESOLVED_RESOURCE_URL/$inPath";
        }else{
            return $result;
        }
    }

    ////////////////////////////////////
    //  javascript clicks and links
    
    /**
     * Create an onclick handler for a component action
     * 
     * @param string $inActionID
     * @param string $inData
     * @param string $indicatorID
     * @param string $confirmation
     * @param string $requestChannel
     * 
     * @return string
     */
    public function onclick($inActionID,$inData = null,$indicatorID = null,$confirmation = null,$requestChannel = null) {
        return Utils::escape_html($this->call_action($inActionID,$inData,$indicatorID,$confirmation,$requestChannel));
    }
    
    /**
     * Create a href action call, prepended with 'javascript:' 
     * 
     * @param string $inActionID
     * @param string $inData
     * @param string $indicatorID
     * @param string $confirmation
     * @param string $requestChannel
     * 
     * @return string
     */
    public function href($inActionID,$inData = null,$indicatorID = null,$confirmation = null,$requestChannel = null) {
        return "javascript:" . $this->onclick($inActionID,$inData,$indicatorID,$confirmation,$requestChannel);
    }
    
    /**
     * Create a javascript setInterval function to call an action after a specified time
     * 
     * @param integer $interval
     * @param integer $repeat
     * @param string $inActionID
     * @param string $inData
     * @param string $indicatorID
     * @param string $confirmation
     * @param string $requestChannel
     * 
     * @return string
     */
    public function timer($interval,$repeat,$inActionID,$inData = null,$indicatorID = null,$confirmation = null,$requestChannel = null) {
        $call = $this->call_action($inActionID, $inData, $indicatorID, $confirmation, $requestChannel);
        if($repeat) {
            return "setInterval(function() { {$call} },{$interval});";
        }else{
            return "setTimeout(function() { {$call} },{$interval});";
        }
    }
    
    /**
     * Internal function use to create the javascript
     * 
     * @param string $inActionID
     * @param string $inData
     * @param string $indicatorID
     * @param string $confirmation
     * @param string $requestChannel
     * @param string $inMutable
     * 
     * @return string
     */
    public function call_action($inActionID,$inData = null,$indicatorID = null,$confirmation = null,$requestChannel = null, $inMutable = true) {
        if($inData !== null) {
            if(is_array($inData)) {
                $inData = json_encode($inData);
            }
        }

        if ($this->component instanceof Page)
        	$url = $this->component->getActionUrl($inActionID);
        else
        	$url = $this->component->getActionUrl($inActionID, $inMutable);
        $data = $inData === null ? "{}" : $inData;
        $iID = $indicatorID === null ? "null" : "'$indicatorID'";
        if($confirmation) {
            $confirmation = addslashes($confirmation);
        }
        $conf = $confirmation === null ? "null" : "'$confirmation'";
        $channel = $requestChannel === null ? "null" : "'$requestChannel'";
        return "cricket_ajax('$url',$data,$iID,$conf,$channel);";
        
    }
    
    
    ////////////////////////////////////
    // form submits
    
    /**
     * Form action caller
     * 
     * @param string $inFormSelector
     * @param string $inActionID
     * @param string $inIndicatorID
     * @param string $inConfirmation
     * @param string $requestChannel
     * 
     * @return string cricket_ajax_form
     */
    public function action($inFormSelector,$inActionID,$inIndicatorID = null,$inConfirmation = null,$requestChannel = null) {
        $js = $this->call_form_action("jQuery('{$inFormSelector}').get()[0]", $inActionID, $inIndicatorID, $inConfirmation, $requestChannel);
        return "javascript:" . Utils::escape_html($js);
    }
    
    /**
     * Form submit action
     * 
     * @param string $inActionID
     * @param string $inIndicatorID
     * @param string $inConfirmation
     * @param string $requestChannel
     * 
     * @return string cricket_ajax_form
     */
    public function submit($inActionID,$inIndicatorID = null,$inConfirmation = null,$requestChannel = null) {
        $js = $this->call_form_action("this", $inActionID, $inIndicatorID, $inConfirmation, $requestChannel);
        return Utils::escape_html($js);
    }
    
    /**
     * Internal function to construct form action javascript
     * 
     * @param string $inJSFormReference
     * @param string $inActionID
     * @param string $inIndicatorID
     * @param string $inConfirmation
     * @param string $requestChannel
     * 
     * @return string cricket_ajax_form
     */
    public function call_form_action($inJSFormReference,$inActionID,$inIndicatorID = null,$inConfirmation = null,$requestChannel = null) {   
        
        $url = $this->component->getActionUrl($inActionID);
        $inIndicatorID = $inIndicatorID === null ? "null" : "'$inIndicatorID'";
        if($inConfirmation) {
            $inConfirmation = addslashes($inConfirmation);
        }
        $inConfirmation = $inConfirmation === null ? "null" : "'$inConfirmation'";
        $requestChannel = $requestChannel === null ? "null" : "'$requestChannel'";
        
        return "cricket_ajax_form($inJSFormReference,'{$url}',$inIndicatorID,$inConfirmation,$requestChannel);";
    }
    
    //////////////////////////////////////////
    // accessors
    
    /**
     *  Return the Page
     *  
     *  @return Page
     */
    public function getPage() {
        return $this->page;
    }
    
    /** 
     * Return the Container
     * 
     * @return Container 
     */
    public function getComponent() {
        return $this->component;
    }

    /** 
     * Return the request context
     * 
     * @return RequestContext
     */
    public function getRequest() {
        return $this->req;
    }
    
    
    ///////////////////////////////////////////
    // templating
    
    
    /**
     * Template inherentence method
     * 
     * @param string $inPath
     * @param array $additionalParams
     * 
     * @return void
     */
    public function tpl_include($inPath,$additionalParams = array()) {
        $fullPath = $this->resolveTemplatePath($inPath);
        if($fullPath !== null) {
            
            foreach($this->page->getRequest()->getFlattenedMap() as $k => $v) {
                $$k = $v;
            }
            
            foreach($additionalParams as $k => $v) {
                $$k = $v;
            }
            
            include($fullPath);
        }else{
            echo "UNRESOLVED TEMPLATE PATH: $inPath";
        }
    }    
    
    //////////////////////////////////////////
    // implementation
    
    /** @var Container */
    public $component;
    
    /** @var Page */
    public $page;
    
    /** @var RequestContext */
    public $req;
    
    
    /**
     * Construct the Context object
     * 
     * @param RequestContext $inReq
     * 
     * @return void
     */
    public function __construct(RequestContext $inReq) {
        $this->req = $inReq;
    }
    
    /**
     * Set the Page
     * 
     * @param Page $page
     * 
     * @return void
     */
    public function setPage(Page $page) {
        $this->page = $page;
        $this->component = null;
    }
    
    /**
     * Set the Component
     * 
     * @param Component $c
     * 
     * @return void
     */
    public function setComponent($c) {
        $this->component = $c;
    }
    
    
    /**
     * Resolve a template's path
     * 
     * @param string $inPath
     * 
     * @return string Full template path
     */
    public function resolveTemplatePath($inPath) {
        $fullPath = null;
        $a = array();
        if($this->component !== null) {
            $fullPath = $this->component->resolveTemplatePath($this->page, get_class($this->component), $inPath, $a,false);
        }else{
            $fullPath = $this->page->resolveTemplatePath($this->page, get_class($this->page), $inPath, $a, false);
        }
        
        return $fullPath;
    }
    
    ////////////////////////////////////////////
    // DEPRECATED
    
	/**
	 * No longer needed -- instance id embded into url
	 * 
	 * @return string
	 */
    public function form_instance_id() {
        return "<input type='hidden' name='_CRICKET_PAGE_INSTANCE_' value='" . $this->page->getInstanceID() . "'>";
    }
    
            
    /**
     * No longer needed -- instance ID embdeed into url
     * 
     * @param unknown $inURL
     * 
     * @return string
     */
    public function addInstanceIDToURL($inURL) {
        $result = new \cricket\utils\URL($inURL);
        $result->setQueryParameter(Dispatcher::INSTANCE_ID, $this->getPage()->getInstanceID());
        return $result->toString();
    }
    
    /**
     * Alternative for href
     * 
     * @param string $inActionID
     * @param string $inDataString
     * @param string $indicatorID
     * @param string $confirmation
     * @param string $requestChannel
     * 
     * @return string
     */
    public function call_href($inActionID,$inDataString = null,$indicatorID = null,$confirmation = null,$requestChannel = null) {
    	return $this->href($inActionID,$inDataString,$indicatorID,$confirmation,$requestChannel);
    }
    
    /**
     * Alternative for timer
     * 
     * @param integer $interval
     * @param integer $repeat
     * @param string $inActionID
     * @param string $inDataString
     * @param string $indicatorID
     * @param string $confirmation
     * @param string $requestChannel
     * 
     * @return string
     */
    public function call_timer($interval,$repeat,$inActionID,$inDataString = null,$indicatorID = null,$confirmation = null,$requestChannel = null) {
    	return $this->timer($interval, $repeat, $inActionID, $inDataString, $indicatorID, $confirmation, $requestChannel);
    }
    
    /**
     * Alternative for call_action
     * 
     * @param string $inActionID
     * @param string $inData
     * @param string $indicatorID
     * @param string $confirmation
     * @param string $requestChannel
     * 
     * @return string
     */
    public function call($inActionID,$inData = null,$indicatorID = null,$confirmation = null,$requestChannel = null) {
        return $this->call_action($inActionID, $inData, $indicatorID, $confirmation, $requestChannel);
    }

    /**
     * Asycnronous action call
     * 
     * @param string $inActionID
     * @param string $inData
     * @param string $indicatorID
     * @param string $confirmation
     * @param string $requestChannel
     * 
     * @return string
     */
    public function call_async($inActionID,$inData = null,$indicatorID = null,$confirmation = null,$requestChannel = null) {
    	return $this->call_action($inActionID, $inData, $indicatorID, $confirmation, $requestChannel, false);
    }
    
    /**
     * Call with escaped attribute
     * 
     * @param string $inActionID
     * @param string $inDataString
     * @param string $indicatorID
     * @param string $confirmation
     * @param string $requestChannel
     * 
     * @return string
     */
    public function call_attr($inActionID,$inDataString = null,$indicatorID = null,$confirmation = null,$requestChannel = null) {
        return $this->escapeAttr($this->call($inActionID,$inDataString,$indicatorID,$confirmation,$requestChannel));
    }
    
    /**
     * Unsafely call attribute, for malformed quotes
     * 
     * @param string $inActionID
     * @param string $inDataString
     * @param string $indicatorID
     * @param string $confirmation
     * @param string $requestChannel
     * 
     * @return string
     */
    public function call_attr_unsafe($inActionID,$inDataString = null,$indicatorID = null,$confirmation = null,$requestChannel = null) {
        return $this->escapeAttrUnsafe($this->call($inActionID,$inDataString,$indicatorID,$confirmation,$requestChannel));
    }
    
    /**
     * Use on a form action
     * 
     * @todo Currently "$inFormSelector" is interpretted as an "ID".   This is unfortunate.  It really should be a jQuery selector.
     * @param string $inActionID
     * @param string $inFormSelector
     * @param string $inIndicatorID
     * @param string $inConfirmation
     * @param string $requestChannel
     * 
     * @return string
     */
    public function form_action($inActionID,$inFormSelector,$inIndicatorID = null,$inConfirmation = null,$requestChannel = null) {
        $url = $this->component->getActionUrl($inActionID);
        $ind = $inIndicatorID === null ? "null" : "&#39;$inIndicatorID&#39;";
        $confirm = $inConfirmation === null ? "null" : "&#39;$inConfirmation&#39;";
        $channel = $requestChannel === null ? "null" : "&#39;$requestChannel&#39;";
        return "javascript:cricket_ajax_form(jQuery(&#39;#$inFormSelector&#39;).get()[0],&#39;$url&#39;,$ind,$confirm,$channel);";
    }
    
    /**
     * Use a a button's onclick to submit its parent form
     * 
     * @param string $inActionID
     * @param string $inIndicatorID
     * @param string $inConfirmation
     * @param string $inFormID
     * @param string $requestChannel
     * @param boolean $failSilent
     * 
     * @todo We can probably remove all jQuery references from these functions
     * 
     * @return string
     */
    public function form_submit($inActionID,$inIndicatorID = null, $inConfirmation = null,$inFormID = null,$requestChannel = null,$failSilent = false) {
        $form = "this";
        if($inFormID) {
            $form = "jQuery('#{$inFormID}').get()[0]";
        }
        
        $url = $this->component->getActionUrl($inActionID);
        $ind = $inIndicatorID === null ? "null" : "'$inIndicatorID'";
        $confirm = $inConfirmation === null ? "null" : "'$inConfirmation'";
        $channel = $requestChannel === null ? "null" : "'$requestChannel'";
        $failSilent = $failSilent ? "true" : "false";
        return "cricket_ajax_form($form,'$url',$ind,$confirm,$channel,$failSilent);";
    }
    
    /**
     * Form submit attr escaped
     * 
     * @param string $inActionID
     * @param string $inIndicatorID
     * @param string $inConfirmation
     * @param string $requestChannel
     * 
     * @return string
     */
    public function form_submit_attr($inActionID,$inIndicatorID = null, $inConfirmation = null,$requestChannel = null) {
        $result = $this->form_submit($inActionID,$inIndicatorID,$inConfirmation,$requestChannel);
        return $this->escapeAttr($result);
    }

    /**
     * Unsafely excape attribute due to malformed quotes
     * 
     * @todo Fix quoting implementation and remove this method
     * 
     * @param unknown $inValue
     * @return mixed
     */
    public function escapeAttrUnsafe($inValue) {
    	$result = str_replace('&apos;',"'",$this->escapeAttr($inValue));
    	$result = str_replace('&quot;','"',$result);
    	return $result;
    }
    
    /**
     * Remove certain values from value
     * 
     * @todo replace with htmlentities or Utils::escape
     * 
     * @param unknown $inValue
     * 
     * @return string excaped string
     */
    public function escapeAttr($inValue) {
        $result = str_replace('&','&amp;',$inValue);
        $result = str_replace('"','&#34;',$result);
        $result = str_replace("'",'&#39;',$result);
        $result = str_replace('<','&lt;',$result);
        $result = str_replace('>','&gt;',$result);
        return $result;
    }

    /**
     * I'm not sure what this does ;)
     * 
     * type: link | button | submit
     * action: "action"
     * label: "label"
     * param: null | array | js-string       used for link and button
     * ind: null | indicator id
     * confirm: null | string
     * 
     * @param unknown $genIndicatorID
     * @param unknown $indicatorFirst
     * @param array $controlDefs
     * 
     * @return void
     */
    public function control_group($genIndicatorID,$indicatorFirst,array $controlDefs) {
        global $INLINE;
        
        $indID = $genIndicatorID ? $this->componentID("_{$genIndicatorID}") : $genIndicatorID;
        if($indicatorFirst && $indID) {
            echo $this->indicator($indID,'vertical-align:top;');
        }
        
        $cList = array();
        foreach($controlDefs as $control) {
            $thisInd = isset($control['ind']) ? $control['ind'] : $indID;
            $param = isset($control['param']) ? $control['param'] : null;
            $confirm = isset($control['confirm']) ? $control['confirm'] : null;
            $label = Utils::escape_html($control['label']);
            
            $thisControl = "";
            switch($control['type']) {
                case "link":
                    $url = $this->call_href($control['action'],$param,$thisInd,$confirm);
                    $thisControl = "<a href='$url' class='action_link'>{$INLINE(Utils::escape_html($label))}</a>";
                    break;
                case "button":
                    $url = $this->call_attr($control['action'],$param,$thisInd,$confirm);
                    $thisControl = "<input type='button' value='{$INLINE(Utils::escape_html($label))}' onclick='$url'>";
                    break;
                case "submit":
                    $url = $this->form_submit_attr($control['action'],$thisInd,$confirm);
                    $thisControl = "<input type='button' value='{$INLINE(Utils::escape_html($label))}' onclick='$url'>";
            }
            $cList[] = $thisControl;
        }
        
        echo implode("&nbsp;",$cList);
        
        if(!$indicatorFirst && $indID) {
            echo $this->indicator($indID,'vertical-align:top;');
        }
    }

    /**
     * Action link for control group
     * 
     * @see control_group()
     * 
     * @param string $action
     * @param string $label
     * @param string $params
     * @param string $genIndicatorID
     * @param string $inConfirmation
     * 
     * @return void
     */
    public function action_link($action,$label,$params=null,$genIndicatorID=null,$inConfirmation = null) {
        $this->control_group($genIndicatorID,false,array(
            array(
                'type' => 'link',
                'action' => $action,
                'param' => $params,
                'label' => $label,
                'confirm' => $inConfirmation,
            )
        ));
    }
    
    /**
     * Action button for control group
     * 
     * @see control_group()
     * 
     * @param string $action
     * @param string $label
     * @param string $params
     * @param string $genIndicatorID
     * @param boolean $indicatorFirst
     * @param string $confirmation
     * 
     * @return void
     */
    public function action_button($action,$label,$params = null,$genIndicatorID=null,$indicatorFirst=true,$confirmation=null) {
        $this->control_group($genIndicatorID,$indicatorFirst,array(
            array(
                'type' => 'button',
                'action' => $action,
                'param' => $params,
                'label' => $label,
                'confirm' => $confirmation,
            )
        ));
    }
    
    /**
     * Submit action for control group
     * 
     * @param string $action
     * @param string $label
     * @param string $genIndicatorID
     * @param boolean $indicatorFirst
     * @param string $confirmation
     * 
     * @return void
     */
    public function action_submit($action,$label,$genIndicatorID=null,$indicatorFirst=true,$confirmation=null) {
        $this->control_group($genIndicatorID,$indicatorFirst,array(
            array(
                'type' => 'submit',
                'action' => $action,
                'label' => $label,
                'confirm' => $confirmation,
            )
        ));
    }
    
    /**
     * OK / Cancel action for control group
     * 
     * @param string $okAction
     * @param string $okLabel
     * @param string $okForm
     * @param string $okNotFormParams
     * @param string $okConfirm
     * @param string $cancelAction
     * @param string $cancelLabel
     * @param string $indicatorFirst
     */
    public function action_ok_cancel($okAction,$okLabel,$okForm = true,$okNotFormParams = null,$okConfirm = null,$cancelAction='close',$cancelLabel = "Cancel",$indicatorFirst = true) {
        $this->control_group("ind_ok_cancel",$indicatorFirst,array(
            array(
                'type' => "button",
                'action' => $cancelAction,
                'label' => $cancelLabel,
            ),
            array(
                'type' => $okForm ? 'submit' : 'button',
                'action' => $okAction,
                'label' => $okLabel,
                'param' => $okNotFormParams,
                'confirm' => $okConfirm,
            )
        ));
    }
    
    /**
     * Returns (space-separated) class names associated with the given key
     * 
     * @param string $key
     */
    public function getClasses($key) {
        $app = Application::getInstance();
        if(method_exists($app, 'getFEComponentClasses')) {
            return $app->getFEComponentClasses($key);
        } else {
            error_log('Application does not implement "getFEComponentClasses($key) method. $cricket->getClasses() will not function correctly"');
            return '';
        }
    }
    
}

