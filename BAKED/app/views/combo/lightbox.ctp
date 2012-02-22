<!--  should be same as /views/elements/lightbox.ctp  -->
<section class="lightbox lightbox-bg cf filmstrip drop container_16 hide" id="lightbox">
	<div class='lightbox-tab lightbox-bg hide' title="move the mouse a little lower to see the Lightbox">Lightbox</div>
	<section class="gallery-header grid_16">
		<ul class="inline cf">
			<li><h3>Lightbox <img src="/static/img/css-gui/img_setting.gif" alt="" align="absmiddle"></h3></li>
			<li>
				<nav class="toolbar">
					<div>
						<ul class="inline menu-trigger">
							<li class="btn select-all"><span class="menu-open"><input type="checkbox" value="" name=""></span></li>
							<li class="organize">Organize</li>
							<li class="share">Share</li>
							<li class="create">Create</li>
						</ul>
					</div>
					<h1 class="count"></h1>
				</nav>
			</li>			
			<li class="right">
				<nav class="window-options">
					<ul class="thumb-size inline">
						<li class="label">Size</li>
						<li size="lbx-tiny" class="btn"><img alt="" src="/static/img/css-gui/img_1.gif"></li><li size="sq" class="btn "><img alt="" src="/static/img/css-gui/img_2.gif"></li><li size="lm" class="btn "><img alt="" src="/static/img/css-gui/img_3.gif"></li>
					</ul>
					<ul class="inline">
						<li action='set-display-view:minimize' title="minimize Lightbox"><img src="<?php echo AppController::$http_static[0]; ?>/static/img/css-gui/img_zoomin.gif"><li action='set-display-view:one-row' title="show one row of thumbnails in Lightbox"><img src="<?php echo AppController::$http_static[1]; ?>/static/img/css-gui/img_zoomout.gif"></li><li action="set-display-view:maximize" title="maximize Lightbox"><img src="<?php echo AppController::$http_static[1]; ?>/static/img/css-gui/img_zoomout.gif"></li>
					</ul>
				</nav>
			</li>
		</ul>
	</section>
	<section class='gallery photo lightbox grid_16 '>
		<div class="filmstrip-wrap"><div class='filmstrip'><div class='container'></div>
			</div></div>
	</section>
</section>