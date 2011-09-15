<?php 
//		if (!isset($cloudTags)) return;
Configure::write('debug',1);
	$this->Paginator->options['url']['plugin']='';
	$paginateModel = Configure::read('paginate.Model');		
	
	$state = (array)Configure::read('state');
	$state['displayPage'] = array_filter_keys($this->params['paging']['Tagged'], array('page', 'count', 'pageCount', 'current'));
	$state['displayPage']['perpage'] = $this->params['paging']['Tagged']['options']['limit']; 
	if (!isset($isPreview))  {
		$isPreview = isset($this->params['url']['preview']) ? $this->params['url']['preview'] : 1;  // default for tagCloud == preview
	}
	$total = $state['displayPage']['count'];
?>

<ul class='inline tag-roll-header'>
<?php if (empty($isPreview)  && Configure::read("paginate.Options.{$paginateModel}.context")!='skip' ) { echo "<li>{$this->element('context')}</li>"; }?>
</ul>

<ul class='inline'>
<?php 
	if (isset($cloudTags)) { 
		echo $this->TagCloud->display($cloudTags, array(
			'url' => array('plugin'=>'','controller' => 'tags', 'action'=>'home'),
			'before' => '<li><span style="font-size: %size%pt" class="tag">',
			'after' => '</span></li>',
			'minSize' => '8',
			'maxSize' => '16',
			'shuffle' => 0
		));
	}
?>
</ul>	
<p>Total of <?php echo $total; ?> Tags</p>			


<?php  		
	$xhrFrom = Configure::read('controller.xhrFrom');
	if (!$isPreview) {
		// setup aui_paginator
?>
		<script type="text/javascript">
			var initOnce = function() {
				//TODO: init aui_paginator 
			};
			try {SNAPPI.ajax; initOnce(); }			// run now for XHR request, or
			catch (e) {PAGE.init.push(initOnce); }	// run from Y.on('domready') for HTTP request
		</script>	
<?php
	} else {
		if (isset($state['displayPage']['pageCount']) && $state['displayPage']['pageCount'] > 1) {
			$controllerAlias = Configure::read("lookup.xfr.{$xhrFrom['keyName']}.ControllerAlias");
			$next = $this->passedArgs+array('controller'=>$controllerAlias,'action'=>'trends', $xhrFrom['uuid']);
			echo $this->Html->link('more...', $next);
		}
	}
?>
