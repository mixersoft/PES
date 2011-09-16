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
		PAGE.goto = function (o) {
			window.location.href = o.options[o.selectedIndex].value;
		} 
		PAGE.myGroups = function(o){
			var set = /selected/.test(o.className) ? null : 1;
			var href = window.location.href;
			window.location.href = SNAPPI.IO.setNamedParams(href, {'filter-me':set});
		}
		PAGE.toggleDisplayOptions  = function(o){
			var Y = SNAPPI.Y;
			try {
				SNAPPI.STATE.showDisplayOptions = SNAPPI.STATE.showDisplayOptions ? 0 : 1;
				PAGE.setDisplayOptions();
			} catch (e) {}
		};
		PAGE.setDisplayOptions = function(){
			var Y = SNAPPI.Y;
			try {
				if (SNAPPI.STATE.showDisplayOptions) {
					Y.one('section.gallery-header li.display-option').addClass('open');
					Y.one('section.gallery-display-options').removeClass('hide');
				} else {
					Y.one('section.gallery-header li.display-option').removeClass('open');
					Y.one('section.gallery-display-options').addClass('hide');
				}	
			} catch (e) {}
		};
		/**
		 * run after EACH XHR request
		 */
		var initOnce = function() {
			try {
				SNAPPI.mergeSessionData();
				PAGE.setDisplayOptions();
				SNAPPI.Paginator.paginate_Grouproll();				
			} catch (e) {}
		};
		try {
			SNAPPI.ajax.fetchXhr; 
			initOnce(); 
		} catch (e) {
			PAGE.init.push(initOnce); 
		}	// run from Y.on('domready') for HTTP request
	</script>	
<?php $this->Layout->blockEnd(); ?> 