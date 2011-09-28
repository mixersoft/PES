<?php
if (empty($this->passedArgs['wide'])) {
	$this->Layout->blockStart('itemHeader');
		echo $this->element('nav/section', array('icon_src'=>$data['Asset']['src_thumbnail'])); 
?>
	<div class='properties container_16'>
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
<?php echo $this->element('navFilmstrip') ?>
<section class="photo">
	<div class="preview grid_11">
		<section class='preview-body' <?php $size = !empty($this->passedArgs['size']) ?  $this->passedArgs['size'] : 'bp';  echo "size='{$size}' uuid='".AppController::$uuid."'"; ?> >
			<?php echo $this->element('shotGallery') ?>
		</section>		
		<section class="comments">
	<?php
		$xhrSrc = array('plugin'=>'', 'action'=>'discussion', $this->passedArgs[0]);
		$ajaxSrc = Router::url($xhrSrc);	
		echo $this->element('comments/discussion-fragment', array('ajaxSrc'=>$ajaxSrc));
	?>			
		</section>
	</div>
	<aside id="related-content" class="related-content grid_5">
		<section class='Sharing'>
			<h1>Sharing</h1>
		</section>

		<section class="circle">
			<h1 class="circle">Circles</h1>
	<?php
		$ajaxSrc = Router::url(Configure::read('passedArgs.min') + array('action'=>'groups', '?'=>array('preview'=>1)));
		echo "<div id='groups-preview-xhr' class='xhr-get' xhrSrc='{$ajaxSrc}'></div>";
	?>			
		</section>
		
		<section id="tag-cloud" class="popular">
			<h1 class="popular">Tags</h1>
	<?php	// tagCloud
		$xhrSrc = array('plugin'=>'', 'controller'=>'tags','action'=>'show', 'filter'=>'Asset');
		$xhrFrom = Configure::read('controller.xhrFrom');
		$xhrSrc['?'] = array('xhrfrom'=>implode('~', $xhrFrom));
		$ajaxSrc = Router::url($xhrSrc);
		echo "<div id='tags-preview-xhr' class='xhr-get' xhrSrc='{$ajaxSrc}'></div>";
	?>	
	<?php echo $this->element('tags', array('domId'=>'assets-tags', 'data'=>&$asset))?>
		</section>
		
		<section class="details">
			<h1>Snap Details</h1>
		</section>
		<section class="exif">
			<h1>Snap Exif</h1>
		</section>	
	</aside>
	


</section>
	
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
		
		SNAPPI.domJsBinder.bindSelected2Page(fs);
		SNAPPI.xhrFetch.init(); 
	
	};
	try {SNAPPI.xhrFetch.fetchXhr; initOnce(); }			// run now for XHR request, or
	catch (e) {PAGE.init.push(initOnce); }	// run from Y.on('domready') for HTTP request
</script>
<?php $this->Layout->blockEnd();?>