<div id='nav'>
<div id='top'>
<ul class='inline'>
<?php if (Session::read('Auth.User.id')) { ?>
	<li id='username'>Welcome&nbsp;&nbsp;&nbsp;
		<?php echo $this->Html->link(Session::read('Auth.User.displayname'), "/users/home/".Session::read('Auth.User.id')) ; ?></li>
	<li><a href="#">Help</a></li>
	<li><a href="/users/logout">Sign Out</a></li>
<?php  } else { ?>
	<li><a href="#">Help</a></li>
	<li><a href="/users/login">Sign in</a></li>	
	<li><a href="/users/login">Sign up</a></li>	
<?php  } ?>
</ul>
</div>

<div id='tabs'>
<ul class='inline'>
	<li><a class='tab-see' href="#tab-see">See</a></li>
	<li><a class='tab-prepare' href="#tab-prepare">Prepare</a></li>
	<li><a class='tab-discover'href="#tab-discover">Discover</a></li>
	<li><a class='tab-me' href="#tab-me">Me</a></li>
</ul>
</div>

<div id='nested'>
<ul id='tab-see' class='inline hide'>
	<li><a href="/groups/mine">My Groups</a></li>
	<li><a href="#tab-see">My Galleries</a></li>
	<li><a href="/photos/mine">My Photos</a></li>
	<li><a href="/users/mine">My Circle</a></li>
	<li><a href="#tab-see">My Ratings</a></li>
	<li><a href="#tab-see">My Comments</a></li>
	<li><a href="#tab-see">My Tags</a></li>
	<li><a href="#tab-see">My Favorites</a></li>
	<li id='my-own'><a href="/my/settings/my_own">My Own</a></li>
</ul>
<ul id='tab-prepare' class='inline hide'>
	<li><a href="#tab-prepare">Organize</a></li>
	<li><a href="#tab-prepare">Fix</a></li>
	<li><a href="#tab-prepare">Make</a></li>
</ul>
<ul id='tab-discover' class='inline hide'>
	<li><a href="/groups/all">Groups</a></li>
	<li><a href="#tab-discover">Galleries</a></li>
	<li><a href="/photos/all">Photos</a></li>
	<li><a href="/people/all">People</a></li>
	<li><a href="/tags">Topics</a></li>
	<li><a href="#tab-discover">Top Rated</a></li>
	<li><a href="#tab-discover">Featured</a></li>
	<li><a href="#tab-discover">Most Popular</a></li>
	<li><a href="#tab-discover">Most Active</a></li>
	<li><a href="#tab-discover">Most Recent</a></li>
</ul>
<ul id='tab-me' class='inline hide'>
	<li><a href="/my/settings/my_own">Owned By Me</a></li>
	<li><a href="/my/settings/profile">Profile</a></li>
	<li><a href="/my/settings/contact">Contact Information</a></li>
	<li><a href="/my/settings/privacy">Privacy</a></li>
	<li><a href="/my/settings/notifcations">Notifications</a></li>
	<li><a href="/my/settings/orders">Orders</a></li>
	<li><a href="/my/settings/spending">Spending</a></li>
</ul>
</div>
</div>
