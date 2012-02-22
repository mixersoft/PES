<?php
	/*
	 * @params $isPreview, $isRelated, $total, $state
	 */

	$focus = 'll';
	if (isset($this->passedArgs['thumbSize'])) {
		$focus = $this->passedArgs['thumbSize'];		
	}
	$sizes = array(
		'sq'=>'/static/img/css-gui/img_1.gif',
		'lm'=>'/static/img/css-gui/img_2.gif',
		'll'=>'/static/img/css-gui/img_3.gif',
	);
	if ($isPreview) {
		$xhrFrom = Configure::read('controller.xhrFrom');
		$passedArgs = Configure::read('passedArgs.min');
		$next = array('controller'=>$xhrFrom['alias'],'action'=>'groups', $xhrFrom['uuid']) + $passedArgs;
		$tokens['total'] = $total; 
		$tokens['linkTo'] = $this->Html->link('Show all', $next); 
		$tokens['type'] = ($total==1 ? "Circle. " : "Circles. ");
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
		<li><h1><?php echo $total; ?>  Circles</h1></li>
	</ul>	
	<nav class="settings grid_4">
		<ul class="wide-display-options inline">
			<li class="btn white option">Delete</li>
			<li class="btn white option">Group as Shot</li>
			<li class="btn white option">Add to Lightbox</li>
		</ul>
	</nav>	
    <nav class="settings grid_10">
    	<?php echo $this->element('/photo/display-options');  ?>
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
