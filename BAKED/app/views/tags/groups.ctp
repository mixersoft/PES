<?php
if (empty($this->passedArgs['wide'])) {
	$this->Layout->blockStart('itemHeader');
		// echo $this->element('nav/section', array('icon_src'=>$data['Asset']['src_thumbnail'])); 
		echo $this->element('nav/section'); 
?>
<div class="properties container_16">
	<dl class="grid_16">
		<?php $i = 0;
			$dtClass = 'grid_2 alpha';
			$ddClass = 'grid_12 suffix_2 omega';
			$altClass = ' altrow ';
		?>
		<span<?php $i++; echo $dtClass; if ($i % 2 == 0) echo $altClass;?>><?php __('Id'); ?></span>
		<span<?php $ddClass; if ($i++ % 2 == 0) echo $altClass;?>>
			<?php echo $data['Tag']['id']; ?>
			&nbsp;
		</span>
		<span<?php $i++; echo $dtClass; if ($i % 2 == 0) echo $altClass;?>><?php __('Identifier'); ?></span>
		<span<?php $ddClass; if ($i++ % 2 == 0) echo $altClass;?>>
			<?php echo $data['Tag']['identifier']; ?>
			&nbsp;
		</span>
		<span<?php $i++; echo $dtClass; if ($i % 2 == 0) echo $altClass;?>><?php __('Name'); ?></span>
		<span<?php $ddClass; if ($i++ % 2 == 0) echo $altClass;?>>
			<?php echo $data['Tag']['name']; ?>
			&nbsp;
		</span>
		<span<?php $i++; echo $dtClass; if ($i % 2 == 0) echo $altClass;?>><?php __('Keyname'); ?></span>
		<span<?php $ddClass; if ($i++ % 2 == 0) echo $altClass;?>>
			<?php echo $data['Tag']['keyname']; ?>
			&nbsp;
		</span>
		<span<?php $i++; echo $dtClass; if ($i % 2 == 0) echo $altClass;?>><?php __('Weight'); ?></span>
		<span<?php $ddClass; if ($i++ % 2 == 0) echo $altClass;?>>
			<?php echo $data['Tag']['weight']; ?>
			&nbsp;
		</span>
		<span<?php $i++; echo $dtClass; if ($i % 2 == 0) echo $altClass;?>><?php __('Created'); ?></span>
		<span<?php $ddClass; if ($i++ % 2 == 0) echo $altClass;?>>
			<?php echo $data['Tag']['created']; ?>
			&nbsp;
		</span>
		<span<?php $i++; echo $dtClass; if ($i % 2 == 0) echo $altClass;?>><?php __('Modified'); ?></span>
		<span<?php $ddClass; if ($i++ % 2 == 0) echo $altClass;?>>
			<?php echo $data['Tag']['modified']; ?>
			&nbsp;
		</span>
	</dl>
</div>
<?php $this->Layout->blockEnd();	} ?>

<div class="tags groups">
	<?php echo $this->element('/group/roll');?>
	
	<?php	// trends
		$xhrSrc = array('plugin'=>'', 'controller'=>'tags','action'=>'show', 'filter'=>'Group');
		$xhrFrom = Configure::read('controller.xhrFrom');
		$xhrSrc['?'] = array('xhrfrom'=>implode('~', $xhrFrom));
		$ajaxSrc = Router::url($xhrSrc);	
		echo "<div id='tags-preview-xhr' class='xhr-get' xhrSrc='{$ajaxSrc}'></div>";
	?>
</div>

