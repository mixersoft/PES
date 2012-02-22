<?php
	if (isset($this->passedArgs['thumbSize'])) {
		$thumbSize = $this->passedArgs['thumbSize'];		
	} else {
		$PREFIX = 'uuid-';	// Photo 
		$thumbSize = Session::read("thumbSize.{$PREFIX}");
	}
	if (!$thumbSize) $thumbSize = 'lm';		
	$sizes = array(
		'tn'=>AppController::$http_static[1].'/static/img/css-gui/img_1.gif',
		'lm'=>AppController::$http_static[0].'/static/img/css-gui/img_2.gif',
		'll'=>AppController::$http_static[1].'/static/img/css-gui/img_3.gif',
	);
	$xhrFrom = Configure::read('controller.xhrFrom');
	$passedArgs = Configure::read('passedArgs.min');
	$next = array('controller'=>$xhrFrom['alias'],'action'=>'photos', $xhrFrom['uuid']) + $passedArgs;
	$tokens['total'] = $total; 
	if ($isPreview) {
		$tokens['type'] = ($total==1 ? "Snap. " : "Snaps. ");		
		$tokens['linkTo'] = $this->Html->link('Show all', $next); 
		$header_content = String::insert("Total <span class=''>:total</span> :type :linkTo", $tokens);
	} else {
		// NOT isPreview	
		$tokens['type'] = ($total==1 ? "Snap " : "Snaps ");		
		$tokens['linkTo'] = Router::url($next);
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
		return;
	}	
	
?>
<section class="wide gallery-header gallery-display-options container_16">
<?php  if ($isPreview) { ?>
		
	<ul class="toolbar inline grid_3">
		<li class='blue label'><h1><?php echo $header_content;  ?></h1></li>
	</ul>	
	
<?php  } else { ?>

	<ul class="toolbar inline grid_3">
		<li class="btn white select-all"><span class="menu-open"><input type="checkbox" value="" name=""></span></li>
		<li class='btn orange snap-count'><?php echo $btn_snaps; ?></li>
	</ul>	

		
	<nav class="settings cf grid_4">
		<ul class="wide-display-options inline">
			<li class="btn white option">Delete</li>
			<li class="btn white option">Group as Shot</li>
			<li class="btn white option">Add to Lightbox</li>
		</ul>
	</nav>	
    <nav class="settings cf window-options grid_9">
    	<?php echo $this->element('/photo/display-options');  ?>
		<ul class="thumb-size inline inline-break right">
			<li class="label">Size</li>
			<?php 
				foreach ($sizes as $size => $src ) {
					echo "<li class='btn white ".($thumbSize==$size ? 'focus' : '')."' action='set-display-size:{$size}'><img src='{$src}' alt=''></li>\n";
				}
			?>
		</ul>
	</nav> 
	
<?php  } ?>     
</section> 
