<?php 
		
	// TODO: not sure if here is the best place to check if user logins. but it works now. just for testing.
	// by this way, if user doesn't login, there will be no "?" on the header.
	if((Session::read('Auth.User.displayname')) == ''){
		$displayName = null;
		// when user doesn't login, there's no ? and anything else.
	}else {
		$displayName = ucwords(Session::read('Auth.User.displayname')); 
	}
	$passed = array_intersect_key($this->passedArgs, array('sort'=>1, 'direction'=>1, 'page'=>1, 'perpage'=>1));	// copy of array
	$controllerAttr = Configure::read('controller');
	$sections = array();
	if ( AppController::$userid) { 
		$sections['Home']=array('label'=>'Home','href'=>'/my/home');
		$sections['Circles']=array('label'=>'Circles','href'=>'/my/groups');
		$sections['Snaps']=array('label'=>'Snaps','href'=>'/my/photos');
		$sections['People']=array('label'=>'People','href'=>'/my/friends');
		
		// explore action
		$exploreAction = ($controllerAttr['alias'] == 'my') ? $controllerAttr['action'] : $controllerAttr['alias'] ;
		if ( $exploreAction == 'home' ) $exploreAction = 'photos';
		$sections['Explore']=array('label'=>'Explore','href'=>"/{$exploreAction}/all");
	} else {
		$sections['Circles']=array('label'=>'Circles','href'=>'/groups/all');
		$sections['Snaps']=array('label'=>'Snaps','href'=>'/photos/all');
		$sections['People']=array('label'=>'People','href'=>'/persons/all');		
		$sections['Explore']=array('label'=>'Explore','href'=>"/photos/all");
	}
	
	
	$focus = Session::read("nav.primary");
	// $sections[$focus]['href'] = "javascript:;"; // unset href for section with focus
	if ($focus) $sections[$focus]['class'] = 'class="focus"';
?>
<!--top header start-->
<header class="head container_16">
		<div class="grid_3">
			<h1 class="logo"><a href="/photos/all">
				<img src="/img/snappi/snappi-top.png" alt=""></a>
				</h1>
		</div>
		<nav class="primary grid_6">
			<ul class="inline">
				<?php
					$markup = "<li :class:><a href=':href:'>:label:</a></li>\n"; 
					$needle = array(':label:', ':href:' , ':class:');
					foreach ($sections as $label => $section) {
						echo str_replace($needle, $section, $markup);
					}
				?>
			</ul>
		</nav>
		<nav class='user grid_7'>
			<ul class="right rightlink">
			<?php if ( AppController::$userid) { ?>
				<li class="create bg-grey"><img src="/css/images/plus-grey.png" alt="" align="absmiddle" class="add">
					<a class='menu-open'>Create</a>
				</li>
				<li>
					<span class="grey">Welcome,</span>
					<a id='userAccountBtn' class='menu-open'><?php echo $displayName ?></a>
				</li>	
				<li><a href="/users/logout">Sign out</a></li>				
			<?php  } else { ?>
				<li><a href="/users/login">Sign in</a></li>
				<li><a href="/users/register">Sign up</a></li>
			<?php  } ?>					
		</ul>
		</nav>
</header> <!--top header end-->		