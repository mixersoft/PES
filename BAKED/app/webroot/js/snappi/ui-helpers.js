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
			var type = UIHelper.markupGallery.getGalleryType(e.currentTarget);
	    	var CSS_ID = ID_LOOKUP[ type ];
	    	if (e==false && !SNAPPI.MenuAUI.find[CSS_ID]) return;
	    	if (e) e.preventDefault();
	    	// load/toggle contextmenu
	    	if (!SNAPPI.MenuAUI.find[CSS_ID]) {
	    		var contextMenuCfg = {
	    			triggerType: type,		// group, person, photo, etc. 
	    			currentTarget: e.currentTarget,
	    			triggerRoot:  SNAPPI.Y.one('.gallery.photo .container'),
	    			init_hidden: false,
				}; 
	    		SNAPPI.MenuAUI.CFG[CSS_ID].load(contextMenuCfg);
	    	} else {
	    		var menu = SNAPPI.MenuAUI.toggleEnabled(CSS_ID, e);
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
	UIHelper.markupGallery = {
		/*
		 * markup "gallery" helpers, migrates to SNAPPI.Gallery when ready
		 * compares to GalleryFactory.listeners{}
		 */
		getGalleryType : function(node) {
			var Y = SNAPPI.Y;
			node = node || Y.one('.gallery-container section.gallery');
			node = node.ancestor('.gallery-container section.gallery', true);
			if (node.hasClass('group')) return 'group';
			if (node.hasClass('person')) return 'person';
			return null;
		},
        LinkToClick : function(node) {
        	var Y = SNAPPI.Y;
        	node = node || Y.one('.gallery .container');
        	var action = 'LinkToClick';
        	
        	node.listen = node.listen || {};
            if (node.listen[action] == undefined) {
				node.listen[action] = node.delegate('click', 
	                function(e){
	            		var linkTo = e.currentTarget.getAttribute('linkTo');
	            		if (linkTo) {
	            			if (e.ctrlKey) window.open(linkTo, '_blank') 
	            			else window.location.href = linkTo;
	            			e.stopImmediatePropagation();
	            		} 
	                }, '.FigureBox > figure > img', UIHelper);
			}
			// back reference
			UIHelper.listen[action] = node.listen[action];
        },        
        ContextMenuClick : function(node) {
        	var Y = SNAPPI.Y;
        	node = node || Y.one('.gallery .container');
        	var action = 'ContextMenuClick';
        	
        	node.listen = node.listen || {};
            if (node.listen[action] == undefined) {
				node.listen[action] = node.delegate('contextmenu', 
	                function(e){
	                	UIHelper.nav.toggle_ContextMenu(e);
	                	e.stopImmediatePropagation();
	                }, '.FigureBox', UIHelper);
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
	                	UIHelper.nav.toggle_ContextMenu(false);	// hide contextmenu
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
		}      
	}
	
})();