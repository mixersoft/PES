<?php
	if (isset($this->passedArgs['thumbSize'])) {
		$thumbSize = $this->passedArgs['thumbSize'];		
	} else {
		$PREFIX = 'Photo';	// Photo 
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

		
	<nav class="settings cf grid_11">
		<?php  echo $this->element('/workorder/shot/display-options');  ?>  
	</nav>	
    <nav class="settings cf window-options grid_2">
		<ul class="thumb-size inline inline-break right">
			<li class="label">Size</li>
			<?php 
				foreach ($sizes as $size => $src ) {
					echo "<li class='btn white ".($thumbSize==$size ? 'focus' : '')."' action='set-display-size:{$size}'><img src='{$src}' alt=''></li>\n";
				}
			?>
			<li class='btn white' action='toggle-keydown'><span class='keydown'>&nbsp;</span></li>
		</ul>
	</nav> 
	
<?php  } ?>     
</section> 
