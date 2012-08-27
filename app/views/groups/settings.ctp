<?php
	$this->Layout->blockStart('itemHeader');
		$badge_src = Stagehand::getSrc($data['Group']['src_thumbnail'], 'sq', $data['Group']['type']);
		$classLabel = $data['Group']['type'];
		$label = $data['Group']['title'];
		echo $this->element('nav/section', compact('badge_src', 'classLabel', 'label'));
?>
<div class="properties hide container_16">	
	<dl class="grid_16">
		<?php $i = 0;
			$dtClass = 'grid_3 alpha';
			$ddClass = 'grid_12 suffix_1 omega';
			$altClass = ' altrow ';
		?>
		<span class='<?php $i++;  echo $dtClass; if ($i % 2 == 0) echo $altClass;?>'><?php __('Owner'); ?></span>
		<span class='<?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>'>
			<?php echo $this->Html->link($data['Owner']['username'], array('controller' => 'person', 'action' => 'home', $data['Owner']['id'])); ?>
			&nbsp;
		</span>	
		<span class='<?php $i++;  echo $dtClass; if ($i % 2 == 0) echo $altClass;?>'><?php __('Photos'); ?></span>
		<span class='<?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>'>
			<?php echo $this->Html->link("{$data['Group']['assets_group_count']} photos","photos/{$data['Group']['id']}"); ?>
			&nbsp;
		</span>				
		<span class='<?php $i++;  echo $dtClass; if ($i % 2 == 0) echo $altClass;?>'><?php __('Description'); ?></span>
		<span class='<?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>'>
			<?php echo $data['Group']['description']; ?>
			&nbsp;
		</span>
		<span class='<?php $i++;  echo $dtClass; if ($i % 2 == 0) echo $altClass;?>'><?php __('Membership Policy'); ?></span>
		<span class='<?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>'>
			<?php echo $data['Group']['membership_policy']; ?>
			&nbsp;
		</span>
		<span class='<?php $i++;  echo $dtClass; if ($i % 2 == 0) echo $altClass;?>'><?php __('Invitation Policy'); ?></span>
		<span class='<?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>'>
			<?php echo $data['Group']['invitation_policy']; ?>
			&nbsp;
		</span>
		<span class='<?php $i++;  echo $dtClass; if ($i % 2 == 0) echo $altClass;?>'><?php __('NSFW'); ?></span>
		<span class='<?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>'>
			<?php if(1 == $data['Group']['isNC17']){ echo __('Yes'); } else { echo __('No');}?>
			&nbsp;
		</span>
		<span class='<?php $i++;  echo $dtClass; if ($i % 2 == 0) echo $altClass;?>'><?php __('Last Visit'); ?></span>
		<span class='<?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>'>
			<?php echo $data['Group']['lastVisit']; ?>
			&nbsp;
		</span>
		<span class='<?php $i++;  echo $dtClass; if ($i % 2 == 0) echo $altClass;?>'><?php __('Created On'); ?></span>
		<span class='<?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>'>
			<?php echo $data['Group']['created']; ?>
			&nbsp;
		</span>
	</dl>
</div>	
<?php	$this->Layout->blockEnd(); ?>

<div class="groups view main-div ">

<div id='tab-list-settings'>
<ul class='inline'>
<?php 	
	$xhrSrc = Router::url(array('plugin'=>'', 'action'=>'settings', AppController::$uuid));
	$detailsSrc = $xhrSrc."?xhrview=settings-details";
	$privacySrc = $xhrSrc."?xhrview=settings-privacy";
	$policySrc = $xhrSrc."?xhrview=settings-policy";
	
	$xhrFrom = Configure::read('controller.xhrFrom');
	if (empty($xhrFrom['view'])) {
		$tabName = Session::read('settings.tabName');
		if ($tabName) {
			Session::delete('settings.tabName');
			$xhrFrom['view'] = "settings-{$tabName}";
		}
		else $xhrFrom['view'] = "settings-details";
	}
?>	
	<li class='tab btn'><a id='tab-details' href='<?php echo $detailsSrc ?>' onclick='return SNAPPI.tabSection.selectByCSS(this);'>Details</a></li>
	<li class='tab btn'><a id='tab-privacy' href='<?php echo $privacySrc ?>' onclick='return SNAPPI.tabSection.selectByCSS(this);'>Privacy</a></li>
	<li class='tab btn'><a id='tab-policy' href='<?php echo $policySrc ?>' onclick='return SNAPPI.tabSection.selectByCSS(this);'>Policies</a></li>
</ul>
</div>	

	<div id='tab-view-settings' class="setting xhr-get  prefix_1 grid_14 suffix_1 wrap-v"  xhrSrc='<?php echo $xhrSrc."?xhrview={$xhrFrom['view']}" ?>'  delay='0'></div>	
</div>


