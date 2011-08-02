<?php
	echo $this->element('nav/section', array('icon_src'=>$data['Group']['src_thumbnail']));
?>
<div class="groups join">
<h2><?php  echo sprintf(__('Contribute Photostreams to Group <b>%s</b>', true), __($title, true)); ?></h2>
<?php echo $this->Form->create('Group', array('url'=>$this->here))?>
	<table cellpadding="0" cellspacing="0">
	<tr>
			<th><?php echo 'Select';?></th>
			<th><?php echo 'Photostream';?></th>
			<th><?php echo 'Batch Name';?></th>
			<th><?php echo 'Preview';?></th>
	</tr>
	<?php
	$i = 0;
	if (empty($photostreams)) echo __("<tr><td colspan='4'><h4>You have no photos to contribute. Why don't you {$this->Html->link("grab some photos", '/welcome/connect')} now?</h4></td></tr>"); 
	foreach ($photostreams as $photostream):
		$class = null;
		if ($i++ % 2 == 0) {
			$class = ' class="altrow"';
		}
	?>
	<tr<?php echo $class;?>>
		<td><?php echo $this->Form->checkbox('ProviderAccountBatch_'.$i, array('value'=>$photostream['key'])); ?>&nbsp;</td>
		<td><?php echo $photostream['ProviderAccount']['provider_name']; ?>&nbsp;</td>
		<td><?php echo $photostream['Asset']['batchId']; ?>&nbsp;</td>
		<td><?php foreach($photostream['Asset']['previews'] as $src=>$caption){
			$options['url'] = "/assets/view/{$photostream['ProviderAccount']['id']}";
			$options['title'] = $caption;
			$src = getImageSrcBySize($src, 'sq');
			echo $this->Html->image(Session::read('stagepath_baseurl').$src, $options);
		} ?>&nbsp;</td>
	</tr>
<?php endforeach; ?>
	</table>
	<p>
<?php echo $this->Form->hidden('id', array('value'=>$id)); ?>	
<?php echo $this->Form->end('Contribute')?>


</div>		