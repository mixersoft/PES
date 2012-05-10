<h1>Create Share Link</h1>
<p>You are about to share the link: <?php echo $this->Html->link($link, null, array('target'=>'_blank')); ?></p>
<?php
$urlForm = array(
	'controller' => 'share_links', 'action' => 'form_create', $targetId,
	$ownerId, $securityHash, '?link=' . urlencode($link)
);
echo $this->Form->create('ShareLink', array('url' => $urlForm));
echo $this->Form->input('ShareLink.security_level', array(
	'label' => 'Security level',
	'type' => 'radio',
	'options' => array('1' => 'none', '2' => 'simple password', '3' => 'sign-on required'),
));
echo $this->Form->input('ShareLink.hashed_password', array('type' => 'text'));
echo $this->Form->input('ShareLink.add_expiration', array('type' => 'checkbox'));
?>
<div id="ShareLinkExpirationFields">
<?php
echo $this->Form->input('ShareLink.expiration_date', array('empty' => '-', 'timeFormat' => '24'));
echo $this->Form->input('ShareLink.expiration_count');
?>
</div>
<?php
echo $this->Form->submit('Create');
?>

<h1>Lookup Share Link</h1>
<?php 
	$url = Router::url(array(
		'action'=>'find', 
		'target_id'=>$targetId
	));
	echo $this->Html->link($url, null, array('target'=>'_blank'));
?>
