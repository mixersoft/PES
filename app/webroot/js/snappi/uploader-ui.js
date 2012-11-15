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
	var _Y = null;
    SNAPPI.namespace('SNAPPI.onYready');
    SNAPPI.onYready.ThriftUploader = function(Y){
		if (_Y === null) _Y = Y;
		SNAPPI.ThriftUploader = ThriftUploader;
	}	
	var ThriftUploader = function(cfg) {	}; 
	
	/*
	 * static methods/properties
	 */
	ThriftUploader.container_id = '#uploader-ui-xhr';
	
	ThriftUploader.listen = {};		// global ref to active listeners
	
	ThriftUploader.nav = {
		
	};
	ThriftUploader.action = {
		timer: null,
		handleClick: function(d) {
			var action, n = d.ynode();
			SNAPPI.ThriftUploader.action.refresh(true);
			action = d.getAttribute('action');
			window.location.href = action;
		},
		refresh: function(start) {
			if (start) {
				ThriftUploader.timer = _Y.later(5000, SNAPPI.xhrFetch, function(){
						var n = _Y.one( ThriftUploader.container_id );
						this.requestFragment(n);			// this == SNAPPI.xhrFetch
						var t = ThriftUploader.util.getState();
						if (t.is_cancelled || !t.active) {
							ThriftUploader.timer.cancel();
						} 
					}, 
					null, true
				);
			} else {
				ThriftUploader.timer.cancel();
			} 
		}
	}

	ThriftUploader.util = {
		getState: function(){
			ThriftUploader.count = ThriftUploader.count ? ThriftUploader.count+1 : 1;
			if (ThriftUploader.count>3) return {is_cancelled: 1}
			else {is_cancelled: 0}
		}
	}
	ThriftUploader.listeners = {

        /*
         * Click-Action listener/handlers
         * 	start 'click' listener for action=
         * 		set-display-size:[size] 
         * 		set-display-view:[mode]
         * adds minimize/maximize btns for item-header
         */
        WindowOptionClick : function(node) {
        	node = node || _Y.one('.item-header');        	
        	if (!node) return;
        	var action = 'WindowOptionClick';
        	node.listen = node.listen || {};
        	var delegate_container = node.one('.window-options');
            if (delegate_container && node.listen[action] == undefined) {
            	delegate_container.removeClass('hide');
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
				// back reference
				ThriftUploader.listen[action] = node.listen[action];	                
			}
        },
        /*
         * Montage or Gallery
         */
 		SectionOptionClick : function(node) {
        	node = node || _Y.one('nav.section-header');        	
        	if (!node) return;
        	var action = 'SectionOptionClick';
        	node.listen = node.listen || {};
        	var delegate_container = node;
            if (delegate_container && node.listen[action] == undefined) {
            	delegate_container.removeClass('hide');
				node.listen[action] = delegate_container.delegate('click', 
	                function(e){
	                	// action=[section-view:[montage|gallery]
	                	// context = node
                		var action = e.currentTarget.getAttribute('action').split(':');
                		try {
			    		switch(action[0]) {
			    			case 'section-view':
			    				ThriftUploader.action['section-view'][ action[1] ](e, action[1]);
			    				break;
			    			case 'xxx':
			    				break;
			    		}} catch(e) {
			    			console.error("ThriftUploader.listeners.SectionOptionClick(): possible error on action name.");
			    		}	
	                }, 'ul > li', node);
				// back reference
				ThriftUploader.listen[action] = node.listen[action];	   
			}
        },        
        DragDrop : function(){
        	SNAPPI.DragDrop.pluginDrop(_Y.all('.droppable'));
        	SNAPPI.DragDrop.startListeners();
        },
	}
	
	
})();