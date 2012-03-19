<?php
	$this->Layout->blockStart('itemHeader');
		$badge_src = Stagehand::getSrc($data['User']['src_thumbnail'], 'sq', 'Person');
		echo $this->element('nav/section', compact('badge_src')); 
?>
	<div class='properties hide container_16'>
		<dl class="grid_16">
		<?php $i = 0;
			$dtClass = 'grid_2 alpha';
			$ddClass = 'grid_12 suffix_2 omega';
			$altClass = ' altrow ';
		?>
			<span class='<?php $i++; echo $dtClass; if ($i % 2 == 0) echo $altClass;?>'>
				<?php __('Username'); ?></span>
			<span class='<?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>'>
				<?php echo $data['User']['username']; ?>
			</span>
			<span class='<?php $i++; echo $dtClass; if ($i % 2 == 0) echo $altClass;?>'><?php __('Last Visit'); ?></span>
			<span class='<?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>'>
				<?php echo $this->Time->timeAgoInWords($data['User']['lastVisit']); ?>
			</span>
			<span class='<?php  $i++; echo $dtClass; if ($i % 2 == 0) echo $altClass;?>'><?php __('Member Since'); ?></span>
			<span class='<?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>'>
				<?php echo $this->Time->nice($data['User']['created']); ?>
			</span>
		</dl>
	</div>
<?php 
	$this->Layout->blockEnd();	
?>
<div class="users view">
	<div class="users ">
		<div id='tab-list-settings'>
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
				<li class='tab btn'><a id='tab-identity' href='<?php echo $identitySrc ?>' onclick='return SNAPPI.tabSection.selectByCSS(this);'>Identity & Profile</a></li>
				<li class='tab btn'><a id='tab-emails' href='<?php echo $emailsSrc ?>' onclick='return SNAPPI.tabSection.selectByCSS(this);'>Emails & Notifications</a></li>
				<li class='tab btn'><a id='tab-privacy' href='<?php echo $privacySrc ?>' onclick='return SNAPPI.tabSection.selectByCSS(this);'>Privacy</a></li>
				<li class='tab btn'><a id='tab-moderator' href='<?php echo $moderatorSrc ?>' onclick='return SNAPPI.tabSection.selectByCSS(this);'>Moderation</a></li>
			</ul>
		</div>	
		<div id='tab-view-settings' class="setting tab-view xhr-get prefix_1 grid_14 suffix_1"  xhrSrc='<?php echo $xhrSrc."?xhrview={$xhrFrom['view']}" ?>' nodelay='1'></div>	
	</div>	
</div>