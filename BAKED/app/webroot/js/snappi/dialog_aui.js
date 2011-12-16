(function(){
	var _Y = null;
    SNAPPI.namespace('SNAPPI.onYready');
    SNAPPI.onYready.Dialog = function(Y){
		if (_Y === null) _Y = Y;
		SNAPPI.Dialog = Dialog;
		SNAPPI.namespace('SNAPPI.Helper');
		SNAPPI.Helper.Dialog = DialogHelper;
	}      
    

	var BUTTONS_OK_CANCEL = [{
			text: 'OK',
			handler: null
		},{
			test: 'Cancel',
			handler: function(){this.close();}
		}],
			
		BUTTONS_CLOSE =[{
			text: 'Close',
			handler: function(){this.close();}
		}];
	
	var DEFAULT_CFG_dialog = {
			centered: true,
			constrain2view: true,
			draggable: true,
			resizble: false,
			destroyOnClose: true,
			height: 250,
			width: 500,
			close: true,
			buttons: [],
			end:null
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
		var detach = d.get('closeChange', function(e){
			for (var i in this.listen) {
				this.listen[i].detach();
			}
		}, d)
		return detach;		
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
		dialog.cellOffsets = {
			bodyNodeOffset: {w:64, h:64}, // +19 px for scrollbar
		}		
		// resize dialog to show .preview-body
		dialog.refresh = function(previewBody){
			previewBody = previewBody instanceof _Y.Node ? previewBody : dialog.getStdModNode('body').one('.preview-body');
			if (previewBody) {
	        	var delayed = new _Y.DelayedTask( function() {
		        	var h = previewBody.get('clientHeight')+ this.cellOffsets.bodyNodeOffset.h;
					this.set('height', h );	
					delete delayed;					
				}, dialog);
				delayed.delay(100);  // wait 100 ms					
			}
		};

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
			title: 'My Circle',
			id: CSS_ID,
			width: 740,	// 3 columns, for now
			height: 395,	// 3 rows
			destroyOnClose: false,
			modal: true,
			buttons: [
			{
				// TODO: convert this toggle button to tabs in the contentBox
				text: 'Show Public Circles',
				handler: function() {
					var dialog = this;
					var uri = dialog.io.get('uri');
					if (uri != '/groups/open') {
						// toggle to public
						var cfg = {
							text: 'Show My Circles',
							title: 'Public Circles',
							uri: '/groups/open' 
						}
					} else {
						// toggle to memberships
						cfg = {
							text: 'Show Public Circles',
							title: 'My Circles',
							uri: '/my/groups' 
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
				text: 'Remove from Circle',
				handler: function() {
					var check;
					var content = this.get('contentBox');
					var selected = content.one('.selected');
					var gid = selected.get('id');
					var detach = _Y.on('snappi:share-complete', function(lightbox, loading, response){
						// TODO: show response in msg
						loading.loadingmask.hide();
						SNAPPI.multiSelect.clearAll(this.get('contentBox'));
						// update asset count in dialog
						detach.detach();
					}, this);
					var options = {
						data: {
							'data[Asset][unshare]': 1
						},
						uri: '/photos/setprop/.json'
					};
					SNAPPI.lightbox.applyShareInBatch(gid, selected, options);
				}
			},
			{
				text: 'Share with Circle',
				handler: function() {
					var check;
					var content = this.get('contentBox');
					var selected = content.one('.container .FigureBox.selected');
					var gid = selected.get('id');
					var detach = _Y.on('snappi:share-complete', function(lightbox, loading, response){
						loading.loadingmask.hide();
						SNAPPI.multiSelect.clearAll(this.get('contentBox').one('.container'));
						// TODO: show response in msg
						// update asset count in dialog
						detach.detach();
					}, this);
					SNAPPI.lightbox.applyShareInBatch(gid, selected);
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
				text: 'Apply',
				handler: function() {
					var content = this.get('contentBox');
					var selected = content.one('.selected');
					var value = parseInt(selected.getAttribute('value'));
					var detach = _Y.on('snappi:privacy-complete', function(lightbox, loading){
						loading.loadingmask.hide();
						// update asset count in dialog
						detach.detach();
					});
					SNAPPI.lightbox.applyPrivacyInBatch(value, selected);
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
			title: 'Sign In to Snaphappi',
			id: CSS_ID,
			height: 300,	// 3 rows
			destroyOnClose: false,
			modal: true,
			buttons: [
			// {
				// text: 'Sign in',
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
	CFG_Dialog_Login.markup = "";
	
	
	// save CFG in static
	Dialog.CFG = {
		'dialog-photo-roll-hidden-shots': CFG_Dialog_Hidden_Shots,
		'dialog-select-circles': CFG_Dialog_Select_Circles,
		'dialog-select-privacy': CFG_Dialog_Select_Privacy,
		'dialog-login': CFG_Dialog_Login,
	};
		
	
	
	
	
	/*********************************************************************
	 * helper methods for Dialogs
	 */
	var DialogHelper = function(cfg) {};
	
	/**
	 * @params g SNAPPI.Gallery object
	 * @params selected obj, audition of selected item
	 */
	DialogHelper.bindSelected2DialogHiddenShot = function(g, selected) {
		// from MenuItems.showHiddenShot_click()
		var Y = SNAPPI.Y;
		var shotType = selected.Audition.Substitutions.shotType;
		
		var dialog_ID = 'dialog-photo-roll-hidden-shots';
		var dialog = SNAPPI.Dialog.find[dialog_ID];
		
    	var args = {
    		selected : selected,
    		uuid: selected.id,
    		dialog: dialog,
        }; 
        var previewBody, previewSize;
        if (!dialog) {
        	// create dialog
        	dialog = SNAPPI.Dialog.CFG[dialog_ID].load();
        	args.dialog = dialog;
        	
        	try {		
	        	var DEFAULT_THUMBSIZE = 'bm';
	        	// save in PreviewPhoto.size property for later use
	        	previewSize = PAGE.jsonData.profile.thumbSize[dialog_ID] || DEFAULT_THUMBSIZE;
	        } catch (e){
	        	previewSize = DEFAULT_THUMBSIZE;
	        } 
        	previewBody = _Y.Node.create('<section class="preview-body" />')
        	previewBody.setAttribute('size', previewSize);
        	
        	dialog.setStdModContent('body', previewBody);
        	previewBody.Dialog = dialog;
        	dialog.show();
        	
        	var loadingmaskTarget = dialog.getStdModNode('body');
			// plugin loadingmask
			previewBody.plug(_Y.LoadingMask, {
				strings: {loading:''}, 	// BUG: A.LoadingMask
				target: loadingmaskTarget,
				end: null
			});
			// BUG: A.LoadingMask does not set target properly
			previewBody.loadingmask._conf.data.value['target'] = loadingmaskTarget;
			previewBody.loadingmask.overlayMask._conf.data.value['target'] = previewBody.loadingmask._conf.data.value['target'];
			previewBody.loadingmask.set('zIndex', 10);
    		previewBody.loadingmask.overlayMask.set('zIndex', 10);
        } else {
        	// update/show dialog 
			if (!dialog.get('visible')) {
				dialog.show();
			}        	
			previewBody = dialog.getStdModNode('body').one('.preview-body');
			previewSize = null; // use size from existing Thumbnail.PhotoPreview
        }
        // start listeners
        
		// add preview markup to Dialog body, set initial preview size
		previewBody.loadingmask.refreshMask();
		previewBody.loadingmask.show();
		SNAPPI.Factory.Thumbnail.PhotoPreview.bindSelected(selected, previewBody, previewSize);
		
		// add shotGallery		
       	var shotGallery = SNAPPI.Gallery.find['hiddenshot-'];
    	if (!shotGallery) {
			shotGallery = new SNAPPI.Gallery({
				type: 'DialogHiddenShot',
				node: previewBody,
				render: false,
				// size: 'sq',
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
    			// TODO: use Gallery.showShotGallery() codepath
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
                    // use custom event here instead?				
                    previewBody.Dialog.refresh(previewBody);
                    return false;					
				}
    		}
    	);
		dialog.refresh(); 	// resize Dialog, and again when shotGallery.render() complete
	};
	
	
	/**
	 * Login Dialog
	 * @params g SNAPPI.Gallery object
	 * @params selected obj, audition of selected item
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