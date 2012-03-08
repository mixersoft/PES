<?php
	// debug($data['Group']);	
	$isExpress = !empty($this->params['url']['express']);
	$role = AppController::$role;
	$signin_redirect =  $this->here;  // Router::url(env('REQUEST_URI'));
		
	$controllerAlias = Configure::read('controller.alias');
	$previewSrc = Stagehand::getSrc($data['Group']['src_thumbnail'], 'bp');
	$options = array('linkTo'=>Router::url(array('plugin'=>'','controller'=>$controllerAlias, 'action'=>'home', $data['Group']['id']))); 
	if (isset($fields['title'])) $options['title'] = $fields['trim_caption'];
	
	// User badges
	if (!empty($this->params['url']['uuid'])) {
		$from =  $badges[$this->params['url']['uuid']];
		$tokens['from_usernameLinkTo'] = $this->Html->link(ucFirst($from['User']['username']) , $from[0]['linkTo'], array('target'=>'_blank'));	
		$tokens['from_src'] =  $from[0]['src'];
		$tokens['from'] = $from[0]['fullname'] ? "<b>{$from[0]['fullname']}<b> ({$tokens['from_usernameLinkTo']})" : "<b>{$tokens['from_usernameLinkTo']}</b>";
	}
	$tokens['circle'] = ucfirst($data['Group']['title']);
	$tokens['group_type'] = $data['Group']['type'];
	// debug($from);	
	// debug($signin_redirect);
	$title = String::insert("Join :circle", $tokens);
	$body = String::insert("Join the <b>:circle</b> :group_type at Snaphappi. As a member, you will be able share Snaps and connect with other members of this :group_type.", $tokens);
	
	// add listeners to start
	$this->viewVars['jsonData']['listeners']['WindowOptionClick'] = null;
	
	$this->Layout->blockStart('itemHeader');
		$badge_src = Stagehand::getSrc($data['Group']['src_thumbnail'], 'sq', $data['Group']['type']);
		$classLabel = $data['Group']['type'];
		$label = $data['Group']['title'];
		echo $this->element('nav/section', compact('badge_src', 'classLabel', 'label'));
?>
<div class="properties hide container_16">	
	<dl class="grid_16">
		<?php $i = 0;
			$dtClass = 'grid_3 alpha';
			$ddClass = 'grid_12 suffix_1 omega';
			$altClass = ' altrow ';
		?>
		<span class='<?php $i++;  echo $dtClass; if ($i % 2 == 0) echo $altClass;?>'><?php __('Owner'); ?></span>
		<span class='<?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>'>
			<?php echo $this->Html->link($data['Owner']['username'], array('controller' => 'person', 'action' => 'home', $data['Owner']['id'])); ?>
			&nbsp;
		</span>	
		<span class='<?php $i++;  echo $dtClass; if ($i % 2 == 0) echo $altClass;?>'><?php __('Photos'); ?></span>
		<span class='<?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>'>
			<?php echo $this->Html->link("{$data['Group']['assets_group_count']} photos","photos/{$data['Group']['id']}"); ?>
			&nbsp;
		</span>				
		<span class='<?php $i++;  echo $dtClass; if ($i % 2 == 0) echo $altClass;?>'><?php __('Description'); ?></span>
		<span class='<?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>'>
			<?php echo $data['Group']['description']; ?>
			&nbsp;
		</span>
		<span class='<?php $i++;  echo $dtClass; if ($i % 2 == 0) echo $altClass;?>'><?php __('Membership Policy'); ?></span>
		<span class='<?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>'>
			<?php echo $data['Group']['membership_policy']; ?>
			&nbsp;
		</span>
		<span class='<?php $i++;  echo $dtClass; if ($i % 2 == 0) echo $altClass;?>'><?php __('Invitation Policy'); ?></span>
		<span class='<?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>'>
			<?php echo $data['Group']['invitation_policy']; ?>
			&nbsp;
		</span>
		<span class='<?php $i++;  echo $dtClass; if ($i % 2 == 0) echo $altClass;?>'><?php __('NSFW'); ?></span>
		<span class='<?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>'>
			<?php if(1 == $data['Group']['isNC17']){ echo __('Yes'); } else { echo __('No');}?>
			&nbsp;
		</span>
		<span class='<?php $i++;  echo $dtClass; if ($i % 2 == 0) echo $altClass;?>'><?php __('Last Visit'); ?></span>
		<span class='<?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>'>
			<?php echo $data['Group']['lastVisit']; ?>
			&nbsp;
		</span>
		<span class='<?php $i++;  echo $dtClass; if ($i % 2 == 0) echo $altClass;?>'><?php __('Created On'); ?></span>
		<span class='<?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>'>
			<?php echo $data['Group']['created']; ?>
			&nbsp;
		</span>
	</dl>
</div>
<?php	$this->Layout->blockEnd(); ?>


<section class='join prefix_1 grid_14 suffix_1'>
	<h2 class="alpha"><?php echo $title ?></h2>
	<div class='wrap grid_14'>
		<div class="grid_12 omega">
			<p><?php echo $body ?></p>			
			<div class="response wrap prefix_1">
				<?php
					echo $this->Form->create('Group', array('action'=>'join'));  
					if (!$role || in_array($role, array('VISITOR','GUEST'))) {
						// $options = array('name'=>'register', 'class'=>'orange',
							// // 'onclick'=>"window.location.href='{$signin_redirect}'; return false;",
							// // 'type'=>'button',
						// );
						// echo $this->Form->button("I'd like to join this group!", $options);
						echo $this->Form->hidden('signin_redirect', array('value'=>$signin_redirect));
						echo $this->Form->hidden('noauth_redirect', array('value'=>'/users/signin'));
						echo $this->Form->button("I'd like to join this group!", array('value'=>"Sign In", 'name'=>'data[Group][action]', 'class'=>'orange'));
					} else {
						echo $this->Form->hidden('id', array('value'=>$id)); 
						echo $this->Form->hidden('title', array('value'=>$data['Group']['title'])); 
						echo $this->Form->button("I'd like to join this group!", array('value'=>"Accept Invitation", 'name'=>'data[Group][action]', 'class'=>'orange'));	
					}	
					$checkbox_options = array('type'=>'checkbox','label'=>'Enable Express Upload', 'title'=>'Express Upload allows you to upload photos and share with this Circle in one step.');
					if ($isExpress) $checkbox_options['checked']=1;
					echo $this->Form->input('express_upload',  $checkbox_options);
					echo $this->Form->end();			
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

