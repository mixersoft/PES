<?php 
/*
 *  * WORKORDER VIEW (copied from: /elements/photos/roll)
 */ 

	$primary = Session::read("nav.primary");
	$controllerAttrs = Configure::read('controller');
	try {
		if (!isset($data)) throw new Exception("Error: no data for section header");
		switch ($controllerAttrs['class']) {
			case 'Workorder': 
				$label = "Workorder: {$data['Workorder']['id']}"; 
				break;
			case 'TasksWorkorder': 
				$label = "Task: {$data['TasksWorkorder']['task_id']}"; 
				break;	
			case 'Group': $label = $data['Group']['title']; break;
			case 'User': $label = $data['User']['username']; break;
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
	<h1 class="grid_7"><?php echo $badge . $label ?></h1>
    <ul class="inline grid_5">
    	<li><?php echo "lookup {$data['Workorder']['source_model']}:{$data['Workorder']['source_id']}" ?>
    	</li>
		<li class="gallery rounded-5 white" action='section-view:gallery'><a>Gallery</a></li>
	</ul>
    <aside class="grid_4">
      	<?php echo $this->element('nav/search')?>
    </aside>
</nav>