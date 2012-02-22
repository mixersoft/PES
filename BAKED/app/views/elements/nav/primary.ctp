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
// debug($controllerAttr);	
	if ( AppController::$ownerid ) {	// authenticated
		$sections['Home']=array('label'=>'Home','href'=>'/my/home');
		$sections['Circles']=array('label'=>'Circles','href'=>'/my/groups');
		$sections['Snaps']=array('label'=>'Snaps','href'=>'/my/photos');
		$sections['People']=array('label'=>'People','href'=>'/person/all');
		
		// explore action
		$exploreAction = ($controllerAttr['alias'] == 'my') ? $controllerAttr['action'] : $controllerAttr['alias'] ;
		if ( $exploreAction == 'home' ) $exploreAction = 'photos';
		$sections['Explore']=array('label'=>'Explore','href'=>"/{$exploreAction}/all");
	} else { // visitor
		$sections['Circles']=array('label'=>'Circles','href'=>'/groups/all');
		$sections['Snaps']=array('label'=>'Snaps','href'=>'/photos/all');
		$sections['People']=array('label'=>'People','href'=>'/person/all');		
		$sections['Explore']=array('label'=>'Explore','href'=>"/photos/all");
	}
	
	
	$focus = Session::read("nav.primary");	// [Home,Circles,Snaps,People,'']
	// $sections[$focus]['href'] = "javascript:;"; // unset href for section with focus
	if ($focus) $sections[$focus]['class'] = 'class="focus"';
	$help_status =  ($controllerAttr['name'] == 'Help') ? 'green' : 'blue-gloss'; 
?>
<!--top header start-->
<header class="head container_16">
		<div class="grid_3">
			<h1 class="logo"><a href="/photos/all">
				<img src="<?php echo AppController::$http_static[0]; ?>/static/img/css-gui/snappi-top.png" alt=""></a>
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
				<li class="menu-trigger-create">
					<span class="header-btn green rounded-5"><b>+</b>&nbsp;Create</span>
				</li>
			<?php if ( AppController::$ownerid) { ?>
				<li>
					<span class="grey">Welcome,</span>
					<a id='userAccountBtn' class='menu-open'><?php echo $displayName ?></a>
				</li>	
				<li class="help" title="Ask questions or get help for this page.">
					<span class="header-btn rounded-5 <?php echo $help_status; ?>"  onclick="SNAPPI.UIHelper.nav.showHelp(this);">?</span>
				</li>
				<li><a href="/users/signout">Sign out</a></li>				
			<?php  } else { ?>
				<li class="help" title="Ask questions or get help for this page." >
					<span class="header-btn rounded-5 <?php echo $help_status; ?>"  onclick="SNAPPI.UIHelper.nav.showHelp(this);">?</span>
				</li>
				<li><a href="/users/signin">Sign in</a></li>
				<li><a href="/users/register">Sign up</a></li>
			<?php  } ?>					
		</ul>
		</nav>
</header> <!--top header end-->		

<!--  add help block if not HelpController -->
<?php	if (Configure::read('controller.name')!=='Help') {
			$controllerAttr = Configure::read('controller');
			$topicId = "{$controllerAttr['name']}~{$controllerAttr['alias']}~{$controllerAttr['action']}"; 
		?>	
<section class="help container_16 hide" topics="<?php echo $topicId; ?>">
</section>
<?php } ?>