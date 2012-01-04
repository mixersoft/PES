<?php
/**
 * Copyright 2010, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
?>
<div class="prefix_2 grid_12 suffix_2">
<h2><?php __d('users', 'Resend email verify link?'); ?></h2>
<p>
	<?php __d('users', 'Please enter your Snaphappi email address. We\'ll send  you a new email verification message.'); ?>
</p>
<?php
	$orange = array('class'=>'orange'); 
	echo $this->Form->create($model, array(
		'url' => array(
			'admin' => false,
			'action' => 'resend_email_verify')));
	echo $this->Form->input('email', array(
		'label' => __d('users', 'Your Email', true)));
	echo $this->Form->submit(__d('users', 'Submit', true), $orange);
	echo $this->Form->end();
?>
</div>