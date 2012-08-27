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
		debug('DEBUG: WHAT IS THIS CODE PATH (COMMENTS PLUGIN)');		
		$pager->options(array_merge(
			array('url' => $commentWidget->prepareUrl($url)),
			$commentWidget->globalParams['ajaxOptions']));
	} else {
		if($this->action=='fragment') 	$url['action']=$this->passedArgs['a'];	// 
		if(isset($this->passedArgs['f'])) 	$url['f'] = $this->passedArgs['f'];
		$pager->options(array('url' => $url));
	}
	$paging = $pager->params('Comment');
//	debug($paging);
	
?>

<?php if (!empty(${$viewComments})): ?>
	<div class="paging-control paging-numbers">
		<?php 
//		$this->Paginator->options(array('model'=>'Comment'));
		echo $pager->prev(' '.__('«', true), array('model'=>'Comment'), null, array('class'=>'disabled'));?>
	  	<?php echo $pager->numbers(array('model'=>'Comment', 'separator'=>null, 'modulus'=>'20'));?>
		<?php echo $pager->next( __('»', true).' ', array('model'=>'Comment'), null, array('class'=>'disabled'));?>
	</div>
<?php endif; ?>
