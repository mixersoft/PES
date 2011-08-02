<?php $uuid = Session::read('Auth.User.id'); ?>
<script type="text/javascript">
gotoTab = function(dom){
	if (dom.href.search('/cancel')>=0) {
			return false;
	}
	PAGE={section: dom.className};
	SNAPPI.TabNav.selectByName(PAGE);
	var container = SNAPPI.Y.one("#tab-section").setAttribute('ajaxSrc', dom.href);
	SNAPPI.ajax.requestFragment(container);
	return false;
};
var initOnce = function() {
	// init xhr paging & fetch fragments
	// NOTE: any fragments will bind own PAGE.init() method
	SNAPPI.ajax.fetchXhr(); 
};
PAGE.init.push(initOnce);
</script>
<?php 
	echo $this->element('nav/section', array('icon_src'=>$data['User']['src_thumbnail']));
?>	
<div class="users view placeholder">
	<div class="users ">
		<div id='section-tabs'>
			<ul class='inline'>
	<?php 		
		$xhrSrc = array('plugin'=>'', 'action'=>'settings', $this->passedArgs[0]);
		$xhrFrom = Configure::read('controller.xhrFrom');
		$xhrSrc['?'] = array('xhrview'=>'settings-identity');
		$identitySrc = Router::url($xhrSrc);
		$xhrSrc['?'] = array('xhrview'=>'settings-emails');
		$emailsSrc = Router::url($xhrSrc);	
		$xhrSrc['?'] = array('xhrview'=>'settings-privacy');
		$privacySrc = Router::url($xhrSrc);	
		$xhrSrc['?'] = array('xhrview'=>'settings-moderator');
		$moderatorSrc = Router::url($xhrSrc);	
	?>			
				<li><a class='tab-identity' href='<?php echo $identitySrc ?>' onclick='return gotoTab(this);'>Identity & Profile</a></li>
				<li><a class='tab-emails' href='<?php echo $emailsSrc ?>' onclick='return gotoTab(this);'>Emails & Notifications</a></li>
				<li><a class='tab-privacy' href='<?php echo $privacySrc ?>' onclick='return gotoTab(this);'>Privacy</a></li>
				<li><a class='tab-moderator' href='<?php echo $moderatorSrc ?>' onclick='return gotoTab(this);'>Moderation</a></li>
			</ul>
		</div>	
		<div id='tab-section' class="setting placeholder fragment"  ajaxSrc='<?php echo $identitySrc ?>'>
		</div>	
	</div>	
</div>