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
<section class="gallery-header container_16">
	<ul class="toolbar inline grid_3">
		<li class="select-all"><input type="checkbox" value="" name=""><a class="menu-open"> </a></li>
		<li><h1><?php echo $total; ?>  Snaps</h1></li>
	</ul>
    <nav class="settings push_6 grid_7">
		<ul class="inline right">
			<li class="display-option" onclick='PAGE.toggleDisplayOptions();'>
    			<a class='menu-open'>Display Options</a>
    		</li>
    	</ul><ul class="thumb-size inline right">
			<li class="label">Size</li>
			<?php 
				foreach ($sizes as $size => $src ) {
					echo "<li class='btn ".($focus==$size ? 'focus' : '')."' thumb-size='{$size}'><img src='{$src}' alt=''></li>";
				}
			?>
			<li class='btn' onclick="PAGE.toggle_fullscreen(true);">Fullscreen</li>
		</ul>
	</nav>      
</section> 
<?php echo $this->element('/photo/display-options');  ?>