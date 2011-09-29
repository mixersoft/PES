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
	$User = $comment[$userModel];
	$DEFAULT_SRC_ICON = Configure::read('path.blank_user_photo');
	$THUMBSIZE = 'sq';
	$SHORT = 12;
	$options = array();
	$fields = array();
	$ownerUrl = Router::url(array('plugin'=>'','controller'=>'person', 'action'=>'home', $User['id']));
	$fields['owner'] = $User['username'];
	$fields['trim_owner'] = $this->Text->truncate($User['username'], $SHORT-4);	
	$fields['src_icon'] =  $DEFAULT_SRC_ICON;
	$fields['src_icon'] =  $User['src_thumbnail'] ? Session::read('stagepath_baseurl').getImageSrcBySize($User['src_thumbnail'], $THUMBSIZE) : $DEFAULT_SRC_ICON;
	$fields['ownerLink'] = $this->Html->link($fields['trim_owner'], $ownerUrl, $options );
	$fields['new'] = ($this->Time->wasWithinLast('3 day', $comment['Comment']['created'])) ? "<span class='new'>New! </span>" : '';
	// debug($User);
	
	$person_snaps['label'] = String::insert(":asset_count Snaps", $User); 
	// $person_snaps['href'] = Router::url($options+array('action'=>'photos'));
	$person_memberships['label'] = String::insert(":groups_user_count Circles", $User); 
	// $person_memberships['href'] =  Router::url($options+array('action'=>'groups'));
	$fields['last_login'] = "last visit: {$this->Time->timeAgoInWords($User['last_login'])}";
	$fields['owner_tooltip'] = "{$person_snaps['label']}, {$person_memberships['label']}, {$fields['last_login']}";
	/**
	 * below is the list of comments with username, comment title,commentbody, posted time 
	 */
?>
<div class="comments">
	<article class="FigureBox Person <?php  echo $THUMBSIZE; ?>" id='<?php echo $User['id'] ?>'>
       	<figure>
	<?php $options = array('url'=>$ownerUrl, 'title'=>$fields['owner_tooltip']); 
	     if (isset($fields['title'])) $options['title'] = $fields['title'];
	     echo $this->Html->image($fields['src_icon'] , $options);  
	?>
    		<figcaption>
    		<div class="label">
    			<?php echo String::insert(":new", $fields); ?>
    			<span class="user"><?php echo $fields['ownerLink'] ?> says:</span>
    			<span class='right'> &nbsp;<?php echo join('&nbsp;', $_actionLinks);?></span>
    		</div>
	<div class='item'>
		<div class="title"><!--<a name="comment<?php echo $comment['Comment']['id'];?>">--><?php echo $comment['Comment']['title'];?><!--</a>--></div>
		<div class="body"><?php echo $cleaner->bbcode2js($comment['Comment']['body']);?></div>
		<div class="instruction">
			<span ><?php __d('comments', 'Posted'); ?>&nbsp; <?php echo $time->timeAgoInWords($comment['Comment']['created']); ?></span>
		</div>
	</div>	    		
    		<ul class="inline extras hide">
    		 	<li class="snaps"><a></a></li>
    			<li class="circles last"><a></a></li>
			</ul></figcaption>
		</figure>
	</article>
</div>