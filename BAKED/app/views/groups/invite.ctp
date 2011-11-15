<?php
	$this->Layout->blockStart('itemHeader');
	echo $this->element('nav/section', array('icon_src'=>$data['Group']['src_thumbnail'], 
		'classLabel'=>$data['Group']['type'],
		'label'=>$data['Group']['title'],
		)); 
	$this->Layout->blockEnd();
// debug($data['Group']);	

	$controllerAlias = Configure::read('controller.alias');
	$previewSrc = Session::read('stagepath_baseurl').getImageSrcBySize($data['Group']['src_thumbnail'], 'bp');
	$options = array('linkTo'=>Router::url(array('plugin'=>'','controller'=>$controllerAlias, 'action'=>'home', $data['Group']['id']))); 
	// if (isset($fields['title'])) $options['title'] = $fields['trim_caption'];
	
	$tokens['src'] = Stagehand::getSrc($data['Group']['src_thumbnail'], 'lm', $data['Group']['type']); 
	$tokens['group_type'] = ucFirst($data['Group']['type']);
	$tokens['linkTo'] = Router::url(array('plugin'=>'','controller'=>$controllerAlias, 
		'action'=>'invitation', 
		$data['Group']['id'],
		'?'=>array('uuid'=>AppController::$userid),
	), true);
	
?>
<section class='invitation prefix_1 grid_14 suffix_1'>
	<h2 ><?php echo String::insert("Invite your friends & family to join this :group_type.", $tokens); ?></h2>
	<div class='wrap grid_14'>
		<div class="alpha grid_2">
			<div class="right">
				<?php echo $this->Html->image($tokens['src']); ?>	
			</div></div>
		<div class="grid_12 omega">
			<p><?php echo String::insert("To send an invitation to join this :group_type, just share this link by email, facebook, etc.", $tokens); ?></p>
			<div class="wrap"><?php echo  String::insert("<a href=':linkTo' target='_blank'>:linkTo</a>", $tokens) ?>	</div>
		</div>,
	</div>
</section>