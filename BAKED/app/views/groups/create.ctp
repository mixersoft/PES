<script type="text/javascript">
PAGE=PAGE || {};	// namespace for page functions
PAGE.finish = function(){
	SNAPPI.tabSection.selectByCSS("#tab-finish");
	SNAPPI.tabSection.disable();
	var Y = SNAPPI.Y;
	// show all sections
	Y.all('form div.submit').addClass('hide');	// hide section buttons
	Y.all('form .create.tab-panel').removeClass('hide').addClass('review'); // show all sections
	Y.all('form > div#panel-choose').addClass('hide'); // except 1st panel, choose
	// Y.all('form > div#create-finish').removeClass('hide');
	return false;
}
PAGE.saveChoice = function(o) {
	var Y = SNAPPI.Y;
	var privacy = o.getAttribute('privacy');
	Y.one('#GroupPrivacyGroups'+privacy).set('checked', true);
	// set default policy
	PAGE.setPolicyDefaults(privacy);
	Y.all('form#create-choose #panel-choose > div').addClass('hide');
	Y.one('form#create-choose #panel-choose > div#Group'+privacy).removeClass('hide');
}
</script>
<?php
	$this->Layout->blockStart('itemHeader');
		$type = 'Circle'; // Group Event Wedding
		$badge_src = Stagehand::getSrc(null, 'sq', $type);
		echo $this->element('nav/section', 
			array('badge_src'=>$badge_src,
				// 'classLabel'=>$type,
				'label'=>"Create a New {$type}",
		));
	$this->Layout->blockEnd();
?>
<div class="groups view ">
	<div class='groups'>
		<div id='tab-list-settings'>
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
				<li class='tab btn focus' ><a id='tab-choose' href='<?php echo $chooseSrc ?>' onclick='return SNAPPI.tabSection.selectByCSS(this);'>Choose Type</a></li>
				<li class='tab btn'><a id='tab-details' href='<?php echo $detailsSrc ?>' onclick='return SNAPPI.tabSection.selectByCSS(this);'>Add Details</a></li>
				<li class='tab btn'><a id='tab-privacy' href='<?php echo $privacySrc ?>' onclick='return SNAPPI.tabSection.selectByCSS(this);'>Privacy</a></li>
				<li class='tab btn'><a id='tab-policy' href='<?php echo $policySrc ?>' onclick='return SNAPPI.tabSection.selectByCSS(this);'>Policies</a></li>
				<li class='tab btn'><a id='tab-finish' href='#'  onclick='PAGE.finish(); return false;'>Finish</a></li>
			</ul>
		</div>
		<?php 
			$formOptions['url']=Router::url(array('controller'=>Configure::read('controller.alias'), 'action'=>'create'));
			$formOptions['id']='create-choose';
			echo $this->Form->create('Group', $formOptions); 
		?>		
		<div id='tab-view-settings' class="tab-view-settings prefix_1 grid_14 suffix_1 wrap-v">
			<?php echo $this->element("/groups/create-choose"); ?>
			<?php echo $this->element("/groups/create-details"); ?>
			<?php echo $this->element("/groups/create-privacy"); ?>
			<?php echo $this->element("/groups/create-policy"); ?>
			<?php echo $this->element("/groups/create-finish"); ?>
		</div>
	</div>
</div>
