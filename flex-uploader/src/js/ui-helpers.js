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
(function() {
		
	var _Y = null;
    SNAPPI.namespace('SNAPPI.onYready');
    SNAPPI.onYready.UIHelper = function(Y){
		if (_Y === null) _Y = Y;
		SNAPPI.AIR.UIHelper = UIHelper;
		
		SNAPPI.AIR.onDrop = UIHelper.actions.onDrop;
	}
		
	SNAPPI.namespace('SNAPPI.STATE');
			
	/***************************************************************************
	 * UIHelpers Static Class
	 * 	SNAPPI.AIR.UIHelpers = UIHelpers;
	 */
	var UIHelper = function(){	}
	
	UIHelper.isAuthorized = function() {
		try {
			var userid = SNAPPI.STATE.user.id;
			return userid;
		} catch (e) {
		}
		return false;
	}
	
	UIHelper.set_Folder = function(node, target){
		if (SNAPPI.AIR.uploadQueue.isUploading()) {
			var detach = _Y.on('snappi-air:upload-status-changed', function(isUploading){
				if(!isUploading){
					detach.detach();
					UIHelper.set_Folder(node, target);
				};						
			}, this);		
			UIHelper.toggle_upload(null, false);	// force pause 
			return;
		}
		// node == selected li/menuItem
		// target = menu trigger
		SNAPPI.AIR.Helpers.init_GalleryLoadingMask();
						
		
		var folder = node.hasAttribute('baseurl') ? node.getAttribute('baseurl') : node.get('innerHTML');			
LOG("+++ set folder, folder="+folder);		
		var delayed = new _Y.DelayedTask( function() {
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
			_Y.one('body').removeClass('wait');
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
		var delayed = new _Y.DelayedTask( function() {
			var batchid = node.getAttribute('batch');
			SNAPPI.AIR.Helpers.initUploadGallery(null, 1, null, batchid);		// reload gallery with new baseurl
			
			node.siblings('li').removeClass('focus');
			node.addClass('focus');	
			delete delayed;		
		});
		delayed.delay(100);  // wait 100 ms				
	};
	UIHelper.toggle_upload = function(el, force) {
		el = el || '.gallery-header .upload-toolbar li.btn.start';
		var node = _Y.one(el);
		SNAPPI.setPageLoading(true);
		if (UIHelper.isAuthorized()) {
				// hide loadingmask, just in case
				try {
					var pluginNode = _Y.one('#gallery-container .gallery.photo');
// LOG(pluginNode);		
// lm = pluginNode.loadingmask;			
					pluginNode.loadingmask.hide();
LOG("+++ DEBUGGING: UIHelper.toggle_upload(). loadingmask.hide()");					
				} catch(e) {
LOG("+++ EXCEPTION: loadingmask.hide()");						
				}

			/*
			 * status = [ready| uploading| pause]
			 */			
			var statusLabel = {
				'ready': 'Start Upload',
				'uploading': 'Pause Upload',
				'pause': 'Resume Upload',
			}	
			var status = node.getAttribute('status') || 'ready';
			if (force===false) status = 'pause';
			if (force===true) status = 'uploading';
			if (force=='done') status = 'done';
			if (status == 'pause' || status == 'ready') {
				// n.set('innerHTML', 'Resume Upload');
				status = 'uploading';
				SNAPPI.AIR.uploadQueue.action_start();
			} else if (status == 'uploading') {
				status = "pause";
				SNAPPI.AIR.uploadQueue.action_pause();
			} else if (status == 'done') {
				status = "ready";
				SNAPPI.AIR.uploadQueue.action_pause();
			}
			node.setContent(statusLabel[status]).setAttribute('status', status);
			
		} else {
			// show login screen by menu click
			var menu = SNAPPI.MenuAUI.find['menu-sign-in-markup'].show();
			var detach = _Y.on('snappi-air:sign-in-success', function(){
				detach.detach();
				// startup upload after login
				UIHelper.toggle_upload();
			})
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
    			triggerRoot:  _Y.one('.gallery.photo .container'),
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
	        	
	        	node = node || _Y.one('body');
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
	        	
	        	node = node || _Y.one('.gallery-display-options');
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
	        	
	        	node = node || _Y.one('.gallery.photo .container');
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
	        	
	        	node = node || _Y.one('.gallery.photo .container');
	        	var container = node;
	        	var action = 'MultiSelect';
	        	
	        	node.listen = node.listen || {};
	            if (node.listen[action] == undefined) {
	            	SNAPPI.multiSelect.listen(node, true);
				}
				// back reference
				UIHelper.listen[action] = node.listen[action];	        	
	        	
	        	// select-all checkbox listener
	        	var galleryHeader = _Y.one('#gallery-container .gallery-header');
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
	        /**
	         * 'snappi-air:import-complete' fired by UploaderUI.as: onScanFoldersComplete()
	         */
	        ImportComplete : function(node)	{
	        	var action = 'ImportComplete';
	        	node = node || _Y.one('#drop-target');	// who is listening???
	        	node.listen = node.listen || {};
	            if (node.listen[action] == undefined) {
	            	node.listen[action] = _Y.on('snappi-air:import-complete', 
	            	function(args){
						// LOG('ON snappi-air:import-complete');	
	            		try {	// show folder baseurl
	            			var added = UIHelper.actions.addToUploader(SNAPPI.AIR.uploadQueue, args.baseurl);
	            		} catch (e) {}
	            		
	            		// show next steps
	            		// var uri = "http://" + SNAPPI.AIR.host + '/combo/markup/importComplete';
	            		var tokens = {
	            			folder: args.baseurl,
	            			count: args.count,
	            			added: added,
	            		}
	            		// show dialog-alert .alert-import-complete 
			 			var alert = SNAPPI.Alert.load({
		    				selector: '#markup .alert-import-complete',
		    				height: 340,
		    				tokens: tokens,
			    		});
			    		if (tokens.added < tokens.count) {
			    			var body = alert.getStdModNode('body');
			    			body.one('.added').removeClass('hide');
			    		}
	            	});
				}
	        },        
	}
	// for lister getAttribute('action') handlers
	UIHelper.actions = {
		/*
	     * called inernally when drop a folder
	     * called with one param as string i.e. folder's nativepath
	     * no return value
	     * */
		onDrop : function(droppedFolder, uploader){
	    	LOG(SNAPPI.AIR.uploadQueue);
	    	uploader = uploader ||  SNAPPI.AIR.uploadQueue;
	    	uploader.onDrop.call(this, droppedFolder);
	    },
		'goto' : function (o) {
			window.location.href = o.options[o.selectedIndex].value;
		}, 
		/*
		 * opens NATIVE browser from Flex
		 */ 
		'openPage' : function(page) {
			// domParent.domWindow.flexAPI_UI = Config.UI == UploaderUI (UploaderUI.as);
			_flexAPI_UI.openPage(page);
			return false;
		},		
		'orderBy' : function (o) {
			window.location.href = o.options[o.selectedIndex].value;
		},
		/**
		 * add imported photos (by baseurl) to uploadQueue, creates a new batchid
		 *	@params uploader OPTIONAL, uses global SNAPPI.AIR.UploadQueue
		 *	@params baseurl string OPTIONAL, add all baseurls if null 
		 *	@return int - count of photos added
		 */
		addToUploader : function(uploader, baseurl){
	//  baseurl = baseurl || 'C:\\USERS\\michael\\Pictures\\importTest';
			uploader = uploader || SNAPPI.AIR.uploadQueue;
	        var query = {	
	        		page: 1,
	                perpage: 1999,
	                baseurl: baseurl  
	            };
	        var added = 0;
LOG("SNAPPI.AIR.UIHelper.actions.addToUploader  ****************************************************");
	        var datasource = uploader.ds;
	        datasource.getAuditions_all(query, function (auditions) {
	        	LOG("datasource.getAuditions_all");
	            //before adding photos to upload queue set batch_id first
	            added = uploader.flexUploadAPI.addToUploadQueue(auditions);
	            var batchId = uploader.flexUploadAPI.getLastOpenBatchId();
LOG("added to uploadQueue, count="+added+", open batchId="+batchId);			                
	            uploader.show("reload");
	        }, datasource, false);
	        // open added folder
			var delayed = new _Y.DelayedTask( function() {
				var uploader = SNAPPI.AIR.uploadQueue;
				uploader.initQueue('pending', {
					folder: baseurl, 				// folder='all' => baseurl=''
					page: 1,
				});
				uploader.view_showPage();
				delete delayed;		
				_Y.one('body').removeClass('wait');
			});
			delayed.delay(100);  // wait 100 ms	        
	        return added;
	    },
		setDisplayView : function(view) {
			// show/hide body
			var body = _Y.one('#item-body');
			if (view=='minimize') body.addClass('hide');
			else body.removeClass('hide');
			// flex_setDropTarget: js global defined in snaphappi.mxml,
			flex_setDropTarget();		 // reset dropTarget in Flex
		},
		toggleDisplayOptions  : function(o){
			
			SNAPPI.AIR.UIHelper.toggle_ContextMenu(false);	// hide contextmenu
			try {
				SNAPPI.STATE.showDisplayOptions = SNAPPI.STATE.showDisplayOptions ? 0 : 1;
				UIHelper.actions.setDisplayOptions();
			} catch (e) {}
		},
		setDisplayOptions : function(){
			
			try {
				if (SNAPPI.STATE.showDisplayOptions) {
					_Y.one('section.gallery-header li.display-option').addClass('open');
					_Y.one('section.gallery-display-options').removeClass('hide');
					
				} else {
					_Y.one('section.gallery-header li.display-option').removeClass('open');
					_Y.one('section.gallery-display-options').addClass('hide');
				}	
			} catch (e) {}
		},		
		/*
		 * NOTE: ok to change filter while SNAPPI.AIR.UploadManager.isUploading 
		 */
		filter : function(node, value) {
			if (SNAPPI.AIR.uploadQueue.isUploading()) {
				var detach = _Y.on('snappi-air:upload-status-changed', function(isUploading){
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
			
			var delayed = new _Y.DelayedTask( function() {
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
				var detach = _Y.on('snappi-air:upload-status-changed', function(isUploading){
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
	UIHelper.nav = UIHelper.actions;		// TODO: refactor
	
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
				node.listen['import-complete'] = _Y.on('snappi-air:import-complete', function(){
					// reset upload batch folders, will automatically rebuild
		            try {
		            	_Y.one('#markup #menu-uploader-batch-markup ul').setContent('');	
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
	XhrHelper._setUser = function(user, PHPSESSID) {
		
		SNAPPI.namespace('SNAPPI.STATE');
		SNAPPI.STATE.user = user;
		var expressNode = _Y.one('#gallery-container .express-upload-container');
		
		if (user && user.id) {
			try {
	//			var datasource = _flexAPI_UI.datasource;
				var datasource = SNAPPI.DATASOURCE;
				datasource.setSessionId(PHPSESSID);
	LOG(">>> UPLOAD HOST=" + datasource.getConfigs().uploadHost + ", sessionId=" + SNAPPI.DATASOURCE.sessionId);
	
				// cleanup actions: hide menu
				var menu = SNAPPI.MenuAUI.find['menu-sign-in-markup'];
				menu.hide();
				
				// add express Upload section
				XhrHelper.insertExpressUpload(expressNode);	
				
				// save username
				datasource.setConfig({username: user.username});
				
				_Y.fire('snappi-air:sign-in-success');
			} catch (e) {
			}
		} else {
			// login unsuccessful
			var datasource = SNAPPI.DATASOURCE;
			PHPSESSID = PHPSESSID || 'data[User][id]=null';
			datasource.setSessionId(PHPSESSID);
			XhrHelper.insertExpressUpload(expressNode, true);	// clear
			SNAPPI.setPageLoading(false);
		}
		XhrHelper._setHeader(user);
	}
	XhrHelper._setHeader = function(user) {
		
		var userHeader = _Y.one('header nav.user');
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
		if (typeof container == "string") container = _Y.one(container);
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
	 * json testSession, 
	 * 	test ability to connect to cakephp SESSION
	 */
	XhrHelper.testSession = function(){
		
		var node=_Y.one('body');
		var i=10;
		var ioCfg = {
   					uri: '/users/login/.json',
					parseContent: false,
					autoLoad: false,
					context: node,
					arguments: null, 
					method: "POST",
					dataType: 'json',
					qs: {},
					on: {
						successJson: function(e, i, o,args){
							var resp = o.responseJson;
							var node = this;
LOG(' >>>>>>>>>>>>>  testSession(): successJson    ');							
LOG(resp);
							// save Session Cookie
							XhrHelper.setCookies(o.responseJson.Cookie);
							
							var authUser = resp.response.User;
							var sessionId = resp.Cookie.CAKEPHP;
							XhrHelper._setUser(authUser, sessionId);
							
							if (args.i>0){
								if (node.io) node.unplug(_Y.Plugin.IO);
								node.plug(_Y.Plugin.IO, ioCfg);
								args.i--;
LOG(' >>>>>>>>>>>>>  testSession(): starting next iteration    #'+args.i);										
								node.io.set('arguments', {i:args.i})
								node.io.start();
							}
							return false;
						}, 
					}
			};		
			ioCfg = SNAPPI.IO.pluginIO_RespondAsJson(ioCfg);
			node.plug(_Y.Plugin.IO, ioCfg);
			i--;
			node.io.set('arguments', {i:i});
			node.io.start();
	}
	XhrHelper.getCookie = function(c_name){
		var i,x,y,ARRcookies=document.cookie.split(";");
		for (i=0;i<ARRcookies.length;i++)
		  {
		  x=ARRcookies[i].substr(0,ARRcookies[i].indexOf("="));
		  y=ARRcookies[i].substr(ARRcookies[i].indexOf("=")+1);
		  x=x.replace(/^\s+|\s+$/g,"");
		  if (x==c_name)
		    {
		    return unescape(y);
		    }
		  }
		};
	XhrHelper.setCookies = function(data) {
		var setCookie=function(c_name,value,exdays)
		{
			var exdate=new Date();
			exdate.setDate(exdate.getDate() + exdays);
			var c_value=escape(value) + ((exdays==null) ? "" : "; expires="+exdate.toUTCString());
			document.cookie=c_name + "=" + c_value;
		};		
// LOG(data);
// LOG("COOKIES aved above.");		
		var cookie = data;
		for (var p in cookie) {
			setCookie(p, cookie[p], 14);
		}
	}
	XhrHelper.signIn = function(postData) {
		
		SNAPPI.setPageLoading(true);
		if (postData === undefined) {
			postData = {};
			_Y.all('form#UserLoginForm .postData').each(function(n, i, l) {
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
		}

		var uri = XhrHelper.login_Url || "http://" + SNAPPI.AIR.host + "/users/login/.json";
LOG(">>> LOGIN, login_Url="+uri+", postdata follows:");
LOG(postData);
            
		// SNAPPI.io GET JSON  
		var container = _Y.one('#menu-sign-in-markup #login');
		// XhrHelper.resetSignInForm(container);
		
    	var args = {
    		node: container,
    		uri: uri,
    	};
    	/*
		 * plugin _Y.Plugin.IO
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
							// save Session Cookie
							XhrHelper.setCookies(resp.Cookie);
							
							var authUser = resp.response.User;
							var sessionId = resp.Cookie.CAKEPHP;
							XhrHelper._setUser(authUser, sessionId);
							this.one('.message').setContent('').addClass('hide');
							args.loadingmask.hide();
// XhrHelper.testSession();							
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
			container.plug(_Y.Plugin.IO, SNAPPI.IO.pluginIO_RespondAsJson(ioCfg));
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
	/*
	 * sign out, close session
	 */
	XhrHelper.checkUpdate = function(){
		var url = '/snappi/uploader_version';
		var callback = {
			complete : function(id, o, args) {
				var latest_version = o.responseText;
				// flex_app_version set in snaphappi.mxml::init()
// LOG('>>> uploader version check:  '+  latest_version +'=='+ flex_app_version);
				if (o.status == 0) return;	// hack: not returning correct response
								
				var isCurrent = function(o, n) {
					o = o.split('.');
					n = n.split('.');
					for (var i=0;i<n.length; i++){
						try {
// LOG('>>> uploader version check   :  '+  n[i] +'>'+ o[i]);							
							if ((n[i]||0) > (o[i]||0)) return false;	
						}
						catch(e) {
							return true;
						}
					}
					return true;
				}
				if (isCurrent(flex_app_version, latest_version)) return;
				// else
				// show Dialog.alert with link to new version
				try {
					// show dialog-alert .upload-complete 
		 			SNAPPI.Alert.load({
	    				selector: '#markup .alert-newer-version',
		    		});
				} catch (e){}
			}
		}		
		SNAPPI.io.get.call(this, url, callback);
	}
	
	XhrHelper.getMarkup = function(){
		var url = '/combo/markup/uploaderMarkup';
		var callback = {
			// complete : function(id, o, args) {	},
			complete : function(id, o ,args) {
				var markup = o.responseText;
				_Y.one('#markup').setContent('').append(markup);
				_Y.fire('snappi-air:markup-loaded');
			}
		}		
		SNAPPI.io.get.call(this, url, callback);		
	}
	
	/**
	 * change a.href to openPage() to open in browser
	 */
	XhrHelper.href2openPage = function(node) {
		try {
			node.all('a').each(function(n){
				var href = n.getAttribute('href');
				var openPage = "return SNAPPI.AIR.UIHelper.nav.openPage('"+href+"');"
				n.setAttribute('href', '').setAttribute('onclick', openPage);
			});
		} catch(e) {}
		return node;
	}
	
	XhrHelper.insertExpressUpload = function(node, clear) {
		
		node = node || _Y.one('#gallery-container .express-upload-container');
		// reset content before use
		if (clear) {
			node.setContent('');
			if (node.io) node.unplug(_Y.Plugin.IO);	
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
						var body = _Y.Node.create(o.responseText);
						var found = body.one('ul li input[type=checkbox]');
						body = XhrHelper.href2openPage(body);
						if (found) this.setContent(body);
						return false;
					},
					failure: function(e,i,o,args) {
						return _Y.Node.c.create('<aside id="express-upload-options"  class="related-content blue rounded-5 message">').setContent(o.responseText);
					}
					
				}
			};
			ioCfg = SNAPPI.IO.getIORequestCfg(ioCfg.uri, ioCfg.on, ioCfg);
			SNAPPI.setPageLoading(true);
			node.plug(_Y.Plugin.IO, ioCfg);	
		}
	}	
	XhrHelper.getExpressUploads = function(){
		
		var gids = [];
		_Y.all('#express-upload-options input[type=checkbox]').each(function(n,i,l){
			if (n.get('checked')) gids.push(n.getAttribute('uuid'));
		});
		return gids.join(',');
	}
	/*
	 * Experimental: trying to upload from Flex using javascript
	 * - may NOT be able to create File object from Flex/js. security error
	 */
	XhrHelper.uploader = null;
	XhrHelper.initUpload = function(files, postData){
		var timestamp = Math.round(new Date().getTime() / 1000);
		var gids = getExpressUploads();
		var uploader = XhrHelper.uploader || new qq.FileUploaderBasic({
		    // pass the dom node (ex. $(selector)[0] for jQuery users)
		    // element: document.getElementById('valums-file-uploader'),
		    // path to server-side upload script
		    action: '/my/upload',
		    allowedExtensions:['jpg', 'jpeg'],
	    	// sizeLimit: // 10Mb is the default in vender file,
		    debug: true,
			onSubmit: function(id, fileName){
				
			},
			onProgress: function(id, fileName, loaded, total){
				var fp = getFtp(id, fileName);
				fp.uploadProgress_Callback(null, loaded, total);
			},
			onComplete: function(id, fileName, responseJSON){
				
			},
			onCancel: function(id, fileName){
				
			},
			end: null
		});
		postData.isAir = 1;
		postData.groupIds = XhrHelper.getExpressUploads();;
		postData.batchId = postData.batchId || timestamp; 
		uploader.setParams(postData);  
		// load files to upload and begin
		// file instanceof File
		uploader._uploadFileList(files);
	}
	XhrHelper.addUpload = function(files){
		
	}
	
	
	LOG("load complete: XhrHelpers.js");	
})();	
