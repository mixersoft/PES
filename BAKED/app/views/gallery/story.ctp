<div id="glass">
	<div id="centerbox" class="hide">
		<div id="closeBox" class="hidden"></div>
		<span id="prevPhoto" ></span>
		<span id="nextPhoto" ></span> 
		<img id="lightBoxPhoto" />
	</div>
	<div class='loading'></div>
	<div id='bottom'></div>
</div>
<div id="header" class='cf'>
	<div id="paging" class="hidden<?php if ($isPreview) echo " hide"; ?>">
		<div class='center'>
			<div id="prevPage"></div>
			<span id="pagenum"></span>
			<div id="nextPage"></div>
			<div class="hide">
				<!-- load  these imgs first-->
				<img src='/static/img/css-pm/prevlabel.gif'><img src='/static/img/css-pm/nextlabel.gif'>
			</div>			
		</div>
	</div>
	<div class="sharethis inline right hide">
		<span class='st_facebook_large' displayText='Facebook'></span>
		<span class='st_twitter_large' displayText='Tweet'></span>
		<span class='st_plusone_large' displayText='Google +1'></span>
		<span class='st_email_large' displayText='Email'></span>
		<span class='st_sharethis_large' displayText='ShareThis'></span>
	</div>
</div>
<div id='content' class='hidden'>
 	<?php foreach ($page_gallery as $row) {
		echo $row;
	} ?> 
	<?php if (!$isPreview && $link) { ?>
		<div id="share">Share this story: <a id="share-link" href="<?php
		echo $link ?>"><?php echo $link ?></a>
		</div>
	<?php } ?>
</div>
<div id="footer" class='<?php if ($isPreview) echo "hide"?>'> 
	<a href="http://snaphappi.com" target="_new"><img src="<?php echo AppController::$http_static[0]; ?>/static/img/css-gui/snappi-top.png"></a>
</div>
