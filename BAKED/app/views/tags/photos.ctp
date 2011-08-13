
<script type="text/javascript">
var initOnce = function() {
	SNAPPI.ajax.init(); 
};
PAGE.init.push(initOnce);
</script>
<?php 
	echo $this->element('nav/section');
?>
<div class="tags photos">
	<h2><?php 
		$link2Tag = $this->Html->link($data['Tag']['name'], array('action'=>'home', $data['Tag']['keyname']));  
		echo "Photos for Tag {$link2Tag}";?>
	</h2>
	<p>Show as <?php echo $this->Html->link('Photostream', $this->passedArgs + array('action'=>'photostreams') );?>
	</p>	
	<div id='paging-photos' class='paging-content' xhrTarget='paging-photos-inner'>
		<?php echo $this->element('/photo/roll');?>
	</div>
	<?php
		$xhrSrc = array('plugin'=>'', 'controller'=>'tags','action'=>'show', 'filter'=>'Asset');
		$xhrFrom = Configure::read('controller.xhrFrom');
		$xhrSrc['?'] = array('xhrfrom'=>implode('~', $xhrFrom));
		$ajaxSrc = Router::url($xhrSrc);	
		echo "<div id='tags-preview-xhr' class='fragment' ajaxSrc='{$ajaxSrc}'></div>";
	?>		
</div>