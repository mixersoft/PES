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
<div id="paging" class="hidden<?php if ($isPreview) echo " hide"; ?>">
	<div id="prevPage">&lt Prev</div>
	<span id="pagenum"></span>
	<div id="nextPage">Next &gt</div>
</div>
<div id='content' class='hidden'>
 	<?php foreach ($page_gallery as $row) {
		echo $row;
	} ?> 
	<?php if (!$isPreview && $link) { ?>
		<div id="share">Share this story: <a id="share-link" href="<?php
		echo $link ?>"><?php echo $link ?></a></div>
	<?php } ?>
</div>
<div id="footer" class='<?php if ($isPreview) echo "hide"?>'> 
	<div>This story was brought to you by <a
		href="http://www.snaphappi.com" target="_new">Snaphappi</a>.
		</div>
</div>
