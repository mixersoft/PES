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
		var initOnce = function() {
			try {
				SNAPPI.mergeSessionData();
				SNAPPI.UIHelper.nav.setDisplayOptions();
				var parent = SNAPPI.Y.one('.gallery.group .container');
				var listeners = {
					// 'WindowOptionClick':1, 
					'DisplayOptionClick':null,
					'ContextMenuClick':{node:parent, type:'Person'}, 
					'MultiSelect':null,
				};
				for (var listen in listeners) {
					if (listeners[listen]!==false) SNAPPI.UIHelper.listeners[listen](null);
				}					

				SNAPPI.Paginator.paginate_CircleMemberGallery('.gallery.person');
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
<?php $this->Layout->blockEnd(); ?> 
