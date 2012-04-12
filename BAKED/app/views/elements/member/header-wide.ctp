<?php
	/*
	 * @params $isPreview, $isRelated, $total, $state
	 */

	$focus = 'lm';
	if (isset($this->passedArgs['thumbSize'])) {
		$focus = $this->passedArgs['thumbSize'];		
	}
	$sizes = array(
		'sq'=>AppController::$http_static[1].'/static/img/css-gui/img_1.gif',
		'lm'=>AppController::$http_static[0].'/static/img/css-gui/img_2.gif',
		'll'=>AppController::$http_static[1].'/static/img/css-gui/img_3.gif',
	);
	$tokens['total'] = $total; 
	if ($controllerAttrs['name'] == 'Users') {
		// action == 'all'
		$tokens['type'] = ($total==1 ? "{$controllerAttrs['label']}" : "{$controllerAttrs['titleName']}");
	} else {
		$tokens['type'] = ucFirst($controllerAttrs['action']);	// TODO: not adjusted for singular		
	}	
	if ($isPreview) {
		$xhrFrom = Configure::read('controller.xhrFrom');
		$passedArgs = Configure::read('passedArgs.min');
		// $next = array('controller'=>$xhrFrom['alias'],'action'=>'groups', $xhrFrom['uuid']) + $passedArgs;
		if ($xhrFrom['action'] == 'all') {
			$next = array('controller'=>$controllerAttrs['alias'],'action'=>'all', $xhrFrom['uuid']) + $passedArgs;
		} else $next = array('controller'=>$controllerAttrs['alias'],'action'=>$controllerAttrs['action'], $xhrFrom['uuid']) + $passedArgs;
		if ($next['controller'] == 'my') unset($next[0]);
		$tokens['linkTo'] = $this->Html->link('Show all', $next); 
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
<section class="wide gallery-header gallery-display-options container_16">
<?php  if ($isPreview) { ?>
	
	<ul class="toolbar inline grid_3">
		<li class='blue label'><h1><?php echo $header_content;  ?></h1></li>
	</ul>	

	
	
<?php  } else { ?>	
	
	
	<ul class="toolbar inline grid_2">
		<li class="btn white select-all"><span class="menu-open"><input type="checkbox" value="" name=""></span></li>
		<li><h1><?php echo "{$total} {$tokens['type']}"; ?></h1></li>
	</ul>	
	<nav class="settings grid_4">
		<ul class="wide-display-options inline">
			<li class="btn white option">Delete</li>
		</ul>
	</nav>	
    <nav class="settings grid_10">
    	<?php echo $this->element('/member/display-options');  ?>
		<ul class="thumb-size inline inline-break right">
			<li class="label">Size</li>
			<?php 
				foreach ($sizes as $size => $src ) {
					echo "<li class='btn white ".($focus==$size ? 'focus' : '')."' size='{$size}'><img src='{$src}' alt=''></li>";
				}
			?>
		</ul>
	</nav>    

<?php  } ?>	
</section> 
