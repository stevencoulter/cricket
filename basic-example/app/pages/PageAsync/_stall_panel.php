<?php
/* @var $cricket cricket\core\CricketContext */
/* @var $count int */
?>

<?php $color = ($finished) ? 'green' : 'red'; ?>

<div style="background-color:<?= $color ?>;width:40px;height:40px;margin:5px;text-align:center;">
	<br><?= $count ?>
</div>

<?php if (!$finished): ?>
	<script>
		<?php if ($sync): ?>
			<?= $cricket->call('stall');?>
		<?php else: ?>
			<?= $cricket->call_async('stall');?>
		<?php endif;?>
	</script>
<?php endif; ?>