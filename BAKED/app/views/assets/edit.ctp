<div class="assets view main-div placeholder">
	<h2><?php  echo $data['Asset']['caption']?></h2>
	
<div class="assets">
	<h2></h2>
	
	<div class='img placeholder'>
		<ul class='sizes inline'>
			<li><h3 style='display:inline'>Sizes:</h3></li>
			<?php $sizes = array('sq'=>'Square', 'tn'=>'Thumbnail', 'bs'=>'Small', 'bm'=>'Medium', 'bp'=>'Preview'); 
				foreach($sizes as $size=>$label) {
					echo "<li>{$this->Html->link($label, setNamedParam($this->params['url'], 'size', $size))}</li>";
				}
			?>
		</ul>
		<?php $size = isset($this->params['named']['size'])  ? $this->params['named']['size'] : 'bm';
				$src = Stagehand::getSrc($data['Asset']['src_thumbnail'], $size);
				echo $this->Html->image($src);
			?>
	</div>	
	
	<div id='fields' class="setting placeholder">
		<h3>Description</h3>
		<?php
			$formOptions['url']=$formOptions['url']=Router::url(array('controller'=>'photos', 'action'=>'edit', $this->Form->value('Asset.id')));
			$formOptions['id']='AssetForm-fields';
			$radio_attrs = array('legend'=> false, 'separator'=>'<br />');
			echo $this->Form->create('Asset', $formOptions);?>
			
		<?php echo $this->Form->input('caption', array('label'=>'Caption'));?>
		<?php echo $this->Form->input('keyword', array('label'=>'Keywords'));?> 
		<?php echo $this->Form->hidden('Asset.id');?>
		<?php echo $this->Form->hidden('setting',array('value'=>$formOptions['id']));?>
		<?php echo $this->Form->end(__('Submit', true));?>
	</div>	
	
	
	<div id='privacy' class="setting placeholder">
		<h3>Privacy Settings</h3>
		<?php $formOptions['id']='AssetForm-privacy'; 
			echo $this->Form->create('Asset', $formOptions);
			?>
		<p>These settings control who can see this Photo.</p>
		
		
		<h4>Photos</h4>
		<p>This Photo is:</p>
		<div class="radio-group">
		<?php echo $form->radio('privacy_assets', $privacy['Asset'], $radio_attrs );?>
		</div>	
		<h4>Group and Event Contents</h4>
		<p>When this Photo is shared with a Group:</p>
		<div class="radio-group">
		<?php echo $form->radio('privacy_groups', $privacy['Groups'], $radio_attrs );?>	
		</div>
					
		<h4>Secret Key Sharing</h4>
		<p>Regardless of privacy settings, content can also be accessed by secret key. These keys are added to special links which can be selectively shared by email, IM or the web. Note that content accessed by Secret Key will not include links to related content.</p>
		<br></br>
		<p>Show Secret Keys to:</p>
		<div class="radio-group">
		<?php echo $form->radio('privacy_secret_key', $privacy['SecretKey'], $radio_attrs );?>			
		</div>
					
		<?php 	echo $this->Form->hidden('id');?>
		<?php echo $this->Form->end(__('Edit', true));?>					
	</div>		
	
</div>
</div>