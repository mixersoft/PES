<div class="grid_7 prefix_1">
<h2>Sign-in to Snaphappi</h2>
<div >
<?php echo $this->Form->create('User');?>
<?php echo $this->Form->input('username');?> 
<?php echo $this->Form->input('password');?>
<?php echo $this->Form->submit('Login');?>
<?php $userlist = array_merge(array(' '=>'select test accounts'),$userlist);
echo $this->Form->select('magic', $userlist, ' ', array(), false);?>
</div>
</div>

<div class="grid_7 suffix_1 clearfix">
<?php if (!@ empty($guest_pass)) { ?>
<h2>or continue as a Guest</h2>
<div>
<p>Any activity will be saved in your session for up to 2 weeks. Upgrade to a full account any time before then.</p>
<br></br>
<?php echo $this->Form->submit('Continue as Guest');?>
<input type='hidden' id='UserGuestPass' name='data[User][guest_pass]' value='<?php echo $guest_pass ?>'> 
</div>
<?php } ?>
<?php echo $this->Form->end();?>
</div>
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


