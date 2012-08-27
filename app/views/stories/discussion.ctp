<?php
if (empty($this->passedArgs['wide'])) {
	$this->Layout->blockStart('itemHeader');
		$badge_src = Stagehand::getSrc($data['Collection']['src_thumbnail'], 'sq');
		echo $this->element('nav/section', compact('badge_src')); 
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
	<span <?php $i++; echo $dtClass; if ($i % 2 == 0) echo $altClass;?>><?php __('Batch Id'); ?></span>
	<span <?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>><?php echo $data['Asset']['batchId']; ?>
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
	</div>
<?php	$this->Layout->blockEnd();	} ?>
<section class="discussion grid_11">	
	<h1><?php __('Discussion'); ?></h1>
	<div id='paging-comments' class="paging-content ">
		<?php echo $this->element('comments/discussion')?>
	</div>	
</section>