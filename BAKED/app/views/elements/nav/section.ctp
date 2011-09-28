<?php
$DEFAULT_SRC_ICON = Configure::read('path.blank_user_photo');
$ICON_SIZE = 'sq~';
// Session::read('stagepath_baseurl').
$src = isset($icon_src) ? getImageSrcBySize(Session::read('stagepath_baseurl').$icon_src, $ICON_SIZE)
: $DEFAULT_SRC_ICON;

//debug(Configure::read('controller'));
$controllerAttrs = Configure::read('controller');
if (AppController::$uuid) {
	$trail = Session::read("lookup.trail.".$controllerAttrs['keyName']);
	$label = $trail['label'];
	$classLabel = $trail['classLabel']; // AppController::cacheClickStream(): $this->displayName for current context
} else {
	// /controller/all pages
	$classLabel = $this->name;
	$label = 'Discover';
}
$context = Session::read('lookup.context');
$contextKeyName = $context['keyName'];
$actions = array();
$passed = array_diff_key($this->passedArgs, array('sort'=>1, 'direction'=>1, 'page'=>1, 'perpage'=>1));	// copy of array
// remove sort,direction, page, perpage from passed
switch ($classLabel) {
	case 'person': // $controller->keyName,
	case 'Users':
		$home = array('action'=>'all');
		$actions['Most Recent'] = array('action'=>'most_recent');
		$actions['Most Active'] = array('action'=>'most_active');
		$actions['Most Photos'] = array('action'=>'most_photos');
		$actions['Most Groups'] = array('action'=>'most_groups');
		$actions['Most Contributions'] = '#';
		$classLabel = 'People';
		break;
	case 'Groups':
//	case 'events':
		$home = array('action'=>'all');
		$actions['Most Recent'] = array('action'=>'most_recent');
		$actions['Most Active'] = array('action'=>'most_active');
		$actions['Most Members'] = array('action'=>'most_members');
		$actions['Most Photos'] = array('action'=>'most_photos');
		$actions['Public Groups'] = array('action'=>'open');
		$actions['Create'] = array('action'=>'create');
		break;
	case 'Assets':
		$home = array('action'=>'all');
		$actions['Most Recent'] = array('action'=>'most_recent');
		$actions['Top Rated'] = array('action'=>'top_rated');
		$actions['Most Active'] = array('action'=>'most_active');
		$actions['Most Views'] = array('action'=>'most_views');
		$classLabel = 'Photos';
		break;
	case 'Tags':
		$home = array('action'=>'all');
		$actions['Most Common'] = array('action'=>'most_common');
		$actions['Most Recent'] = array('action'=>'most_recent');
		$actions['Most Active'] = array('action'=>'most_active');
		break;
	case 'Me': // $controller->displayName, custom attribute;
		unset($passed[0]);	// remove from url, then continue
		$label = Session::read('Auth.User.displayname');
	case 'Person': // $controller->displayName, custom attribute;
		$home = array('action'=>'home');
		if ($classLabel == 'Person') $home[0]= $passed[0];
//		$actions['Pin'] = $this->passedArgs + array('context'=>$controllerAttrs['keyName']);
		$actions['Photos'] = array('action'=>'photos');
		$actions['Groups'] = array('action'=>'groups');
		$actions['Trends'] = $this->action=='home' ? '#trends' : array('action'=>'trends');  //array('action'=>'home', 0=>null, 1=>'#trends');
		$actions['Followers'] = array('action'=>'#');
		$actions['Photostreams'] = array('action'=>'photostreams',0=>$trail['uuid']);
//		$actions['More...'] = array('action'=>'home');
		$moreActions['Create a New Group'] = array('controller'=>'groups','action'=>'create');
		if (AppController::$writeOk ) {
			$moreActions['Upload Photos from My Desktop'] = array('action'=>'upload');
			$moreActions['Settings'] = array('action'=>'settings');
		}
		
		break;
	case 'Group':
	case 'Event':
		$home = array('action'=>'home', $passed[0]);
//		$actions['Pin'] = $this->passedArgs + array('context'=>$controllerAttrs['keyName']);
		$actions['Photos'] = array('action'=>'photos');
		$actions['Members'] = array('action'=>'members');
		$actions['Discussion'] = $this->action=='home' ? '#discussion' : array('action'=>'discussion', 0=>null);
		$actions['Trends'] = $this->action=='home' ? '#trends' : array('action'=>'trends');  // array('action'=>'home', 0=>null, 1=>'#trends');
		$actions['Tags'] = $this->action=='home' ? '#tags' : array('action'=>'home', 0=>null, 1=>'#tags');
		$actions['Photostreams'] = array('action'=>'photostreams',0=>$context['uuid']);
//		$actions['More...'] = array('action'=>'home');
		$isMember = in_array(AppController::$uuid, Permissionable::getGroupIds());
		$isOwner = $this->viewVars['data']['Group']['owner_id'] == AppController::$userid;
		if ($isMember || $isOwner) {
			$moreActions['Contribute'] = array('action'=>'contribute');
			// TODO: check group invitation Policy to determine where to show invite link
			$moreActions['Invite'] = array('action'=>'invite');
		} else {
			$moreActions['Join'] = array('action'=>'join');
		}
		$moreActions['Create a New Group'] = array('action'=>'create');
		if (AppController::$writeOk ) {
			$moreActions['Settings'] = array('action'=>'settings');
			// TODO: add a better confirmation dialog for delete
			$moreActions['Delete'] = array('action'=>'delete', 'confirm'=>'Are you sure you want to DELETE this group?');
		}
		break;
	case 'Photo':
		$home = array('action'=>'home', $passed[0]);
		$actions['Groups'] = array('action'=>'groups');
		$actions['Discussion'] =  $this->action=='home' ? '#discussion' : array('action'=>'discussion', 0=>null);
		$actions['Tags'] = array('action'=>'home', 0=>null, 1=>'#tags');
		$actions['Share'] = array('action'=>'share');
//		$actions['More...'] = array('action'=>'home');
		$moreActions['Create a New Group'] = array('controller'=>'groups','action'=>'create');
		if (AppController::$writeOk ) {
			$moreActions['Settings'] = array('action'=>'settings');
			// TODO: add a better confirmation dialog for delete
			$moreActions['Delete'] = array('action'=>'delete', 'confirm'=>'Are you sure you want to DELETE this photo from your account?');
			$moreActions['UnShare'] = array('action'=>'unshare', 'confirm'=>'Are you sure you want to UNSHARE this photo from all groups?');
		}
		$moreActions['Set as My Photo'] = array('action'=>'set_as_photo', 0=>null);
		// TODO: we should check writeOk on Group
		if ($contextKeyName=='Group' ) {
			$gid = $context['uuid'];
			if (ClassRegistry::init('Group')->hasPermission('write',$gid)) {
				$context = Session::read("lookup.context");
				$moreActions['Set as the '."{$context['classLabel']} {$context['label']}".' cover photo'] = array('action'=>'set_as_group_cover', 0=>null, 1=>$gid);
			}
		}
		$moreActions['Substitutes'] = array('action'=>'substitutes', 0=>null);
		if (empty($data['Asset']['substitute'])) {
			$moreActions['Substitutes']['options'] = array('class'=> 'hide'); 
		}
		$moreActions['Neighbors'] = array('action'=>'neighbors', 0=>null);
		if (!isset($ccid)) {
			$moreActions['Neighbors']['options'] = array('class'=> 'hide'); 
		} else {
			$moreActions['Neighbors'][1] = $ccid; 
		}
		break;
	case 'Tag': // $controller->displayName, custom attribute;
		$home = array('action'=>'home', $passed[0]);
//		$actions['Pin'] = $this->passedArgs + array('context'=>$controllerAttrs['keyName']);
		$actions['Photos'] = array('action'=>'photos');
		$actions['Groups'] = array('action'=>'groups');
		$actions['Trends'] = $this->action=='home' ? '#trends' : array('action'=>'trends');  // array('action'=>'home', 0=>null, 1=>'#trends');
		$actions['Discussion'] =  $this->action=='home' ? '#discussion' : array('action'=>'discussion', 0=>null);
		break;
}
?>
<section class='item-header container_16'>
	<div class='wrap'>
		<ul class="inline grid_16">
			<li class='thumbnail sq'><?php 
				$img = $this->Html->image($src, array('width'=>75, 'height'=>75));
				$uuid = AppController::$userid  == AppController::$uuid ? array() : array(AppController::$uuid);
				echo $this->Html->link($img, array('action'=>'home')+$uuid , array('escape'=>false)); 
				?></li>
			<li>
				<div class='item-class'><?php echo $classLabel; ?></div>
				<h1 class='label'>
				<?php 
					// $options = ($label=='Guest') ? array('title'=>"Your guest id is ".AppController::$uuid) : array(); 
					// echo $this->Html->link(ucwords($label), $home, $options); 
					echo ucwords($label);
					?>
				</h1>
			</li>
		</ul>
	</div>
</section>
