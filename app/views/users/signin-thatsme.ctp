<?php 
	$this->Layout->blockStart('HEAD');
?>
	<style type="text/css">
		body {
			background: transparent;
			overflow: hidden;
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
	<script type="text/javascript" src="http://snappi.snaphappi.com/min/b=static/js&f=bootstrap/modernizr-2.6.2-respond-1.1.0.min.js,bootstrap/bootstrap.js&123"></script>
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
			Util.setWaiting = function(o){
				Util.waiting = Util.waiting || [];
				if (o) {
					Util.waiting.push(o);
					o.filter('button').each(function(){
						$(this).button('loading');
					});
					o.css('cursor', 'wait');
				} else {
					for (var i in Util.waiting) {
						o = Util.waiting[i];
						o.css('cursor', 'default');
						o.filter('button').each(function(){
							$(this).button('reset');
						});
					}	
					Util.waiting = [];
				}
			};
			Util.formErrors = function(form, json) {
				var msg, id;
				// reset errors
				form.find('.control-group').removeClass('error');
				form.find('.controls .help-inline').html('');	
				for (var field in json.errors){
					msg = json.errors[field];
					switch(field){
						case 'username': id = '#UserUsername'; break;
						case 'password': id = '#UserPassword'; break;
						default: id=null; break;
					}
					$(id).closest('.control-group').addClass('error')
						.find('.help-inline').html(msg);
				}
				$('input[type="password"]').html('');
				var json = {key:'resize', value:{h:form.height()}};
				CFG['aaa'].postMessage(json)
			}
			Util.signinSuccess = function(form, json) {
				var jsonMsg = {key:'flash',value:json.message};
				CFG['aaa'].postMessage(jsonMsg); 
				
				jsonMsg = {key:'auth',value:json.response};
				CFG['aaa'].postMessage(jsonMsg);
			}
			Util.signinFailure = function(form, json) {
				var jsonMsg = {key:'flash',value:json.message};
				CFG['aaa'].postMessage(jsonMsg);
				$('form #UserPassword').val(''); 
				if (form.attr('data-action')=='guest') $('form #UserUsername').val('');
				form.attr('data-action', '');
				if (json.response && json.response.errors) {
					Util.formErrors(form, json.response);
				}
			}
			Util.submit = function(e) {
				var form = $(e.currentTarget),
					// submit = $(e.explicitOriginalTarget),	// ff only
					src = "/users/signin/.json",
					postData = {},
					guestpass = form.find('#UserGuestPass').val();
				Util.setWaiting(form);
					
				postData.cookie = {
					optional: 1,
					forcexhr: 1,
					debug: 0,
				};
				postData.signin = {
					'data[User][username]': form.find('#UserUsername').val(), 
					'data[User][password]':form.find('#UserPassword').val(),
					forcexhr: 1,
					debug: 0,
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
							if (json.success) {
								Util.signinSuccess(form, json);
							} else {
								Util.signinFailure(form, json);
							}
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
							if (json.success) {
								Util.signinSuccess(form, json);
							} else {
								Util.signinFailure(form, json);
							}
							CFG['aaa'].setWaiting(false);
						} catch (ex) {		}
					},
				}
				var postcfg;
				if (form.attr('data-action') =='guest') {
					postcfg = guestpass ? step.two : step.one;
				} else {
					if (!form.find('#UserUsername').val()) {
						Util.signinFailure(form, {success:false, 
								message:'Please fix the errors marked below', 
								response:{
									errors:{'username':'You must enter a username'}
								}
							});
						return false;	
					} else 
						postcfg = step.two;
				}
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
			$('form#UserSigninForm a').bind('click', function(e){
				var json, target = $(this).attr('data-target');
				if (target) {
					json = {key:'href', value:target}; 
					Util.postMessage(json);
				}
				e.preventDefault();
			})
			
			$('form#UserSigninForm button[type="submit"]').bind('click', function(e){
				Util.setWaiting($(this));
				// copy button action to form
				var btn_action = $(e.currentTarget).attr('data-action');
				$('form#UserSigninForm').attr('data-action', btn_action);
			});
			$('form#UserSigninForm').bind('submit', function(e){
				return CFG['aaa'].submit(e);
			});
	});	
	</script>
<?php 		
	$this->Layout->blockEnd();		
?>
<form id="UserSigninForm" class="form-horizontal" accept-charset="utf-8">
	<div style="display:none;">
		<input type="hidden" value="POST" name="_method">
	</div>
	<div class="control-group">
		<label class="control-label" for="UserUsername">Username</label>
		<div class="controls">
			<input type="text" id="UserUsername" maxlength="166" name="data[User][username]" placeholder="Username">
			<span class="help-inline"></span>
		</div>
	</div>
	<div class="control-group">
		<label class="control-label" for="UserPassword">Password</label>
		<div class="controls">
			<input type="password" id="UserPassword" name="data[User][password]" placeholder="Password">
			<span class="help-inline"></span>
		</div>
	</div>
	<div class="control-group">
		<div class="controls">
			<div class='form-inline'>
				<button type="submit" class="btn btn-awesome" 
					data-action="signin"
					data-loading-text='<i class="icon-spinner icon-spin"></i> Sign in' >
					Sign in
				</button>
			</div>
		</div>
	</div>
	<div class="control-group">
		<div class="controls">
			<strike>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</strike>&nbsp;&nbsp;or&nbsp;&nbsp;<strike>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</strike>
		</div>
	</div>
	<div class="control-group">
		<div class="controls">
			<button type="submit" class="btn btn-awesome" 
				data-action="guest"
				data-loading-text='<i class="icon-spinner icon-spin"></i> Sign in as Guest' >
				Sign in as Guest
			</button>
			<input id="UserGuestPass" type="hidden" value="<?php echo $guestpass; ?>" name="data[User][guest_pass]" >
		</div>
		<div class="controls">
			<p><br />Any activity will be saved in your session for up to 2 weeks. Upgrade to a full account any time before then.</p>
		</div>
	</div>
	<div class="control-group">
		<label class="control-label"></label>
		<div class="controls">
			<strike>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</strike>&nbsp;&nbsp;or&nbsp;&nbsp;<strike>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</strike>
		</div>
	</div>
	<div class="control-group">
		<div class="controls">
			<label>Click here to <a href="#" data-target="/users/register">Sign up now.</a></label>
		</div>
	</div>
</form>

