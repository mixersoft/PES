<?php
	if (isset($this->passedArgs['thumbSize'])) {
		$thumbSize = $this->passedArgs['thumbSize'];		
	} else {
		$PREFIX = 'uuid-';	// Photo 
		$thumbSize = Session::read("thumbSize.{$PREFIX}");
	}
	if (!$thumbSize) $thumbSize = 'lm';	
	$sizes = array(
		'sq'=>'/css/images/img_1.gif',
		'lm'=>'/css/images/img_2.gif',
		'll'=>'/css/images/img_3.gif',
	);
?>
<section class="wide gallery-header gallery-display-options container_16">
	<ul class="toolbar inline grid_2">
		<li class="btn white select-all"><span class="menu-open"><input type="checkbox" value="" name=""></span></li>
		<li><h1><?php echo $total; ?>  Snaps</h1></li>
	</ul>	
	<nav class="settings cf grid_4">
		<ul class="wide-display-options inline">
			<li class="btn white option">Delete</li>
			<li class="btn white option">Group as Shot</li>
			<li class="btn white option">Add to Lightbox</li>
		</ul>
	</nav>	
    <nav class="settings cf window-options grid_10">
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
</section> 
