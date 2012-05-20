<?php 
$badge_src = Stagehand::getSrc($data['User']['src_thumbnail'], 'sq', 'Person');
$ownerCount = $jsonData['castingCall']['CastingCall']['Auditions']['Total'];
echo $this->element('/workorder/snap/gallery', compact('badge_src', 'ownerCount') );
?>

<?php  $this->Layout->blockStart('markup');
		if (Configure::read('controller.alias') == 'my') {	?>
			<div class='empty-photo-gallery-message hide'><div class=' message blue rounded-5 wrap-v'>
				<h1>Snap Gallery</h1>
				<p>This is where you find your Snaps (i.e. the photos you have uploaded).</p>
				<p>For best results, we recommend you download and install our Desktop Uploader to quickly upload <b>all your photos</b> - even if you have 1000s. 
					Once uploaded, you can return here to organize and share your Snaps.  
					Or better yet, you can (someday soon) ask us to do it for you.</p>
				<ul class='inline' ><li class='btn orange rounded-5'><a href='/my/upload'>Get started now.<a></li></ul>
			</div></div>
	<?php } else if ( Configure::read('controller.alias') == 'person' && $data['User']['asset_count'] >0 ) { ?>
			<div class='empty-photo-gallery-message hide'><div class='message blue rounded-5 wrap-v'>
				<h1>Snap Gallery</h1>
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