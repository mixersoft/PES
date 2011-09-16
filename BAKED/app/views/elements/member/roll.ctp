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
		echo $this->element('/member/paging-inner', compact('isPreview', 'isWide', 'total'));
		
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
 * @param array $members - usually $data['Member'] from $Model->find()
 */

// save for jsonData ouput 
$this->viewVars['jsonData']['STATE'] = $state;


?>

<?php echo $this->element('/member/section-header'); ?>
<div class='gallery-container'>
		<?php 
			if ($isWide) {
				echo $this->element('/member/header-wide', array('total'=>$total));
			} else echo $this->element('/member/header', array('total'=>$total));
		?>
	<?php echo $this->element('/member/paging-inner', compact('isPreview', 'isWide', 'total')); ?>
</div>

<?php $this->Layout->blockStart('javascript'); ?>
	<script type="text/javascript">		
		PAGE.goto = function (o) {
			window.location.href = o.options[o.selectedIndex].value;
		}; 
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
		var initOnce = function() {
			try {
				SNAPPI.mergeSessionData();
				PAGE.setDisplayOptions();
				SNAPPI.Paginator.paginate_Gallery('.gallery.person');
			} catch (e) {}
		};
		try {
			SNAPPI.ajax.fetchXhr; 
			initOnce(); 
		} catch (e) {
			PAGE.init.push(initOnce); 
		}	// run from Y.on('domready') for HTTP request		
		var check;
	</script>	
<?php $this->Layout->blockEnd(); ?> 
