<div id="paging" class="hidden<?php if ($isPreview) echo " hide"; ?>">
<div id="prevPage">&lt Prev</div>
<span id="pagenum"></span>
<div id="nextPage">Next &gt</div>
</div>
<div id="center">
	<div id="centerbox" class="hide">
		<img id="lightBoxPhoto" />
		<span id="prevPhoto" class="hidden"></span>
		<span id="nextPhoto" class="hidden"></span> 
		<span id="closeBox" class="hidden"></span>
	</div>
</div>
<div id="glass">
	<div class='loading'></div>
</div>
<div id='content' class='hidden'>
 	<?php foreach ($page_gallery as $row) {
		echo $row;
	} ?> 
	<?php if (!$isPreview && $link) { ?>
		<div id="share">Share this Page Gallery: <a id="share-link" href="<?php
		echo $link ?>"><?php echo $link ?></a></div>
	<?php } ?>
</div>
<div id="footer" class='<?php if ($isPreview) echo "hide"?>'> 
	<div>This Page Gallery was brought to you by <a
		href="http://www.snaphappi.com" target="_new">Snaphappi</a>.
		</div>
</div>
