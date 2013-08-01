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
		h2 {
			font-size:30px;
			line-height: 1.8em;
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
						case 'confirm': id = '#UserConfirm'; break;
						default: id=null; break;
					}
					$(id).closest('.control-group').addClass('error')
						.find('.help-inline').html(msg);
				}
				var json = {key:'resize', value:{h:form.height()}};
				CFG['aaa'].postMessage(json);
			}
			Util.resetSuccess = function(form, json) {
				var jsonMsg = {key:'flash',value:json.message};
				CFG['aaa'].postMessage(jsonMsg); 
				
				jsonMsg = {key:'success',value:1};
				CFG['aaa'].postMessage(jsonMsg);
			}
			Util.resetFailure = function(form, json) {
				var jsonMsg = {key:'flash',value:json.message};
				CFG['aaa'].postMessage(jsonMsg);
				form.attr('data-action', '');
				Util.formErrors(form, json.response);
				Util.setWaiting(false);
			}
			Util.goBack = function(){
				jsonMsg = {key:'href',value:'/users/snaps'};
				CFG['aaa'].postMessage(jsonMsg);
			}
			Util.submit = function(e) {
				var form = $(e.currentTarget),
					// submit = $(e.explicitOriginalTarget),	// ff only
					src = "/my/truncate/.json?min",
					postData = {};
				Util.setWaiting(form);
					
				postData.reset = {
					'data[User][id]': form.find('#UserId').val(), 
					'data[User][confirm]': form.find('#UserConfirm').prop('checked') ? 1 : 0,
					forcexhr: 1,
					debug: 0,
				}
				
				/*
				 * POST should include begin/end timestamps to filter photostream
				 */
				var step = {};
				step.one = {
					url: src,
					type: 'POST',
					data: postData.reset,
					dataType: 'json',
					success: function(json, status, o){
						try {
							if (json.success) {
								Util.resetSuccess(form, json);
							} else {
								Util.resetFailure(form, json);
							}
						} catch (ex) {
							console.error("reset failed");
						}
					},
				}
				if (form.attr('data-action') !== 'reset') {
					return Util.goBack();
				} else if (postData.reset['data[User][confirm]']==false) {
					return Util.resetFailure(form, {
						message: 'Please confirm you would like to REMOVE all your photos',
						response: {
							errors: 
								{'confirm': 'Please check this box to confirm'}
							}
					});
				}
				
				$.ajax(
					step.one
				).fail(function(json, status, o){
					console.error("setCookie failed");
					CFG['aaa'].setWaiting(false);
				});
				return false; // Keep the form from submitting
			};
			CFG['aaa'] = $.extend(CFG['aaa'] || {}, Util);
			
			// form init
			$('form#UserResetForm button[type="submit"]').bind('click', function(e){
				Util.setWaiting($(this));
				// copy button action to form
				var btn_action = $(e.currentTarget).attr('data-action');
				$('form#UserResetForm').attr('data-action', btn_action);
			});
			$('form#UserResetForm').bind('submit', function(e){
				return CFG['aaa'].submit(e);
			});
	});	
	</script>
<?php 		
	$this->Layout->blockEnd();		
?>
<h2 class="center">Remove all your photos from Snaphappi</h2>
<form id="UserResetForm" class="form-horizontal" accept-charset="utf-8" onsubmit="return false;">
	<div style="display:none;">
		<input type="hidden" value="POST" name="_method">
	</div>
	<div class="control-group">
		<div class="controls">
			<input id="UserId" type="hidden" value="<?php echo AppController::$userid; ?>" name="data[User][id]" >
			<label class="checkbox" for="UserConfirm">
				<input type="checkbox" id="UserConfirm" name="data[User][confirm]">
				I want to remove all my photos from Snaphappi.
				<span class="help-inline"></span>
			</label>
			<button type="submit" class="btn btn-awesome" 
				<?php  if (isset($disabled)) echo 'disabled="1" title="The reset action has been disabled for this demo account"'; ?>
				data-action="reset"
				data-loading-text='<i class="icon-spinner icon-spin"></i> Reset Account' >
				Reset Account
			</button>
		</div>
	</div>
	<div class="control-group">
		<div class="controls">
			<button type="submit" class="btn btn-awesome" 
				data-action="go-back"
				data-loading-text='<i class="icon-spinner icon-spin"></i> Go Back' >
				Go Back
			</button>
		</div>
	</div>
</form>

