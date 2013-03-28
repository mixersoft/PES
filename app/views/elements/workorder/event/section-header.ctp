<?php 
/*
 *  * WORKORDER VIEW (called from: /workorders/snap/gallery.ctp)
 */ 
	$label = array();
	$label[] = "<span class='tw-id'>{$description['id']}</span>"; 
	$label[] = "&nbsp;<span class='tw-type'>{$description['tw-type']}</span>"; 
	$label[] = "&nbsp;<span class='wo-type'>".strtolower($description['wo-type']).':</span>';
	$label[] = "<span class='wo-label'>{$description['wo-label']}</span>";
	$label[] = "&nbsp;<span class='wo-count'>({$description['wo-count']})</span>";
	
	$link_imageGroup = Router::url(array('action'=>'image_group', $this->passedArgs[0]));

	// $primary = Session::read("nav.primary");
	$controllerAttrs = Configure::read('controller');
	if (empty($badge_src)) $badge = "";
	else if ($controllerAttrs['class'] == 'Collection') $badge = "";
	else $badge = $this->Html->image($badge_src, array(
		'title'=>'Click to go to the home page.',
		'class'=>'badge-tiny', 
		'url'=>array('action'=>'home')+$this->passedArgs)) . "&nbsp;"; 
?>
<nav class="section-header container_16">
	<h1 class="grid_7"><?php echo $badge . implode('',$label) ?></h1>
    <ul class="inline grid_5">Timescale (days):
		<li class="gallery rounded-5 orange">
			<a href='<?php echo Router::url(array('timescale'=>1)+$this->passedArgs);  ?>'>1</a>
		</li>
		<li class="gallery rounded-5 orange">
			<a href='<?php echo Router::url(array('timescale'=>7)+$this->passedArgs); ?>'>7</a>
		</li>
		<li class="gallery rounded-5 orange">
			<a href='<?php echo Router::url(array('timescale'=>30)+$this->passedArgs);  ?>'>30</a>
		</li>
		<li class="gallery rounded-5 orange">
			<a href='<?php echo Router::url(array('timescale'=>90)+$this->passedArgs);  ?>'>90</a>
		</li>
	</ul>
    <aside class="grid_4">
      	<?php echo $this->element('nav/search')?>
    </aside>
</nav>