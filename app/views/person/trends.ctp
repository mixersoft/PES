<?php 
	$this->Layout->blockStart('itemHeader');
		$badge_src = Stagehand::getSrc($data['User']['src_thumbnail'], 'sq', 'Person');
		echo $this->element('nav/section', compact('badge_src')); 
	$this->Layout->blockEnd();	
?>
<section id="tag-cloud" class="trends grid_16">
<?php	// tagCloud
	$xhrSrc = array('plugin'=>'', 'controller'=>'tags','action'=>'show');
	if (isset($this->passedArgs['perpage'])) $xhrSrc['perpage'] = $this->passedArgs['perpage'];
	$xhrFrom = Configure::read('controller.xhrFrom');
	$xhrSrc['?'] = array('xhrfrom'=>implode('~', $xhrFrom), 'gallery'=>1, 'preview'=>0);
	$xhrSrc = Router::url($xhrSrc);	
	echo "<div id='paging-tags-xhr' class='xhr-get' delay='0' xhrSrc='{$xhrSrc}'></div>";
?>	
</section>

<?php $this->Layout->blockStart('javascript'); ?> 
	<script type="text/javascript">
		var initOnce = function() {
			try {
				var Y = SNAPPI.Y;
				SNAPPI.mergeSessionData();
			} catch (e) {}
		};
		try {
			SNAPPI.xhrFetch.fetchXhr; 
			initOnce(); 
		} catch (e) {
			PAGE.init.push(initOnce); 
		}	// run from Y.on('domready') for HTTP request		
	</script>
<?php $this->Layout->blockEnd(); ?> 			