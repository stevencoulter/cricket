<?php

namespace cricket\core;


class Widget {
    
    private $name;
    private $parts = array();
    private $curPart = null;
    /** @var TemplateInheritanceContext */
    private $tpl;

    // searches for "widgets/$name.php"
    public function __construct(TemplateInheritanceContext $tpl,$name,$firstPart = null) {

        $this->name = $name;
        $this->tpl = $tpl;
        
        if($firstPart) {
            if(is_array($firstPart)) {
                $this->parts = $firstPart;
            }else{
                $this->add($firstPart);
            }
        }
    }

    /**
     * Add content to a specific variable of a widget
     * 
     * @param string $inPartName
     * 
     * @return void
     */
    public function add($inPartName) {
        $this->end_part();
        $this->curPart = $inPartName;
        ob_start();
    }

    /**
     * Terminator for add, ending the current part
     * 
     * @link add()
     * 
     * @return void
     */
    public function end_part() {
        if($this->curPart != null) {
            $this->parts[$this->curPart] = ob_get_contents();
            ob_end_clean();
            $this->curPart = null;
        }
    }
    
    /**
     * Terminator for widget
     * 
     * @return void
     */
    public function end() {
        $this->end_part();
            
        //$path = "widgets/" . $this->name . ".php";
        $this->parts['cricket'] = $this->tpl->getCricketContext();
        $this->parts['tpl'] = $this->tpl;
        $this->tpl->include_template($this->name, $this->parts);
    }
}