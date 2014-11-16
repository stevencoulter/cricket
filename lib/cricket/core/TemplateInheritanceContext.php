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


class Block {
    public $name;
    public $blocks;
    public $parent;
    
    public function __construct($inName,$inParent) {
        $this->name = $inName;
        $this->parent = $inParent;
        $this->blocks = array();
    }
    
    
    public function output() {
        foreach($this->blocks as $b) {
            if(is_string($b)) {
                echo $b;
            }else{
                $b->output();
            }
        }
    }
    
//    public function __toString() {
//        foreach($this->blocks as $b) {
//            echo $b;
//        }
//    }
}


class TemplateInheritanceContext {
    private $root;
    private $currentBlock;
    private $namedBlocks;
    private $stack;
    
    /** @var CricketContext */
    private $ctx;

    public function __construct(CricketContext $inContext) {
        $this->root = null;
        $this->currentBlock = null;
        $this->blockNames = array();
        $this->stack = array();
        $this->ctx = $inContext;
    }
    
    /**
     * Extend a template
     * 
     * @todo There was a release in svn cricket that needs updated here
     * 
     * @param string $inPath
     * @param array $additionalParams
     * 
     * @return void
     */
    public function extend_template($inPath,$additionalParams = array()) {
        if(!$this->root) {
            $this->root = new Block(null,null);
            $this->currentBlock = $this->root;
            ob_start();
        }
        $this->include_template($inPath,$additionalParams);
    }
    
    /**
     * Create a template block.  Default data can be specified
     * 
     * @link block_end()
     * 
     * @param string $inName
     * 
     * @return void
     */
    public function block_start($inName) {
        if($this->root) {
            $buffer = ob_get_clean();
            $this->currentBlock->blocks[] = $buffer;
            
            $thisBlock = null;
            if(isset($this->namedBlocks[$inName])) {
                $thisBlock = $this->namedBlocks[$inName];
                $thisBlock->blocks = array();
            }else{
                $thisBlock = new Block($inName,$this->currentBlock);
                $this->currentBlock->blocks[] = $thisBlock;
                $this->namedBlocks[$inName] = $thisBlock;
            }
            array_push($this->stack,$this->currentBlock);
            $this->currentBlock = $thisBlock;
            ob_start();
        }
    }    
    
    /**
     * End a template block
     * 
     * @link block_start()
     * 
     * @return void
     */
    public function block_end() {
        if($this->root) {
            $buffer = ob_get_clean();
            $this->currentBlock->blocks[] = $buffer;
            $this->currentBlock = array_pop($this->stack);
            ob_start();
        }
    }
    
    /**
     * Create a template block with no default content
     * 
     * @param string $inName
     */
    public function block($inName) {
        $this->block_start($inName);
        $this->block_end();
    }
    
    /**
     * Flush output
     * 
     * @return void
     */
    public function flush() {
    	
        if($this->root) {
            $this->root->output();
            //echo $this->root;
        }
    }

    /**
     * Render a widget from within a template
     *  
     * @param string $inName
     * @param string $inFirstPart
     *
     * @return Widget
     */
    public function widget($inName,$inFirstPart) {
        return new Widget($this,$inName,$inFirstPart);
    }
    
    /**
     * Include template with additional params
     * 
     * @param string $inPath
     * @param array $additionalParams
     * 
     * @return void
     */
    public function include_template($inPath,$additionalParams = array()) {
        $additionalParams['tpl'] = $this;
        $this->ctx->tpl_include($inPath,$additionalParams);
    }


    /** 
     * @return CricketContext 
     */
    public function getCricketContext() {
        return $this->ctx;
    }
}