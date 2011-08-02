<?php 
	echo $this->element('nav/section', array('icon_src'=>$data['Group']['src_thumbnail']));
?>
<div class="groups photos">
	<p>Show as <?php echo $this->Html->link('Photostream', $this->passedArgs + array('action'=>'photostreams'))?>
	</p>
	<div id='paging-photos' class='paging-content' xhrTarget='paging-photos-inner'>
		<?php echo $this->element('/photo/paging-photos');?>
	</div>
</div>
<?php	// tagCloud
	$xhrSrc = array('plugin'=>'', 'controller'=>'tags','action'=>'show', 'filter'=>'Asset');
	$xhrFrom = Configure::read('controller.xhrFrom');
	$xhrSrc['?'] = array('xhrfrom'=>implode('~', $xhrFrom));
	$ajaxSrc = Router::url($xhrSrc);
	echo "<div id='tags-preview-xhr' class='fragment' ajaxSrc='{$ajaxSrc}'></div>";
?>	
<script type="text/javascript">
var initOnce = function() {
	// init xhr paging & fetch fragments
	// NOTE: any fragments will bind own PAGE.init() method
	SNAPPI.ajax.init(); 
};
try {SNAPPI.ajax; initOnce(); }			// run now for XHR request, or
catch (e) {PAGE.init.push(initOnce); }	// run from Y.on('domready') for HTTP request
</script>
