<p>You are about to share the link: <?php echo $link; ?></p>
<?php
$urlForm = array(
	'controller' => 'share_links', 'action' => 'form_create', $targetId,
	$ownerId, $securityHash, '?link=' . urlencode($link)
);
echo $this->Form->create('ShareLink', array('url' => $urlForm));
echo $this->Form->input('ShareLink.security_level', array(
	'label' => 'Security level',
	'type' => 'radio',
	'options' => array('1' => 'none', '2' => 'password protected'),
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