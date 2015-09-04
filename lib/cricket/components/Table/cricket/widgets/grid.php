<?php
    $thisGrid = $cricket->getComponent();

    $height = $thisGrid->scrollingHeight;
    if(empty($thisGrid->pageSize) && $height === null) {
        $height = isset($desc['height']) ? $desc['height'] : null;
    }
	
$showSort = isset($desc['showSort']) ? $desc['showSort'] : true;

$showSearch = isset($desc['showSearch']) ? $desc['showSearch'] : false;

$selected_item = $itemSort->column;
$selected_direction = $itemSort->desc;

$td_width = round((100/$numColumns),2)."%";

$grid_indicator = 'grid_sort_indicator'; 

?>

<?php if ($showSort): ?>

	<div style="width: 100%;">
		<div style="float:left;padding-left:10px;">
			<?php if ($showSearch): ?>
		        <b>Search</b></br>
		        
		        
		        <select id="grid_search">
		         <? foreach($search_options as $id=>$value){
		                echo "<option value = $id ";
		
		            if($id == $selected_direction)
		                echo "selected= 'selected'";
		
		            echo  ">".$value."</option>";
		            }?>   
		        </select>
		        <input type='text' id='grid_search_character' onkeyup="if (event.keyCode==13) {
		        	 cricket_ajax('<?= $cricket->component->getActionUrl('search')?>',{'field':jQuery('#grid_search').val(),'value':jQuery('#grid_search_character').val()}, 'ind_grid_search');
			        }"></input>
			        &nbsp;
			        <button onclick="cricket_ajax('<?= $cricket->component->getActionUrl('search')?>',{'field':jQuery('#grid_search').val(),'value':''}, 'ind_grid_search');">
			        	Clear
			        </button>
		        <?= $cricket->indicator('ind_grid_search'); ?>
		        
			<?php endif;?>
		</div>
		
	
	    <div style="float:right; padding-right: 5px;"><?= $cricket->indicator($grid_indicator); ?></div>
	    
	    <div style="float:right; padding-right: 5px;">
	        <? $sort_direction = array( 0 => "Lowest to Highest", 1 => "Highest to Lowest"); ?>
	        <b>Sort Direction</b></br>
	        <select id="grid_sort_order" onchange="cricket_ajax('<?= $cricket->component->getActionUrl('sort')?>',{'column':jQuery('#grid_by_column_sort').val(),'direction':jQuery('#grid_sort_order').val()}, '<?= $grid_indicator ?>');">
	         <? foreach($sort_direction as $id=>$value){
	                echo "<option value = $id ";
	
	            if($id == $selected_direction)
	                echo "selected= 'selected'";
	
	            echo  ">".$value."</option>";
	            }?>   
	        </select>
	    </div>
	    <div style="float: right; padding-right: 5px;">
	        <b>Sort By</b></br>
	        <select id="grid_by_column_sort" onchange="cricket_ajax('<?= $cricket->component->getActionUrl('sort')?>',{'column':jQuery('#grid_by_column_sort').val(),'direction':jQuery('#grid_sort_order').val()}, '<?= $grid_indicator ?>');">
	            <? foreach($sort_options as $id=>$value){
	                echo "<option value = $id ";
	
	            if($id == $selected_item)
	                echo "selected= 'selected'";
	
	            echo  ">".$value."</option>";
	            }?>
	        </select>  
	    </div>
	
	</div>

<?php endif; ?>

<div style="clear: both"></div>
    
<div style="width: 100%;">
    <table style="width: 100%;">
        <tr>
            
            <? $counter = 0;?>
                  
            <? foreach ($desc['items'] as $item) {
                    if (!($counter % $numColumns))
                        echo "</tr><tr>";
               echo '<td style="width:' . $td_width . ';vertical-align:top">';

               $displayItem($item);

               echo "</td>";

            $counter ++; 
            } ?>
        </tr>
    </table>
</div>

<?php
    if(!empty($thisGrid->pageSize)) {
        /* @var $r RequestContext */
        $r = $cricket->getRequest();
        $pageTotal = $r->getAttribute("pageTotal");
        $pageStart = $r->getAttribute("pageStart");
        $pageEnd = $r->getAttribute("pageEnd");
        $pageNext = $r->getAttribute("pageNext");
        $pagePrev = $r->getAttribute("pagePrev");
        $pageSize = $r->getAttribute("pageSize");
        $pageCount = $r->getAttribute("pageCount");
        $pageCurrent = $r->getAttribute("pageCurrent");
        $pages = $r->getAttribute("pages");
        $adjacents = $r->getAttribute("adjacents");
        
        $pager = $tpl->widget("cricket/widgets/pagination.php",array(
            'page_prev' => $pagePrev,
            'page_next' => $pageNext,
            'page_start' => $pageStart,
            'page_end' => $pageEnd,
            'page_total' => $pageTotal,
            'page_size' => $pageSize,
            'page_count' => $pageCount,
            'page_current' => $pageCurrent,
            'pages' => $pages,
            'adjacents' => $adjacents,
            'page_record_name' => $thisGrid->getRecordLabel($pageTotal),
        ));
        $pager->end();
    }
?>


