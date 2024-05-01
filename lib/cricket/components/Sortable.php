<?php

namespace cricket\components;

// public function receive_an_item($currentListID, $indexOfReceivedItem, $cameFromListID);
// public function remove_an_item($currentListID, $indexOfRemovedItem, $movedToListID);
// public function update_an_item($currentListID, $oldIndex, $newIndex);

abstract class Sortable extends \cricket\core\Component {
    
    public function __construct($inID) {
        parent::__construct($inID);
        
        $this->get_lists();
        foreach ($this->lists as &$list) {
            $list['sortable_id'] = "sortable_".rand(0,10000);
        }
    }
    
    public function action_sortable_receive() {
        $listID = $this->getParameter('listID');
        $oldListID = $this->getParameter('oldListID');
        $newIndex = $this->getParameter('newIndex');
        $oldIndex = $this->getParameter('oldIndex');
        $action = $this->getParameter('action');
        if ($action)
            $this->$action($listID, $newIndex, $oldListID, $oldIndex);
    }
    
    public function action_sortable_remove() {
        $listID = $this->getParameter('listID');
        $newListID = $this->getParameter('newListID');
        $oldIndex = $this->getParameter('oldIndex');
        $action = $this->getParameter('action');
        if ($action)
            $this->$action($listID, $oldIndex, $newListID);
    }
    
    public function action_sortable_update() {
        $oldIndex = $this->getParameter('oldIndex');
        $newIndex = $this->getParameter('newIndex');
        $listID = $this->getParameter('listID');
        $action = $this->getParameter('action');
        if ($action)
            $this->$action($listID, $oldIndex, $newIndex);
    }
    
    public function action_sortable_select_item() {
        $listID = $this->getParameter('listID');
        $itemIndex = $this->getParameter('itemIndex');
        $action = $this->getParameter('action');
        if ($action)
            $this->$action($listID, $itemIndex);
    }
    
    static public function contributeToHead($page) {
        //$href = self::resolveResourceUrl($page, __CLASS__, 'cricket/css/tables.css');
        //return "<link rel=\"stylesheet\" href=\"$href\" type=\"text/css\">";
    }
    
    abstract protected function renderSortable($inParams);
    
    public function render() {
    	$params = array();
        $params['lists'] = $this->lists;
        $params['myself'] = $this;
    	$this->renderSortable($params);
    }
}
