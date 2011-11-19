/**
 * 
 * Copyright (c) 2009-2011, Snaphappi.com. All rights reserved.
 * 
 * This program is free software: you can redistribute it and/or modify it under
 * the terms of the Affero GNU General Public License as published by the Free
 * Software Foundation, either version 3 of the License, or (at your option) any
 * later version.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the Affero GNU General Public License for more
 * details.
 * 
 * You should have received a copy of the Affero GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * 
 * 
 * @author Michael Lin, info@snaphappi.com
 * 
 * 
 */
// console.log("load BEGIN: ui-helpers.js");	
// (function() {
	
	SNAPPI.namespace('SNAPPI.STATE');
			
	/***************************************************************************
	 * UIHelpers Static Class
	 * 	SNAPPI.AIR.UIHelpers = UIHelpers;
	 */
	var UIHelper = function(){	}
	UIHelper.prototype = {};
	SNAPPI.AIR.UIHelper = UIHelper;

	
	UIHelper.set_Folder = function(node, target){
		if (SNAPPI.AIR.uploadQueue.isUploading()) {
			var detach = Y.on('snappi-air:upload-status-changed', function(isUploading){
				if(!isUploading){
					detach.detach();
					UIHelper.set_Folder(node, target);
				};						
			}, this);			
			SNAPPI.AIR.UIHelper.toggle_upload(null, false);	// force pause 
			return;
		}
		// node == selected li/menuItem
		// target = menu trigger
		SNAPPI.AIR.Helpers.init_GalleryLoadingMask();
						
		var Y = SNAPPI.Y;
		var folder = node.hasAttribute('baseurl') ? node.getAttribute('baseurl') : node.get('innerHTML');			
LOG("+++ set folder, folder="+folder);		
		var delayed = new Y.DelayedTask( function() {
			node.siblings('li').removeClass('focus');
			node.addClass('focus');	
							
			// SNAPPI.AIR.Helpers.initUploadGallery(null, 1, null, null, folder);		// reload gallery with new baseurl
			var uploader = SNAPPI.AIR.uploadQueue,
				page = 1;
			uploader.initQueue(uploader.status, {
				folder: folder, 				// folder='all' => baseurl=''
				page: page,
			});
			uploader.view_showPage();
			// var p = SNAPPI.Paginator.find['PhotoAirUpload'];
			// SNAPPI.Paginator._getPageFromAirDs(p.container, page);
			delete delayed;		
			Y.one('body').removeClass('wait');
		});
		delayed.delay(100);  // wait 100 ms					
	};
	UIHelper.set_UploadBatchid = function(node){
		// node == selected li/menuItem
		// target = menu trigger
		try {
			var pluginNode = target.ancestor('#gallery-container').one('.gallery.photo');			
			pluginNode.loadingmask.refreshMask();
			pluginNode.loadingmask.show();
		} catch(e) {
		}
		var delayed = new Y.DelayedTask( function() {
			var batchid = node.getAttribute('batch');
			SNAPPI.AIR.Helpers.initUploadGallery(null, 1, null, batchid);		// reload gallery with new baseurl
			
			node.siblings('li').removeClass('focus');
			node.addClass('focus');	
			delete delayed;		
		});
		delayed.delay(100);  // wait 100 ms				
	};
	UIHelper.toggle_upload = function(el, force) {
		SNAPPI.setPageLoading(true);
		if (SNAPPI.AIR.Helpers.isAuthorized()) {
			if (!el) {
				var n = SNAPPI.Y.one('.gallery-header .upload-toolbar li.btn.start');
				el = n.getDOMNode();
			} else {
				// hide loadingmask, just in case
				try {
					var pluginNode = SNAPPI.Y.one('#gallery-container .gallery.photo');
LOG(pluginNode);		
lm = pluginNode.loadingmask;			
					pluginNode.loadingmask.hide();
LOG("+++ DEBUGGING: UIHelper.toggle_upload(). loadingmask.hide()");					
				} catch(e) {
LOG("+++ EXCEPTION: loadingmask.hide()");						
				}
			}
			var state = el.innerHTML;
			if (force===false) state = 'Pause Upload';
			if (force===true) state = 'Resume Upload';
			if (state == 'Pause Upload') {
				// n.set('innerHTML', 'Resume Upload');
				el.innerHTML = "Resume Upload";
				SNAPPI.AIR.uploadQueue.action_pause();
			} else {
				el.innerHTML = "Pause Upload";
				SNAPPI.AIR.uploadQueue.action_start();
			}
		} else {
			// show login screen by menu click
			SNAPPI.MenuAUI.find['menu-sign-in-markup'].show();
			SNAPPI.setPageLoading(false);
		}
	};	
	
	UIHelper.toggle_ContextMenu = function(e) {
		// copied from SNAPPI.Gallery
    	var CSS_ID = 'contextmenu-photoroll-markup';
    	if (e==false && !SNAPPI.MenuAUI.find[CSS_ID]) return;
    	if (e) e.preventDefault();
    	// load/toggle contextmenu
    	if (!SNAPPI.MenuAUI.find[CSS_ID]) {
    		var contextMenuCfg = {
    			currentTarget: e.currentTarget,
    			triggerRoot:  SNAPPI.Y.one('.gallery.photo .container'),
    			init_hidden: false,
			}; 
    		SNAPPI.MenuAUI.CFG[CSS_ID].load(contextMenuCfg);
    	} else {
LOG("toggle CONTEXT MENU, E="+e);		    		
    		var menu = SNAPPI.MenuAUI.toggleEnabled(CSS_ID, e);
    	}		
	}
	/*
	 * manage all UI listeners
	 */
	UIHelper.listen = {};
	UIHelper.listeners = {
	        /*
	         * Click-Action listener/handlers
	         * 	start 'click' listener for action=
	         * 		set-display-size:[size] 
	         * 		set-display-view:[mode]
	         */
	        WindowOptionClick : function(node) {
	        	var Y = SNAPPI.Y;
	        	node = node || Y.one('body');
	        	var action = 'WindowOptionClick';
	        	node.listen = node.listen || {};
	            if (node.listen[action] == undefined) {
					node.listen[action] = node.delegate('click', 
		                function(e){
		                	// action=[set-display-size:[size] | set-display-view:[mode]]
		                	// context = UIHelper
		                	UIHelper.toggle_ContextMenu(false);	// hide contextmenu
		                	var action = e.currentTarget.getAttribute('action').split(':');
				    		switch(action[0]) {
				    			case 'set-display-view':
				    				UIHelper.actions.setDisplayView(action[1]);
				    				break;
				    			case 'toggle-display-options':
				    				UIHelper.actions.toggleDisplayOptions();
				    				break;
				    		}		                	
		                }, 'nav.window-options > ul > li', UIHelper);
				}
				// back reference
				UIHelper.listen[action] = node.listen[action];
	        },	        
	        DisplayOptionClick : function(node) {
	        	var Y = SNAPPI.Y;
	        	node = node || Y.one('.gallery-display-options');
	        	var action = 'DisplayOptionClick';
	        	
	        	node.listen = node.listen || {};
	            if (node.listen[action] == undefined) {
					node.listen[action] = node.delegate('click', 
		                function(e){
		                	UIHelper.toggle_ContextMenu(false);	// hide contextmenu
		                	var action = e.currentTarget.getAttribute('action').split(':');
				    		switch(action[0]) {
				    			case 'filter':
				    				UIHelper.actions['filter'](e.currentTarget, action[1]);
				    				break;
				    			case 'folder':
				    				// uses MenuItems.uploader_setFolder_click()
				    				// to call set_UploadBatchid(menuItem) or set_Folder(menuItem)
				    				break;
				    			case 'retry':
				    				UIHelper.actions['retry'](e.currentTarget, action[1]);
				    				break;
				    		}		                	
		                }, 'ul > li.btn', UIHelper);
				}
				// back reference
				UIHelper.listen[action] = node.listen[action];
	        },  		
	        ContextMenuClick : function(node) {
	        	var Y = SNAPPI.Y;
	        	node = node || Y.one('.gallery.photo .container');
	        	var action = 'ContextMenuClick';
	        	
	        	node.listen = node.listen || {};
	            if (node.listen[action] == undefined) {
					node.listen[action] = node.delegate('contextmenu', 
		                function(e){
		                	UIHelper.toggle_ContextMenu(e);
		                	e.stopImmediatePropagation();
		                }, '.FigureBox', UIHelper);
				}
				// back reference
				UIHelper.listen[action] = node.listen[action];
	        }, 	        
	        MultiSelect : function (node) {
	        	var Y = SNAPPI.Y;
	        	node = node || Y.one('.gallery.photo .container');
	        	var container = node;
	        	var action = 'MultiSelect';
	        	
	        	node.listen = node.listen || {};
	            if (node.listen[action] == undefined) {
	            	SNAPPI.multiSelect.listen(node, true);
				}
				// back reference
				UIHelper.listen[action] = node.listen[action];	        	
	        	
	        	// select-all checkbox listener
	        	var galleryHeader = Y.one('#gallery-container .gallery-header');
	        	if (galleryHeader && !container.listen['selectAll']) {
		        	container.listen['selectAll'] = galleryHeader.delegate('click', 
		        	function(e){
		        		var checked = e.currentTarget.get('checked');
		        		if (checked) this.all('.FigureBox').addClass('selected');
		        		else {
		        			this.all('.FigureBox').removeClass('selected');
		        			SNAPPI.STATE.selectAllPages = false;
		        		}
		        	},'li.select-all input[type="checkbox"]', container);
	        	}
	        	return;
	        },	        
	}
	// for lister getAttribute('action') handlers
	UIHelper.actions = {
		setDisplayView : function(view) {
			// show/hide body
			var body = SNAPPI.Y.one('#item-body');
			if (view=='minimize') body.addClass('hide');
			else body.removeClass('hide');
			// flex_setDropTarget: js global defined in snaphappi.mxml,
			flex_setDropTarget();		 // reset dropTarget in Flex
		},
		toggleDisplayOptions  : function(o){
			var Y = SNAPPI.Y;
			SNAPPI.AIR.UIHelper.toggle_ContextMenu(false);	// hide contextmenu
			try {
				SNAPPI.STATE.showDisplayOptions = SNAPPI.STATE.showDisplayOptions ? 0 : 1;
				UIHelper.actions.setDisplayOptions();
			} catch (e) {}
		},
		setDisplayOptions : function(){
			var Y = SNAPPI.Y;
			try {
				if (SNAPPI.STATE.showDisplayOptions) {
					Y.one('section.gallery-header li.display-option').addClass('open');
					Y.one('section.gallery-display-options').removeClass('hide');
				} else {
					Y.one('section.gallery-header li.display-option').removeClass('open');
					Y.one('section.gallery-display-options').addClass('hide');
				}	
			} catch (e) {}
		},		
		/*
		 * NOTE: ok to change filter while SNAPPI.AIR.UploadManager.isUploading 
		 */
		filter : function(node, value) {
			if (SNAPPI.AIR.uploadQueue.isUploading()) {
				var detach = Y.on('snappi-air:upload-status-changed', function(isUploading){
					if(!isUploading){
						detach.detach();
						this.filter(node, value);
					};						
				}, this);			
				SNAPPI.AIR.UIHelper.toggle_upload(null, false);	// force pause 
				return;
			}				
			// try {
				// var pluginNode = node.ancestor('#gallery-container').one('.gallery.photo');			
				// pluginNode.loadingmask.refreshMask();
				// pluginNode.loadingmask.show();
			// } catch(e) {}
LOG("+++ set filter, status="+value);			
			SNAPPI.AIR.Helpers.init_GalleryLoadingMask();
			var Y = SNAPPI.Y;
			var delayed = new Y.DelayedTask( function() {
				// SNAPPI.AIR.uploadQueue.show(value);
				var uploader = SNAPPI.AIR.uploadQueue,
					page = 1;
				uploader.initQueue(value, {
					page: page,
				});
				uploader.view_showPage();
				
				node.siblings('li.btn').removeClass('focus');
				node.addClass('focus');
				delete delayed;
				SNAPPI.setPageLoading(false);
			});
			delayed.delay(100);  // wait 100 ms		
		},
		retry : function() {
			if (SNAPPI.AIR.uploadQueue.isUploading()) {
				var detach = Y.on('snappi-air:upload-status-changed', function(isUploading){
					if(!isUploading){
						detach.detach();
						SNAPPI.AIR.uploadQueue.action_retry();
						SNAPPI.setPageLoading(false);
					};						
				}, this);			
				SNAPPI.AIR.UIHelper.toggle_upload(null, false);	// force pause 
				return;
			}				
			SNAPPI.AIR.uploadQueue.action_retry();
		},
	}	
	
	UIHelper.menu = {
		/**
		 * load open/closed batches into menu
		 * @params node, menu contentBox
		 * @reload boolean, default false. rebuild menu if true
		 */		
		load_batches: function(node, reload) {
			try {
				node = node.one('ul')
				if (node.all('li').size() && !reload) return;
				
				var Y = SNAPPI.Y;
				// folders = upload batches
				var batches = SNAPPI.AIR.uploadQueue.flexUploadAPI.getBatchIdsFromDB(),
					currentBatchid = SNAPPI.AIR.uploadQueue.batchId;
				var li, label, batchid;

				// for All imported folders
				label = 'All photos';
				batchid = '';
				li = node.create("<li></li>");
				li.setContent(label).setAttribute('batch', batchid).setAttribute('action', 'uploader_setFolder');
				if (!currentBatchid) li.addClass('focus');					
				node.append(li);
				// open batches
				for (var i in batches.open) {
					batchid = batches.open[i];
					label = batchid;
					li = node.create("<li></li>");
					li.setContent(label).setAttribute('batch', batchid).setAttribute('action', 'uploader_setFolder');
					if (batchid == currentBatchid) li.addClass('focus');
					node.append(li);	
				}				
				// closed batches
				for (var i in batches.closed) {
					batchid = batches.closed[i];
					label = batchid + ' (done)';
					li = node.create("<li></li>");
					li.setContent(label).setAttribute('batch', batchid).setAttribute('action', 'uploader_setFolder');
					if (batchid == currentBatchid) li.addClass('focus');
					node.append(li);	
				}					
			} catch (e) {}
			// add listener
			node.listen = node.listen || {};
			if (!node.listen['import-complete']) {
				node.listen['import-complete'] = Y.on('snappi-air:import-complete', function(){
					// reset upload batch folders, will automatically rebuild
		            try {
		            	SNAPPI.Y.one('#markup #menu-uploader-batch-markup ul').setContent('');	
		            } catch (e) {}
				});
			}
		},
		/**
		 * load baseurls into menu
		 * 	NOTE: uploadQueue.initQueue() filters on batchid, not baseurl
		 * @params node, menu contentBox
		 * @reload boolean, default false. rebuild menu if true
		 */
		load_folders: function(node, reload) {
			try {
				node = node.one('ul')
				if (node.all('li').size() && !reload) return;
				
				var Y = SNAPPI.Y;
				// folders = baseurls	
				var folders =  SNAPPI.AIR.uploadQueue.ds.getBaseurls(),
				selected = SNAPPI.AIR.uploadQueue.baseurl;					
LOG('>>>>>>> BASEURL='+selected);				
				var li, longname;
				folders.unshift('All imported folders');		// for All imported folders
				for (var i in folders) {
					longname = folders[i];
					li = node.create("<li></li>");
					li.setContent(longname).setAttribute('action', 'uploader_setFolder');
					if (longname == 'All imported folders') li.setAttribute('baseurl', 'all');
					if (longname == selected) li.addClass('focus');
					if (!selected && longname=='All imported folders') li.addClass('focus');
					node.append(li);	
				}
			} catch (e) {}
		}
	};
	
	
	LOG("load complete: ui-helpers.js");	
// }());



// (function() {
	/***************************************************************************
	 * XhrHelper Static Class
	 * 		SNAPPI.AIR.XhrHelper = XhrHelper;
	 * 	WARNING: 
	 */
	var XhrHelper = function() {}
	XhrHelper.prototype = {};
	SNAPPI.AIR.XhrHelper = XhrHelper;
	
		
	/*
	 * private methods
	 */
	XhrHelper._setUser = function(user) {
		var Y = SNAPPI.Y;
		SNAPPI.namespace('SNAPPI.STATE');
		SNAPPI.STATE.user = user;
		var expressNode = Y.one('#gallery-container .express-upload-container');
		if (user && user.id) {
			try {
	//			var datasource = _flexAPI_UI.datasource;
				var datasource = SNAPPI.DATASOURCE;
				datasource.setSessionId('data[User][id]=' + user.id);
	LOG(">>> UPLOAD HOST=" + datasource.getConfigs().uploadHost + ", uuid=" + SNAPPI.DATASOURCE.sessionId);
	
				// cleanup actions: hide menu
				var menu = SNAPPI.MenuAUI.find['menu-sign-in-markup'];
				menu.hide();
				
				// add express Upload section
				XhrHelper.insertExpressUpload(expressNode);	
			} catch (e) {
			}
		} else {
			var datasource = SNAPPI.DATASOURCE;
			datasource.setSessionId('data[User][id]=');
			XhrHelper.insertExpressUpload(expressNode, true);
			SNAPPI.setPageLoading(false);
		}
		XhrHelper._setHeader(user);
	}
	XhrHelper._setHeader = function(user) {
		var Y = SNAPPI.Y;
		var userHeader = Y.one('header nav.user');
		if (user && user.id) {
			// update 'header nav.user' 
			userHeader.one('#userAccountBtn').setContent(user.username);
			userHeader.one('ul.authenticated').removeClass('hide');
			userHeader.one('ul.guest').addClass('hide');			
		} else {
			userHeader.one('#userAccountBtn').setContent('');
			userHeader.one('ul.authenticated').addClass('hide');
			userHeader.one('ul.guest').removeClass('hide');			
		}
	}
	XhrHelper.resetSignInForm = function(container) {
		if (typeof container == "string") container = SNAPPI.Y.one(container);
		// reset Sign-in dialog
		try {
			container.one('.message').setContent('').addClass('hide');
			container.all('input').each(function(n,i,l){ n.set('value',null ); });
			container.loadingmask.hide();	
		} catch (e) {}		
		
	}
	/*
	 * static methods
	 */
	XhrHelper.login_Url = null;
	
	/*
	 * json signIn
	 */
	XhrHelper.signIn = function() {
		var Y = SNAPPI.Y;
		SNAPPI.setPageLoading(true);
		var postData = {};
		Y.all('form#UserLoginForm .postData').each(function(n, i, l) {
			var key = n.getAttribute('name');
			switch (key) {
			case 'data[User][username]':
			case 'data[User][password]':
			case 'UserUsername':
			case 'UserPassword':
				postData[key] = n.get('value');
				break;
			case 'data[User][magic]':
			case 'UserMagic':
				n.get("options").some(function(n, i, l) {
					// this = option from the select
						if (n.get('selected')) {
							postData[key] = n.getAttribute('value') || '';
							return true;
						}
						;
						return false;
					});
				break;
			default:
				// skip
				break;
			}
		});
		var uri = XhrHelper.login_Url || "http://" + SNAPPI.AIR.host + "/users/login/.json?optional=1";
LOG(">>> LOGIN, login_Url="+uri+", postdata follows:");
LOG(postData);
            
		// SNAPPI.io GET JSON  
		var container = Y.one('#menu-sign-in-markup #login');
		// XhrHelper.resetSignInForm(container);
		
    	var args = {
    		node: container,
    		uri: uri,
    	};
    	/*
		 * plugin Y.Plugin.IO
		 * TODO: refactor. pattern also used in AssetRatingController.setProp()
		 */
		var ioCfg;
		if (!container.io) {
			ioCfg = {
   					// uri: args.uri,
					parseContent: false,
					autoLoad: false,
					context: container,
					arguments: args, 
					method: "POST",
					dataType: 'json',
					qs: postData,
					on: {
						successJson: function(e, i, o,args){
							var resp = o.responseJson;
// LOG(' >>>>>>>>>>>>>  successJson    ');							
// LOG(resp);
							var authUser = resp.response.User;
							XhrHelper._setUser(authUser);
							this.one('.message').setContent('').addClass('hide');
							args.loadingmask.hide();
							return false;
						}, 
						complete: function(e, i, o, args) {
// LOG(' >>>>>>>>>>>>>  complete ');								
// LOG(o);
							args.loadingmask.hide();
						},
						failure : function (e, i, o, args) {
							// post failure or timeout
// LOG(' >>>>>>>>>>>>>  LOGIN XHR FAILURE    ');	
// LOG(o);
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
			// var loadingmaskTarget = container.get('parentNode');
			var loadingmaskTarget = container.one('div');
			// set loadingmask to parent
			container.plug(Y.LoadingMask, {
				strings: {loading:'One moment...'}, 	// BUG: A.LoadingMask
				target: loadingmaskTarget,
			});    			
			container.loadingmask._conf.data.value['target'] = loadingmaskTarget;
			container.loadingmask.overlayMask._conf.data.value['target'] = container.loadingmask._conf.data.value['target'];
			// container.loadingmask.set('target', target);
			// container.loadingmask.overlayMask.set('target', target);
			container.loadingmask.set('zIndex', 10);
			container.loadingmask.overlayMask.set('zIndex', 10);
			container.plug(Y.Plugin.IO, SNAPPI.IO.pluginIO_RespondAsJson(ioCfg));
		} else {
			ioCfg = {
				arguments: args, 
				method: "POST",
				qs: postData,
			}
			ioCfg = SNAPPI.IO.pluginIO_RespondAsJson(ioCfg); 
			container.io.set('data', ioCfg.data);
		}
		args.loadingmask = container.loadingmask;
		// get CC via XHR and render
		container.io.set('uri', args.uri);
		container.io.set('arguments', args);
		container.loadingmask.refreshMask();
		container.loadingmask.show();		
		container.io.start();
		return;
	}

	/*
	 * sign out, close session
	 */
	XhrHelper.signOut = function(){
		XhrHelper._setUser(null);
		var url = '/users/logout';
		var callback = {
			complete : function(id, o, args) {
				var resp = o.response;
			}
		}		
		SNAPPI.io.get.call(this, url, callback);
	}
	
	XhrHelper.insertExpressUpload = function(node, clear) {
		var Y = SNAPPI.Y;
		node = node || Y.one('#gallery-container .express-upload-container');
		// reset content before use
		if (clear) {
			node.setContent('');
			if (node.io) node.unplug(Y.Plugin.IO);	
		} else {
			var ioCfg = {
				uri:  '/my/express_uploads',
				parseContent: true,
				autoLoad: true,
				modal: false,
				context: node,
				dataType: 'html',
				on: {
					success: function(e,i,o,args){
						SNAPPI.setPageLoading(false);
						var body = this.create(o.responseText);
						var found = body.one('ul li input[type=checkbox]');
						return (found)? body : false;
					},
					failure: function(e,i,o,args) {
						return this.create('<aside id="express-upload-options"  class="related-content blue">').setContent(o.responseText);
					}
					
				}
			};
			ioCfg = SNAPPI.IO.getIORequestCfg(ioCfg.uri, ioCfg.on, ioCfg);
			SNAPPI.setPageLoading(true);
			node.plug(Y.Plugin.IO, ioCfg);	
		}
	}	
	XhrHelper.getExpressUploads = function(){
		var Y = SNAPPI.Y;
		var gids = [];
		Y.all('#express-upload-options input[type=checkbox]').each(function(n,i,l){
			if (n.get('checked')) gids.push(n.getAttribute('uuid'));
		});
		return gids.join(',');
	}
	LOG("load complete: XhrHelpers.js");	
// }());