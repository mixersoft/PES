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
<?php 
	
	$this->Layout->blockEnd();	} 


	// tagged photos
	$ajaxSrc = Router::url(Configure::read('passedArgs.min') + array('plugin'=>'','action'=>'photos', '?'=>array('gallery'=>1)));
	echo "<div id='gallery-photo-xhr' class='xhr-get' xhrSrc='{$ajaxSrc}' nodelay='1'></div>";
	Configure::write('js.render_lightbox', true);
?>

<?php
	// tagged groups
//	$ajaxSrc = Router::url(array('action'=>'groups', AppController::$uuid));
	$ajaxSrc = Router::url(Configure::read('passedArgs.min') + array('plugin'=>'','action'=>'groups', '?'=>array('preview'=>1)));
	echo "<div id='groups-preview-xhr' class='xhr-get' xhrSrc='{$ajaxSrc}'></div>";
?>	

<?php
	$xhrSrc = array('plugin'=>'', 'controller'=>'tags','action'=>'show');
	$xhrFrom = Configure::read('controller.xhrFrom');
	$xhrSrc['?'] = array('xhrfrom'=>implode('~', $xhrFrom));
	$xhrSrc = Router::url($xhrSrc);
	echo "<div id='tags-preview-xhr' class='xhr-get' xhrSrc='{$xhrSrc}'></div>";
?>	

<?php
	$xhrSrc = array('plugin'=>'', 'action'=>'discussion', $this->passedArgs[0]);
	$xhrSrc = Router::url($xhrSrc);
	echo $this->element('comments/discussion-fragment', array('xhrSrc'=>$xhrSrc));
?>
<script type="text/javascript">
var initOnce = function() {
	// init xhr paging & fetch xhr-gets
	// NOTE: any xhr-gets will bind own PAGE.init() method
	SNAPPI.xhrFetch.init();
};
try {SNAPPI.xhrFetch.fetchXhr; initOnce(); }			// run now for XHR request, or
catch (e) {PAGE.init.push(initOnce); }	// run from Y.on('domready') for HTTP request
</script>
