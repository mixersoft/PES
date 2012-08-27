<h2>Please enter the password for this share link</h2>
<?php
	echo $this->Form->create(array(
		'url'=>'/share_links/password'
	));
	echo $this->Form->hidden('ShareLink.id',array(
		'value'=>$sharelink_id
	));
	echo $this->Form->input('ShareLink.password');
	echo $this->Form->submit();
?>