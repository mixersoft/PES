<?php
	echo $this->element('nav/section', array('icon_src'=>$data['Group']['src_thumbnail']));
?>
<?php 	
	$isMember = in_array($data['Group']['id'], Permissionable::getGroupIds());
?>
<div class="groups main-div placeholder">

<div class="related photos">
<?php 
if ($browseContentOk) {  
	$ajaxSrc = Router::url($this->passedArgs + array('action'=>'photos', '?'=>array('preview'=>1)));
	echo "<div id='photos-preview-xhr' class='fragment' ajaxSrc='{$ajaxSrc}' nodelay='1'></div>";
	Configure::write('js.render_lightbox', true);
} 
?>
</div>
<div class="related collections">
	<?php if (!empty($data['Collection'])):?>
	<h3><?php printf(__('Related %s', true), __('Collections', true));?></h3>
	<?php if ($browseContentOk) { ?>
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
			<td><?php echo $collection['owner_id'];?></td>
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
<?php }?>
<?php endif; ?>
</div>

<?php 
if ($browseContentOk) { 
	$ajaxSrc = Router::url($this->passedArgs + array('action'=>'fragment', 'a'=>'members', 'e'=>'preview-members'));
	$ajaxSrc = Router::url($this->passedArgs + array('action'=>'members', '?'=>array('preview'=>1)));
	echo "<div id='members-preview-xhr' class='fragment' ajaxSrc='{$ajaxSrc}'></div>";
}
?>	
	
<div class="properties">	
	<dl class="grid_16">
		<?php $i = 0;
			$dtClass = 'grid_3 alpha';
			$ddClass = 'grid_12 suffix_1 omega';
			$altClass = ' altrow ';
		?>
		<span class='<?php $i++;  echo $dtClass; if ($i % 2 == 0) echo $altClass;?>'><?php __('Owner'); ?></span>
		<span class='<?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>'>
			<?php echo $this->Html->link($data['Owner']['username'], array('controller' => 'users', 'action' => 'home', $data['Owner']['id'])); ?>
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
		<span class='<?php $i++;  echo $dtClass; if ($i % 2 == 0) echo $altClass;?>'><?php __('Is NC17'); ?></span>
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

<?php if ($browseContentOk) { ?>	
	<?php echo $this->element('tags', array('domId'=>'groups-tags', 'data'=>&$group))?>
	<?php	// tagCloud
		$xhrSrc = array('plugin'=>'', 'controller'=>'tags','action'=>'show');
		$xhrFrom = Configure::read('controller.xhrFrom');
		$xhrSrc['?'] = array('xhrfrom'=>implode('~', $xhrFrom));
		$ajaxSrc = Router::url($xhrSrc);
		echo "<div id='tags-preview-xhr' class='fragment' ajaxSrc='{$ajaxSrc}'></div>";
	?>
	<?php
		$xhrSrc = array('plugin'=>'', 'action'=>'discussion', $this->passedArgs[0]);
		$ajaxSrc = Router::url($xhrSrc);	
		echo $this->element('comments/discussion-fragment', array('ajaxSrc'=>$ajaxSrc));
	?>
<?php }?>	
</div>
<script type="text/javascript">
var initOnce = function() {
	// init xhr paging & fetch fragments
	// NOTE: any fragments will bind own PAGE.init() method
	SNAPPI.ajax.init(); 
};
try {SNAPPI.ajax; initOnce(); }			// run now for XHR request, or
catch (e) {PAGE.init.push(initOnce); }	// run from Y.on('domready') for HTTP request
</script>
