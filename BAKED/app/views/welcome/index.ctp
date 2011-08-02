<div class="assets ">
	<h2><?php __('Photos');?></h2>
		<p class='total-count'><?php echo isset($this->params['paging']['Asset']['count']) ? $this->params['paging']['Asset']['count'] : count($data['Asset'])?> photos</p>

		<?php $lookupParams = array('labelField'=>'owner_id', 'lookupField'=>Session::read('lookup.owner_names'));
			echo $this->element('/photo/roll', array_merge(array('photos'=>$data['Asset']), $lookupParams)); 
			?>
</div>

<div class="groups ">
	<h2><?php __('Groups');?></h2>
		<p class='total-count'><?php echo isset($this->params['paging']['Group']['count']) ? $this->params['paging']['Group']['count'] : count($data['Asset'])?> Groups</p>
	<?php echo $this->element('/group/roll', array('groups'=>$data['Group'], 'labelField'=>'src_thumbnail'))?>
</div>

<?php	// tagCloud
	$ajaxSrc = Router::url(array('plugin'=>'', 'controller'=>'tags','action'=>'show'));
	echo "<div id='tags-preview-xhr' class='fragment' ajaxSrc='{$ajaxSrc}'></div>";
?>	
<script type="text/javascript">
var initOnce = function() {
	// TODO: bind members to MemberRoll
	SNAPPI.ajax.init();
};
try {SNAPPI.ajax; initOnce(); }			// run now for XHR request, or
catch (e) {PAGE.init.push(initOnce); }	// run from Y.on('domready') for HTTP request
</script>