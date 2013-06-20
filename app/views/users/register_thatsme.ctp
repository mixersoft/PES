<?php 
	$uploadhost = Configure::read('isLocal') ? 'snappi-dev' : 'dev.snaphappi.com';
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
			Util.registerSuccess = function(form, json) {
				var jsonMsg = {key:'flash',value:json.message};
				CFG['aaa'].postMessage(jsonMsg); 
				
				jsonMsg = {key:'auth',value:json.response};
				CFG['aaa'].postMessage(jsonMsg);
			}
			Util.formErrors = function(form, json) {
				var msg, id;
				// reset errors
				form.find('.control-group').removeClass('error');
				form.find('.controls .help-inline').html('');	
				for (var field in json.errors){
					msg = json.errors[field];
					switch(field){
						case 'username': id = '#UserUsername'; break;
						case 'email': id = '#UserEmail'; break;
						case 'password': id = '#UserPassword'; break;
						case 'tos': id = '#UserTos'; break;
						default: id=null; break;
					}
					$(id).closest('.control-group').addClass('error')
						.find('.help-inline').html(msg);
				}
				$('input[type="password"]').html('');
				var json = {key:'resize', value:{h:form.height()}};
				CFG['aaa'].postMessage(json)
			}
			Util.submit = function(e) {
				var form = $(e.currentTarget),
					// submit = $(e.explicitOriginalTarget),	// ff only
					src = "/users/register/.json",
					postData = {};
				Util.setWaiting(form);
					
				postData.register = {
					'data[User][username]': form.find('#UserUsername').val(), 
					'data[User][password]':form.find('#UserPassword').val(),
					'data[User][email]':form.find('#UserEmail').val(),
					'data[User][tos]': form.find('#UserTos').prop('checked') ? 1 : 0,
					forcexhr: 1,
					debug: 2,
				}
				/*
				 * POST should include begin/end timestamps to filter photostream
				 */
				var step = {};
				
				step.one = {
					url: src,
					type: 'POST',
					data: postData.register,
					dataType: 'json',
					success: function(json, status, o){
						try {
							if (json.success) {
								Util.registerSuccess(form, json);
							} else {
								if (json.response && json.response.errors) {
									Util.formErrors(form, json.response);
								}
							}
							CFG['aaa'].postMessage(json);
						} catch (ex) {
							throw ex;
						}
						CFG['aaa'].setWaiting(false);
					},
				}
				$.ajax(
					step.one
				).fail(function(json, status, o){
					console.error("register failed");
					CFG['aaa'].setWaiting(false);
				});
				return false; // Keep the form from submitting
			};
			CFG['aaa'] = $.extend(CFG['aaa'] || {}, Util);
			// form init
			$('form#UserRegisterForm a[data-action]').bind('click', function(e){
				var json = {key:'href', value:$(this).attr('data-action')};
				Util.postMessage(json);
			});
			$('#submit').bind('click', function(e){
				Util.setWaiting($(this));
			});
			$('form#UserRegisterForm').bind('submit', function(e){
				return CFG['aaa'].submit(e);
			});
	});	
	</script>
<?php 		
	$this->Layout->blockEnd();		
?>
<form id="UserRegisterForm" class="form-horizontal" accept-charset="utf-8"
		onsubmit="return false;">
	<div style="display:none;">
		<input type="hidden" value="POST" name="_method">
	</div>
	<div class="control-group">
		<div class="control-group">
			<label class="control-label" for="UserUsername">Username</label>
			<div class="controls">
				<input type="text" id="UserUsername" maxlength="166" name="data[User][username]">
				<span class="help-inline"></span>
			</div>
		</div>
		<div class="control-group">
			<label class="control-label" for="UserEmail">E-mail</label>
			<div class="controls">
				<input type="text" id="UserEmail" maxlength="166" name="data[User][email]">
				<span class="help-inline"></span>
			</div>
		</div>
		<div class="control-group">
			<label class="control-label" for="UserPassword">Password</label>
			<div class="controls">
				<input type="password" id="UserPassword" name="data[User][password]">
				<span class="help-inline"></span>
				</div>
		</div>
		<div class="control-group">
			<label class="control-label" for="UserTemppassword">Password (confirm)</label>
			<div class="controls">
				<input type="password" id="UserTemppassword" name="data[User][temppassword]">
			</div>
		</div>
		<div class="control-group">
			<div class="controls">
				<label class="checkbox" for="UserTos">
				<input type="checkbox" id="UserTos" value="1" name="data[User][tos]">
				I have read and agreed to the <a data-action='tos' href="">Terms of Service</a>
				<span class="help-inline"></span>
			</label>
		</div>
		</div>
		<div class="control-group">
			<div class="controls">
				<div class='form-inline'>
					<button id='submit' type="submit" class="btn btn-awesome" 
						data-action="submit"
						data-loading-text='<i class="icon-spinner icon-spin"></i> Submit' >
						Submit
					</button>
				</div>
			</div>
		</div>
		<div class="control-group">
			<div class="controls">
				<h4>Already a member?</h4>
				<p>Click here to <a data-action='signin' href="#">Sign-in now</a></p>
			</div>
		</div>
	</div>
</form>
