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
console.log("load BEGIN: ui-helpers.js");	
(function() {
	
	SNAPPI.namespace('SNAPPI.STATE');
		
	/***************************************************************************
	 * UIHelpers Static Class
	 * 	SNAPPI.AIR.UIHelpers = UIHelpers;
	 */
	var UIHelper = function() {}
	UIHelper.prototype = {};
	SNAPPI.AIR.UIHelper = UIHelper;
	
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
		                	// action=[set-display-size:[size] | set-display-view:[mode]]
		                	// context = Gallery.node
		                	var action = e.currentTarget.getAttribute('action').split(':');
				    		switch(action[0]) {
				    			case 'filter':
				    				UIHelper.actions['filter'](e.currentTarget, action[1]);
				    			break;
				    		}		                	
		                }, 'ul > li.btn', UIHelper);
				}
				// back reference
				UIHelper.listen[action] = node.listen[action];
	        },  		
	}
	UIHelper.actions = {
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
			SNAPPI.AIR.uploadQueue.show(value);
			var Y = SNAPPI.Y;
			if (value=='failed') {
				Y.one('section.gallery-header .upload-toolbar li.btn.retry').removeClass('disabled');
			} else Y.one('section.gallery-header .upload-toolbar li.btn.retry').addClass('disabled');
			node.siblings('li.btn').removeClass('focus');
			node.addClass('focus');
		},
		set_Folder : function(node){
			// node == selected li/menuItem
			var Y = SNAPPI.Y;
			var folder = node.hasAttribute('baseurl') ? node.getAttribute('baseurl') : node.get('innerHTML');			
			
			// TODO: set baseurl does not currently filter photos in uploadQueue
			SNAPPI.DATASOURCE.setBaseurl(folder);
			
			SNAPPI.AIR.Helpers.initUploadGallery(null, 1, null, null, folder);		// reload gallery with new baseurl
			node.siblings('li').removeClass('focus');
			node.addClass('focus');		
		},
		set_UploadBatchid : function(node){
			// node == selected li/menuItem
			var batchid = node.getAttribute('batch');
			SNAPPI.AIR.Helpers.initUploadGallery(null, 1, null, batchid);		// reload gallery with new baseurl
			
			node.siblings('li').removeClass('focus');
			node.addClass('focus');		
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
				var folders =  SNAPPI.DATASOURCE.getBaseurls(),
				selected = SNAPPI.DATASOURCE.getBaseurl();					
LOG('>>>>>>> BASEURL='+selected);				
				var li, longname;
				folders.unshift('All imported folders');		// for All imported folders
				for (var i in folders) {
					longname = folders[i];
					li = node.create("<li></li>");
					li.setContent(longname).setAttribute('action', 'uploader_setFolder');
					if (longname == 'All imported folders') li.setAttribute('baseurl', '');
					if (longname == selected) li.addClass('focus');
					if (!selected && longname=='All imported folders') li.addClass('focus');
					node.append(li);	
				}
			} catch (e) {}
		}
	};
	
	
	LOG("load complete: ui-helpers.js");	
}());
