<?php 	$this->Layout->blockStart('itemHeader');  ?>
<section class='item-header container_16'>
	<div class='wrap'>
		<h1 class='grid_16'>Welcome to Snaphappi!</h1>
	</div>
</section>
<?php 	$this->Layout->blockEnd();  ?>

<section class="main container_16">
	<div class="grid_7 prefix_1 ">
		<h2>Sign-in</h2>
		<div >
		<?php $orange = array('class'=>'orange'); 
			echo $this->Form->create('User');?>
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
	
	<div class="grid_7 suffix_1 clearfix">
	<?php if (!@ empty($cookie_guest_pass)) { ?>
		<h2>Continue as a Guest</h2>
		<div>
			<p>Any activity will be saved in your session for up to 2 weeks. Upgrade to a full account any time before then.</p>
			<br></br>
			<?php echo $this->Form->submit('Continue as Guest', $orange);?>
			<input type='hidden' id='UserGuestPass' name='data[User][guest_pass]' value='<?php echo $cookie_guest_pass ?>'> 
		</div>
	<?php } ?>
	<?php echo $this->Form->end();?>
		<h2>or Become a Member</h2>
		<div>Click here to <a href='/users/register'>Sign up now.</a></div>
	</div>
	
</section>
<?php
// debug(session_id());
// debug($_COOKIE);
?>
<!-- 
<div style="width: 350px; float: right; margin-right: 20px;">
<p>No need to register a new account, You can also sign in with an existing account at any of
the sites below.</p>
<br />
<br />
<div class='show-loading-gif'>
<div style='visibility: hidden'><iframe
	onload='this.parentNode.style.visibility="visible";'
	src="https://snaphappi.rpxnow.com/openid/embed?token_url=<?php echo $rpxTokenUrl ?>"
	scrolling="no" frameBorder="no" style="width: 400px; height: 240px;"> </iframe>
<script src="https://rpxnow.com/openid/v2/widget" type="text/javascript">
                </script> <script type="text/javascript">
                    RPXNOW.overlay = true;
                    RPXNOW.language_preference = 'en';
                </script></div>
</div>
</div>
 -->


