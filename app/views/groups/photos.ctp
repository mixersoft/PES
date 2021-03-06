<div class="groups photos">
	<?php 
		// $photostreams = $this->Html->link('Photostream', $this->passedArgs + array('action'=>'photostreams'));
		// echo "<p>Show as {$photostreams}</p>\n";
		$badge_src = Stagehand::getSrc($data['Group']['src_thumbnail'], 'sq', $data['Group']['type']);
		$ownerCount = $data['Group']['assets_group_count'];
		echo $this->element('/photo/roll', compact('badge_src', 'ownerCount'));
	?>
</div>
<?php	// tagCloud
	$xhrSrc = array('plugin'=>'', 'controller'=>'tags','action'=>'show', 'filter'=>'Asset');
	$xhrFrom = Configure::read('controller.xhrFrom');
	$xhrSrc['?'] = array('xhrfrom'=>implode('~', $xhrFrom),'preview'=>1);
	$xhrSrc = Router::url($xhrSrc);
	echo "<div id='tags-preview-xhr' class='xxhr-get' xhrSrc='{$xhrSrc}' delay='8000'></div>";
?>	
<?php  if ( $data['Group']['assets_group_count']>0 ) {
		$this->Layout->blockStart('markup'); 		?>
			<div class='empty-photo-gallery-message hide'><div class='message blue rounded-5 wrap-v'>
				<h1>Snap Gallery</h1>
				<p>You must be a member of this Circle to see these Snaps from this Group.
				Join this Circle to share Snaps, Stories, and more with other members.</p>
				<ul class='inline' ><li class='btn orange rounded-5'><a href='/groups/join/<?php echo AppController::$uuid; ?>'>Join now<a></li></ul>
			</div></div>
<?php 	$this->Layout->blockEnd(); } ?>	
<script type="text/javascript">
var initOnce = function() {
	// init xhr paging & fetch xhr-gets
	// NOTE: any xhr-gets will bind own PAGE.init() method
	SNAPPI.xhrFetch.init(); 
};
try {SNAPPI.xhrFetch.fetchXhr; initOnce(); }			// run now for XHR request, or
catch (e) {PAGE.init.push(initOnce); }	// run from Y.on('domready') for HTTP request
</script>
