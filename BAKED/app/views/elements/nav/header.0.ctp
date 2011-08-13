<div id='topxx' class='grid_5 push_7' >
	<div id='menu-header' class="yui3-menu yui3-menu-horizontal">
	<div class="yui3-menu-content">
	<ul>
		<li>Welcome	<a id="userAccountBtn" ><?php 
		
		// TODO: not sure if here is the best place to check if user logins. but it works now. just for testing.
		// by this way, if user doesn't login, there will be no "▼" on the header.
		if((Session::read('Auth.User.displayname')) == ''){
			// when user doesn't login, there's no ▼ and anything else.
		}else {
			echo ucwords(Session::read('Auth.User.displayname')) . " ▼"; 
		}
		?></a>
		</li>
		<li><a href="#">Help</a></li>
		<?php 
			if (AppController::$userid) { ?>
		<li><a href="/users/logout">Sign Out</a></li>
		<?php  } else { ?>
		<li><a href="/users/login">Sign in</a></li>
		<li><a href="/users/register">Sign up</a></li>
		<?php  } ?>
	</ul>
	</div>
	</div>
</div>