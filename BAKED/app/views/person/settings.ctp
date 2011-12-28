<?php
	$this->Layout->blockStart('itemHeader');
		$badge_src = Stagehand::getSrc($data['User']['src_thumbnail'], 'sq', 'person');
		echo $this->element('nav/section', array('badge_src'=>$badge_src)); 
	$this->Layout->blockEnd();	
?>
<div class="users view">
	<div class="users ">
		<div id='section-tabs'>
			<ul class='inline'>
	<?php 	
		$xhrSrc = Router::url(array('plugin'=>'', 'action'=>'settings'));
		$identitySrc = $xhrSrc."?xhrview=settings-identity";
		$emailsSrc = $xhrSrc."?xhrview=settings-emails";
		$privacySrc = $xhrSrc."?xhrview=settings-privacy";
		$moderatorSrc = $xhrSrc."?xhrview=settings-moderator";
		
		$xhrFrom = Configure::read('controller.xhrFrom');
		if (empty($xhrFrom['view'])) {
			$tabName = Session::read('settings.tabName');
			if ($tabName) {
				Session::delete('settings.tabName');
				$xhrFrom['view'] = "settings-{$tabName}";
			}
			else $xhrFrom['view'] = "settings-identity";
		}
	?>			
				<li class='btn'><a id='tab-identity' href='<?php echo $identitySrc ?>' onclick='return SNAPPI.UIHelper.nav.gotoTab(this);'>Identity & Profile</a></li>
				<li class='btn'><a id='tab-emails' href='<?php echo $emailsSrc ?>' onclick='return SNAPPI.UIHelper.nav.gotoTab(this);'>Emails & Notifications</a></li>
				<li class='btn'><a id='tab-privacy' href='<?php echo $privacySrc ?>' onclick='return SNAPPI.UIHelper.nav.gotoTab(this);'>Privacy</a></li>
				<li class='btn'><a id='tab-moderator' href='<?php echo $moderatorSrc ?>' onclick='return SNAPPI.UIHelper.nav.gotoTab(this);'>Moderation</a></li>
			</ul>
		</div>	
		<div id='tab-section' class="setting  xhr-get"  xhrSrc='<?php echo $xhrSrc."?xhrview={$xhrFrom['view']}" ?>' nodelay='1'>
		</div>	
	</div>	
</div>