<?php	

/**
 * @param array $groups - usually $data['Group'] from $Model->find()
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
		// XHR response
		if ($isInner) {
			// from paginator
			echo $this->element('/group/paging-inner', compact('isPreview', 'isWide', 'total', 'controllerAttrs'));
		} else {
			// isPreview  TODO: wrap in .gallery class for trigger consistency???
			// initial page of gallery.group from /groups/home
			if ($isWide) {
				echo $this->element('/group/header-wide', compact('total', 'isPreview', 'state', 'controllerAttrs'));
			} else echo $this->element('/group/header', compact('total', 'isPreview', 'state', 'controllerAttrs'));
			echo $this->element('/group/paging-inner', compact('isPreview', 'isWide', 'total', 'controllerAttrs'));
		}
		
		$this->Layout->blockStart('javascript');
?> 
	<script type="text/javascript">
		SNAPPI.mergeSessionData();
		var listeners, parent = SNAPPI.Y.one('.gallery.group .container') 
		listeners = {
			'ContextMenuClick':{node:parent, type:'Group'}, 
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
		echo $this->element('/group/section-header');
	
?>
<div class='gallery-container'>
		<?php 
			if ($isWide) {
				echo $this->element('/group/header-wide', compact('total', 'isPreview', 'state', 'controllerAttrs'));
			} else echo $this->element('/group/header', compact('total', 'isPreview', 'state', 'controllerAttrs'));
		?>
	<section class="<?php if ($isWide) echo "wide "; ?>gallery group">	
	<?php echo $this->element('/group/paging-inner', compact('isPreview', 'isWide', 'total', 'controllerAttrs')); ?>
	</section>
</div>

<?php $this->Layout->blockStart('javascript'); ?>
	<script type="text/javascript">		
		/**
		 * run after GET request
		 */
		var initOnce = function() {
			try {
				var Y = SNAPPI.Y;
				SNAPPI.mergeSessionData();
				SNAPPI.UIHelper.nav.setDisplayOptions();
				var parent = Y.one('.gallery.group .container');
				var listeners = {
					// 'WindowOptionClick':1, 
					'DisplayOptionClick':null,
					'ContextMenuClick':{node:parent, type:'Group'}, 
					'LinkToClick': {node:parent},					
					'MultiSelect':parent,
				};
				for (var listen in listeners) {
					if (listeners[listen]!==false) SNAPPI.UIHelper.listeners[listen](listeners[listen]);
				}					
<?php if (!$isPreview) echo "SNAPPI.Paginator.paginate_CircleMemberGallery('.gallery.group');" ?>;				
				if (parent.all('.FigureBox.Group').size() == 0) {
					var emptyMsg = Y.one('#markup .empty-circle-gallery-message');
            		if (emptyMsg) parent.append(emptyMsg.removeClass('hide'));	
				}
				Y.fire('snappi:after_GalleryInit', this); 
			} catch (e) {}
		};
		try {
			SNAPPI.xhrFetch.fetchXhr; 
			initOnce(); 
		} catch (e) {
			PAGE.init.push(initOnce); 
		}	// run from Y.on('domready') for HTTP request
	</script>	
<?php $this->Layout->blockEnd(); 
	}
?> 