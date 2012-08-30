<?php
	/**
	 * @param array $members - usually $data['Member'] from $Model->find()
	 */
	$isInner = (!empty($this->params['url']['inner']));
	$isPreview = (!empty($this->params['url']['preview']));
	$isWide = !empty($this->params['named']['wide']);		// fluid layout
	$isXhr = Configure::read('controller.isXhr');
	$paginateModel = Configure::read('paginate.Model');
	$state['displayPage'] = array_filter_keys($this->params['paging'][$paginateModel], array('page', 'count', 'pageCount', 'current'));
	$state['displayPage']['perpage'] = $this->params['paging'][$paginateModel]['options']['limit'] ;
	$total = $state['displayPage']['count'] + 0;	// as int
	$state['displayPage']['total'] = $total;	// as int;
	$controllerAttrs = Configure::read('controller');

	if ($isXhr) {
		// XHR response, no headers for members
		if (!$isInner) {
			if ($isWide) {
				echo $this->element('/member/header-wide', compact('total', 'isPreview', 'state', 'controllerAttrs'));
			} else echo $this->element('/member/header', compact('total', 'isPreview', 'state', 'controllerAttrs'));
		}
		echo $this->element('/member/paging-inner', compact('isPreview', 'isWide', 'total', 'controllerAttrs'));
		
		$this->Layout->blockStart('javascript');
?> 
	<script type="text/javascript">
		SNAPPI.mergeSessionData(); // XHR response
		var listeners, parent = SNAPPI.Y.one('.gallery.collection .container') 
		listeners = {
			'ContextMenuClick':{node:parent, type:'Person'}, 
			'LinkToClick': {node:parent},
		};
		for (var listen in listeners) {
			if (listeners[listen]!==false) SNAPPI.UIHelper.listeners[listen](listeners[listen]);
		}		
	</script>
<?php 
		$this->Layout->blockEnd();	
		
		
	} else {
		
		// HTTP GET response
		
		$this->viewVars['jsonData']['STATE'] = $state;
		echo $this->element('/member/section-header'); 


?>
<div class='gallery-container'>
		<?php 
			if ($isWide) {
				echo $this->element('/member/header-wide', compact('total', 'isPreview', 'state', 'controllerAttrs'));
			} else echo $this->element('/member/header', compact('total', 'isPreview', 'state', 'controllerAttrs'));
		?>
	<section class="<?php if ($isWide) echo "wide "; ?>gallery person">	
	<?php echo $this->element('/member/paging-inner', compact('isPreview', 'isWide', 'total', 'controllerAttrs')); ?>
	
</div>

<?php $this->Layout->blockStart('javascript'); ?>
	<script type="text/javascript">		
		var initOnce = function() {
			try {
				var Y = SNAPPI.Y;
				SNAPPI.mergeSessionData();
				SNAPPI.UIHelper.action.setDisplayOptions();
				var parent = Y.one('.gallery.group .container');
				var listeners = {
					// 'WindowOptionClick':1, 
					'DisplayOptionClick':null,
					'ContextMenuClick':{node:parent, type:'Person'}, 
					'LinkToClick': {node:parent},
					'MultiSelect':null,
				};
				for (var listen in listeners) {
					if (listeners[listen]!==false) SNAPPI.UIHelper.listeners[listen](listeners[listen]);
				}					

<?php if (!$isPreview) echo "SNAPPI.Paginator.paginate_CircleMemberGallery('.gallery.person');" ?>;				
				Y.fire('snappi:after_GalleryInit', this); 
			} catch (e) {}
		};
		try {
			SNAPPI.xhrFetch.fetchXhr; 
			initOnce(); 
		} catch (e) {
			PAGE.init.push(initOnce); 
		}	// run from Y.on('domready') for HTTP request		
		var check;
	</script>	
<?php $this->Layout->blockEnd(); 
	}
?> 
