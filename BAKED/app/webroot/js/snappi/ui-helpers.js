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
    SNAPPI.onYready.UIHelper = function(Y){
		if (_Y === null) _Y = Y;
		SNAPPI.UIHelper = UIHelper;
	}	
	SNAPPI.namespace('SNAPPI.STATE');

	var UIHelper = function(cfg) {	}; 
	
	/*
	 * static methods/properties
	 */
	UIHelper.listen = {};		// global ref to active listeners
	
	UIHelper.nav = {
		'goto' : function (o) {
			window.location.href = o.options[o.selectedIndex].value;
		}, 
		'orderBy' : function (o) {
			window.location.href = o.options[o.selectedIndex].value;
		},
		'goSearch' : function() {
			var value = _Y.one('#search input').get('value');
			if (value) {
				if (value.length>2) {
					var here = window.location.href;
					var namedData = {q:value, page: null};
					window.location.href = SNAPPI.IO.setNamedParams(here, namedData);
					return true;
				} else {
					alert('please enter at least 3 chars in your search');
				}
			}  
			return false;
		},
		/**
		 * old-style 'snappi-tabs' nav
		 */
		'gotoTab' : function(dom){
			var _Y = SNAPPI.Y;
			var n = _Y.one(dom);
			var href = n.getAttribute('href');
			if (/\/cancel/.test(href)) return false;
			PAGE.section = n.get('id');
			SNAPPI.TabNav.selectByName(PAGE);
			var container = SNAPPI.Y.one("#tab-section").setAttribute('xhrSrc', href);
			SNAPPI.xhrFetch.requestFragment(container);
			return false;
		},
		'showHelp': function(btn, node){
			try {
				var container = node || _Y.one('section.help');
				btn = _Y.one(btn);
				if (btn.hasClass('green')) {
					container.addClass('hide');
					btn.replaceClass('green', 'blue-gloss');
				} else if (container.one('article.topic')) {
					container.removeClass('hide');
					btn.replaceClass('blue-gloss','green');
				} else {
					var article, uri, topics = container.getAttribute('topics').split(':');
					container.setContent().removeClass('hide');
					for (var i in topics) {
						uri = '/help/topic/'+topics[i];
						article = container.create('<article class="grid_16 topic wrap-v cf"></article>');
						container.append(article);
						var ioCfg = {
							parseContent: true,
							autoLoad: true,
							context: container,
							dataType: 'html',
						};
						ioCfg = SNAPPI.IO.getIORequestCfg(uri, {}, ioCfg);
						article.plug(_Y.LoadingMask,{});
						article.plug(_Y.Plugin.IO, ioCfg);
					}
					btn.replaceClass('blue-gloss','green');
				}
			} catch(e) {}
		},
		toggleDisplayOptions  : function(o){
			try {
				SNAPPI.STATE.showDisplayOptions = SNAPPI.STATE.showDisplayOptions ? 0 : 1;
				UIHelper.nav.setDisplayOptions();
			} catch (e) {}
		},
		/*
		 * restore open/closed state for Gallery display options
		 */
		setDisplayOptions : function(){
			try {
				if (SNAPPI.STATE.showDisplayOptions) {
					_Y.one('section.gallery-header li.display-option').addClass('open');
					_Y.one('section.gallery-display-options').removeClass('hide');

					// for /photo/roll.ctp: init rating
					// TODO: move to a better spot?
					var ratingFilterNode = _Y.one('#filter-rating-parent');
			        if (ratingFilterNode) {
			        	SNAPPI.filter.initRating();
			        }
			        					
				} else {
					_Y.one('section.gallery-header li.display-option').removeClass('open');
					_Y.one('section.gallery-display-options').addClass('hide');
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
			// var isPreview = 0&& !e.currentTarget.test('.gallery.'+type+' .FigureBox');
	    	var CSS_ID = ID_LOOKUP[ type ];
	    	if (e==false && !SNAPPI.MenuAUI.find[CSS_ID]) return;
	    	
	    	// load/toggle contextmenu
	    	var listenerNode = e.currentTarget.ancestor('.container');
	    	if (!SNAPPI.MenuAUI.find[CSS_ID]) {
	    		var contextMenuCfg = {
	    			triggerType: type,		// NOTE: add .gallery.group to id=groups-preview-xhr
	    			currentTarget: e.currentTarget,
	    			triggerRoot:  listenerNode,
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
		},
		/**
		 * @params cfg.gid, uuid of Group
		 * @params cfg.isExpress Boolean, NOT NULL
		 * @params cfg.node, _Y.Node for loading mask, menuItem
		 */
		isExpress: function(cfg){
			var data = {
					'data[Group][id]' : cfg.gid,
					'data[Group][isExpress]': cfg.isExpress
			};
			var uri = '/groups/express_upload/.json';	
			var loadingNode = cfg.node;
			if (loadingNode.io == undefined) {
				var ioCfg = SNAPPI.IO.pluginIO_RespondAsJson({
					uri: uri ,
					parseContent:true,
					method: 'POST',
					qs: data,
					dataType: 'json',
					context: this,	
					arguments: cfg,
					on: {
						successJson:  function(e, id, o, args) {
							var resp = o.responseJson;
							if (args.menuItem) {
								try {
									if (args.isExpress) {
										args.menuItem.addClass('selected');
										// update local copy
										if (_Y.Lang.isArray(PAGE.jsonData.expressUploadGroups)) PAGE.jsonData.expressUploadGroups = {};
										PAGE.jsonData.expressUploadGroups[args.gid] = 1;
									} else {
										args.menuItem.removeClass('selected');
										delete (PAGE.jsonData.expressUploadGroups[args.gid]);
									}
								} catch(ex) {}
								
							}
							// args.node.io.loadingmask.hide();
							return false;
						}
					}
				});
	            loadingNode.plug(_Y.Plugin.IO, ioCfg );
			} else {
				loadingNode.io.set('data', data);
				loadingNode.io.set('context', this);
				loadingNode.io.set('uri', uri);
				loadingNode.io.set('arguments', cfg);
				loadingNode.io.start();
	        }
	        return;			
		},
	}
	UIHelper.create = {
		launchPageGallery : function(e){
			try {
				var g = _Y.one('section.gallery.photo').Gallery;
				if (g.getSelected().count()) g.launchPagemaker();
			}catch(e){}			
		}
	}
	UIHelper.util = {
		checkSupportedBrowser : function(){
			//TODO: note: need to check on first _Y.use for ie, not in _Y.ready()
			try {
				if (1 ) {
					var browserOk = _Y.UA.gecko || _Y.UA.webkit;
					if (!browserOk) {
						// show recomended browser
						// alert('unsupported browser');
					} 
				}
			}catch(e){}			
		}
	}	
	UIHelper.markup = {
		set_ItemHeader_WindowOptions: function(){
			try {
				var found = _Y.one('div.properties.hide');
				if (found) {
					var itemHeader = _Y.one('.item-header');
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
			node = node || _Y.one('.gallery-container section.gallery');
			if (node.hasClass('FigureBox')) {
				if (node.hasClass('Group')) return 'group';
				if (node.hasClass('Person')) return 'person';	
			} else {
				node = node.ancestor('section.gallery', true);
				if (node.hasClass('group')) return 'group';
				if (node.hasClass('person')) return 'person';	
			}
			return null;
		},
        LinkToClick : function(cfg) {
        	var node = cfg.node || _Y.one('.gallery .container');
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
        	var node = cfg.node || _Y.one('.gallery .container');
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
        	node = node || _Y.one('.gallery .container');
        	var container = node;
        	var action = 'MultiSelect';
        	
        	node.listen = node.listen || {};
            if (node.listen[action] == undefined) {
            	SNAPPI.multiSelect.listen(node, true);
			}
			// back reference
			UIHelper.listen[action] = node.listen[action];	        	
        	
        	// select-all checkbox listener
        	var galleryHeader = _Y.one('.gallery-container .gallery-header');
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
	        	// enable select-all menu
				SNAPPI.MenuAUI.initMenus({'menu-select-all-markup':1});
        	}
        	return;
        },		
        DisplayOptionClick : function(node) {
        	node = node || _Y.one('.gallery-display-options');
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
         * adds minimize/maximize btns for item-header
         */
        WindowOptionClick : function(node) {
        	node = node || _Y.one('.item-header');        	
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
				UIHelper.listen[action] = node.listen[action];	                
			}
        },
        DragDrop : function(){
        	SNAPPI.DragDrop.pluginDrop(_Y.all('.droppable'));
        	SNAPPI.DragDrop.startListeners();
        },
        CommentReply : function(node) {
        	node = node || _Y.one('div.comments-main');  
        	if (_Y.Lang.isString(node)) node = _Y.one(node);      	
        	var action = 'CommentReply';
        	node.listen = node.listen || {};
        	if (node.listen[action] == undefined) {
	        	node.listen[action] = node.delegate('click', 
	                function(e){
	                	var href = e.currentTarget.getAttribute('href');
	                	var post = node.one('div.post');
	                	var form = post.one('form').setAttribute('action', href);
	                	var before = e.currentTarget.ancestor('div.comments');
	                	before.insert(post, 'after');
	                	var title = before.one('span.title a');
	                	if (title && title.get('innerHTML')) {
	                		title = title.get('innerHTML');
	                		post.one('label[for="CommentTitle"]').addClass('hide');
	                	} else {
	                		title = '';
	                		post.one('label[for="CommentTitle"]').removeClass('hide');
	                	}
	                	post.one('input#CommentTitle').set('value', title);
	                }, 'div.posted a.reply', node);
				// back reference
				UIHelper.listen[action] = node.listen[action];
			}
        }
	}
	
	
})();