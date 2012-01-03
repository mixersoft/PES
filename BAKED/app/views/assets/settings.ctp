<?php
		$this->Layout->blockStart('itemHeader');
		$badge_src = Stagehand::getSrc($data['Asset']['src_thumbnail'], 'sq');
		echo $this->element('nav/section', array('badge_src'=>$badge_src));
?>
	<div class='properties hide container_16'>
		<dl class="grid_16">
		<?php $i = 0;
			$dtClass = 'grid_2 alpha';
			$ddClass = 'grid_12 suffix_2 omega';
			$altClass = ' altrow ';
		?>
	<span <?php $i++; echo $dtClass; if ($i % 2 == 0) echo $altClass;?>><?php __('Owner'); ?></span>
	<span <?php echo $ddClass; if ($i++ % 2 == 0) echo $altClass;?>><?php echo $this->Html->link($data['Owner']['username'], array('controller' => 'users', 'action' => 'home', $data['Owner']['id'])); ?>
	&nbsp;</span>
	<span <?php $i++; echo $dtClass; if ($i % 2 == 0) echo $altClass;?>><?php __('Photostream'); ?></span>
	<span <?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>><?php $photostream = "{$data['ProviderAccount']['display_name']}@{$data['ProviderAccount']['provider_name']}";
	echo $this->Html->link($photostream, array('controller' => 'provider_accounts', 'action' => 'view', $data['ProviderAccount']['id'])); ?>
	&nbsp;</span>
	<span <?php $i++; echo $dtClass; if ($i % 2 == 0) echo $altClass;?>><?php __('Avg Rating'); ?></span>
	<span <?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>><?php echo $this->Html->link($data['SharedEdit']['score'], array('controller' => 'shared_edits', 'action' => 'view', $data['Asset']['asset_hash'])); ?>
	&nbsp;</span>
	<span <?php $i++; echo $dtClass; if ($i % 2 == 0) echo $altClass;?>><?php __('Date Taken'); ?></span>
	<span <?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>><?php echo $data['Asset']['dateTaken']; ?>
	&nbsp;</span>
	<span <?php $i++; echo $dtClass; if ($i % 2 == 0) echo $altClass;?>><?php __('Camera Id'); ?></span>
	<span <?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>><?php echo $data['Asset']['cameraId']; ?>
	&nbsp;</span>
	<span <?php $i++; echo $dtClass; if ($i % 2 == 0) echo $altClass;?>><?php __('Is Flash'); ?></span>
	<span <?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>><?php if(1 == $data['Asset']['isFlash']){ __('Yes'); } else { __('No'); } ?>
	&nbsp;</span>
	<span <?php $i++; echo $dtClass; if ($i % 2 == 0) echo $altClass;?>><?php __('Is RGB'); ?></span>
	<span <?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>><?php if(1 == $data['Asset']['isRGB']) { __('Yes'); } else { __('No'); } ?>
	&nbsp;</span>
	<span <?php $i++; echo $dtClass; if ($i % 2 == 0) echo $altClass;?>><?php __('Upload Id'); ?></span>
	<span <?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>><?php echo $data['Asset']['uploadId']; ?>
	&nbsp;</span>
	<span <?php $i++; echo $dtClass; if ($i % 2 == 0) echo $altClass;?>><?php __('Uploaded On'); ?></span>
	<span <?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>><?php echo  $this->Time->nice($data['Asset']['batchId']); ?>
	&nbsp;</span>
	<span <?php $i++; echo $dtClass; if ($i % 2 == 0) echo $altClass;?>><?php __('Caption'); ?></span>
	<span <?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>><?php echo $data['Asset']['caption']; ?>
	&nbsp;</span>
	<span <?php $i++; echo $dtClass; if ($i % 2 == 0) echo $altClass;?>><?php __('Keyword'); ?></span>
	<span <?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>><?php echo $data['Asset']['keyword']; ?>
	&nbsp;</span>
	<span <?php $i++; echo $dtClass; if ($i % 2 == 0) echo $altClass;?>><?php __('Created On'); ?></span>
	<span <?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>><?php echo $data['Asset']['created']; ?>
	&nbsp;</span>
		</dl>
			<!--
			<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Src Thumbnail'); ?></dt>
			<dd<?php if ($i++ % 2 == 0) echo $class;?>>
				<?php echo $data['Asset']['src_thumbnail']; ?>
				&nbsp;
			</dd>
			<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Json Src'); ?></dt>
			<dd<?php if ($i++ % 2 == 0) echo $class;?>>
				<?php echo $data['Asset']['json_src']; ?>
				&nbsp;
			</dd>
			<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Json Exif'); ?></dt>
			<dd<?php if ($i++ % 2 == 0) echo $class;?>>
				<?php echo $data['Asset']['json_exif']; ?>
				&nbsp;
			</dd>
			<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Json Iptc'); ?></dt>
			<dd<?php if ($i++ % 2 == 0) echo $class;?>>
				<?php echo $data['Asset']['json_iptc']; ?>
				&nbsp;
			</dd>
			-->		
	</div>
<?php	$this->Layout->blockEnd(); ?>
<div class="assets view main-div placeholder">
<div class="assets">
	<div class='img placeholder'>
		<ul class='sizes inline'>
			<li><h3 style='display:inline'>Sizes:</h3></li>
			<?php $sizes = array('sq'=>'Square', 'tn'=>'Thumbnail', 'bs'=>'Small', 'bm'=>'Medium', 'bp'=>'Preview'); 
				foreach($sizes as $size=>$label) {
					echo "<li>{$this->Html->link($label, setNamedParam($this->params['url'], 'size', $size))}</li>";
				}
			?>
		</ul>
		<?php $size = isset($this->params['named']['size'])  ? $this->params['named']['size'] : 'bm';
				$src = Stagehand::getSrc($data['Asset']['src_thumbnail'], $size);
				echo $this->Html->image($src);
			?>
	</div>	
	
<div id='section-tabs'>
<ul class='inline'>
	<?php 		
		$xhrSrc = Router::url(array('plugin'=>'', 'action'=>'settings', AppController::$uuid));
		$detailsSrc = $xhrSrc."?xhrview=settings-details";
		$privacySrc = $xhrSrc."?xhrview=settings-privacy";
		
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
	<li class='btn'><a id='tab-details' href='<?php echo $detailsSrc ?>' onclick='return SNAPPI.UIHelper.nav.gotoTab(this);'>Details</a></li>
	<li class='btn'><a id='tab-privacy' href='<?php echo $privacySrc ?>' onclick='return SNAPPI.UIHelper.nav.gotoTab(this);'>Privacy</a></li>
</ul>
</div>	
<div id='tab-section' class="setting  xhr-get  prefix_1 grid_14 suffix_1 wrap-v"  xhrSrc='<?php echo $xhrSrc."?xhrview={$xhrFrom['view']}" ?>' nodelay='1'>
</div>	


	
	
	
</div>
</div>


