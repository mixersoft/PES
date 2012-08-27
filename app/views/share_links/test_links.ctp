<?php
//example of implementation of sharelink from an external app


function urlFormShare($targetId, $ownerId, $link) {
	$securityHash = Security::hash($targetId . $ownerId . $link);
	return Router::url(array(
		'action'=>'form_create',
		$targetId, $ownerId, $securityHash,
		'?'=> compact('link')),
		true
	);
}

$target_url = Router::url("/stories/story/{$TARGET_UUID}", true);
$create_url = urlFormShare($TARGET_UUID, $OWNER_UUID, $target_url);

?>

<p>Target page: <?php echo $this->Html->link($target_url, null, array('target'=>'_blank')); ?></p>
<br />
<button onclick='window.location.href="<?php echo $create_url; ?>"'			>
	click here to create share link
</button>	
<br /><br />

<h1>Lookup Share Link</h1>
<?php 
	$url = Router::url(array(
		'action'=>'find', 
		'target_id'=>$TARGET_UUID
	));
	echo $this->Html->link($url, null, array('target'=>'_blank'));
?>
