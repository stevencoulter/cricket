<?php
/* @var $cricket cricket\core\CricketContext */

use cricket\core\Page;
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Example</title>
        <style type="text/css">
            body {
                font-family: sans-serif;
                padding: 30px;
            }
        </style>
        <!-- jQuery is required for Cricket's ajax handling -->
        <script type="text/javascript" src="<?= $cricket->resource_url("/resources/jquery-1.10.2.js") ?>"></script>
        <?= $cricket->head() ?>
    </head>
    <body>
        <div style='width:600px'>
            <h1>Basic Asynchronous Cricket Example</h1>
			<p><?= $number*2 ?> instances of the same component are shown.  The component reads and increments a counter stored in the session, and then calls an action to stall for a certain period of time.  The component is then invalidated and the counter is again read and incremented.</p>
        </div>
        
        <b>Session mode:</b>
        <table width='600px'>
        	<tr>
        		<td width='33%' style='text-align:center'>
        			<?php $colored = ($mode == Page::MODE_RELOAD) ? " style='background-color:green;' " : '';?>
        			<a href="<?= $cricket->page_url("PageAsync")?>"><button <?= $colored ?>>MODE_RELOAD</button></a>
        		</td>
        		<td width='33%' style='text-align:center'>
        		    <?php $colored = ($mode == Page::MODE_PRESERVE) ? " style='background-color:green;' " : '';?>
        			<a href="<?= $cricket->page_url("PageAsyncp")?>"><button <?= $colored ?>>MODE_PRESERVE</button></a>
        		</td>
          		<td width='33%' style='text-align:center'>
          		    <?php $colored = ($mode == Page::MODE_STATELESS) ? " style='background-color:green;' " : '';?>
          			<a href="<?= $cricket->page_url("PageAsyncs")?>"><button <?= $colored ?>>MODE_STATELESS</button></a>
          		</td>
        	</tr>
        </table>
        <br>
        
        <div style='width:600px'>
	        <b>Asychronous calls.</b>  
	        <p>
		        <?php 
		        	switch($mode) {
						case Page::MODE_RELOAD:
							$max = 2*$number;
							echo "These should all complete at the same time.  Upon invalidation they will show the latest count stored in the session, {$max}.  You may be limited by the number of calls that your browser allows.";
							break; 
						case Page::MODE_PRESERVE:
							$double = 2*$number;
							echo "On every reload these components will invalidate, always using the latest count stored in the session.  It will increment by {$double} each time due to the rendering of all components.";
							break;
						case Page::MODE_STATELESS:
							echo "All calls on this page, including blocking calls, should finish at the same time.  Again, you may be limited by the number calls that your browser allows.  This page is entierly stateless, so all invalidations should report 0.";
							break;
					};
		        ?>
	        </p>
        </div>
        <?php for ($x=0;$x<$number;$x++): ?>
        	<div style='float:left;padding-left:10px;text-align:center'>
        		<?php $cricket->component("async_{$x}") ?>
        		<?= $x ?>
        	</div>
        <?php endfor;?>
		<div style='clear:left'></div>
		<br>
		
		<div style='width:600px'>
			<b>Blocking calls.</b>
			<p>
				<?php 
		        	switch($mode) {
						case Page::MODE_RELOAD:
							$min = 2*$number;
							$max = 3*$number-1;
							echo "These will finish one after the other, but not necessarily in order.  The counts after invalidation will range between {$min} and {$max}.";
							break; 
						case Page::MODE_PRESERVE:
							$double = 2*$number;
							echo "The initial load of this page will be exactly as MODE_RELOAD.  These components will save their final state, but upon reload will increment by {$double} due to the rendering of the all components";
							break;
						case Page::MODE_STATELESS:
							echo "All calls on this page should finish at the same time.  Again, you may be limited by the number calls that your browser allows.  This page is entierly stateless, so all invalidations should report 0.";
							break;
					};
		        ?>
			</p>
		</div>
        <?php for ($x=0;$x<$number;$x++): ?>
        	<div style='float:left;padding-left:10px;text-align:center'>
        		<?php $cricket->component("sync_{$x}") ?>
        		<?= 10+$x ?>
        	</div>
        <?php endfor;?>
		<br>
		
        </body>
</html>