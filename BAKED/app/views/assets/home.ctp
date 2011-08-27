<?php
echo $this->element('nav/section', array('icon_src'=>$data['Asset']['src_thumbnail']));
?>
<?php Configure::write('js.render_lightbox', true); ?>
<script type="text/javascript">
var initOnce = function() {
	var Y = SNAPPI.Y;
	SNAPPI.mergeSessionData();
	SNAPPI.domJsBinder.bindAuditions2Filmstrip();
	SNAPPI.domJsBinder.bindSelected2Preview();
	
	SNAPPI.ajax.init(); 

};
try {SNAPPI.ajax; initOnce(); }			// run now for XHR request, or
catch (e) {PAGE.init.push(initOnce); }	// run from Y.on('domready') for HTTP request
</script>
<div class="assets main-div placeholder">
<div class='element-roll preview'>
	<div id='neighbors' class='photo filmstrip placeholder' ></div>
	<div id='preview' class='photo placeholder' <?php $size = !empty($this->passedArgs['size']) ?  $this->passedArgs['size'] : 'bp';  echo "size='{$size}' uuid='".AppController::$uuid."'"; ?> >
		<ul class='inline photo-header'>
			<li class="button" onclick="return false;">actions</li>
			<li class="button" onclick="return false;">share</li>
			<li id='set-rating' class="button hide" onclick="return false;"></li>
		</ul>
		<ul class='sizes inline'>
			<li><h3 style='display: inline'>Sizes:</h3></li>
		</ul>
		<div class='preview'>
		</div>
	</div>
</div>
<div id='hiddenshots' class='filmstrip placeholder'><h3>Hidden Shots</h3><ul></ul></div>
<?php
	$ajaxSrc = Router::url($this->passedArgs + array('action'=>'groups', '?'=>array('preview'=>1)));
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

