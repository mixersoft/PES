<?php 
if (empty($this->passedArgs['wide'])) {
	$this->Layout->blockStart('itemHeader');
		echo $this->element('nav/section', array('badge_src'=>null)); 
	$this->Layout->blockEnd();
}
?>
<div class="users all">
	<div id='paging-members' class='paging-content'  xhrTarget='paging-members-inner'>
		<?php echo $this->element('/member/paging-members');?>
	</div>
</div>
<script type="text/javascript">
var initOnce = function() {
	// init xhr paging & fetch xhr-gets
	// NOTE: any xhr-gets will bind own PAGE.init() method
	SNAPPI.xhrFetch.init(); 
};
try {SNAPPI.xhrFetch.fetchXhr; initOnce(); }			// run now for XHR request, or
catch (e) {PAGE.init.push(initOnce); }	// run from Y.on('domready') for HTTP request
</script>