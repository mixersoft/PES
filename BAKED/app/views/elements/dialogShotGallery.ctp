<?php
	$PREFIX = 'DialogHiddenShot';
	$thumbSize = Session::read("thumbSize.{$PREFIX}");
	if (!$thumbSize) $thumbSize = 'sq';
	$sizes = array(
		'sq'=>AppController::$http_static[1].'/static/img/css-gui/img_1.gif',
		'tn'=>AppController::$http_static[0].'/static/img/css-gui/img_2.gif',
		'lm'=>AppController::$http_static[1].'/static/img/css-gui/img_3.gif',
	);
?>
<div id="dialog-hidden-shot" class="container_16" > 
	<section class="filmstrip filmstrip-bg drop alpha omega"> 
 		<div class="preview grid_11 alpha-b1 omega-b1"><nav class="toolbar"></nav></div>+		
		<section class="gallery photo filmstrip hiddenshots grid_11 alpha-b1 omega-b1"> 
			<div class="filmstrip-wrap hidden"><div class="filmstrip"><div class="container"></div></div></div> 
		</section>	 
		<section class="gallery-header alpha-b1 grid_11 omega-b1"> 
			<ul class="inline cf"> 
				<li><h3>Bestshot Gallery <img src="/static/img/css-gui/info.gif" alt="" align="absmiddle"></h3></li> 
				<li> 
					<nav class="toolbar"> 
						<div> 
							<ul class="inline menu-trigger"> 
								<li class="btn white select-all"><span class="menu-open"><input type="checkbox" value="" name=""></span></li> 
							</ul> 
						</div> 
						<h1 class="count">0 Snaps</h1> 
					</nav> 
				</li>		 	
				<li class="right"> 
					<nav class="window-options"> 
						<ul class="thumb-size inline"> 
							<li class="label">Size</li>
						<?php 
							foreach ($sizes as $size => $src ) {
								echo "<li class='btn white ".($thumbSize==$size ? 'focus' : '')."'  action='set-display-size:{$size}'><img src='{$src}' alt=''></li>\n";
							}
						?>
						</ul><ul class="inline"> 
							<li action="set-display-view:one-row"><img src="/static/img/css-gui/img_zoomin.gif"></li><li action="set-display-view:maximize"><img src="/static/img/css-gui/img_zoomout.gif"></li> 
						</ul> 
					</nav> 
				</li> 
			</ul> 
		</section> 
	</section>	 
</div>
