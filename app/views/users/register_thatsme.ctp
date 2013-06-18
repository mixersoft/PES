<div id="signin" class="featurette preview track-page-view ">
	<div class="vcenter-wrap">
		<div class="vcenter-padding">
			<div class="fw-band vcenter-body alpha black a70 ">
				<div class="container">
					<div class="row">
						<h1 class='center'>Become a Memeber</h1>
					</div>
					<div class="row">
		<form accept-charset="utf-8" method="post" id="UserRegisterForm" action="/users/register" class="form-horizontal offset3 span6">
			<div class="control-group">
				<div class="input text required">
					<label class="control-label" for="UserUsername">Username</label>
					<div class="controls">
						<input type="text" id="UserUsername" maxlength="166" name="data[User][username]">
						</div>
				</div>
				<div class="input text required">
					<label class="control-label" for="UserEmail">E-mail</label>
					<div class="controls">
						<input type="text" id="UserEmail" maxlength="166" name="data[User][email]">
						</div>
				</div>
				<div class="input password required">
					<label class="control-label" for="UserPassword">Password</label>
					<div class="controls">
						<input type="password" id="UserPassword" name="data[User][password]">
						</div>
				</div>
				<div class="input password required">
					<label class="control-label" for="UserTemppassword">Password (confirm)</label>
					<div class="controls">
						<input type="password" id="UserTemppassword" name="data[User][temppassword]">
					</div>
				</div>
				<label class="checkbox" for="UserTos">
					<input type="hidden" value="0" id="UserTos_" name="data[User][tos]">
					<input type="checkbox" id="UserTos" value="1" name="data[User][tos]">
					I have read and agreed to <a href="/pages/tos">Terms of Service</a>
				</label>
				<div class="submit">
					<input type="submit" value="Submit" class="orange">
				</div>
			</div>
		</form>
					</div>
					<div class="row">
						<h1>Already a member?</h1>
						<p>Click here to <a href="/users/signin">Sign in now</a>.</p>
					</div>	
				</div>
			</div>
		</div>
	</div>
	<div class='fw-band footer alpha black a85'>
		<div class="container center">
			<a title='see our Facebook page' target='_social' href='http://www.facebook.com/Snaphappi'><i class="icon-facebook-sign"></i></a>
			&nbsp;<a title='see our Twitter feed' target='_social' href='https://twitter.com/snaphappi'><i class="icon-twitter-sign"></i></a>
			&nbsp;<a title='see our Pinterest board' target='_social' href='http://pinterest.com/snaphappi/curated-family-photos/'><i class="icon-pinterest-sign"></i></a>
		</div>
	</div>
</div>

