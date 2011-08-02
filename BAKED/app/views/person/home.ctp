<?php 
	echo $this->element('nav/section', array('icon_src'=>$data['User']['src_thumbnail']));
?>
<?php  	$DEFAULT_SRC_ICON = Configure::read('path.blank_user_photo');	 ?>
<div class="users main-div placeholder" <?php if (Configure::read('controller.alias')=='my'); ?>>
	<div class='properties'>
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
	$ajaxSrc = Router::url($this->passedArgs + array('action'=>'photos', '?'=>array('preview'=>1)));
	echo "<div id='photos-preview-xhr' class='fragment' ajaxSrc='{$ajaxSrc}' nodelay='1'></div>";
?>

<div class="related">
	<?php if (!empty($data['Collection'])):?>
	<h3><?php printf(__('Related %s', true), __('Collections', true));?></h3>
	<table cellpadding = "0" cellspacing = "0">
	<tr>
		<th><?php __('Id'); ?></th>
		<th><?php __('Title'); ?></th>
		<th><?php __('User Id'); ?></th>
		<th><?php __('Description'); ?></th>
		<th><?php __('Markup'); ?></th>
		<th><?php __('Src'); ?></th>
		<th><?php __('LastVisit'); ?></th>
		<th><?php __('Created'); ?></th>
		<th><?php __('Modified'); ?></th>
		<th class="actions"><?php __('Actions');?></th>
	</tr>
	<?php
		$i = 0;
		foreach ($data['Collection'] as $collection):
			$class = null;
			if ($i++ % 2 == 0) {
				$class = ' class="altrow"';
			}
		?>
		<tr<?php echo $class;?>>
			<td><?php echo $collection['id'];?></td>
			<td><?php echo $collection['title'];?></td>
			<td><?php echo $collection['user_id'];?></td>
			<td><?php echo $collection['description'];?></td>
			<td><?php echo $collection['markup'];?></td>
			<td><?php echo $collection['src'];?></td>
			<td><?php echo $collection['lastVisit'];?></td>
			<td><?php echo $collection['created'];?></td>
			<td><?php echo $collection['modified'];?></td>
			<td class="actions">
				<?php echo $this->Html->link(__('View', true), array('controller' => 'collections', 'action' => 'home', $collection['id'])); ?>
				<?php echo $this->Html->link(__('Edit', true), array('controller' => 'collections', 'action' => 'edit', $collection['id'])); ?>
				<?php echo $this->Html->link(__('Delete', true), array('controller' => 'collections', 'action' => 'delete', $collection['id']), null, sprintf(__('Are you sure you want to delete # %s?', true), $collection['id'])); ?>
			</td>
		</tr>
	<?php endforeach; ?>
	</table>
<?php endif; ?>
</div>

<?php
	$ajaxSrc = Router::url($this->passedArgs + array('action'=>'groups', '?'=>array('preview'=>1)));
	echo "<div id='groups-preview-xhr' class='fragment' ajaxSrc='{$ajaxSrc}' ></div>";
?>	
</div>
<?php	// tagCloud
	$xhrSrc = array('plugin'=>'', 'controller'=>'tags','action'=>'show');
	$xhrFrom = Configure::read('controller.xhrFrom');
	$xhrSrc['?'] = array('xhrfrom'=>implode('~', $xhrFrom));
	$ajaxSrc = Router::url($xhrSrc);
	echo "<div id='tags-preview-xhr' class='fragment' ajaxSrc='{$ajaxSrc}' ></div>";
?>	
<script type="text/javascript">
var initOnce = function() {
	// init xhr paging & fetch fragments
	// NOTE: any fragments will bind own PAGE.init() method
	SNAPPI.ajax.init(); 
};
try {SNAPPI.ajax; initOnce(); }			// run now for XHR request, or
catch (e) {PAGE.init.push(initOnce); }	// run from Y.on('domready') for HTTP request
</script>