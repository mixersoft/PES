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
				SNAPPI.mergeSessionData();
				SNAPPI.UIHelper.nav.setDisplayOptions();
				var listeners = {
					// 'WindowOptionClick':1, 
					'DisplayOptionClick':1,
					'ContextMenuClick':1, 
					'LinkToClick': 1,
					'MultiSelect':1,
				};
				for (var listen in listeners) {
					SNAPPI.UIHelper.markupGallery[listen](null);
				}					
				SNAPPI.Paginator.paginate_CircleMemberGallery('.gallery.group');				
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