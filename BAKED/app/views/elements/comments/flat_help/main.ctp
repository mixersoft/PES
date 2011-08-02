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
<div class="comments-main">
<div>
	<?php	
		// show paging
		echo $commentWidget->element('paginator');
		// show comments
		foreach (${$viewComments} as $comment):
			echo $commentWidget->element('item', array('comment' => $comment));
		endforeach;
		$comment = null;
	?>
</div>

<?php 
	// show add form
	$isAddMode=1;  // always show add form
	if ($allowAddByAuth):
		if ($isAddMode && $allowAddByAuth): ?>
			<h3><?php __d('comments', 'Add your comment'); ?></h3>
			<?php
			echo $commentWidget->element('form', array('comment' => (!empty($comment) ? $comment : 0)));
		else:
			if (empty($this->params[$adminRoute]) && $allowAddByAuth):
				echo $commentWidget->link(__d('comments', 'Add comment', true), am($url, array('plugin'=>'','comment' => 0)));
			endif;
		endif;
	else: ?>
		<h3><?php __d('comments', 'Comments'); ?></h3>
		<?php
			echo sprintf(__d('comments', 'If you want to post comments, you need to login first.', true), $html->link(__d('comments', 'login', true), array('controller' => 'users', 'action' => 'login', 'prefix' => $adminRoute, $adminRoute => false)));
	endif;
?>


<?php echo $this->Html->image('/comments/img/indicator.gif', array('id' => 'busy-indicator',
 'style' => 'display:none;')); ?>
</div>