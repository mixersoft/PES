<?php 
/*
 *  * WORKORDER VIEW (from: /elements/photos/roll)
 */ 
echo $this->Html->css('workorder/workorder');

$badge_src = Stagehand::getSrc($data['User']['src_thumbnail'], 'sq', 'Person');
$ownerCount = $jsonData['castingCall']['CastingCall']['Auditions']['Total'];
echo $this->element('/workorder/event/gallery', compact('badge_src', 'ownerCount') );
?>

<?php  $this->Layout->blockStart('markup');
		if (Configure::read('controller.alias') == 'my') {	?>
			<div class='empty-photo-gallery-message hide'><div class=' message blue rounded-5 wrap-v'>
				<h1>Event Gallery</h1>
				<p>This is where you see your Shots.</p>
			</div></div>
	<?php } else if ( Configure::read('controller.alias') == 'person' && $data['User']['asset_count'] >0 ) { ?>
			<div class='empty-photo-gallery-message hide'><div class='message blue rounded-5 wrap-v'>
				<h1>Event Gallery</h1>
				<p>You are not connected with this Person.</p>
				<p>You can connect with other members by joining the same Circle. Send your friends an invitation to join your Circles.</p>
			</div></div>
	<?php } ?>			
			<div class='empty-lightbox-gallery-message hide'><div class=' message blue rounded-5 wrap-v'>
				<p>Drag selected Snaps from above into the Lightbox. 
					Use <span class='keypress Ctrl-Cmd'>Ctrl-Click</span> or <span class='keypress'>Shift-Click</span> to select multiple Snaps.
				</p>
			</div></div>
<?php 	$this->Layout->blockEnd(); ?>	