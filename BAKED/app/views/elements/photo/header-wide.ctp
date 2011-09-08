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
?>
<section class="wide gallery-header gallery-display-options container_16">
	<ul class="toolbar inline grid_2">
		<li class="select-all"><input type="checkbox" value="" name=""><a class="menu-open"> </a></li>
		<li><h1><?php echo $total; ?>  Snaps</h1></li>
	</ul>	
	<nav class="settings grid_4">
		<ul class="wide-display-options inline">
			<li class="btn option">Delete</li>
			<li class="btn option">Group as Shot</li>
			<li class="btn option">Add to Lightbox</li>
		</ul>
	</nav>	
    <nav class="settings grid_9">
		<ul class="thumb-size inline right">
			<li class="label">Size</li>
			<?php 
				foreach ($sizes as $size => $src ) {
					echo "<li class='btn ".($focus==$size ? 'focus' : '')."' thumb-size='{$size}'><img src='{$src}' alt=''></li>";
				}
			?>
		</ul>
		<?php echo $this->element('/photo/display-options');  ?>
	</nav>      
</section> 
