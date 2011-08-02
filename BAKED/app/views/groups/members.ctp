<?php 
	echo $this->element('nav/section', array('icon_src'=>$data['Group']['src_thumbnail']));
?>
<div class="groups members">
	<p>Total of <?php $total = isset($this->params['paging']['Member']['count']) ? $this->params['paging']['Member']['count']: count($data['Member']);
					echo $total; ?> members</p>
	
	<div id='paging-members' class='paging-content' xhrTarget='paging-members-inner'>
		<?php echo $this->element('/member/paging-members');?>
	</div>
</div>
<script type="text/javascript">
var initOnce = function() {
	// TODO: bind members to MemberRoll
	SNAPPI.ajax.initPaging();
};
// add to PAGE.init array
try {SNAPPI.ajax; initOnce(); }			// run now for XHR request, or
catch (e) {PAGE.init.push(initOnce); }	// run from Y.on('domready') for HTTP request
</script>