<?php 

	// NOTE: tagClouds are always served up via XHR

Configure::write('debug',1);
	$paginateModel = Configure::read('paginate.Model');		
	$passedArgs = Configure::read('passedArgs.min');
	$state = (array)Configure::read('state');
	$state['displayPage'] = array_filter_keys($this->params['paging']['Tagged'], array('page', 'count', 'pageCount', 'current'));
	$state['displayPage']['perpage'] = $this->params['paging']['Tagged']['options']['limit']; 
	$total = $state['displayPage']['count'] + 0;	// as int
	$state['displayPage']['total'] = $total;	// as int;
	if (!isset($isPreview)) $isPreview = (!empty($this->params['url']['preview']));
	$isWide = !empty($this->params['named']['wide']);		// fluid layout
	$isXhr = Configure::read('controller.isXhr');
	// $controllerAttr = Configure::read('controller');
	$xhrFrom = Configure::read('controller.xhrFrom');
	$PREVIEW_LIMIT = $isPreview ? 12 : false;
?>

<ul class='inline tag-roll-header'>
	<?php if (!$isPreview  && Configure::read("paginate.Options.{$paginateModel}.context")!='skip' ) { echo "<li>{$this->element('context')}</li>"; }?>
</ul>
<?php if ($isPreview) { ?>	
	<h1 class='count'>
		<?php
			if ($total==0) {
				echo "There are no added Tags. Add some below.";
			} else {
				echo "Total <span>{$total}</span> Tag" . ($total>1 ? "s. " : ". ");
				$next = array('controller'=>$xhrFrom['keyName'],'action'=>'trends', $xhrFrom['uuid']) + $passedArgs;
				echo $this->Html->link('Show all', $next); 
			}
		?> 
	</h1>
<?php } ?>	
<ul class='inline'>
<?php 
	if (isset($cloudTags)) {
		if ($PREVIEW_LIMIT) $cloudTags = array_slice($cloudTags,  0, $PREVIEW_LIMIT); 
		echo $this->TagCloud->display($cloudTags, array(
			'url' => array('plugin'=>'','controller' => 'tags', 'action'=>'home'),
			'before' => '<li><span style="font-size: %size%pt" class="tag">',
			'after' => '</span></li>',
			'minSize' => '8',
			'maxSize' => '16',
			'named' => 0,
			'shuffle' => 0
		));
	}
?>
</ul>	

<?php  		
	if (!$isPreview) {
		// setup aui_paginator
?>
		<script type="text/javascript">
			var initOnce = function() {
				//TODO: init aui_paginator 
			};
			try {SNAPPI.xhrFetch.fetchXhr; initOnce(); }			// run now for XHR request, or
			catch (e) {PAGE.init.push(initOnce); }	// run from Y.on('domready') for HTTP request
		</script>	
<?php } ?>
