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

class CricketContext {

    //////////////////////////////////////////
    // header contribution
    
    public function head() {
        $result = $this->page->getHeadContributions();
        return $result;
    }
    
    
    //////////////////////////////////////////
    // component rendering
    
    public function component($inID) {
        if($this->component !== null) {
            $inID = $this->component->resolveChildID($inID);
        }
        
        $this->page->renderComponent($inID);
    }
    
    public function static_component($inStaticID,$inRenderID,$inData) {
        if($this->component !== null) {
            $inID = $this->component->resolveChildID($inStaticID);
        }
        
        $this->page->renderStaticComponent($inID,$inRenderID,$inData);
    }
    
    // return unique id based on component prefix
    public function componentID($inSuffix) {
        return $this->getComponent()->getId() . $inSuffix;
    }

    public function indicator($inID,$extraStyles="") {
        $imagePath = $this->resource_url("cricket/img/indicator.gif");
        return "<img src=\"$imagePath\" border=\"0\" id=\"$inID\" style=\"visibility:hidden;$extraStyles\">";
    }
    
    
    ////////////////////////////////////
    //  URL generation
    
    public function page_url($pageClass = null,$actionID = null) {
        return $this->page->getActionUrl($actionID, $pageClass);
    }
    
    public function component_url($actionID) {
        return $this->component->getActionUrl($actionID);
    }
    
    public function resource_url($inPath) {
        $result = null;
        
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
    
    public function onclick($inActionID,$inData = null,$indicatorID = null,$confirmation = null,$requestChannel = null) {
        return Utils::escape_html($this->call_action($inActionID,$inData,$indicatorID,$confirmation,$requestChannel));
    }
    
    
    public function href($inActionID,$inData = null,$indicatorID = null,$confirmation = null,$requestChannel = null) {
        return "javascript:" . $this->onclick($inActionID,$inData,$indicatorID,$confirmation,$requestChannel);
    }
    
    public function timer($interval,$repeat,$inActionID,$inData = null,$indicatorID = null,$confirmation = null,$requestChannel = null) {
        $call = $this->call_action($inActionID, $inData, $indicatorID, $confirmation, $requestChannel);
        if($repeat) {
            return "setInterval(function() { {$call} },{$interval});";
        }else{
            return "setTimeout(function() { {$call} },{$interval});";
        }
    }
    
    
    public function call_action($inActionID,$inData = null,$indicatorID = null,$confirmation = null,$requestChannel = null, $inMutable = true) {
        if($inData !== null) {
            if(is_array($inData)) {
                $inData = json_encode($inData);
            }
        }
        
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
    
    public function action($inFormSelector,$inActionID,$inIndicatorID = null,$inConfirmation = null,$requestChannel = null) {
        $js = $this->call_form_action("jQuery('{$inFormSelector}').get()[0]", $inActionID, $inIndicatorID, $inConfirmation, $requestChannel);
        return "javascript:" . Utils::escape_html($js);
    }
    
    public function submit($inActionID,$inIndicatorID = null,$inConfirmation = null,$requestChannel = null) {
        $js = $this->call_form_action("this", $inActionID, $inIndicatorID, $inConfirmation, $requestChannel);
        return Utils::escape_html($js);
    }
    
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
    
    /** @return Page */
    public function getPage() {
        return $this->page;
    }
    
    /** @return Container */
    public function getComponent() {
        return $this->component;
    }

    /** @return RequestContext */
    public function getRequest() {
        return $this->req;
    }
    
    
    ///////////////////////////////////////////
    // templating
    
    
    // template inheritance method
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
    
    
    public function __construct(RequestContext $inReq) {
        $this->req = $inReq;
    }
    
    public function setPage(Page $page) {
        $this->page = $page;
        $this->component = null;
    }
    
    
    public function setComponent($c) {
        $this->component = $c;
    }
    
    
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
    
    // TODO:  I'm 98% sure this isnt needed any more since cricket now always embeds the instance ID into the URL
    public function form_instance_id() {
        return "<input type='hidden' name='_CRICKET_PAGE_INSTANCE_' value='" . $this->page->getInstanceID() . "'>";
    }
    
            
    // TODO:  I'm 98% sure this isnt needed any more since cricket now always embeds the instance ID into the URL
    public function addInstanceIDToURL($inURL) {
        $result = new \cricket\utils\URL($inURL);
        $result->setQueryParameter(Dispatcher::INSTANCE_ID, $this->getPage()->getInstanceID());
        return $result->toString();
    }
    
    public function call_href($inActionID,$inDataString = null,$indicatorID = null,$confirmation = null,$requestChannel = null) {
        return "javascript:" . $this->escapeAttr($this->call($inActionID,$inDataString,$indicatorID,$confirmation,$requestChannel));
    }
    
    
    
    public function call_timer($interval,$repeat,$inActionID,$inDataString = null,$indicatorID = null,$confirmation = null,$requestChannel = null) {
        if($inDataString !== null) {
            if(is_array($inDataString)) {
                $inDataString = json_encode($inDataString);
            }
        }
        
        $url = $this->component->getActionUrl($inActionID);
        $data = $inDataString === null ? "{}" : $inDataString;
        $iID = $indicatorID === null ? "null" : "\'$indicatorID\'";
        $conf = $confirmation === null ? "null" : "\'$confirmation\'";
        $channel = $requestChannel === null ? "null" : "\'$requestChannel\'";
        if ($repeat) {
            return "setInterval('cricket_ajax(\'$url\',$data,$iID,$conf,$channel)',$interval);";
        } else {
            return "setTimeout('cricket_ajax(\'$url\',$data,$iID,$conf,$channel)',$interval);";
        }
    }
    
    public function call($inActionID,$inData = null,$indicatorID = null,$confirmation = null,$requestChannel = null) {
        return $this->call_action($inActionID, $inData, $indicatorID, $confirmation, $requestChannel);
    }

    public function call_async($inActionID,$inData = null,$indicatorID = null,$confirmation = null,$requestChannel = null) {
    	return $this->call_action($inActionID, $inData, $indicatorID, $confirmation, $requestChannel, false);
    }
    
    public function call_attr($inActionID,$inDataString = null,$indicatorID = null,$confirmation = null,$requestChannel = null) {
        return $this->escapeAttr($this->call($inActionID,$inDataString,$indicatorID,$confirmation,$requestChannel));
    }
    
    public function call_attr_unsafe($inActionID,$inDataString = null,$indicatorID = null,$confirmation = null,$requestChannel = null) {
        return $this->escapeAttrUnsafe($this->call($inActionID,$inDataString,$indicatorID,$confirmation,$requestChannel));
    }
    
    // use this on a form "action" attribute
    // TODO:  Currently "$inFormSelector" is interpretted as an "ID".   This is unfortunate.  It really should be a jQuery selector.
    public function form_action($inActionID,$inFormSelector,$inIndicatorID = null,$inConfirmation = null,$requestChannel = null) {
        $url = $this->component->getActionUrl($inActionID);
        $ind = $inIndicatorID === null ? "null" : "&#39;$inIndicatorID&#39;";
        $confirm = $inConfirmation === null ? "null" : "&#39;$inConfirmation&#39;";
        $channel = $requestChannel === null ? "null" : "&#39;$requestChannel&#39;";
        return "javascript:cricket_ajax_form(jQuery(&#39;#$inFormSelector&#39;).get()[0],&#39;$url&#39;,$ind,$confirm,$channel);";
    }
    
    
    
    
    // use this on a button's onclick to submit the parent form
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
    
    public function form_submit_attr($inActionID,$inIndicatorID = null, $inConfirmation = null,$requestChannel = null) {
        $result = $this->form_submit($inActionID,$inIndicatorID,$inConfirmation,$requestChannel);
        return $this->escapeAttr($result);
    }
    
    

    
    // TODO:  I think this is here because of the poor quoting implementation on the "call" methods
    // if thats the reason, then after thats fixed, this can be removed.
    
    public function escapeAttrUnsafe($inValue) {
    	$result = str_replace('&apos;',"'",$this->escapeAttr($inValue));
    	$result = str_replace('&quot;','"',$result);
    	return $result;
    }
    
    // TODO:  shoulde we use htmlentities, or Utils::escape?
    public function escapeAttr($inValue) {
        $result = str_replace('&','&amp;',$inValue);
        $result = str_replace('"','&#34;',$result);
        $result = str_replace("'",'&#39;',$result);
        $result = str_replace('<','&lt;',$result);
        $result = str_replace('>','&gt;',$result);
        return $result;
    }
    
    

    
    // control def
    //      type: link | button | submit
    //      action: "action"
    //      label: "label"
    //      param: null | array | js-string     <-- used for link and button
    //      ind:   null | indicator id          <-- if null uses supplied indicator
    //      confirm: null | string
    
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
}

