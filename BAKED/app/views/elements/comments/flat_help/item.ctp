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
	$_actionLinks = array();
	if (!empty($displayUrlToComment)) {
		$_actionLinks[] = sprintf('<a href="%s">%s</a>', $urlToComment . '/' . $comment['Comment']['slug'], __d('comments', 'View', true));
	}

	if (!empty($isAuthorized)) {
		$_actionLinks[] = $commentWidget->link(__d('comments', 'Reply', true), array_merge($url, array('plugin'=>'','comment' => $comment['Comment']['id'], '#' => 'comment' . $comment['Comment']['id'])));
		if (!empty($isAdmin)) {
			if (empty($comment['Comment']['approved'])) {
				$_actionLinks[] = $commentWidget->link(__d('comments', 'Publish', true), array_merge($url, array('comment' => $comment['Comment']['id'], 'comment_action' => 'toggleApprove', '#' => 'comment' . $comment['id'])));
			} else {
				$_actionLinks[] = $commentWidget->link(__d('comments', 'Unpublish', true), array_merge($url, array('comment' => $comment['Comment']['id'], 'comment_action' => 'toggleApprove', '#' => 'comment' . $comment['Comment']['id'])));
			}
		}
	}

	$_userLink = $comment[$userModel]['username'];
	
	$DEFAULT_SRC_ICON = Configure::read('path.blank_user_photo');
	$SHORT = 12;
	$options = array();
	$fields = array();
	$fields['owner'] = $comment[$userModel]['username'];
	$fields['trim_owner'] = $this->Text->truncate($comment[$userModel]['username'], $SHORT-4);	
	$fields['src_icon'] =  $DEFAULT_SRC_ICON;
	$fields['ownerLink'] = $this->Html->link($fields['trim_owner'], array('plugin'=>'','controller'=>'users', 'action'=>'home', $comment[$userModel]['id']), $options );
	$fields['new'] = ($this->Time->wasWithinLast('3 day', $comment['Comment']['created'])) ? "<span class='new'>New! </span>" : '';
	//print_r($comment[$userModel]);
	
	/**
	 * below is the list of comments with username, comment title,commentbody, posted time 
	 */
?>
<div class="comments">
	 <li class='member-label thumbnail sq' id='<?php echo $comment[$userModel]['id'] ?>'>
	   <div class='thumb'>
	    <?php $options = array('url'=>array_merge(array('plugin'=>'','controller'=>'users', 'action'=>'home', $comment[$userModel]['id']))); 
	     if (isset($fields['title'])) $options['title'] = $fields['title'];
	     echo $this->Html->image($fields['src_icon'] , $options) ?>
	   </div>
	   <div class='thumb-label'>
	    <?php echo String::insert(":new :ownerLink", $fields); ?>
	   </div>
	  </li>
	
	<div class='item'>
		<span class="user"><?php echo $fields['ownerLink'] ?> says:</span>
		<div class="title"><!--<a name="comment<?php echo $comment['Comment']['id'];?>">--><?php echo $comment['Comment']['title'];?><!--</a>--></div>
		<div class="body"><?php echo $cleaner->bbcode2js($comment['Comment']['body']);?></div>
		<div class="instruction">
			<span style='float:right'> &nbsp;<?php echo join('&nbsp;', $_actionLinks);?></span>
			<span ><?php __d('comments', 'Posted'); ?>&nbsp; <?php echo $time->timeAgoInWords($comment['Comment']['created']); ?></span>
		</div>

	</div>
</div>