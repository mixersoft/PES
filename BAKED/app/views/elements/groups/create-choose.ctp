<div id='panel-choose' class="create tab-panel alpha prefix_1 grid_12 suffix_1  omega ">
	<h3><?php printf(__('%s', true), __('What type of Group do you want?', true));?></h3>
	<div id='Group567' class="alpha grid_4">
		<div class="center wrap-v">
			<?php printf(__('%s', true), __('Public', true));?>
			<div class='submit'><input id="group-public"  class='orange' privacy='567' type="button" value="Create" onclick='PAGE.saveChoice(this);return SNAPPI.tabSection.selectByCSS("#tab-details");'></input></div>
		</div>
		<div class="instruction">
			<ul>
				<li><b>Public</b> groups are useful for discussion and content about general
				subjects like Gardening, or Recipes to Share, or geographical
				locations, like Vancouver.</li>
				<li>The group page is public and anyone can join.</li>
				<li>Admins can choose to show or hide discussions and/or group pools
				from non-members.</li>
			</ul>
		</div>
	</div>
	
	<div id='Group119' class=" grid_4 ">
		<div class="center wrap-v">
			<?php printf(__('%s', true), __('Members Only', true));?>
			<div class='submit'><input id="group-members"  class='orange' privacy='119' type="button" value="Create" onclick='PAGE.saveChoice(this);return SNAPPI.tabSection.selectByCSS("#tab-details");'></input></div>
		</div>
		<div class="instruction">
			<ul>
				<li><b>Members-only</b> groups allow owners to maintain control over
				membership.</li>
				<li>Anyone can view the group listing, but an invitation is required to join.</li>
				<li>Only members can view group content.</li>
				<li>Admins can choose to show or hide discussions and/or group pools
				from non-members.</li>
			</ul>
		</div>
	</div>
	
	<div id='Group63' class=" grid_4 omega">
		<div class="center wrap-v">
			<?php printf(__('%s', true), __('Private', true));?>
			<div class='submit'><input id="group-private"  class='orange' privacy='63'  type="button" value="Create" onclick='PAGE.saveChoice(this);return SNAPPI.tabSection.selectByCSS("#tab-details");'></input></div>
			</div>
		<div class="instruction">
			<ul>
				<li>Private groups are useful for families or groups of friends.</li>
				<li>Only group members can view the group content.</li>
				<li>Private groups are completely hidden from group searches, and won't
				appear in public member profiles.</li>
				<li><b>Private</b> groups cannot be made public later.</li>
			</ul>
		</div>
	</div>
</div>
