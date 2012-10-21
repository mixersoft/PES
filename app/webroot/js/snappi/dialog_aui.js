(function(){
	if (typeof SNAPPI.Dialog !== 'undefined') return; 	// firefox/firebug 1.9.1 bug
	
	var _Y = null;
    SNAPPI.namespace('SNAPPI.onYready');
    SNAPPI.onYready.Dialog = function(Y){
		if (_Y === null) _Y = Y;
		SNAPPI.Dialog = Dialog;
		SNAPPI.Alert = CFG_Dialog_Alert;
		SNAPPI.namespace('SNAPPI.Helper');
		SNAPPI.Helper.Dialog = DialogHelper;
		SNAPPI.Dialog.BUTTONS_OK_CANCEL = BUTTONS_OK_CANCEL;
		Dialog.listen['body-rendered'] = _Y.on('snappi:dialog-body-rendered', 
		function(d, cfg){
			if (cfg && cfg.skipRefresh) return;
			Dialog.refresh(d,cfg);
		})
	}      
    

	var BUTTONS_OK_CANCEL = [{
			label:'OK',
			handler: null
		},{
			label:'Cancel',
			handler: function(){this.close();}
		}],
			
		BUTTONS_CLOSE =[{
			label:'Close',
			handler: function(){this.close();}
		}];
	
	var DEFAULT_CFG_dialog = {
			centered: true,
			constrain2view: true,
			draggable: true,
			resizable: false,
			destroyOnClose: true,
			height: 250,
			width: 500,
			close: true,
			buttons: [],
		};
	var DEFAULT_CFG_modal = {
			modal: true,
			centered: true,
			constrain2view: true,
			draggable: false,
			resizable: false,
			destroyOnClose: true,
			height: 250,
			width: 500,
			close: true,
			buttons: [],
		};		
	var DEFAULT_CFG_io = {
		};
	

	var Dialog = function(){
		if (Dialog.doClassInit) Dialog.classInit();
	};
	
	/*
	 * static properties and methods
	 */
	Dialog.listen = {};
	Dialog.find = {};	// keep track of dialog instances for reuse
	
	Dialog.listen_select = function(d) {
		var content = d.get('contentBox');
		var detach = content.delegate('click', function(e,i,o){
			var target = e.currentTarget;
			e.halt(true);
			target.get('parentNode').all('.selected').removeClass('selected');
			target.addClass('selected');
		}, 'li', d);
		return detach;
	}
	Dialog.listen_close = function(d) {
		var detach = d.on('closeChange', function(e){
			for (var i in this.listen) {
				this.listen[i].detach();
				delete this.listen[i];
			}
		}, d)
		return detach;		
	}
	Dialog.handleKeydown = function(e, d){
// console.warn('>>> handleKeydown (DEFAULT)');        	
    	var done;
    	var charCode = {
    			enterPatt: /(^XXX$)/,
		        closePatt: /(^27$)/, // escape
		    };
    	var charStr = e.charCode + '';
		// key navigation for GalleryFactory.Photo
        if (charStr.search(charCode.closePatt) == 0) {
            d.close();
        }
        if (done) e.preventDefault();
    };
	
	/**
	 * refresh Dialog, typically after 'snappi:dialog-body-render'
	 * @params d instanceof Dialog
	 * @params cfg, 
	 * 	cfg.h, height of body 
	 * 	cfg.w, width of body
	 * 	cfg.center, default = true
	 */
	var _default_refresh_cfg = {
		minH:120, minW:120,
		marginH:0, marginW:0,
		h:0, w:0,
		center: true,
		outerMargin: 100,	// dialog margin 
		bodySelector: null, 
	};
	Dialog.refresh = function(d, cfg){
		cfg = _Y.merge(_default_refresh_cfg,cfg);
		if (cfg.center !== false) cfg.center=true;
		var h=0, w=0;
		try {
			h += d.getStdModNode('header').get('offsetHeight');
			// w = Math.max(w, d.getStdModNode('header').get('scrollWidth'));
		} catch(e){}
		try {
			h += d.getStdModNode('footer').get('offsetHeight');
			w = Math.max(w, d.getStdModNode('footer').get('offsetWidth'));
		} catch(e){}
		try {
			var body_wrap = d.getStdModNode('body');
			if( cfg.bodySelector )body_wrap = body_wrap.one(cfg.bodySelector);
			else if (body_wrap.get('childNodes').size()==1 ) {
				body_wrap = body_wrap.one('*');
				body_wrap.addClass('cf');  // setStyle('overflow', 'hidden');
			}
			w = Math.max(w, cfg.minW, cfg.w, body_wrap.get('offsetWidth'));
			w = Math.min(w, body_wrap.get('winWidth')-cfg.outerMargin); // max height limit 100
			d.set('width', w+18);
			
			h += cfg.h || body_wrap.get('offsetHeight');
			h = Math.max(h, cfg.minH);
			h = Math.min(h, body_wrap.get('winHeight')-cfg.outerMargin); // max height limit 100
			d.set('height', h+12+11+cfg.marginH);	// add borders for dialog contentBox+bodyNode
		} catch(e){
			console.warn('WARNING: Dialog.refresh(), dialog body not properly wrapped');
		}		
		if (cfg.center) d.centered();
		else {
			// TODO: add listener for winResize()???	
		}
	}
		
	/*
	 * DialogCfgs
	 */
	
	var CFG_Dialog_Hidden_Shots = function(){}; 
	/*
	 * Photoroll Hidden Shots dialog
	 */
	CFG_Dialog_Hidden_Shots.load = function(cfg){
		var CSS_ID = 'dialog-photo-roll-hidden-shots';
		var _cfg = {
			title: 'Hidden Shot Gallery',
			id: CSS_ID,
			width: (660+20),	// 19 px for scrollbar
			// height: (2*97+146),
			destroyOnClose: false,
			modal: false			
		}
		cfg = cfg || {};
		_cfg = _Y.merge(DEFAULT_CFG_dialog, _cfg, cfg);
		
		var dialog = new _Y.Dialog(_cfg);
		dialog.listen = {};
		if (cfg.autoLoad !== false) dialog.render();
		// save reference
		Dialog.find[CSS_ID] = dialog;
		return dialog;		
	}
	
	var CFG_Dialog_Select_Circles = function(){}; 
	/*
	 * Lightbox, choose circles dialog
	 */
	CFG_Dialog_Select_Circles.load = function(cfg){
		var CSS_ID = 'dialog-select-circles';
		var _cfg = {
			title: 'My Circles',
			id: CSS_ID,
			width: 650+20,	// 2 columns, /size:sq, plus VScroll
			height: 255,	// 2 rows
			destroyOnClose: false,
			modal: true,
			buttons: [
			{
				// TODO: convert this toggle button to tabs in the contentBox
				label: 'Show Public Circles',
				handler: function() {
					var dialog = this;
					var uri = dialog.io.get('uri');
					if (uri != '/groups/open?preview=1') {
						// toggle to public
						var cfg = {
							text: 'Show My Circles',
							title: 'Public Circles',
							uri: '/groups/open?preview=1' 
						}
					} else {
						// toggle to memberships
						cfg = {
							text: 'Show Public Circles',
							title: 'My Circles',
							uri: '/my/groups?preview=1' 
						}
					}
					dialog.set('title', cfg.text);
					dialog.io.set('uri', cfg.uri);    			
					dialog.io.start();
					var detach = dialog.on('bodyContentChange', function(e, cfg){
						var footer = this.get('footerContent');
						footer.one('button').set('innerHTML', cfg.text);
						detach.detach();
					}, dialog, cfg);
				}
			},
			{
				label:'Remove from Circle',
				handler: function() {
					var check;
					var content = this.get('contentBox');
					var selected = content.one('.selected');
					var gid = selected.get('id');
					_Y.once('snappi:share-complete', function(lightbox, loading, response){
						// TODO: show response in msg
						loading.loadingmask.hide();
						this.hide();
						SNAPPI.multiSelect.clearAll(this.get('contentBox'));
						// update asset count in dialog
					}, this);
					var options = {
						batch: this.batch, 
						data: {
							'data[Asset][unshare]': 1
						},
						uri: '/photos/setprop/.json'
					};
					SNAPPI.lightbox.applyShareInBatch(gid, selected, options);
				}
			},
			{
				label: 'Share with Circle',
				handler: function() {
					var check;
					var content = this.get('contentBox');
					var selected = content.one('.container .FigureBox.selected');
					var gid = selected.get('id');
					_Y.once('snappi:share-complete', function(lightbox, loading, response){
						loading.loadingmask.hide();
						this.hide();	// hide dialog instead of clearing selection
						// SNAPPI.multiSelect.clearAll(this.get('contentBox').one('.container'));
						// TODO: show response in msg
						// update asset count in dialog
					}, this);
					var options = {
						batch: this.batch, 
					}
					SNAPPI.lightbox.applyShareInBatch(gid, selected, options);
				}
			}
			]			
		}
		cfg = cfg || {};
		_cfg = _Y.merge(DEFAULT_CFG_dialog, _cfg, cfg);
		
		var dialog = new _Y.Dialog(_cfg);
		dialog.listen = {};
		dialog.listen['select'] = SNAPPI.Dialog.listen_select(dialog);
		
		if (cfg.autoLoad !== false) dialog.render();
		// save reference
		Dialog.find[CSS_ID] = dialog;
		return dialog;		
	}
	
	var CFG_Dialog_Select_Privacy = function(){}; 
	/*
	 * Lightbox, choose circles dialog
	 */
	CFG_Dialog_Select_Privacy.load = function(cfg){
		var CSS_ID = 'dialog-select-privacy';
		var _cfg = {
			title: 'Privacy Settings',
			id: CSS_ID,
			// width: 678,	// 2 columns, for now
			height: 300,	// 3 rows
			destroyOnClose: false,
			modal: true,
			buttons: [
			{
				label:'Apply',
				handler: function(e) {
					var content = this.get('contentBox');
					var setting = content.one('.selected');
					var value = parseInt(setting.getAttribute('value'));
					var target = this.get('target');
					_Y.once('snappi:set-property-complete', function(args){
						args.loadingNode.loadingmask.hide();
					});
					_Y.once('snappi:set-property-complete', function(){
						this.hide();
					}, this);
					if (target.hasClass('FigureBox')) {
						var selected = SNAPPI.Auditions.find(target.get('uuid'));
						var batch = new SNAPPI.SortedHash(null, selected);
						SNAPPI.AssetPropertiesController.setPrivacy.call(this, batch, value, setting);
					} else if (target instanceof SNAPPI.Gallery) {
						// TODO: add apply privacy setting for selectAll from gallery selection
						var check;
					} else if (target instanceof SNAPPI.Lightbox) {
						SNAPPI.lightbox.applyPrivacyInBatch(value, setting);
					}
				}
			}
			]			
		}
		cfg = cfg || {};
		_cfg = _Y.merge(DEFAULT_CFG_dialog, _cfg, cfg);
		
		var dialog = new _Y.Dialog(_cfg);
		dialog.listen = {};
		dialog.listen['select'] = Dialog.listen_select(dialog);
		
		if (cfg.autoLoad !== false) dialog.render();
		// save reference
		Dialog.find[CSS_ID] = dialog;
		return dialog;		
	}

	var CFG_Dialog_Login = function(){}; 
	/*
	 * 
	 * Currently not working. some problems with CSS?
	 * user Login, for AIR Uploader
	 * 
	 */
	CFG_Dialog_Login.load = function(cfg){
		var CSS_ID = 'dialog-login';
		var _cfg = {
			title: 'Sign In',
			id: CSS_ID,
			height: 300,	// 3 rows
			destroyOnClose: true,
			modal: true,
			buttons: [
			// {
				// label:'Sign in',
				// handler: function() {
					// var content = this.get('contentBox');
				// }
			// }
			],
			resizble: true,			
		}
		cfg = cfg || {};
		_cfg = _Y.merge(DEFAULT_CFG_dialog, _cfg, cfg);
		
		var dialog = new _Y.Dialog(_cfg);
		dialog.listen = {};
		
		if (cfg.autoLoad !== false) dialog.render();
		// save reference
		Dialog.find[CSS_ID] = dialog;
		return dialog;		
	}
	
	
	var CFG_Dialog_Alert = function(){}; 
	CFG_Dialog_Alert.load = function(cfg){
		var CSS_ID = 'dialog-alert';
		var _cfg = {
			id: CSS_ID,
			// height: 500,	
			// width: 500,
			// bodyContent: null,
		}
		cfg = cfg || {};
		_cfg = _Y.merge(DEFAULT_CFG_modal, _cfg, cfg);
		var alert = Dialog.find[_cfg.id];
		if (alert) {
			try {	// destroy existing alert box
				// TODO: listen to alert.on('destroy', function(e){}) to clean up dialog body 
				// see ThumbnailFactory.actions.keydown for example
				_Y.fire('snappi-alert:clear-body', alert, alert.getStdModNode('body'));
				alert.setStdModContent('body', '');
				alert.setStdModContent('footer', '');
			}catch(e){}
			for (var i in alert.listen) {
				alert.listen[i].detach();
				delete alert.listen[i];
			}
		}
		alert = new _Y.Dialog(_cfg).render();
		_Y.fire('snappi:dialog-visible', alert, true);
		alert.listen = {};
		alert.listen['Keydown'] = _Y.once('keydown', Dialog.handleKeydown, document, alert, alert);
		alert.once('close',
			function(e){
				_Y.fire('snappi:dialog-visible', alert, false);
			}
		, alert);
					
		var body = alert.getStdModNode('body');
		if (_cfg.bodyNode) {
			// body.setContent(_cfg.bodyNode);
			alert.setStdModContent('body', _cfg.bodyNode);
		} else if (_cfg.selector && _Y.one(_cfg.selector)) {
			var markup = _Y.one(_cfg.selector).outerHTML();
			if (_cfg.tokens) markup = _Y.Lang.sub(markup, _cfg.tokens);
			// body.setContent(markup);
			alert.setStdModContent('body', markup);
		} else if (_cfg.markup) {	
			if (_cfg.tokens) _cfg.markup = _Y.Lang.sub(_cfg.markup, _cfg.tokens);
			// body.setContent(_cfg.markup);
			alert.setStdModContent('body', _cfg.markup);
		} else if (_cfg.uri) {
			// XHR content for dialog contentBox
    		var args = {
	    		dialog: alert,
	    		cfg: _cfg,
	    	}
	    	if (_cfg.tokens) args.tokens = _cfg.tokens;
	    	if (alert.io) alert.unplug(SNAPPI.Y.Plugin.IO);
			var ioCfg = {
				uri: cfg.uri,
				parseContent: true,
				autoLoad: true,
				// modal: false,
				context: alert,
				dataType: 'html',
				arguments: args,    					
				on: {
					success: _cfg.success || function(e, i, o, args) {
						var content, markup;
						SNAPPI.setPageLoading(false);
						// add to #markup
						markup = _Y.one('#markup').append(o.responseText);
						// get added markup from #markup
						if (args && args.cfg.selector) markup = _Y.one(args.cfg.selector);
						if (args && args.tokens) {
							markup = _Y.Lang.sub(markup.outerHTML(), args.tokens);
						} else markup = markup.outerHTML();
						this.setStdModContent('body', markup);
						content = this.getStdModNode('body').one('*');
						_Y.fire('snappi:dialog-body-rendered', this, _cfg);
						_Y.fire('snappi:dialog-alert-xhr-complete', this, _cfg);
						return false; 
					}					
				}
			};
			ioCfg = SNAPPI.IO.getIORequestCfg(cfg.uri, ioCfg.on, ioCfg);
			alert.plug(SNAPPI.Y.Plugin.IO, ioCfg);
			var WAIT_FOR_XHR = true;
		}	
		if (!WAIT_FOR_XHR){
			_Y.fire('snappi:dialog-body-rendered', alert, _cfg);
			_Y.fire('snappi:dialog-alert-xhr-complete', alert, _cfg);
		}
		Dialog.find[_cfg.id] = alert;		// save reference for lookup
		return alert;		
	}	
	
	
		
	// save CFG in static
	Dialog.CFG = {
		'dialog-photo-roll-hidden-shots': CFG_Dialog_Hidden_Shots,
		'dialog-select-circles': CFG_Dialog_Select_Circles,
		'dialog-select-privacy': CFG_Dialog_Select_Privacy,
		'dialog-login': CFG_Dialog_Login,
		'dialog-alert': CFG_Dialog_Alert,
	};
	

	
	
	
	/*********************************************************************
	 * helper methods for Dialogs
	 */
	var DialogHelper = function(cfg) {};
	
	DialogHelper.showSigninDialog = function(cfg){
		cfg = cfg || {};
		cfg.uri = cfg.uri || '/users/signin';	// get XHR view	
		cfg.message = cfg.message ? '<div class="messages"><div class="message">'+cfg.message+'</div></div>' : '';
		cfg.tokens = {
			message: cfg.message,
		}
		delete cfg.message;
		var detach = _Y.on('snappi:dialog-body-rendered', function(){
			try {
				detach.detach();
				var username = PAGE.jsonData.User[0].username;
				_Y.one('#UserUsername').set('value', username);
			}catch(e){}
		});
		return SNAPPI.Alert.load(cfg);
	}
	DialogHelper.signIn = function(form){
		SNAPPI.setPageLoading(true);
		form = (form instanceof _Y.Node) ? form : form.ynode();
		postData = {};
		postData['data[User][username]'] = form.one('#UserUsername').get('value');
		postData['data[User][password]'] = form.one('#UserPassword').get('value');
		if (form.one('#UserMagic')) {
			form.one('#UserMagic').get("options").some(function(n, i, l) {
					// this = option from the select
						if (n.get('selected')) {
							postData['data[User][magic]'] = n.getAttribute('value') || '';
							return true;
						}
						return false;
					});
		}

		var uri = form.get('action');
		if (SNAPPI.AIR && SNAPPI.AIR.host) uri = "http://" + SNAPPI.AIR.host + uri;  
            
		// SNAPPI.io GET JSON  
		var container = form;
		// XhrHelper.resetSignInForm(container);
		
    	var args = {
    		node: container,
    		uri: uri,
    	};
    	/*
		 * plugin _Y.Plugin.IO
		 */
		if (container.io) container.unplug(SNAPPI.Y.Plugin.IO);
		var loadingmaskTarget = container;
		container.plug(_Y.LoadingMask, {
			strings: {loading:'One moment...'}, 	// BUG: A.LoadingMask
			target: loadingmaskTarget,
		});    			
		container.loadingmask._conf.data.value['target'] = loadingmaskTarget;
		container.loadingmask.overlayMask._conf.data.value['target'] = container.loadingmask._conf.data.value['target'];
		// container.loadingmask.set('target', target);
		// container.loadingmask.overlayMask.set('target', target);
		container.loadingmask.set('zIndex', 10);
		container.loadingmask.overlayMask.set('zIndex', 10);
		args.loadingmask = container.loadingmask;
		var	ioCfg = {
   					uri: args.uri,
					// parseContent: false,
					// autoLoad: false,
					context: container,
					arguments: args, 
					method: "POST",
					dataType: 'json',
					qs: postData,
					on: {
						successJson: function(e, i, o,args){
							var resp = o.responseJson;
							if (resp.response && resp.response.User) {
								args.loadingmask.hide();
								Dialog.find['dialog-alert'].hide();
							}
							return false;
						}, 
						complete: function(e, i, o, args) {
							args.loadingmask.hide();
						},
						failure : function (e, i, o, args) {
							// post failure or timeout
							var resp = o.responseJson || o.responseText || o.response;
							var msg = resp.message || resp;
							if (msg) {
								this.one('.message').setContent(msg).removeClass('hide');	
							}
							args.loadingmask.hide();
							SNAPPI.setPageLoading(false);
							return false;
						},
					}
			};
		container.loadingmask.show();	
		container.plug(_Y.Plugin.IO, SNAPPI.IO.pluginIO_RespondAsJson(ioCfg));
		return;		
	}	
	/**
	 * 
	 * useS ThumbnailFactory.PhotoPreview
	 * @params g SNAPPI.Gallery object
	 * @params selected obj, audition of selected item
	 */
	DialogHelper.bindSelected2DialogHiddenShot = function(g, selected) {
		// from MenuItems.showHiddenShot_click()
		var Y = SNAPPI.Y;
		var previewBody, previewSize,
			shotType = selected.Audition.Substitutions.shotType,
			dialog_ID = 'dialog-photo-roll-hidden-shots',
			dialog = SNAPPI.Dialog.find[dialog_ID],
    		args = {
    		selected : selected,
    		uuid: selected.id,
    		dialog: dialog,
        };
        
        if (g._cfg.type == 'Photo') {
        	g.setFocus(selected);  // set focus in Photo gallery, but not NavFilmstrip
        }
        if (!dialog) {
        	// create dialog
        	dialog = SNAPPI.Dialog.CFG[dialog_ID].load();
        	args.dialog = dialog;
        	try {		
	        	previewSize = SNAPPI.STATE.thumbSize.PhotoPreview_HiddenShot;
		    } catch (e){}
		   	if (!previewSize) previewSize = 'bp';
		   	
        	previewBody = _Y.Node.create('<section class="preview-body" />');
        	previewBody.setAttribute('size', previewSize);
        	// for this pattern: cfg.size = previewBody.getAttribute('size');  
        	dialog.setStdModContent('body', previewBody);
        	
        	previewBody.Dialog = dialog;
        	dialog.show();
        	
        	var loadingmaskTarget = dialog.getStdModNode('body');
			// plugin loadingmask
			previewBody.plug(_Y.LoadingMask, {
				strings: {loading:''}, 	// BUG: A.LoadingMask
				target: loadingmaskTarget,
			});
			// BUG: A.LoadingMask does not set target properly
			previewBody.loadingmask._conf.data.value['target'] = loadingmaskTarget;
			previewBody.loadingmask.overlayMask._conf.data.value['target'] = previewBody.loadingmask._conf.data.value['target'];
			previewBody.loadingmask.set('zIndex', 10);
    		previewBody.loadingmask.overlayMask.set('zIndex', 10);
			// start listeners
			
	        dialog.listen['preview-change'] = _Y.on('snappi:preview-change', 
	        	function(thumb){
	        		if (thumb.Thumbnail._cfg.type == 'PhotoPreview' ) 
	        			_Y.fire('snappi:dialog-body-rendered', dialog, {center:false, w:662});
	        	}, '.FigureBox.PhotoPreview figure > img', dialog
	        )
    		
        } else {
        	var doNotCenter = false;
        	// update/show dialog 
			if (!dialog.get('visible')) {
				dialog.show();
				Dialog.refresh(dialog, {center:false, w:662});
			}        	
			previewBody = dialog.getStdModNode('body').one('.preview-body');
        }
        
		// add preview markup to Dialog body, set initial preview size
		SNAPPI.Factory.Thumbnail.PhotoPreview.bindSelected(selected, previewBody, {gallery:g, size:previewSize});
		
		// add shotGallery, but PhotoPreview is bound to Photo gallery at this point		
       	var shotGallery = SNAPPI.Gallery.find['hiddenshot-'];
    	if (!shotGallery) {
			shotGallery = new SNAPPI.Gallery({
				type: 'DialogHiddenShot',
				node: previewBody,
				render: false,
			});
    	}   
    	
    	// TODO: bind shotGallery, move to ThumbnailFactory.PhotoPreview.bindShotGallery2Preview()
    	if (!shotGallery.view || shotGallery.view == 'minimize') {
    		SNAPPI.Factory.Gallery.actions.setView(shotGallery, 'one-row');
    	}      	
    	previewBody.loadingmask.refreshMask();
    	shotGallery.showShotGallery(selected, {
    		successJson: function(e, i,o,args) {
    			// same as Gallery.showShotGallery(), but add dialog.refresh()
					var response = o.responseJson.response;
					// get auditions from raw json castingCall
					var shotCC = response.castingCall;
					if (shotCC.CastingCall.Auditions.Total) {
	                    var options = {
	                    	uuid: args.uuid,
	                    	castingCall: shotCC,
	                    	replace: true,			// same as SNAPPI.Auditions.onDuplicate_ORIGINAL
	                    }
	                    this.render( options);		// render shot directly	
					}
                    _Y.fire('snappi:dialog-body-rendered', previewBody.Dialog, {center:doNotCenter});		
                    return false;					
				}
    		}
    	);
    	_Y.fire('snappi:dialog-body-rendered', previewBody.Dialog, {center:doNotCenter});
	};
	
	
	/**
	 * @deprecate. desktop Uploader uses #login from uploaderMarkup.ctp
	 * replace with showSigninDialog() posting to /users/signin/.json
	 * 
	 * Login Dialog
	 * @params g SNAPPI.Gallery object
	 * @params selected obj, audition of selected item
	 * 
	 */
	DialogHelper.showLogin = function(show) {
		if (show == undefined) show = true; 	// default
		// from MenuItems.showHiddenShot_click()
		
		var dialog_ID = 'dialog-login';
		var dialog = SNAPPI.Dialog.find[dialog_ID];
        var body;
        if (!dialog) {
        	// create dialog
        	dialog = SNAPPI.Dialog.CFG[dialog_ID].load();
        	if (SNAPPI.Dialog.CFG[dialog_ID].markup) {
        		body = _Y.Node.create(SNAPPI.Dialog.CFG[dialog_ID].markup);	
        	} else {
        		body = _Y.one('#login').removeClass('hide');
        	}
        	
        	dialog.setStdModContent('body', body);
        	body.Dialog = dialog;
        	dialog.show();
        	
        	// var loadingmaskTarget = dialog.getStdModNode('body');
			// // plugin loadingmask
			// body.plug(_Y.LoadingMask, {
				// strings: {loading:''}, 	// BUG: A.LoadingMask
				// target: loadingmaskTarget,
				// end: null
			// });
			// // BUG: A.LoadingMask does not set target properly
			// body.loadingmask._conf.data.value['target'] = loadingmaskTarget;
			// body.loadingmask.overlayMask._conf.data.value['target'] = body.loadingmask._conf.data.value['target'];
			// body.loadingmask.set('zIndex', 10);
    		// body.loadingmask.overlayMask.set('zIndex', 10);
        } else {
        	// update/show dialog 
       	
			body = dialog.getStdModNode('body').one('#login');
        }
    	if (!dialog.get('visible')) {
			dialog.show();
		} 
		// dialog.refresh();
        // start listeners
        
		// add preview markup to Dialog body, set initial preview size
		// body.loadingmask.refreshMask();
		// body.loadingmask.show();
    	
    	// body.loadingmask.refreshMask();
		// body.loadingmask.hide();
	};	
	
})();

