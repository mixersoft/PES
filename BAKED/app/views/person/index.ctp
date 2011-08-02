<?php 
	echo $this->element('nav/section'); 
?>
<div class="users all">
	<div id='paging-members' class='paging-content'  xhrTarget='paging-members-inner'>
		<?php echo $this->element('/member/paging-members');?>
	</div>
</div>
<script type="text/javascript">
var initOnce = function() {
	// init xhr paging & fetch fragments
	// NOTE: any fragments will bind own PAGE.init() method
	SNAPPI.ajax.init(); 
};
try {SNAPPI.ajax; initOnce(); }			// run now for XHR request, or
catch (e) {PAGE.init.push(initOnce); }	// run from Y.on('domready') for HTTP request
</script>