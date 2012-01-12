<div id='panel-choose' class="create tab-panel alpha prefix_1 grid_12 suffix_1  omega ">
	<h3>What type of Circle do you want to create?</h3>
	<p>The sharing and privacy settings of your Circle are important to consider. 
		Please review the descriptions below and choose the type of Circle you would like to create. 
		We'll set the default settings to match. 
		However, you will be able to change any of these settings in the following screens.</p>
	<div id='Group567' class="alpha grid_4">
		<div class="center wrap-v">
			<b><?php printf(__('%s', true), __('Public', true));?></b>
			<div class='submit'><input id="group-public"  class='orange' privacy='567' type="button" value="Create" onclick='PAGE.saveChoice(this);return SNAPPI.tabSection.selectByCSS("#tab-details");'></input></div>
		</div>
		<div class="description">
			<ul>
				<li>Anyone can see the Circle or shared Snaps in listings, search results or activity feeds</li>
				<br />
				<li><b>Everyone</b>:</li>
				<li>Anyone can view home page</li>
				<li>Anyone can view shared Snaps</li>
				<li>Anyone can become a member</li>
				<li>Anyone can comment</li>
			</ul>
		</div>
	</div>
	
	<div id='Group119' class=" grid_4 ">
		<div class="center wrap-v">
			<b><?php printf(__('%s', true), __('Members Only', true));?></b>
			<div class='submit'><input id="group-members"  class='orange' privacy='119' type="button" value="Create" onclick='PAGE.saveChoice(this);return SNAPPI.tabSection.selectByCSS("#tab-details");'></input></div>
		</div>
		<div class="description">
			<ul>
				<li>Non-members will <b>NOT</b> see shared Snaps in listings, search results or activity feeds</li>
				<br />
				<li><b>Members only</b>:</li>
				<li>Members can view home page</li>
				<li>Members can view shared Snaps</li>
				<li>Shared photos may appear in members' search results or activity feeds</li>
				<li>Members can comment</li>
				<li>Members can send invitations to join</li>
				<br />
				<li><b>Everyone</b>:</li>
				<li>Anyone can see the Circle in listings, search results or activity feeds</li>
				<li>Anyone can accept an invitation to join</li>
			</ul>
		</div>
	</div>
	
	<div id='Group63' class=" grid_4 omega">
		<div class="center wrap-v">
			<b><?php printf(__('%s', true), __('Private', true));?></b>
			<div class='submit'><input id="group-private"  class='orange' privacy='63'  type="button" value="Create" onclick='PAGE.saveChoice(this);return SNAPPI.tabSection.selectByCSS("#tab-details");'></input></div>
			</div>
		<div class="description">
			<ul>
				<li>Non-members will <b>NOT</b> see the Circle or shared Snaps in listings, search results or activity feeds</li>
				<br />
				<li><b>Members only</b>:</li>
				<li>Members can view home page</li>
				<li>Members can view shared Snaps</li>
				<li>Shared photos may appear in members' search results or activity feeds</li>
				<li>Circle may appear in members' listings, search results and activity feeds</li>
				<li>Members can comment</li>
				<br />
				<li><b>Owner/Admin</b>:</li>
				<li>Owner/Admin can send invitations to join</li>
				<li>Anyone can accept an invitation to join</li>
			</ul>
		</div>
	</div>
</div>
