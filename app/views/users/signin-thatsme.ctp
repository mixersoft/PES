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
	<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js"></script>
	<script type="text/javascript" src="/js/plupload/jquery.cookie.js"></script>
	<script type="text/javascript">
		$(function() {
			CFG = (typeof CFG == 'undefined')? {} : CFG; 
			/*
			 * helper functions
			 */
			var Util = new function(){}
			Util.postMessage = function(json){
				window.parent.postMessage(json, '*');
			}
			Util.setWaiting = function(e){
				Util.waiting = Util.waiting || [];
				var form = $(e.currentTarget);
				Util.waiting.push(form);
				var action = form.attr('data-action');
				Util.waiting.push(form.find('button[type="submit"][data-action="'+action+'"]'));
				var value = (e) ? 'wait' : 'default'; 
				for (var i in Util.waiting) {
					Util.waiting[i].css('cursor', value);
				}
			};
			Util.submit = function(e) {
				Util.setWaiting(e);
				var form = $(e.currentTarget),
					// submit = $(e.explicitOriginalTarget),	// ff only
					src = "/users/signin/.json",
					postData = {},
					guestpass = form.find('#UserGuestPass').val();
				postData.cookie = {
					optional: 1,
					forcexhr: 1,
					debug: 0,
				};
				postData.signin = {
					'data[User][username]': form.find('#UserUsername').val(), 
					'data[User][password]':form.find('#UserPassword').val(),
					forcexhr: 1,
					debug: 2,
				}
				if (form.attr('data-action') =='guest'){
					postData.signin['data[User][guest_pass]'] = guestpass;
				}
				/*
				 * POST should include begin/end timestamps to filter photostream
				 */
				var step = {};
				step.one = {
					url: src,
					type: 'GET',
					data: postData.cookie,
					dataType: 'json',
					success: function(json, status, o){
						try {
							var uuid = json.response.User.id;
							if (uuid) Util.postMessage(json);
							// Util.setWaiting(false);
							else throw new Exception('current auth successful, but invalid uuid')
						} catch (ex) {
							try {
								// new guest_pass issued, try to sign in
								guestpass = json.Cookie.guest_pass;
								step.two.data['data[User][guest_pass]'] = guestpass;
								$.ajax(
									step.two
								).fail(function(json, status, o){
									console.error("signin failed");
									CFG['aaa'].setWaiting(false);
								});
							} catch (ex) {		}	
						}
					},
				}
				step.two = {
					url: src,
					type: 'POST',
					data: postData.signin,
					dataType: 'json',
					success: function(json, status, o){
						try {
							var uuid = json.response.User.id;
							Util.postMessage(json);
							CFG['aaa'].setWaiting(false);
						} catch (ex) {		}
					},
				}
				var postcfg;
				if (form.attr('data-action') =='guest') {
					postcfg = guestpass ? step.two : step.one;
				} else postcfg = step.two;
				$.ajax(
					postcfg
				).fail(function(json, status, o){
					console.error("setCookie failed");
					CFG['aaa'].setWaiting(false);
				});
				return false; // Keep the form from submitting
			};
			CFG['aaa'] = $.extend(CFG['aaa'] || {}, Util);
			
			// form init
			$('form#UserSigninForm button[type="submit"]').bind('click', function(e){
				$('form#UserSigninForm').attr('data-action', $(e.currentTarget).attr('data-action'));
				var check;
			});
			$('form#UserSigninForm').bind('submit', function(e){
				return CFG['aaa'].submit(event);
			});
	});	
	</script>
<?php 		
	$this->Layout->blockEnd();		
?>
<form class="form-horizontal offset3 span8" 
	accept-charset="utf-8" 
	id="UserSigninForm"
	>
	<div style="display:none;">
		<input type="hidden" value="POST" name="_method">
	</div>
	<div class="control-group">
		<div class="controls">
			<button type="submit" class="btn btn-awesome" data-action="guest">
				Sign in as Guest
			</button>
			<input id="UserGuestPass" type="hidden" value="<?php echo $guestpass; ?>" name="data[User][guest_pass]" >
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

