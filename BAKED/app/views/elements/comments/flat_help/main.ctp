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
	<div class="post rounded-5 green">
	<?php 
	$set_view = $this->passedArgs;
	$set_view['?'] = array('view'=>'tree');
	// show add form
	?>
			<h3><?php __d('comments', 'Post a Question, Comment or Suggestion for Topic:'); ?>
				<span class='page'><?php echo "/{$help_page['alias']}/{$help_page['action']}"; ?> </span>
				<ul class='inline right'><li class="btn orange show-all-tips">Show All Tips</li></ul>
			</h3>
			<?php
			echo $commentWidget->element('form', array('comment' => (!empty($comment) ? $comment : 0)));
	?>		
	</div>

	<div>	
		<span class="comment-view right">Showing: <b>most recent</b> | <a href='<?php echo Router::url($set_view) ?>'>threaded</a></span>
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
			var selector = 'section.help';
			SNAPPI.UIHelper.listeners.CommentReply(selector);	
		} catch (e) {
			var initOnce = function(){
				SNAPPI.UIHelper.listeners.CommentReply(selector);
			}
			PAGE.init.push(initOnce);
		}
	</script>	
<?php $this->Layout->blockEnd(); ?>