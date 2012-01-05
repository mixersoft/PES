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
	<div class="post rounded-5 blue">
	<?php 
	$discussion = array_merge(array('action'=>'discussion'), Configure::read('passedArgs.min'));
	$discussion['?'] = @mergeAsArray($discussion['?'], array('view'=>'threaded') );
	// show add form
	$isAddMode=1;  // always show add form
	if ($allowAddByAuth):
		if ($isAddMode && $allowAddByAuth): ?>
			<h3><?php __d('comments', 'Post a Comment'); ?><span class="comment-view right">Show: <b>most recent</b> | <a href='<?php echo Router::url($discussion) ?>'>threaded</a></span></h3>
			<?php
			echo $commentWidget->element('form', array('comment' => (!empty($comment) ? $comment : 0)));
		else:
			if (empty($this->params[$adminRoute]) && $allowAddByAuth):
				echo $commentWidget->link(__d('comments', 'Post a Comment', true), am($url, array('plugin'=>'','comment' => 0)));
			endif;
		endif;
	else: ?>
		<h3><?php __d('comments', 'Discussion'); ?></h3>
		<?php
			echo sprintf(__d('comments', 'Sign in to join the discussion.', true), $html->link(__d('comments', 'login', true), array('controller' => 'users', 'action' => 'login', 'prefix' => $adminRoute, $adminRoute => false)));
	endif;
	?>		
	</div>
	
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
	
<?php echo $this->Html->image('/comments/img/indicator.gif', array('id' => 'busy-indicator',
 'style' => 'display:none;')); ?>
</div>
<?php $this->Layout->blockStart('javascript'); ?>
	<script type="text/javascript">		
		try {
			SNAPPI.UIHelper.listeners.CommentReply();	
		} catch (e) {
			var initOnce = function(){
				SNAPPI.UIHelper.listeners.CommentReply();
			}
			PAGE.init.push(initOnce);
		}
	</script>	
<?php	$this->Layout->blockEnd(); ?>