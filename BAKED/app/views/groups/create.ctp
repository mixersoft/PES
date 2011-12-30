<script type="text/javascript">
PAGE=PAGE || {};	// namespace for page functions
PAGE.gotoStep = function(dom, show){
	try {
		// if we are on the finish tab, disable tab nav
		if (SNAPPI.TabNav.selected.section.name=='Finish') return false;
	} catch(e) {
	} 
	var Y = SNAPPI.Y;
	if (show == 'finish') {
		// show all sections
		Y.all('form > div > div.submit').addClass('hide');	// hide section buttons
//		Y.all('form  div.column-3').setStyle('width','100%');
		Y.all('form > div.create').removeClass('hide'); // show all sections
		Y.all('form > div#choose').addClass('hide'); // show all sections
		Y.all('form > div#create-finish').removeClass('hide');
		
	}
	else {
		Y.all('form > div').addClass('hide');
		Y.one('form > div#'+show).removeClass('hide');
	}
	SNAPPI.TabNav.selectByName({section: 'tab-'+show});
	return dom.id ? (dom.id=='finish') : false;
}
</script>
<div class="groups view placeholder">
	<h2><?php printf(__('Create a New %s', true), __('Group', true)); ?></h2>
	<div class='groups'>
		<div id='section-tabs'>
			<ul class='inline'>
	<?php 	
		$xhrSrc = Router::url(array('plugin'=>'', 'action'=>'create'));
		$chooseSrc = $xhrSrc."?xhrview=create-choose";
		$detailsSrc = $xhrSrc."?xhrview=create-details";
		$privacySrc = $xhrSrc."?xhrview=create-privacy";
		$policySrc = $xhrSrc."?xhrview=create-policy";
		
		$xhrFrom = Configure::read('controller.xhrFrom');
		if (empty($xhrFrom['view'])) {
			$tabName = Session::read('settings.tabName');
			if ($tabName) {
				Session::delete('settings.tabName');
				$xhrFrom['view'] = "create-{$tabName}";
			}
			else $xhrFrom['view'] = "create-choose";
		}
	?>			
				<li class='btn focus'><a id='tab-choose' href='<?php echo $chooseSrc ?>' onclick='return PAGE.gotoStep(this, "choose");'>Choose Group Type</a></li>
				<li class='btn'><a id='tab-details' href='<?php echo $detailsSrc ?>' onclick='return PAGE.gotoStep(this, "details");'>Add Details</a></li>
				<li class='btn'><a id='tab-privacy' href='<?php echo $privacySrc ?>' onclick='return PAGE.gotoStep(this, "privacy");'>Privacy</a></li>
				<li class='btn'><a id='tab-policy' href='<?php echo $policySrc ?>' onclick='return PAGE.gotoStep(this, "policy");'>Policies</a></li>
				<li class='btn'><a id='tab-finish' href='#'  onclick='PAGE.gotoStep(this, "finish"); return false;'>Finish</a></li>
			</ul>
		</div>
		<?php 
			$formOptions['url']=Router::url(array('controller'=>Configure::read('controller.alias'), 'action'=>'create'));
			$formOptions['id']='create-choose';
			echo $this->Form->create('Group', $formOptions); 
		?>		
		<?php echo $this->element("/groups/create-choose"); ?>
		<?php echo $this->element("/groups/create-details"); ?>
		<?php echo $this->element("/groups/create-privacy"); ?>
		<?php echo $this->element("/groups/create-policy"); ?>
		<?php echo $this->element("/groups/create-finish"); ?>
	</div>
</div>
