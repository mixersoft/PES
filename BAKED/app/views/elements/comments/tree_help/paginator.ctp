<?php
/**
 * Copyright 2009 - 2010, Cake Development Corporation
 *                        1785 E. Sahara Avenue, Suite 490-423
 *                        Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 */
?>
<?php
	$pager = $this->Paginator;
	if ($commentWidget->globalParams['target']) {
		$pager->options(array_merge(
			array('url' => $commentWidget->prepareUrl($url)),
			$commentWidget->globalParams['ajaxOptions']));
	} else {
		$pager->options(array('url' => $url));
	}
	$paging = $pager->params('Comment');
?>

<?php if (!empty(${$viewComments})): ?>
	<div class="paging-control paging-numbers">
		<?php echo $pager->prev('<< ', array(), null, array('class'=>'disabled'));?>
	  	<?php echo $pager->numbers(array('separator'=>null, 'modulus'=>'20'));?>
		<?php echo $pager->next(' >>', array(), null, array('class'=>'disabled'));?>
	</div>
<?php endif; ?>
