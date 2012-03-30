<?php
	if (isset($this->passedArgs['thumbSize'])) {
		$thumbSize = $this->passedArgs['thumbSize'];		
	} else {
		$PREFIX = 'Photo';	// Photo 
		$thumbSize = Session::read("thumbSize.{$PREFIX}");
	}
	if (!$thumbSize) $thumbSize = 'lm';	
	$sizes = array(
		'sq'=>AppController::$http_static[1].'/static/img/css-gui/img_1.gif',
		'lm'=>AppController::$http_static[0].'/static/img/css-gui/img_2.gif',
		'll'=>AppController::$http_static[1].'/static/img/css-gui/img_3.gif',
	);
	$xhrFrom = Configure::read('controller.xhrFrom');
	$passedArgs = Configure::read('passedArgs.min');
	if (isset($ownerCount) && $ownerCount !== $total) $total = "{$total}/{$ownerCount}";	
	$tokens['total'] = $total; 
	if (in_array(Configure::read('controller.class'), array('User', 'Group', 'Tag'))) {
		if ($xhrFrom['alias'] == 'my') $xhrFrom['uuid'] = null;
		$next = array('controller'=>$xhrFrom['alias'],'action'=>'photos', $xhrFrom['uuid']) + $passedArgs;
	} else $next = null;
	if ($isPreview) {
		$tokens['type'] = ($total==1 ? "Snap. " : "Snaps. ");		
		$tokens['linkTo'] = $next ? $this->Html->link('Show all', $next) : ''; 
		$header_content = String::insert("Total <span class=''>:total</span> :type :linkTo", $tokens);
	} else {
		// NOT isPreview	
		$tokens['type'] = ($total==1 ? "Snap " : "Snaps ");	
		$tokens['linkTo'] = $next ? Router::url($next) : $this->here;
		$btn_snaps= String::insert("<a href=':linkTo'>:total :type</a>", $tokens);
	}
	$isRelated = empty($this->params['url']['gallery']);
	if ($isPreview && $isRelated) {
		if ($total==0) {
			$header_content = String::insert("There are <span class='count'>no</span> :type for this item.", $tokens);
		} else {
			$header_content = String::insert("Total <span class='count'>:total</span> :type :linkTo", $tokens);
		}
		echo "<h2>{$header_content}</h2>";
		return;	// exit
	}	
	if ($xhrFrom['alias'] == 'my') {
		$snapCount_label = "You are not showing hidden shots";
	} else $snapCount_label = 'You may not have permission to see all Snaps';
	
?>
<section class="gallery-header container_16">
<?php  if ($isPreview) { ?>
	
	<ul class="toolbar inline grid_3">
		<li class='blue label' title='<?php echo $snapCount_label ?>'><h1><?php echo $header_content;  ?></h1></li>
	</ul>	

<?php  } else { ?>
	
	<ul class="toolbar inline grid_3">
		<li class="btn white select-all"><span class="menu-open"><input type="checkbox" value="" name=""></span></li>
		<li class='btn orange snap-count' title='<?php echo $snapCount_label ?>'><?php echo $btn_snaps; ?></li>
	</ul>	
<?php  } ?>
    <nav class="settings window-options push_6 grid_7">
		<ul class="inline right">
			<li class="btn white display-option" action='toggle-display-options'>
    			<span class='menu-open'>Display Options</span>
    		</li>
    	</ul><ul class="thumb-size inline right">
			<li class="label">Size</li>
			<?php 
				foreach ($sizes as $size => $src ) {
					echo "<li class='btn white ".($thumbSize==$size ? 'focus' : '')."' action='set-display-size:{$size}'><img src='{$src}' alt=''></li>\n";
				}
			?>
			<li class='btn white' action='toggle-keydown'><span class='keydown'>&nbsp;</span></li>
			<li class='btn white' onclick="SNAPPI.UIHelper.nav.toggle_fullscreen(true);">Fullscreen</li>
		</ul>
	</nav> 
	<?php echo $this->element('/photo/display-options');  ?>     
</section> 
