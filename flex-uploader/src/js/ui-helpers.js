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
	
	SNAPPI.namespace('SNAPPI.STATE');
			
	/***************************************************************************
	 * UIHelpers Static Class
	 * 	SNAPPI.AIR.UIHelpers = UIHelpers;
	 */
	var UIHelper = function(){
	}
	UIHelper.prototype = {};
	SNAPPI.AIR.UIHelper = UIHelper;
	
	UIHelper.set_Folder = function(node, target){
		// node == selected li/menuItem
		// target = menu trigger
		SNAPPI.AIR.Helpers.init_GalleryLoadingMask();
						
		var Y = SNAPPI.Y;
		var folder = node.hasAttribute('baseurl') ? node.getAttribute('baseurl') : node.get('innerHTML');			
LOG("+++ set folder, folder="+folder);		
		// pause upload.\
		// TODO: this does not work. missing the loadingmask.hide() event;
		if (SNAPPI.AIR.uploadQueue.isUploading) {
			UIHelper.toggle_upload(null, false);
		}
			
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
		}
	};	
	
	UIHelper.toggle_ContextMenu = function(e) {
		// copied from SNAPPI.Gallery
        if (e) e.preventDefault();
    	var CSS_ID = 'contextmenu-photoroll-markup';
    	// load/toggle contextmenu
    	if (!SNAPPI.MenuAUI.find[CSS_ID]) {
    		var contextMenuCfg = {
    			currentTarget: e.currentTarget,
    			triggerRoot:  SNAPPI.Y.one('.gallery.photo .container'),
    			init_hidden: false,
			}; 
    		SNAPPI.MenuAUI.CFG[CSS_ID].load(contextMenuCfg);
    	} else {
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
				    		}		                	
		                }, 'ul > li.btn', UIHelper);
				}
				// back reference
				UIHelper.listen[action] = node.listen[action];
	        },  		
	        WindowOptionClick : function(node) {
	        	var Y = SNAPPI.Y;
	        	node = node || Y.one('.item-header nav.window-options');
	        	var action = 'WindowOptionClick';
	        	node.listen = node.listen || {};
	            if (node.listen[action] == undefined) {
					node.listen[action] = node.delegate('click', 
		                function(e){
		                	// action=[set-display-size:[size] | set-display-view:[mode]]
		                	// context = UIHelper
		                	var action = e.currentTarget.getAttribute('action').split(':');
				    		switch(action[0]) {
				    			case 'set-display-view':
				    				UIHelper.actions.setDisplayView(action[1]);
				    			break;
				    		}		                	
		                }, 'ul > li', UIHelper);
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
		filter : function(node, value) {
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
			});
			delayed.delay(100);  // wait 100 ms		
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
}());
