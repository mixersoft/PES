/**
 *
 * Copyright (c) 2009-2011, Snaphappi.com. All rights reserved.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the Affero GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * Affero GNU General Public License for more details.
 *
 * You should have received a copy of the Affero GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * @author Michael Lin, info@snaphappi.com
 *
 *
 */

(function() {
	
	SNAPPI.namespace('SNAPPI.STATE');

	var UIHelper = function(cfg) {	}; 
	UIHelper.prototype = {};
	SNAPPI.UIHelper = UIHelper;
	
	/*
	 * static methods/properties
	 */
	UIHelper.listen = {};		// global ref to active listeners
	
	UIHelper.nav = {
		'goto' : function (o) {
			window.location.href = o.options[o.selectedIndex].value;
		}, 
		toggleDisplayOptions  : function(o){
			var Y = SNAPPI.Y;
			try {
				SNAPPI.STATE.showDisplayOptions = SNAPPI.STATE.showDisplayOptions ? 0 : 1;
				UIHelper.nav.setDisplayOptions();
			} catch (e) {}
		},
		/*
		 * restore open/closed state for Gallery display options
		 */
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
		toggle_fullscreen : function(value) {
			if (value == undefined) value = SNAPPI.STATE.controller.isWide ? false : true;
			value = value ? 1 : null;
			var here = SNAPPI.IO.setNamedParams(SNAPPI.STATE.controller.here, {wide: value});
			window.location.href = here;
		},
		toggle_ContextMenu : function(e) {
			// copied from SNAPPI.Gallery
			var ID_LOOKUP = {
				'group': 'contextmenu-group-markup',
				'person': 'contextmenu-person-markup',
			}
			var type = UIHelper.listeners.getGalleryType(e.currentTarget);
	    	var CSS_ID = ID_LOOKUP[ type ];
	    	if (e==false && !SNAPPI.MenuAUI.find[CSS_ID]) return;
	    	
	    	// load/toggle contextmenu
	    	var listenerNode = e.currentTarget.ancestor('.container');
	    	if (!SNAPPI.MenuAUI.find[CSS_ID]) {
	    		var contextMenuCfg = {
	    			triggerType: type,		// .gallery.group, .person, .photo, etc. 
	    			currentTarget: e.currentTarget,
	    			// triggerRoot:  SNAPPI.Y.one('.gallery .container'),
	    			init_hidden: false,
				}; 
	    		SNAPPI.MenuAUI.CFG[CSS_ID].load(contextMenuCfg);
	    		// stop LinkToClickListener
	    		listenerNode.listen['disable_LinkToClick'] = true;
	    	} else {
	    		var menu = SNAPPI.MenuAUI.toggleEnabled(CSS_ID, e);
	    		if (menu.get('disabled')) {
        			listenerNode.listen['disable_LinkToClick'] = false;
        		} else {
        			listenerNode.listen['disable_LinkToClick'] = true;
        		}
	    	}		
		}		
	};
	
	UIHelper.groups = {
		// groups, filter by groupType
		// formerly: PAGE.myGroups()
		myGroups : function(o){
			var set = /selected/.test(o.className) ? null : 1;
			var href = window.location.href;
			window.location.href = SNAPPI.IO.setNamedParams(href, {'filter-me':set});
		},
		getProperties : function(triggerType, node) {
			var data = [], 
				uuid = node.get('id');
			switch(triggerType) {
				case 'group':
					data = PAGE.jsonData.Group || PAGE.jsonData.Membership; 
					break;
			}
			for (var i in data ) {
				if (uuid == data[i].id) {
					return data[i];
				}
			}
			return null;
		}
	}
	UIHelper.markup = {
		set_ItemHeader_WindowOptions: function(){
			try {
				var found = SNAPPI.Y.one('div.properties.hide');
				if (found) {
					var itemHeader = SNAPPI.Y.one('.item-header');
					SNAPPI.UIHelper.listeners['WindowOptionClick'](itemHeader);
					itemHeader.one('.window-options').removeClass('hide');
				}
			} catch (e) {}
		}
	}
	UIHelper.listeners = {
		/*
		 * markup "gallery" helpers, migrates to SNAPPI.Gallery when ready
		 * compares to GalleryFactory.listeners{}
		 */
		getGalleryType : function(node) {
			var Y = SNAPPI.Y;
			node = node || Y.one('.gallery-container section.gallery');
			node = node.ancestor('section.gallery', true);
			if (node.hasClass('group')) return 'group';
			if (node.hasClass('person')) return 'person';
			return null;
		},
        LinkToClick : function(cfg) {
        	var Y = SNAPPI.Y;
        	var node = cfg.node || Y.one('.gallery .container');
        	var action = 'LinkToClick';
        	
        	node.listen = node.listen || {};
            if (node.listen[action] == undefined) {
				node.listen[action] = node.delegate('click', 
	                function(e){
	            		var linkTo = e.currentTarget.getAttribute('linkTo');
	            		if (linkTo) {
	            			e.halt();	// intercepts A.click action
		                	if (this.listen['disable_LinkToClick']) {
		                		UIHelper.nav.toggle_ContextMenu(e);	// hide contextmenu
		                		return;		// allows temp disabling of listener
		                	}	            			
	            			window.location.href = linkTo;
	            		} 
	                }, '.FigureBox > figure > img, figure > a > img', node);
			}
			// back reference
			UIHelper.listen[action] = node.listen[action];
        },        
        /**
         * @params cfg object, cfg.node, cfg.type = [group, photo, person], 
         * 		i.e. .FigureBox.Group
         */
        ContextMenuClick : function(cfg) {
        	var Y = SNAPPI.Y;
        	var node = cfg.node || Y.one('.gallery .container');
        	var action = 'ContextMenuClick';
        	var selector = '.FigureBox';
        	if (cfg.type) selector += '.'+cfg.type ;
        	
        	node.listen = node.listen || {};
            if (node.listen[action] == undefined) {
				node.listen[action] = node.delegate('contextmenu', 
	                function(e){
	                	e.halt();
	                	UIHelper.nav.toggle_ContextMenu(e);
	                }, selector, UIHelper);
			}
			// back reference
			UIHelper.listen[action] = node.listen[action];
        }, 	        
        MultiSelect : function (node) {
        	var Y = SNAPPI.Y;
        	node = node || Y.one('.gallery .container');
        	var container = node;
        	var action = 'MultiSelect';
        	
        	node.listen = node.listen || {};
            if (node.listen[action] == undefined) {
            	SNAPPI.multiSelect.listen(node, true);
			}
			// back reference
			UIHelper.listen[action] = node.listen[action];	        	
        	
        	// select-all checkbox listener
        	var galleryHeader = Y.one('.gallery-container .gallery-header');
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
        DisplayOptionClick : function(node) {
        	var Y = SNAPPI.Y;
        	node = node || Y.one('.gallery-display-options');
        	var action = 'DisplayOptionClick';
        	
        	node.listen = node.listen || {};
            if (node.listen[action] == undefined) {
				node.listen[action] = node.delegate('click', 
	                function(e){
	                	// hide contextmenu when opening display option menus
	                	UIHelper.nav.toggle_ContextMenu(false);	
	                	var action = e.currentTarget.getAttribute('action').split(':');
			    		switch(action[0]) {
			    			case 'filter':
			    				break;
			    			case 'sort':
			    				break;
			    		}		                	
	                }, 'ul > li.btn', UIHelper);
			}
			// back reference
			UIHelper.listen[action] = node.listen[action];  
		},
        /*
         * Click-Action listener/handlers
         * 	start 'click' listener for action=
         * 		set-display-size:[size] 
         * 		set-display-view:[mode]
         */
        WindowOptionClick : function(node) {
			var Y = SNAPPI.Y;
        	node = node || Y.one('.item-header');        	
        	var action = 'WindowOptionClick';
        	node.listen = node.listen || {};
            if (node.listen[action] == undefined) {
            	var delegate_container = node.one('.window-options');
				node.listen[action] = delegate_container.delegate('click', 
	                function(e){
	                	// action=[set-display-size:[size] | set-display-view:[mode]]
	                	// context = node
	                	if (this.hasClass('item-header')) {
	                		// show/hide properties
	                		var properties = this.next('.properties');
	                		var action = e.currentTarget.getAttribute('action').split(':');
	                		switch(action[0]) {
				    			case 'set-display-view':
				    				if (action[1]=='minimize') properties.addClass('hide');
				    				else properties.removeClass('hide');
				    				break;
			    			}	
	                	}
	                }, 'ul > li', node);
			}
			// back reference
			UIHelper.listen[action] = node.listen[action]; 
        },
        DragDrop : function(){
        	SNAPPI.DragDrop.pluginDrop(SNAPPI.Y.all('.droppable'));
        	SNAPPI.DragDrop.startListeners();
        }
	}
	
})();