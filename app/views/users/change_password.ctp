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
<div class="plain-form prefix_2 grid_12 suffix_2">
<h2><?php __d('users', 'Change your password'); ?></h2>
<p>
</p>
<?php
	$orange = array('class'=>'orange'); 
	echo $this->Form->create($model, array('action' => 'change_password'));
	echo $this->Form->input('old_password', array(
		'label' => __d('users', 'Old Password', true),
		'type' => 'password'));
	echo "<br />";
	echo $this->Form->input('new_password', array(
		'label' => __d('users', 'New Password', true),
		'type' => 'password'));
	echo $this->Form->input('confirm_password', array(
		'label' => __d('users', 'Password (confirm)', true),
		'type' => 'password'));
	echo $this->Form->submit(__d('users', 'Submit', true), $orange);
	echo $this->Form->end();
?>
</div>