<!--  for XHR login  -->
<div class="plain-form">
	{message}
	<div class="cf">
		<h2>Sign-in</h2>
		<div >
		<?php $orange = array('class'=>'orange'); 
			$formOptions['id']='UserSigninForm';
			$formOptions['url']='/users/signin/.json';
			$formOptions['onsubmit']='SNAPPI.Helper.Dialog.signIn(this); return false;';
			echo $this->Form->create('User', $formOptions);?>
		<?php echo $this->Form->input('username');?> 
		<?php echo $this->Form->input('password');?>
		<?php echo $this->Form->submit('Login', $orange);?>
		<div style="margin-left:148px;font-size:0.8em;">
			<?php echo $this->Html->link('lost password?', '/users/reset_password'); ?>
		</div>
		<?php if (Configure::read('AAA.allow_magic_login')) {
			$userlist = array_merge(array(' '=>'select test accounts'),$userlist);
			echo $this->Form->select('magic', $userlist, ' ', array(), false);
		}
			?>
		</div>
	</div>
	<div class="cf">
	<?php if (!@ empty($guest_pass)) { ?>
		<h2>Continue as a Guest</h2>
		<div>
			<p>Any activity will be saved in your session for up to 2 weeks. Upgrade to a full account any time before then.</p>
			<br></br>
			<?php echo $this->Form->submit('Continue as Guest', $orange);?>
			<input type='hidden' id='UserGuestPass' name='data[User][guest_pass]' value='<?php echo $guest_pass ?>'> 
		</div>
	<?php } ?>
	<?php echo $this->Form->end();?>
		<h2>or Become a Member</h2>
		<div>Click here to <a href='/users/register'>Sign up now.</a></div>
	</div>
</div>	