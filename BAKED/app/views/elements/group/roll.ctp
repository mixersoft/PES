<?php	
	$isPreview = (!empty($this->params['url']['preview']));
	$isWide = !empty($this->params['named']['wide']);		// fluid layout
	$paginateModel = Configure::read('paginate.Model');
	$state['displayPage'] = array_filter_keys($this->params['paging'][$paginateModel], array('page', 'count', 'pageCount', 'current'));
	$state['displayPage']['perpage'] = $this->params['paging'][$paginateModel]['options']['limit'] ;	
	$total = $state['displayPage']['count'] + 0;	// as int
	$state['displayPage']['total'] = $total;	// as int;	
	// xhr response
	if (Configure::read('controller.isXhr')) {
		echo $this->element('/group/paging-inner', compact('isPreview', 'isWide', 'total'));
		
		$this->Layout->blockStart('javascript');
?> 
	<script type="text/javascript">
		SNAPPI.mergeSessionData();
		var parent = SNAPPI.Y.one('.gallery.group .container');
		var listeners = {
			'ContextMenuClick':{node:parent, type:'Group'}, 
			'LinkToClick': {node:parent},
		};
		for (var listen in listeners) {
			if (listeners[listen]!==false) SNAPPI.UIHelper.listeners[listen](listeners[listen]);
		}				
	</script>
<?php 
		$this->Layout->blockEnd();	
		return;
	};
	
	
	// HTTP GET response
/**
 * @param array $groups - usually $data['Group'] from $Model->find()
 */
	$this->viewVars['jsonData']['STATE'] = $state;
	
?>

<?php echo $this->element('/group/section-header'); ?>
<div class='gallery-container'>
		<?php 
			if ($isWide) {
				echo $this->element('/group/header-wide', array('total'=>$total));
			} else echo $this->element('/group/header', array('total'=>$total));
		?>
	<?php echo $this->element('/group/paging-inner', compact('isPreview', 'isWide', 'total')); ?>
</div>

<?php $this->Layout->blockStart('javascript'); ?>
	<script type="text/javascript">		
		/**
		 * run after EACH XHR request
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
					'MultiSelect':null,
				};
				for (var listen in listeners) {
					if (listeners[listen]!==false) SNAPPI.UIHelper.listeners[listen](listeners[listen]);
				}					
				SNAPPI.Paginator.paginate_CircleMemberGallery('.gallery.group');				
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
<?php $this->Layout->blockEnd(); ?> 