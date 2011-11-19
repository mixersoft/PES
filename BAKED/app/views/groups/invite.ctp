<?php
	$this->Layout->blockStart('itemHeader');
		$badge_src = Stagehand::getSrc($data['Group']['src_thumbnail'], 'sq', $data['Group']['type']);
		echo $this->element('nav/section', 
			array('badge_src'=>$badge_src,
				'classLabel'=>$data['Group']['type'],
				'label'=>$data['Group']['title'],
		));
	$this->Layout->blockEnd();
	
// debug($data['Group']);	

	$controllerAlias = Configure::read('controller.alias');
	$previewSrc =  Stagehand::getSrc($data['Group']['src_thumbnail'], 'bp');
	$options = array('linkTo'=>Router::url(array('plugin'=>'','controller'=>$controllerAlias, 'action'=>'home', $data['Group']['id']))); 
	// if (isset($fields['title'])) $options['title'] = $fields['trim_caption'];
	
		// User badges
	if (in_array(AppController::$role, array('USER'))) {
		$from =  $badges[AppController::$userid];
		$tokens['from_usernameLinkTo'] = $this->Html->link(ucFirst($from['User']['username']) , $from[0]['linkTo'], array('target'=>'_blank'));	
		$tokens['from_src'] =  $from[0]['src'];
		$tokens['from'] = $from[0]['fullname'] ? "<b>{$from[0]['fullname']}<b> ({$tokens['from_usernameLinkTo']})" : "<b>{$tokens['from_usernameLinkTo']}</b>";
	}
	$tokens['src'] = Stagehand::getSrc($data['Group']['src_thumbnail'], 'lm', $data['Group']['type']); 
	$tokens['group_type'] = ucFirst($data['Group']['type']);
	$tokens['circle'] = ucFirst($data['Group']['title']);
	$tokens['linkTo'] = Router::url(array('plugin'=>'','controller'=>$controllerAlias, 
		'action'=>'invitation', 
		$data['Group']['id'],
		'?'=>array('uuid'=>AppController::$userid),
	), true);
	
?>
<section class='invitation prefix_1 grid_14 suffix_1'>
	<h2 ><?php echo String::insert("Invite your friends & family to join this :group_type.", $tokens); ?></h2>
	<div class='wrap grid_14'>
			<?php echo String::insert("To send an invitation to join this :group_type, just share one of the customized links below by email, facebook, etc.", $tokens); ?>
		</div>
	<div class='wrap grid_14'>
		<div class="alpha grid_3">
				<?php echo $this->Html->image($tokens['src']); ?>	
			</div>
		<div class="grid_11 omega">
			<section class='invite-copy'>
				<h3>Simple invitation to join.</h3>
				<blockquote>
				<?php echo String::insert(":from has invited you to join the <b>:circle</b> :group_type at Snaphappi. As a member, you will be able share Snaps, create Stories and connect with other members of this :group_type.", $tokens); ?>
				</blockquote>	
			</section>
			<div class="link">
				<?php  
					echo String::insert("<input type='text' class='copy-paste' onclick='this.select();' value=':linkTo' />", $tokens);
					echo String::insert("<a  class='right' href=':linkTo' target='_blank'>Try it</a>", $tokens) ?>
				</div>
		</div>
		<div class="alpha prefix_3 grid_11 omega">
			<section class='invite-copy'>
				<h3>Invitation to upload and share Snaps.</h3>
				<blockquote>
				<?php echo String::insert(":from has invited you to upload and share your Snaps with the <b>:circle</b> :group_type at Snaphappi. 
				Your Snaps are needed to tell the whole story of this :group_type, and an express upload option will be provided to help you upload Snaps directly into this :group_type.", $tokens); ?>
				</blockquote>	
			</section>
			<div class="link">
				<?php  
					echo String::insert("<input type='text' class='copy-paste' onclick='this.select();' value=':linkTo&express=1' />", $tokens);
					echo String::insert("<a  class='right' href=':linkTo&express=1' target='_blank'>Try it</a>", $tokens) ?>
				</div>
		</div>		
	</div>
</section>