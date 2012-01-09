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
	$_url = array_merge($url, array('plugin'=>'','action' => r(Configure::read('Routing.admin') . '_', '', $this->action)));
	foreach (array('page', 'order', 'sort', 'direction', 'loc') as $named) {
		if (isset($this->passedArgs[$named])) {
			$_url[$named] = $this->passedArgs[$named];
		}
	}
	if ($target) {
		$_url['action'] = r(Configure::read('Routing.admin') . '_', '', 'comments');
		$ajaxUrl = $commentWidget->prepareUrl(array_merge($_url, array('plugin'=>'','comment' => $comment, '#' => 'comment' . $comment)));
		echo $form->create(null, array('url' => $ajaxUrl, 'target' => $target));
	} else {
		echo $form->create(null, array('url' => array_merge($_url, array('comment' => $comment, '#' => 'comment' . $comment))));
	}
	?>
	<div class="input text">
		<label for="CommentTitle" onclick="this.className='hide';">Title</label>
		<input class='' type="text" id="CommentTitle" maxlength="255" name="data[Comment][title]">	
	</div>
	<?php
	echo $form->input('Comment.body', array(
		'label' => '',
		'type' => 'textarea',
	    'error' => array(
	        'body_required' => __d('comments', 'This field cannot be left blank',true),
	        'body_markup' => sprintf(__d('comments', 'You can use only headings from %s to %s' ,true), 4, 7))));
	// Bots will very likely fill this fields
	echo $form->input('Other.title', array('type' => 'hidden'));
	echo $form->input('Other.comment', array('type' => 'hidden'));
	echo $form->input('Other.submit', array('type' => 'hidden'));
	$orange = array('class'=>'orange');
	if ($target) {
		echo $js->submit(__('Submit', true), array_merge(array('url' => $ajaxUrl), $commentWidget->globalParams['ajaxOptions'], $orange));
	} else {
		echo $form->submit(__('Submit', true), $orange);
	}
	echo $form->end();
?>
