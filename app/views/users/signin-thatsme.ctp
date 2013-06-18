<?php 
	$this->Layout->blockStart('HEAD');
?>
	<style type="text/css">
		body {
			background: transparent;
		}
		form a {
			color: #F16122;
		}
		form a:hover {
			color: #F16122;
			text-decoration: underline;
		}
	</style>
<?php 		
	$this->Layout->blockEnd();		
?>
<form id='signin' class="form-horizontal offset3 span8" accept-charset="utf-8" method="post" id="UserSigninForm" action="/users/signin">
	<div style="display:none;">
		<input type="hidden" value="POST" name="_method">
	</div>
	<div class="control-group">
		<div class="controls">
			<button type="submit" class="btn btn-awesome" data-action="guest">
				Sign in as Guest
			</button>
			<input type="hidden" value="<?php echo $guestpass; ?>" name="data[User][guest_pass]" id="UserGuestPass">
		</div>
		
	</div>
	<div class="center">Any activity will be saved in your session for up to 2 weeks. Upgrade to a full account any time before then.</div>
	<div class="control-group center">
		<p>
			<strike>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</strike>&nbsp;&nbsp;or&nbsp;&nbsp;<strike>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</strike>
		</p>
	</div>
	<div class="control-group">
		<label class="control-label" for="UserUsername">Username</label>
		<div class="controls">
			<input type="text" id="UserUsername" maxlength="166" name="data[User][username]" placeholder="Username">
		</div>
	</div>
	<div class="control-group">
		<label class="control-label" for="UserPassword">Password</label>
		<div class="controls">
			<input type="password" id="UserPassword" name="data[User][password]" placeholder="Password">
		</div>
	</div>
	<div class="control-group">
		<div class="controls">
			<div class='form-inline'>
				<button type="submit" class="btn btn-awesome" data-action="signin">
					Sign in
				</button>
			</div>
		</div>
	</div>
	<div class="center">or Click here to <a href="/users/register">Sign up now.</a></div>
</form>

