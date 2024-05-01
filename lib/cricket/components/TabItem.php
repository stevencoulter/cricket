<?php

namespace cricket\components;

use cricket\core\Component;

abstract class TabItem extends Component {
    
    public abstract function getTabLabel();    // return tab label for this item
    
    // optional methods
    public function becomeActiveTab() {
        
    }
    
    public function resignActiveTab() {
        
    }
    
}
