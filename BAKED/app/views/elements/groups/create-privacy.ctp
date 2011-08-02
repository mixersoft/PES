	<div id='privacy' class="create placeholder hide ">
		<h3>Privacy Settings</h3>
		<?php $radio_attrs = array('legend'=> false);?>
		<p>These settings control who can see this Group, and reflect your prior choice for group type. However, you can change the group privacy here.</p>

		<h4>Groups and Events</h4>
		<p>Group privacy settings include both listings (i.e. title, description, policies, etc.) and shared content. It is possible to publish listings, but limit content access to members, only.</p>
		<br></br>
		<p>The Groups and Events that I create:</p>
		<?php	
			
			$onclick = "";
			echo $form->radio('privacy_groups', $privacy['Groups'], $radio_attrs+array('onclick'=>'PAGE.setPolicyDefaults(this.value);') );?>
						
		<h4>Secret Key Sharing</h4>
		<p>Regardless of privacy settings, content can also be accessed by secret key. These keys are added to special links which can be selectively shared by email, IM or the web. Note that content accessed by Secret Key will not include links to related content.</p>
		<br></br>
		<p>Show Secret Keys to:</p>
		<?php echo $form->radio('privacy_secret_key', $privacy['SecretKey'], $radio_attrs );?>			
			<div class='submit'>
				<input id="privacy" type="submit" value="Next" onclick='return PAGE.gotoStep(this, "policy");'></input>
			</div>	
	</div>