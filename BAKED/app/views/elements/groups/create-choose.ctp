<script type="text/javascript">
PAGE.saveChoice = function(o) {
	var Y = SNAPPI.Y;
	var privacy = o.getAttribute('privacy');
	Y.one('#GroupPrivacyGroups'+privacy).set('checked', true);
	// set default policy
	PAGE.setPolicyDefaults(privacy);
	Y.all('form#create-choose #choose > div').addClass('hide');
	Y.one('form#create-choose #choose > div#Group'+privacy).removeClass('hide');
}
</script>
<div id='choose' class="create placeholder ">
	<h3><?php printf(__('%s', true), __('Choose Group Privacy', true));?></h3>
	<div id='Group567' class="column-3">
		<div class="ash_backgroud">
			<?php printf(__('%s', true), __('Public', true));?>
			<div class='submit'><input id="group-public" privacy='567' type="submit" value="Create" onclick='PAGE.saveChoice(this);return PAGE.gotoStep(this, "details");'></input></div>
		</div>
		<div class="instruction">
			<ul>
				<li>Public groups are useful for discussion and content about general
				subjects like Gardening, or Recipes to Share, or geographical
				locations, like Vancouver.</li>
				<li>The group page is public and anyone who wants to can join
				instantly.</li>
				<li>Admins can choose to show or hide discussions and/or group pools
				from non-members.</li>
			</ul>
		</div>
	</div>
	
	<div id='Group119' class="column-3">
		<div class="ash_backgroud">
			<?php printf(__('%s', true), __('Members Only', true));?>
			<div class='submit'><input id="group-members"  privacy='119' type="submit" value="Create" onclick='PAGE.saveChoice(this);return PAGE.gotoStep(this, "details");'></input></div>
		</div>
		<div class="instruction">
			<ul>
				<li>Invite-only public groups are useful for small groups who wish to
				focus on a particular theme, but want to maintain control over
				membership.</li>
				<li>Anyone can view the group page, but the only way to join the group
				is by invitation.</li>
				<li>Admins can choose to show or hide discussions and/or group pools
				from non-members.</li>
			</ul>
		</div>
	</div>
	
	<div id='Group63' class="column-3">
		<div class="ash_backgroud">
			<?php printf(__('%s', true), __('Private', true));?>
			<div class='submit'><input id="group-private"  privacy='63'  type="submit" value="Create" onclick='PAGE.saveChoice(this);return PAGE.gotoStep(this, "details");'></input></div>
			</div>
		<div class="instruction">
			<ul>
				<li>Private groups cannot be made public later.</li>
				<li>Private groups are useful for families or groups of friends.</li>
				<li>Only group members and those who have been invited will be able to
				view the group page.</li>
				<li>Private groups are completely hidden from group searches, and don't
				display on people's profiles amongst groups they belong to.</li>
			</ul>
		</div>
	</div>
</div>
