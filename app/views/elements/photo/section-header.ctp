<?php 
	$primary = Session::read("nav.primary");
	$controllerAttrs = Configure::read('controller');
	try {
		if (!isset($data)) throw new Exception();
		switch ($controllerAttrs['class']) {
			case 'Group': $label = $data['Group']['title']; break;
			case 'User': 
				$label = $data['User']['primary_group_id'] == 'role-----0123-4567-89ab--------guest' ? 'Guest' : $data['User']['username']; break;
			case 'Photo': $label = $data['Asset']['caption']; break; 
			case 'Collection': $label = $data['Collection']['title']; break; 
			case 'Tag': $label = $data['Tag']['name']; break; 
			default: $label = $controllerAttrs['label'];
		}
	} catch (Exception $e) {
		$label = "&nbsp;";
	}	
	if (empty($badge_src)) $badge = "";
	else if ($controllerAttrs['class'] == 'Collection') $badge = "";
	else $badge = $this->Html->image($badge_src, array(
		'title'=>'Click to go to the home page.',
		'class'=>'badge-tiny', 
		'url'=>array('action'=>'home')+$this->passedArgs)) . "&nbsp;"; 
?>
<nav class="section-header container_16">
	<h1 class="grid_4"><?php echo $badge . $label ?></h1>
    <ul class="inline grid_8">
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