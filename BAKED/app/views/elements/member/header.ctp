<?php
	$focus = 'lm';
	if (isset($this->passedArgs['thumbSize'])) {
		$focus = $this->passedArgs['thumbSize'];		
	}
	$sizes = array(
		'sq'=>'/css/images/img_1.gif',
		'lm'=>'/css/images/img_2.gif',
		'll'=>'/css/images/img_3.gif',
	);
	if ($isPreview) {
		$xhrFrom = Configure::read('controller.xhrFrom');
		$passedArgs = Configure::read('passedArgs.min');
		$next = array('controller'=>$xhrFrom['alias'],'action'=>'person', $xhrFrom['uuid']) + $passedArgs;
		$tokens['total'] = $total; 
		$tokens['linkTo'] = $this->Html->link('Show all', $next); 
		$tokens['type'] = ($total==1 ? "Member. " : "Members. ");
		$header_content = String::insert("Total <span class=''>:total</span> :type :linkTo", $tokens);
	}
	$isRelated = empty($this->params['url']['gallery']);
	if ($isPreview && $isRelated) {
		if ($total==0) {
			$header_content = String::insert("There are <span class='count'>no</span> :type for this item.", $tokens);
		} else {
			$header_content = String::insert("Total <span class='count'>:total</span> :type :linkTo", $tokens);
		}
		echo "<h2>{$header_content}</h2>";
		return;
	}	
?>
<section class="gallery-header container_16">
<?php  if (!empty($isPreview)) { 	?>
	
	<ul class="toolbar inline grid_3">
		<li class='blue label'><h1><?php echo $header_content;  ?></h1></li>
	</ul>	
	
	
	
<?php  } else { ?>
	
	
		
	<ul class="toolbar inline grid_3">
		<li class="btn select-all"><input type="checkbox" value="" name=""><a class="menu-open"> </a></li>
		<li><h1><?php echo $total; ?>  Members</h1></li>
	</ul>
    <nav class="settings push_6 grid_7">
		<ul class="inline right">
			<li class="display-option" onclick='SNAPPI.UIHelper.nav.toggleDisplayOptions();'>
    			<a class='menu-open'>Display Options</a>
    		</li>
    	</ul><ul class="thumb-size inline right">
			<li class="label">Size</li>
			<?php 
				foreach ($sizes as $size => $src ) {
					echo "<li class='btn ".($focus==$size ? 'focus' : '')."' size='{$size}'><img src='{$src}' alt=''></li>";
				}
			?>
			<li class='btn' onclick="SNAPPI.UIHelper.nav.toggle_fullscreen(true);">Fullscreen</li>
		</ul>
	</nav>      
 
	<?php echo $this->element('/member/display-options');  ?>


<?php  } ?>	
</section>
