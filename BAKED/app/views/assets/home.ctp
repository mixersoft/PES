<?php
if (empty($this->passedArgs['wide'])) {
	$this->Layout->blockStart('itemHeader');
		echo $this->element('nav/section', array('icon_src'=>$data['Asset']['src_thumbnail'])); 
?>
	<div class='properties placehoder container_16'>
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
<?php echo $this->element('filmstrip') ?>
<div class="assets main-div placeholder">
<div class='element-roll preview'>
	<div id='preview' class='photo placeholder' <?php $size = !empty($this->passedArgs['size']) ?  $this->passedArgs['size'] : 'bp';  echo "size='{$size}' uuid='".AppController::$uuid."'"; ?> >
		<ul class='inline photo-header'>
			<li class="button" onclick="return false;">actions</li>
			<li class="button" onclick="return false;">share</li>
			<li id='set-rating' class="button hide" onclick="return false;"></li>
		</ul>
		<ul class='sizes inline'>
			<li><h3 style='display: inline'>Sizes:</h3></li>
		</ul>
		<div class='preview  grid_11'></div>
		<?php echo $this->element('shotGallery') ?>
	</div>
</div>
<div id='hiddenshots' class='filmstrip placeholder'><h3>Hidden Shots</h3><ul></ul></div>
<?php
	$ajaxSrc = Router::url(Configure::read('passedArgs.min') + array('action'=>'groups', '?'=>array('preview'=>1)));
	echo "<div id='groups-preview-xhr' class='fragment' ajaxSrc='{$ajaxSrc}'></div>";
?>	
<h3>Details</h3>
<blockquote>
<!-- I am not sure if it's good to put more info here, so I add styles in tag temporarily. I will add them in css after we fix the node -->
<div><h4 style="float:left;">History</h4></div>

</blockquote>
	<?php echo $this->element('tags', array('domId'=>'assets-tags', 'data'=>&$asset))?>
	<?php	// tagCloud
		$xhrSrc = array('plugin'=>'', 'controller'=>'tags','action'=>'show', 'filter'=>'Asset');
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
</div>


<div class="related"><?php if (!empty($data['Collection'])):?>
<h3><?php printf(__('Related %s', true), __('Collections', true));?></h3>
<table cellpadding="0" cellspacing="0">
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
	<tr <?php echo $class;?>>
		<td><?php echo $collection['id'];?></td>
		<td><?php echo $collection['title'];?></td>
		<td><?php echo $collection['owner_id'];?></td>
		<td><?php echo $collection['description'];?></td>
		<td><?php echo $collection['markup'];?></td>
		<td><?php echo $collection['src'];?></td>
		<td><?php echo $collection['lastVisit'];?></td>
		<td><?php echo $collection['created'];?></td>
		<td><?php echo $collection['modified'];?></td>
		<td class="actions"><?php echo $this->Html->link(__('View', true), array('controller' => 'collections', 'action' => 'home', $collection['id'])); ?>
		<?php echo $this->Html->link(__('Edit', true), array('controller' => 'collections', 'action' => 'edit', $collection['id'])); ?>
		<?php echo $this->Html->link(__('Delete', true), array('controller' => 'collections', 'action' => 'delete', $collection['id']), null, sprintf(__('Are you sure you want to delete # %s?', true), $collection['id'])); ?>
		</td>
	</tr>
	<?php endforeach; ?>
</table>
	<?php endif; ?></div>
	
<?php 
		$this->Layout->blockStart('lightbox'); 
			echo $this->element('/lightbox'); 
		$this->Layout->blockEnd();

		$this->Layout->blockStart('javascript');
?>
<script type="text/javascript">
	var initOnce = function() {
		var Y = SNAPPI.Y;
		SNAPPI.mergeSessionData();
		var filmstripCfg = {
			type: 'NavFilmstrip',
			castingCall: PAGE.jsonData.castingCall,
			uuid: PAGE.jsonData.controller.xhrFrom.uuid,	// sets .focus
		};
		var fs = new SNAPPI.Gallery(filmstripCfg);
		
		var shotGallery = new SNAPPI.Gallery({
			type: 'ShotGallery'
		});
		SNAPPI.DragDrop.pluginDrop(shotGallery.node);
		
		// SNAPPI.domJsBinder.bindAuditions2Filmstrip();
		SNAPPI.domJsBinder.bindSelected2Page(fs);
		SNAPPI.ajax.init(); 
	
	};
	try {SNAPPI.ajax.fetchXhr; initOnce(); }			// run now for XHR request, or
	catch (e) {PAGE.init.push(initOnce); }	// run from Y.on('domready') for HTTP request
</script>
<?php $this->Layout->blockEnd();?>