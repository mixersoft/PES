<?php
	$this->Layout->blockStart('itemHeader');
	echo $this->element('nav/section', array('icon_src'=>$data['Group']['src_thumbnail'], 
		'classLabel'=>$data['Group']['type'],
		'label'=>$data['Group']['title'],
		)); 
	$this->Layout->blockEnd();
// debug($data['Group']);	

	$controllerAlias = Configure::read('controller.alias');
	$previewSrc = Stagehand::getSrc($data['Group']['src_thumbnail'], 'bp');
	$options = array('linkTo'=>Router::url(array('plugin'=>'','controller'=>$controllerAlias, 'action'=>'home', $data['Group']['id']))); 
	if (isset($fields['title'])) $options['title'] = $fields['trim_caption'];
	
	// User badges
	if (!empty($this->params['url']['uuid'])) {
		$from =  $badges[$this->params['url']['uuid']];
		$invitation['from_usernameLinkTo'] = $this->Html->link(ucFirst($from['User']['username']) , $from[0]['linkTo'], array('target'=>'_blank'));	
		$invitation['from_src'] =  $from[0]['src'];
		$invitation['from'] = $from[0]['fullname'] ? "<b>{$from[0]['fullname']}<b> ({$invitation['from_usernameLinkTo']})" : "<b>{$invitation['from_usernameLinkTo']}</b>";
	}
	$invitation['circle'] = $data['Group']['title'];
	$invitation['group_type'] = $data['Group']['type'];
// debug($from);	

	$role = Session::read('Auth.User.role');
	if (!$role || $role == 'GUEST') {
		Session::write('Auth.redirect', env('REQUEST_URI')); 
		$signin_redirect = Router::url('/groups/invitation?register=1');
	}
?>
<section class='invitation prefix_1 grid_14 suffix_1'>
	<h2 class="alpha">Welcome to Snaphappi</h2>
	<div class='wrap grid_14'>
		<div class="alpha grid_2">
			<div class="right">
				<?php echo $this->Html->image($invitation['from_src'], array('url'=>$from[0]['linkTo'])) ?>	
			</div></div>
		<div class="grid_12 omega">
			<p>
				<?php echo String::insert(":from has invited you to join the <b>:circle</b> :group_type at Snaphappi.
				As a member, you will be able share Snaps and connect with other members of this :group_type.", $invitation); ?>
				</p>
			<div class="response wrap prefix_1">
				<?php  
					if (isset($signin_redirect)) {
						echo $this->Form->create('Group', array('url'=>$signin_redirect, 'type'=>'get'));
						echo $this->Form->button("Accept Invitation", array('value'=>"Accept Invitation", 'name'=>'register', 'class'=>'orange'));
						echo $this->Form->end();
					} else {
						echo $this->Form->create('Group', array('action'=>'join'));
						echo $this->Form->hidden('id', array('value'=>$id)); 
						echo $this->Form->hidden('title', array('value'=>$data['Group']['title'])); 
						echo $this->Form->button("Accept Invitation", array('value'=>"Accept Invitation", 'name'=>'data[Group][action]', 'class'=>'orange'));	
						if ($role == 'USER') echo $this->Form->button("Ignore", array('value'=>"Ignore", 'name'=>'data[Group][action]'));	
						echo $this->Form->end();
					} 
				?>			
			</div>
		</div>
	</div>
	<div class='wrap grid_14'>	
		<article class="FigureBox Group bp">
	    	<figure><?php echo $this->Html->image( $previewSrc , $options); ?>
	    		<figcaption>
	    		 <div class="label"><?php $data['Group']['title']  ?></div>
	    		 <ul class="inline extras ">
	    		 	<li class="privacy admin"></li>
	    		 	<li class="members"><a><?php echo $data['Group']['groups_user_count']  ?> Members</a></li>
	    		 	<li class="snaps"><a><?php echo $data['Group']['assets_group_count']  ?> Snaps</a></li>
				</ul></figcaption>
			</figure>
		</article>
	</div>
</section>