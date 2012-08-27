<script  type="text/javascript">
PAGE.setPolicyDefaults = function(privacyValue){
	var Y = SNAPPI.Y;
	var defaultPolicies = <?php echo $this->Js->object($policyDefaultsJson); ?> ;
	var policyDefault = defaultPolicies[privacyValue];
	for (var p in policyDefault) {
		var n = Y.one('#'+p+policyDefault[p]); 
		n.set('checked', 'checked');
	}
};
PAGE.disablePolicyDefaults = function(){
	PAGE.setPolicyDefaults = function(){return};
}
</script>
	<div id='panel-policy' class="create  tab-panel hide ">
		<h3>Policy Settings</h3>
		<?php $radio_attrs = array('legend'=> false,'onclick'=>'PAGE.disablePolicyDefaults();', 'separator'=>'<br />'); ?>
			
		<p>These settings control Group policies.</p>
		
		<h4>Membership</h4>
		<p>The Membership policy for this Group is:</p>
		<div class="radio-group">
		<?php echo $form->radio('membership_policy', $policy['membership'], $radio_attrs );?>
		</div>
		<h4>Invitations</h4>
		<p>Invitations to join this Group can be sent by:</p>
		<div class="radio-group">
		<?php	echo $form->radio('invitation_policy', $policy['invitation'], $radio_attrs );?>
		</div>				
		<h4>Submissions</h4>
		<p>Contributed Photos are:</p>
		<div class="radio-group">
		<?php echo $form->radio('submission_policy', $policy['submission'], $radio_attrs );?>
		</div>	
		<div class='submit'>
			<input id="policy" type="button" class='orange' value="Next" onclick='return PAGE.finish();'></input>
		</div>			
