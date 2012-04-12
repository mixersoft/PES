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
	$isPreview = (!empty($this->params['url']['preview']));
	$isRelated = empty($this->params['url']['gallery']);							// use different default for TagCloud
	$isWide = !empty($this->params['named']['wide']);		// fluid layout
	$controllerAttr = Configure::read('controller');
	$isXhr = $controllerAttr['isXhr'];
	$xhrFrom = $controllerAttr['xhrFrom']; 
	$next = array('controller'=>$xhrFrom['alias'], 'action'=>'trends', $xhrFrom['uuid'])+$passedArgs;
	if (isset($this->passedArgs['filter'])) $next['filter'] = $this->passedArgs['filter'];
	if ($next['controller'] == 'my') unset($next[0]);
	$tokens['total'] = $total; 
	$tokens['linkTo'] = $this->Html->link('Show all', $next); 
	$tokens['type'] = ($total==1 ? "Tag. " : "Tags. ");
	$header_content = String::insert("Total <span class=''>:total</span> :type :linkTo", $tokens);
	
	if ($isRelated) {	// &gallery=1
		if ($total==0) {
			$header_content = String::insert("There are <span class='count'>no</span> :type for this item.", $tokens);
		} else {
			$header_content = String::insert("Total <span class='count'>:total</span> :type :linkTo", $tokens);
		}
		echo "<h2>{$header_content}</h2>";
		// return;
	} else {
		// tag-header for /all /trends
		$tokens['total'] = $total; 
		$tokens['type'] = ($total==1 ? "Tag. " : "Tags. ");
		$header_content = String::insert("Total <span class=''>:total</span> :type", $tokens);
?>		
	
<section class='tag-header'>
	<ul class="toolbar inline grid_16">
		<li class='blue label'><h1><?php echo $header_content; ?> </h1></li>
	</ul>
</section>	
	
<?php	} ?>
	
	
	
	
	
	
	
<ul class='inline'>
<?php 
	if (isset($cloudTags)) {
		// debug($xhrFrom);
		// set Context for tags
		if ($xhrFrom['alias'] == 'my') {
			// set Tag filter 
			// TODO: currently using Context to set Tag Filter 
			$route = array( 'plugin'=>'','controller' => $xhrFrom['alias'], 'action'=>$xhrFrom['action'] );
			$options = array(
				'url' => $route,
				'named' => 'context',	// add tag as context:Tag~[tagId]
				'named_prefix' => 'Tag~',
			);
		} else {
			$route = array('plugin'=>'','controller' => 'tags', 'action'=>'home');
		}
		if ($ENABLED = 0) {	// set context:Tag on click, currently disabled
			$context = array_shift(Session::read("lookup.context"));
			if (!$context && in_array($xhrFrom['alias'], array('photos', 'circles', 'groups', 'events', 'weddings'))) {
				$route['context']=Configure::read("lookup.xfr.{$xhrFrom['alias']}.ControllerLabel");
			}
		}
		$cloudTag_options = array(
			'url' => $route,
			'named' => 0,
			'before' => '<li><span style="font-size: %size%pt" class="tag" title="%count% item(s)">',
			'after' => '</span></li>',
			'minSize' => '8',
			'maxSize' => $isRelated ? '16' : '24',
			'shuffle' => 0
		);
		if (!empty($options)) $cloudTag_options = array_merge($cloudTag_options, $options);
		echo $this->TagCloud->display($cloudTags, $cloudTag_options);
	}
?>
</ul>	

<?php  		
	if (!$isRelated) {
		// setup aui_paginator			
		$this->Layout->blockStart('javascript');
?>
		<script type="text/javascript">
			var initOnce = function() {
				//TODO: init aui_paginator 
				SNAPPI.Y.fire('snappi:after_GalleryInit', this); 
			};
			try {SNAPPI.xhrFetch.fetchXhr; initOnce(); }			// run now for XHR request, or
			catch (e) {PAGE.init.push(initOnce); }	// run from Y.on('domready') for HTTP request
		</script>	
<?php $this->Layout->blockEnd(); } ?>
