<?php 
	$primary = Session::read("nav.primary");
	$controllerAttrs = Configure::read('controller');
	try {
		if (!isset($data)) throw new Exception();
		switch ($controllerAttrs['class']) {
			case 'Group': $label = $data['Group']['title']; break;
			case 'User': $label = $data['User']['username']; break;
			case 'Photo': $label = $data['Asset']['caption']; break; 
			case 'Tag': $label = $data['Tag']['name']; break; 
			default: $label = $controllerAttrs['label'];
		}
	} catch (Exception $e) {
		$label = "&nbsp;";
	}	
?>
<nav class="section-header container_16">
	<h1 class="grid_3"><?php echo $label ?></h1>
    <ul class="inline grid_9">
		<li class="montage rounded-5 white" action='section-view:montage'><a>Montage</a></li>                    
		<li class="gallery rounded-5 white" action='section-view:gallery'><a>Gallery</a></li>
		<li class="disabled"><a>Timeline</a></li>
		<li class="disabled"><a>Invites</a></li>
		<li class="disabled"><a>Activity Feed</a></li>
		<li class="disabled rounded-5 preference"><a>Preferences</a></li>
	</ul>
    <aside class="grid_4">
      	<?php echo $this->element('nav/search')?>
    </aside>
</nav>