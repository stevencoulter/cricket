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


namespace cricket\components;

// TODO:  Add complete support for built in selection
// TODO:  Add multi level sort support

/**
 * A class to display tabular data
 * 
 * Built in support for paging and sorting
 *
 */
abstract class Table extends \cricket\core\Component {
    
    /** @var Table_Sort */
    public $sort;
    public $start;
    public $pageSize;
    public $totalCount;
    public $adjacentPages;
    
    public $scrollingHeight;
    public $autoPagingThreshold;
    public $autoPagingPageSize;
    public $autoPagingScrollingSize;
    public $autoPagingActive;
    public $originalPageSize;
    public $originalScrollingHeight;
    
    public function __construct($inID,Table_Sort $inDefaultSort,$inPageSize,$inAdjacentPages = 3) {
        parent::__construct($inID);
        
        $this->sort = $inDefaultSort;
        $this->start = 0;
        $this->pageSize = $inPageSize;
        $this->totalCount = 0;
        $this->adjacentPages = $inAdjacentPages;
        $this->scrollingHeight = null;
        $this->autoPagingThreshold = 0;
        $this->autoPagingPageSize = 0;
        $this->autoPagingScrollingHeight = null;
        $this->autoPagingActive = false;
        $this->originalPageSize = $inPageSize;
        $this->originalScrollingHeight = null;
    }
    
    // $inPageSize = 0 means scrolling
    public function setPagingMode($inPageSize,$inScrollingHeight = null) {
        $this->pageSize = $inPageSize;
        $this->scrollingHeight = $inScrollingHeight;
        
        $this->originalPageSize = $inPageSize;
        $this->originalScrollingHeight = $inScrollingHeight;
        $this->autoPagingActive = false;
    }
    
    public function setAutoPagingThreshold($inThreshold,$inPageSize,$inScrollingHeight = null) {
        $this->autoPagingThreshold = $inThreshold;
        $this->autoPagingPageSize = $inPageSize;
        $this->autoPagingScrollingHeight = $inScrollingHeight;
    }
    
        
    public function getSort() {
        return $this->sort;
    }
    
    
    public function action_select() {
        $this->selectItem($this->getParameter("id"));
    }
    
    public function action_next_page() {
        $newStart = $this->start + $this->pageSize;
        // can't really validate this here unless I remember the last "total" that was supplied.
        // for now I'm not going to
        $this->start = $newStart;
        error_log("{$this->start}");
        $this->invalidate();
    }
    
    public function action_prev_page() {
        $newStart = $this->start - $this->pageSize;
        if($newStart < 0) {
            $newStart = 0;
        }
        
        $this->start = $newStart;
        error_log("{$this->start}");
        $this->invalidate();
    }
    
    public function action_goto_page() {
        $page = $this->getParameter('page');
        
        $this->start = ($page - 1) * $this->pageSize;
        error_log("{$this->start}");
        $this->invalidate();
    }
    
    
    public function render() {
        $params = null;
        $thisSet = null;
        
        $tryAgain = true;
        while($tryAgain) {
            $tryAgain = false;
            $params = array(
                'pageSize' => $this->pageSize
            );

            /* @var $thisSet Table_DataSet */
            $thisSet = $this->getDataSetAndRenderParams($this->start,$this->pageSize,$this->sort,$params);
            if ($thisSet === false) {
                $thisSet = $this->getDataSet($this->start,$this->pageSize,$this->sort);
            }
            
            if($thisSet->totalCount < count($thisSet->items)) {
                throw new \Exception("Application error: getDataSet returned more items (" . count($thisSet->items) . ") than indicated by the totalCount ({$thisSet->totalCount})");
            }
            
            if($this->autoPagingThreshold > 0) {
                if(count($thisSet->items) > $this->autoPagingThreshold) {
                    if($this->pageSize == $this->autoPagingPageSize) {
                        error_log("LOGIC ERROR:  getDataSet returned more items that indicated by the page size");
                        break;
                    }
                                        
                    $this->pageSize = $this->autoPagingPageSize;
                    $this->scrollingHeight = $this->autoPagingScrollingHeight;
                    $this->start = 0;
                    $this->autoPagingActive = true;
                    
                    $tryAgain = true;
                }else if($thisSet->totalCount <= $this->autoPagingThreshold) {
                    if($this->autoPagingActive) {
                        $this->autoPagingActive = false;
                        $this->pageSize = $this->originalPageSize;
                        $this->scrollingHeight = $this->originalScrollingHeight;
                        $this->start = 0;
                        $tryAgain = true;
                    }
                }
            }
        }
        
        
        $pageTotal = $this->totalCount = $thisSet->totalCount;
        $pageStart = $this->start + 1;
        $pageEnd = $this->start + count($thisSet->items);
        $pageNext = $pageEnd < $pageTotal;
        $pagePrev = $pageStart > 1;
        if ($this->pageSize == 0) {
            $num_pages = 0;
            $current_page = 0;
        } else {
            $num_pages = intval(ceil($this->totalCount / $this->pageSize));
            $current_page = ($this->start / $this->pageSize) + 1;
        }
        
        $pages = $this->getPaginationPages($num_pages, $current_page);
        
        // pass $params last to array_merge so any conflicts from getDataSetAndRenderParams result in 
        // getDataSetAndRenderParams winning
        $this->renderTable(array_merge(array(
            "items" => $thisSet->items,
            "itemSort" => $this->sort,
            "pageTotal" => $pageTotal,
            "pageStart" => $pageStart,
            "pageEnd" => $pageEnd,
            "pageNext" => $pageNext,
            "pagePrev" => $pagePrev,
            "adjacents" => $this->adjacentPages,
            "pageCount" => $num_pages,
            "pageCurrent" => $current_page,
            "pages" => $pages
        ), $params));
    }
    
    protected function getPaginationPages($inNumPages, $inCurrentPage) {
        $pages = array();

        if ($inNumPages > 1) {
            if ($inNumPages < 7 + ($this->adjacentPages * 2)) {
                for ($counter = 1; $counter <= $inNumPages; $counter++) {
                    $pages[] = $counter;
                }
            }
            // enough pages to hide some?
            else if ($inNumPages > 5 + ($this->adjacentPages * 2)) {
                // close to beginning; only hide later pages
                if ($inCurrentPage < 1 + ($this->adjacentPages * 2)) {
                    for ($counter = 1; $counter < 4 + ($this->adjacentPages * 2); $counter++) {
                        $pages[] = $counter;
                    }
                    
                    $pages[] = '...';
                    $pages[] = $inNumPages - 1;
                    $pages[] = $inNumPages;
                }
                // in middle; hide some front and some back
                else if (($inNumPages - ($this->adjacentPages * 2) > $inCurrentPage) && ($inCurrentPage > ($this->adjacentPages * 2))) {
                    $pages[] = 1;
                    $pages[] = 2;
                    $pages[] = '...';

                    for ($counter = $inCurrentPage - $this->adjacentPages; $counter <= $inCurrentPage + $this->adjacentPages; $counter++) {
                        $pages[] = $counter;
                    }

                    $pages[] = '...';
                    $pages[] = $inNumPages - 1;
                    $pages[] = $inNumPages;
                }
                // close to end; only hide early pages
                else {
                    $pages[] = 1;
                    $pages[] = 2;
                    $pages[] = '...';

                    for ($counter = $inNumPages - (2 + ($this->adjacentPages * 2)); $counter <= $inNumPages; $counter++) {
                        $pages[] = $counter;
                    }
                }
            }
        }
        
        return $pages;
    }
    
    
    public function clearSelection() {
        /* @var $r AjaxResponse */
        $r = $this->getAjaxResponse();
        if($r !== null) {
            $r->addPostScript("cricket_clear_selection('" . $this->getId() . "');");
        }
    }
    
    

    public function action_sort()
    {
        $newSort = $this->getParameter("sort");
        $multiSort = $this->getParameter("shift") == 'true';

        $this->sort->setSort($newSort, $multiSort);
        $this->setStart(0);

        $this->invalidate();
    }

    
    
    public function getPageSize() {
        return $this->pageSize;
    }
    
    public function setPageSize($inPageSize) {
        $this->pageSize = $inPageSize;
    }
    
    public function getStart() {
        return $this->start;
    }
    
    public function setStart($inStart) {
        $this->start = $inStart;
    }
    
    /** @return Table_DataSet */
    abstract protected function getDataSet($start,$pageSize,Table_Sort $inSort);
    abstract protected function renderTable($inParams);
    
    /*
     * BLZ 1/27/2012 Added getDataSetAndRenderParams so the implementing class can set parameters that
     * will eventually be passed to renderTable.  For backwards compatibility, if getDataSetAndRenderParams
     * returns false...which the default implementation does, then the original getDataSet is called instead.
     */
    protected function getDataSetAndRenderParams($start,$pageSize,Table_Sort $inSort,array &$ioRenderParams) {
        return false;
    }
    
    protected function selectItem($inID) {
        
    }
    
    public function getRecordLabel($numItems) {
        if($numItems == 1) {
            return "Item";
        }else{
            return "Items";
        }
    }
    
    
    // THIS METHOD IS DEPRECATED.   Use "widget" support now.  See comments below:
    public function renderWidget($desc,$columns) {
        $height = null;
        if(empty($this->pageSize)) {
            $height = isset($desc['height']) ? $desc['height'] : null;
        }
        
        $highlightRows = isset($desc['highlightRows']) ? $desc['highlightRows'] : false;
        $style = $height !== null ? "" : "style='overflow-x:visible;overflow-y:visible;'";
        
        
        echo "<div class='table-fixed'>";
        echo    "<div class='table-head' $style>";
        echo        "<table class='list'>";
        echo            "<tr>";
        
        foreach($columns as $col) {
            $sortBy = isset($col['sort']) ? $col['sort'] : null;
            $onClick = "";
            $sortClass = "";
            
            if($sortBy !== null) {
                $sortUrl = $this->getActionUrl("sort");
                $onClick = "onclick = 'jQuery(this).addClass(\"sort_pending\");cricket_ajax(\"" . $sortUrl . "\",{sort:\"" . $sortBy . "\"},\"ind_sort\");'";
                
                $currentSort = $this->getSort();
                $sortClass = $currentSort->column == $sortBy ? ($currentSort->desc ? "sort_desc" : "sort_asc") : "";
            }
            
            echo "<th class = '" . $sortClass . " " . $sortClass . "' width='" . $col['width'] . "' " . $onClick . ">" . $col['header'] . "</th>";
        }
        
        echo            "</tr>";
        echo        "</table>";
        echo    "</div>";
        
        
        $style = $height === null ? "overflow-x:visible;overflow-y:visible;" : "height: " . $height . "px;";
        
        echo "<div class = 'table-body' style='$style'>";
        echo    "<table class='list'>";
                
        $cricket = $this->getRequest()->getAttribute("cricket");
        $index = 0;
        foreach($desc['items'] as $item) {
            $currentID = null;
            if(is_object($item)) {
            	if(isset($item->id)) {
            		$currentID = $item->id;
            	}else{
            		if(method_exists($item, "id")) {
                    	$currentID = $item->id();
                    } else {
                    	if(method_exists($item, "getId")) {
                        	$currentID = $item->getId();
                        }
                    }
               }
            }else{
            	if(isset($item['id'])) {
                	$currentID = $item['id'];
           		}
            }
            
            if($currentID === null) {
                $currentID = $index;
            }
            
            $rowID = "row_" . $this->getId() . "_" . $currentID;
            $rowClass = "";
            if($index % 2 == 0) {
                $rowClass = "alt";
            }
            
            echo "<tr id='$rowID' class='$rowClass'>";
            foreach($columns as $col) {
                $cssClass = isset($col['cssClass']) ? $col['cssClass'] : "";
                
                $onClick = "";
                if($highlightRows) {
                    $selectRowUrl = $this->getActionUrl("select");
                    $onClick = "onclick='cricket_click_row(\"" . $this->getId() . "\",\"" . $selectRowUrl . "\",\"" . $rowID . "\",\"" . $currentID . "\")'";
                }
                
                echo "<td class='" . $cssClass . "' width='" . $col['width'] . "' " . $onClick . ">";
                $col['td']($item,$index,$cricket);
                echo "</td>";
            }
            
            echo "</tr>";
            
            $index ++;
        }
        echo    "</table>";
        echo "</div>";
        echo "</div>";
    }
    
    
    static public function contributeToHead($page) {
        $href = self::resolveResourceUrl($page, __CLASS__, 'cricket/css/tables.css');
        return "<link rel=\"stylesheet\" href=\"$href\" type=\"text/css\">";
    }
    
}


/*
 *  using this method lets you override the table.php template if you need
 * 
 *  $table = $tpl->widget("cricket/widgets/table.php", array(
    'desc' => array(
        "items" => $items,
        "height" => 200
    ),
    'columns' => array(
        array("header" => "Username","width" => "25%","sort" => "username","td" => function($item){
            echo $item['username'];
        }),
        array("header" => "Name","width" => "25%","sort" => "name","td" => function($item){
            echo $item['name'];
        }),
        array("header" => "Department","width" => "25%","sort" => "department","td" => function($item){
            echo $item['department'];
        }),
        array("header" => "Service Center","width" => "25%","sort" => "service_center","td" => function($item){
            echo $item['service_center'];
        }),
    )
));
$table->end();        
 * 
 */


/*  DEPRECATED
 * $cricket->getComponent()->renderWidget(array(
    "items" => $items,
    "height" => 300
),array(
    array("header" => "Order Batch ID","width" => "16%","sort" => OrderBatch::OB_BATCH_ID,"td" => function(OrderBatch $item,$index,$cricket){ ?>
        <a href="<?= $cricket->page_url('PageOrderdetails') ?>?id=<?= $item->getBatchID() ?>&source=<?= $cricket->getRequest()->getAttribute('currentSource') ?>" target ="_blank"><?= $item->getBatchID() ?></a>
    <?php }),
    array("header" => "Date","width" => "18%","sort" => OrderBatch::OB_BATCH_DATE,"td" => function(OrderBatch $item){
        echo $item->getBatchDate()->format('Y-m-d') . "&nbsp;&nbsp;&nbsp;" . $item->getBatchDate()->format('g:i A') . " EST";
    }),
    array("header" => "Orders","width" => "10%","cssClass" => "center","sort" => OrderBatch::OB_ORDER_COUNT,"td" => function(OrderBatch $item){
        echo $item->getOrderCount();
    }),
    array("header" => "Canceled Orders","width" => "14%","cssClass" => "center","sort" => OrderBatch::OB_CANCELED_COUNT,"td" => function(OrderBatch $item){
        echo $item->getCanceledCount();
    }),
    array("header" => "Not Yet Shipped","width" => "14%","cssClass" => "center","sort" => OrderBatch::OB_NOT_SHIPPED_COUNT,"td" => function(OrderBatch $item){
        echo $item->getNotShippedCount();
    }),
    array("header" => "Problem Orders","width" => "14%","cssClass" => "center","sort" => OrderBatch::OB_PROBLEM_COUNT,"td" => function(OrderBatch $item){
        echo $item->getProblemCount();
    }),
    array("header" => "Status","width" => "14%","sort" => OrderBatch::OB_BATCH_STATUS,"td" => function(OrderBatch $item){
        echo $item->getBatchStatus();
    })
));

 */