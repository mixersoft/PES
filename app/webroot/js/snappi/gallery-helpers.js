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
	var LIGHTBOX_PERPAGE_LIMIT;
	var UIHelper;
    SNAPPI.namespace('SNAPPI.onYready');
    SNAPPI.onYready.GalleryFactory = function(Y){
		if (_Y === null) _Y = Y;
		LIGHTBOX_PERPAGE_LIMIT = 72;
		// UIHelper = SNAPPI.UIHelper;	// undefined
	}
    
    var GalleryFactory = function(){};
    SNAPPI.namespace('SNAPPI.Factory');
    SNAPPI.Factory.Gallery = GalleryFactory;
    /*
     * static methods
     */
    // DEFAULT handlers for Gallery class. move to SNAPPI.Gallery?
    GalleryFactory.actions = {
    	setView: function(g, view) {
    		var parent = g.node.get('parentNode');
    		switch(view) {
    			case 'minimize':  
	        		parent.addClass('minimize');
	        		// g.header.one('ul').addClass('hide');      			
	    			break;
    			case 'one-row': 
    				if (g.view == 'maximize') {
    					g.container.addClass('one-row');
    				} else {
    					parent.removeClass('minimize');
	        			g.container.addClass('one-row');
	        			g.header.one('ul').removeClass('hide');
	        		}
	        		g.setFilmstripWidth();
	        		break;
    			case 'maximize': 
    				// from lighbox action.maximize, not tested 
					var MAX_HEIGHT = window.innerHeight - 120;
					var LIMIT = 999;	// add paging if necessary
					var count = Math.min(g.auditionSH.count(), LIMIT);
					var width = g.container.one('.FigureBox').get('offsetWidth');
					var rows = Math.ceil(count*width/940);
					var height = g.container.one('.FigureBox').get('offsetHeight');
					if (rows*height > MAX_HEIGHT) {
						rows = Math.floor(MAX_HEIGHT/height);
						height = (rows*height)+'px';
					} else {
						height = 'auto';
					}
					g.container.setStyles({
						width: 'auto',
						height: height	
					}).removeClass('one-row');;
					g.container.ancestor('.filmstrip-wrap').removeClass('hide').removeClass('hidden');
					g.container.get('parentNode').removeClass('minimize');
	    			break;
    		}
    		g.view = view;
    	},
    	setSize: function(g, size) {
    		var refreshCC = g._cfg.size == size;
         	if (refreshCC) {
        		g.refresh();
        	}  else {
	        	g.renderThumbSize(size);
        	}
    	},
    	// called by click event handler, context = Gallery.node, set by listener
    	setToolbarOption: function(e){
    		try {
	    		var action = e.currentTarget.getAttribute('action').split(':');
	    		switch(action[0]) {
	    			case 'set-display-view':
	    				var fn = GalleryFactory[this.Gallery._cfg.type]['handle_setDisplayView'];
	    				if (fn) {
	    					fn(this.Gallery, action[1] );
	    				} else GalleryFactory.actions.setView(this.Gallery, action[1]);
	    				e.currentTarget.get('parentNode').all('li').removeClass('focus');
		        		e.currentTarget.addClass('focus');
	    			break;
	    			case 'set-display-size':
	    				SNAPPI.setPageLoading(false);
	    				GalleryFactory.actions.setSize(this.Gallery, action[1]);
	    				e.currentTarget.get('parentNode').all('li').removeClass('focus');
		        		e.currentTarget.addClass('focus');
		        		SNAPPI.io.savePreviewSize(this.Gallery._cfg.type, action[1]);
	    			break;	 
	    			case 'toggle-display-options':
	    				SNAPPI.UIHelper.nav.toggleDisplayOptions();
	    			break; 
	    			case 'toggle-keydown':
	    				if (e.currentTarget.hasClass('selected')) {
	    					this.listen['Keydown_stopListening'](null, 'selected');
	    				} else {
	    					this.listen['Keydown_startListening'](null, 'selected');
	    				}
	    			break;  	
	    			case 'ungroup-shot':	// from ShotGalleryShot
	    				if (e.target.hasClass('disabled')) break;
		    			var parent = e.target.ancestor('section.filmstrip.shot').one('.gallery.filmstrip'),
		    				batch = new SNAPPI.SortedHash(),
		    				g = parent.Gallery;
		    			var thumb = g.node.ancestor('.gallery.shot > .container > .FigureBox.Photo');
		    			if (!thumb) break; 
		    			batch.add(SNAPPI.Auditions.find(thumb.Thumbnail.uuid));
		    			options =  {
							loadingNode: e.currentTarget,
							shotType: g.castingCall.CastingCall.Auditions.ShotType,
						};
						if (/Group/.test(SNAPPI.STATE.controller['class'])) {
							options.group_id = SNAPPI.STATE.controller.xhrFrom.uuid;
						}
						g.unGroupShot(batch, options);
		    			break;	
    				case 'group-shot':	// from ShotGalleryShot
    					if (e.target.hasClass('disabled')) break;
		    			var parent = e.target.ancestor('section.filmstrip.shot').one('.gallery.filmstrip'),
		    				g = parent.Gallery,
		    				batch = g.getSelected();
		    			if (batch.count()==0) batch = g.getSelected('all');
		    			var success = function(){
		    				var check; // deactivate or unGroup
		    				return false;
		    			};
		    			options =  {
							loadingNode: e.currentTarget,
							shotType: g.castingCall.CastingCall.Auditions.ShotType,
							success: success,
						};
						// TODO: make this work for workorders
						if (/Group/.test(SNAPPI.STATE.controller['class'])) {
							options.group_id = SNAPPI.STATE.controller.xhrFrom.uuid;
						}
						g.groupAsShot(batch, options);
    					break;
	    		}
	        	
	        	// check for 'after' event
				var fn = GalleryFactory[this.Gallery._cfg.type]['after_setToolbarOption'];
				if (fn) {
					fn(this.Gallery, action[0], action[1] );
				} 	        		
        	} catch (e) {}
    	},
    	/*
         * DEFAULT Key press functionality of next & previous buttons
         * 	- used by GalleryFactory.Photo
         * NOTE: check for GalleryFactory[g._cfg.type].handleKeydown override!!!
         * 	- GalleryFactory.ShotGallery/DialogHiddenShot
         */
        handleKeydown: function(e){
// console.warn('>>> handleKeydown (DEFAULT)');        	
        	var done, charCode = GalleryFactory[this._cfg.type].charCode;
        	var charStr = e.charCode + '';
            if (e.ctrlKey) {
            	// selectAll
                if (charStr.search(charCode.selectAllPatt) == 0) {
                    this.selectAll(); done = 1;
                }
                // group
                if (charStr.search(charCode.groupPatt) == 0) {
                    this.groupAsShot(); done = 1;
                }
                if (done) e.preventDefault();
                return;
            }
            
			// key navigation for GalleryFactory.Photo
			var focus = this.container.one('.FigureBox.focus');
			if ( focus == null ) {
				focus = this.container.one(':hover.FigureBox');
				if (focus) {
					this.setFocus(focus);
				}
			}
        	if ( focus == null ) {
				var i = this.auditionSH.indexOf(this.auditionSH.getFocus());
				this.setFocus(i); done = 1;
        	}
            if (charStr.search(charCode.nextPatt) == 0) {
                this.next(); done = 1;
            }
            if (charStr.search(charCode.prevPatt) == 0) {
                this.prev(); done = 1;
            }
            if (charStr.search(charCode.downPatt) == 0) {
            	this.down(); done = 1;
            }
            if (charStr.search(charCode.upPatt) == 0) {
            	this.up(); done = 1;
            }
            if (charStr.search(charCode.ratingPatt) == 0) {
            	try {
            		var v = parseInt(charStr) - 48; // 0 - 5
            		if (v > 5) v = parseInt(charStr) - 96; // keybd 0 - 5
            		SNAPPI.Rating.setRating(focus.Rating,v); 
            	} catch(e){}
            	 done = 1;
            }
            if (done) e.preventDefault();
        },
    }
    GalleryFactory.nav = {
    	/**
    	 * @params g SNAPPI.Gallery
    	 * @params e event, needs e.currentTarget
    	 * TODO: see also UIHelper.nav.toggle_ContextMenu. which one is deprecated?
    	 */
    	toggle_ContextMenu : function(g, e) {
	        e.preventDefault();
        	
        	var CSS_ID, TRIGGER;
        	switch(g._cfg.type){
        		case 'Photo':
        		case 'NavFilmstrip': 
        			CSS_ID = 'contextmenu-photoroll-markup';
        			break;
        		case 'ShotGalleryShot':
        			e.stopImmediatePropagation();	// nested inside 'Photo'
        			// move location of menu	
        		case 'DialogHiddenShot': 
        		case 'ShotGallery': 
        			CSS_ID = 'contextmenu-hiddenshot-markup';	
        			break;
				default:
					return;        			
        	}
        	if (g.node.hasClass('hiddenshots') || g.node.hasClass('hidden-shot')) {
        		CSS_ID = 'contextmenu-hiddenshot-markup';
        	}
        	
        	if (/Workorder|TasksWorkorder/.test(SNAPPI.STATE.controller['class'])) {
        		CSS_ID += '-workorder';
        	} 
        	
        	// load/toggle contextmenu
        	if (!SNAPPI.MenuAUI.find[CSS_ID]) {
        		var contextMenuCfg = {
        			// triggerType: 'photo',				// .gallery.photo
        			CSS_ID: CSS_ID,
        			triggerRoot: g.container,
        			currentTarget:e.currentTarget,
        			init_hidden: false,
        			host: g,			// add Gallery.ContextMenu backreference
				}; 
				if (g._cfg.contextmenu) contextMenuCfg = _Y.merge(contextMenuCfg, g._cfg.contextmenu);
        		var menu = SNAPPI.MenuAUI.CFG[CSS_ID].load(contextMenuCfg);
        		g.node.listen['disable_ThumbnailClick'] = true;
        	} else {
        		var menu = SNAPPI.MenuAUI.toggleEnabled(CSS_ID, e);
        		// if (g._cfg.listeners.indexOf('LinkToClick')> -1) {
        			// toggle LinkToClick listener
	        		if (menu.get('disabled')) {
	        			// TODO: nav to attribute "linkTo"
	        			// Factory.listeners.LinkToClick.call(this);
	        			g.node.listen['disable_ThumbnailClick'] = false;
	        		} else {
	        			// g.stopLinkToClickListener();
	        			g.node.listen['disable_ThumbnailClick'] = true;
	        		}
        		// }
        	}
        }
    }
    GalleryFactory.listeners = {
		    FocusClick: function(){
	        	if (this.node.listen['FocusClick'] == undefined) {
	        		this.node.listen['FocusClick'] = this.container.delegate('click', 
		                	GalleryFactory[this._cfg.type].handle_focusClick, 
		                'img', this.node); 
		          }	    	
		    },
		    HiddenShotClick: function(){
		    	// listen hiddenshot-icon
	        	if (this.node.listen['HiddenShotClick'] == undefined) {
	        		this.node.listen['HiddenShotClick'] = this.container.delegate('click', 
							GalleryFactory[this._cfg.type].handle_hiddenShotClick, 
		            	'div.hidden-shot', this.node); 
		          }	    	
		    },	    
	        LinkToClick: function(forceStart) {
	            if (this.node.listen['LinkToClick'] == undefined || forceStart ) {
	            	// section.gallery.photo or div.filmstrip.photo
	                this.node.listen['LinkToClick'] = this.node.delegate('click', function(e){
	            		var linkTo = e.currentTarget.getAttribute('linkTo');
	            		if (linkTo) {
	            			if (this.listen['disable_ThumbnailClick']) {
	            				GalleryFactory.nav.toggle_ContextMenu(this.Gallery, e);
		                		return;		// allows temp disabling of listener
		                	}
		                    if (this.Gallery.castingCall.CastingCall) {
		                    	linkTo += '?ccid=' + this.Gallery.castingCall.CastingCall.ID;
								try {
									var shotType = this.Gallery.castingCall.CastingCall.Auditions.ShotType;
									if (shotType == 'Groupshot'){
										linkTo += '&shotType=Groupshot';
									}
								} catch (e) {}
		                    }
		                    e.halt();		            			
	            			window.location.href = linkTo;
	            		} 	                	
	                }, '.FigureBox > figure > img', this.node);
				}
	        },
	        ZoomClick: function(forceStart) {
	            if (this.node.listen['ZoomClick'] == undefined || forceStart ) {
	            	// section.gallery.photo or div.filmstrip.photo
	                this.node.listen['ZoomClick'] = this.node.delegate('click', function(e){
	            			if (this.listen['disable_ThumbnailClick']) {
	            				GalleryFactory.nav.toggle_ContextMenu(this.Gallery, e);
		                		return;		// allows temp disabling of listener
		                	}
		                	var g = this.Gallery;	// closure
var _showZoom = function(e, g) { 					         
		// copied from MenuItems.zoom_click
		var thumbnail = e.target.ancestor('.FigureBox.Photo');	// target
		var audition = SNAPPI.Auditions.find(thumbnail.uuid);
		if (e.ctrlKey || e.metaKey) {
			var src = audition.Audition.Photo.Img.Src.rootSrc;
			src = audition.getImgSrcBySize(audition.urlbase+src,'bp');
			window.open(src, '_blank');
			return;
		}
		var cfg = {
			// selector: [CSS selector, copies outerHTML and substitutes tokens as necessary],
			markup: "<div id='preview-zoom' class='preview-body'></div>",
			uri: '/combo/markup/null',
			height: 400,
			width: 400,
			skipRefresh: true,
		};
		var dialog = SNAPPI.Alert.load(cfg); // don't resize yet
		var previewBody = dialog.getStdModNode('body').one('.preview-body');
		_Y.once('snappi:preview-change', 
	        	function(thumb){
	        		if (thumb.Thumbnail._cfg.type == 'PhotoZoom' ) {
	        			_Y.fire('snappi:dialog-body-rendered', dialog);
	        		}
	        	}, '.FigureBox.PhotoZoom figure > img', dialog
	        )		
		SNAPPI.Factory.Thumbnail.PhotoZoom.bindSelected(audition, previewBody, {gallery:g});
}
// use lazyLoad in case Alert not ready
		                	SNAPPI.LazyLoad.extras({
					        	module_group:'alert',
					        	ready: function(){
					        		_showZoom(e, g);
					        	},
					        });
		                    e.halt();		            			
	                }, '.FigureBox > figure > img', this.node);
				}	        	
	        },
	        ThumbsizeClick : function(action) {
	        	// for .gallery.photo nav.settings, NOT .filmstrip .window-options
	        	action = 'ThumbsizeClick';
	            if (this.node.listen[action] == undefined) {
	                // listen thumbnail size
	                this.node.listen[action] = this.node.get('parentNode').one('section.gallery-header .thumb-size').delegate('click', 
		                function(e, action){
		                	var fn = GalleryFactory[this.Gallery._cfg.type]['handle_'+action];
		                	try {
		                		fn.call(this, e);
		                	} catch (e) {}
		                }, 'ul > li.btn', this.node, action);
				}
	        },
	        /*
	         * Click-Action listener/handlers
	         * 	start 'click' listener for action=
	         * 		set-display-size:[size] 
	         * 		set-display-view:[mode]
	         * includes .lightbox-tab for lightbox
	         */
	        WindowOptionClick : function() {
	        	var action = 'WindowOptionClick';
	            if (this.node.listen[action] == undefined) {
	            	var delegate_container = this.header.one('.window-options');
					this.node.listen[action] = delegate_container.delegate('click', 
		                function(e){
		                	// action=[set-display-size:[size] | set-display-view:[mode]]
		                	// context = Gallery.node
		                	GalleryFactory.actions.setToolbarOption.call(this, e);
		                }, 'ul > li', this.node);
				}
	        },    
	        ShotGalleryToolbarOptionClick : function() {
	        	var action = 'ShotGalleryToolbarOptionClick';
	        	var delegate_container = this.node.ancestor('section.gallery.shot');
	        	if (!delegate_container.listen) delegate_container.listen = {}; 
	            if (delegate_container.listen[action] == undefined) {
					delegate_container.listen[action] = delegate_container.delegate('click', 
		                function(e){
		                	// action=[ungroup-shot]
		                	// context = Gallery.node
		                	GalleryFactory.actions.setToolbarOption.call(this, e);
		                }, 'section.filmstrip.shot nav.toolbar ul > li', this.node);
				}
	        },     
	        DisplayOptionClick : function(node) {
        		var action = 'DisplayOptionClick';
        		UIHelper = SNAPPI.UIHelper;
	        	UIHelper.listeners.DisplayOptionClick(node);
	        	this.node.listen[action] = UIHelper.listen[action];
	        },
	        MultiSelect : function () {
	        	SNAPPI.multiSelect.listen(this.container, true);
	        	// select-all checkbox listener
	        	if (this.node.previous().hasClass('gallery-header')) {
	        		var selectAll = this.node.previous().one('li.select-all input');
	        	}
	        	if (selectAll && !this.node.listen['selectAll']) {
		        	this.node.listen['selectAll'] = selectAll.on('click', 
			        	function(e){
			        		var checked = e.currentTarget.get('checked');
			        		if (checked) this.Gallery.container.all('.FigureBox').addClass('selected');
			        		else {
			        			this.Gallery.container.all('.FigureBox').removeClass('selected');
			        			SNAPPI.STATE.selectAllPages = false;
			        		}
			        		e.stopImmediatePropagation();
		        	}, this.node);
					// SNAPPI.MenuAUI.initMenus({'menu-select-all-markup':1});
	        	}
	        	return;
	        },
	        Mouseover : function(){
	        	// TODO: convert to 'hover' event
	        	if(this.node.listen['Mouseover'] == undefined){
	        		this.node.listen['Mouseover'] = this.container.delegate('mouseover', 
		        		function(e){
		        			var target = e.currentTarget;
		            		var gallery = this.Gallery;
		        			// may need to encapsulate the following code into a function. will refactor later.
		            		if(gallery.contextMenu && SNAPPI.util.isDOMVisible(gallery.contextMenu.container)) {
		            			gallery.contextMenu.parent.container = target;
		            			// context menu is visible
		            			if(!gallery.contextMenu.getNode().hasClass('hide')){
		                			gallery.contextMenu.show();
		                			gallery.node.listen['disable_ThumbnailClick'] = true;
		            			}
		            		}
		            		// set focus
		            		gallery.setFocus(target);
						}, ' > li', this.node
					);
	        	}
	        	
	        },
	        stopMouseoverListener : function(){
	        	if(this.node.listen.mouseover != undefined){
	        		this.node.listen.mouseover.detach();
	        		delete this.node.listen.mouseover;
	        	}
	        },
			
	        Contextmenu : function (){
	        	if (this.node.listen['ContextMenuClick'] == undefined){
	        		this.node.listen['ContextMenuClick'] = this.container.delegate('contextmenu', 
	        		function(e){
						// this.Gallery.toggle_ContextMenu(e);
						GalleryFactory.nav.toggle_ContextMenu(this.Gallery, e);
	        		}, '.FigureBox.Photo', this.node);
	        		
	        		// .FigureBox li.context-menu.icon
	     			this.node.listen['ContextMenuIconClick'] = this.container.delegate('click', 
	     			function(e){
						// this.Gallery.toggle_ContextMenu(e);
						GalleryFactory.nav.toggle_ContextMenu(this.Gallery, e);
						e.stopImmediatePropagation();
	        		}, '.FigureBox.Photo  figcaption  li.context-menu', this.node);        		
				}        	
	        	return;
	        },   
	        /**
	         * listen to NavFilmstrip.gallery .container li.btn next/prev to page inside filmstrip 
	         */
	        PaginateClick: function(){
	        	// for .gallery.photo nav.settings, NOT .filmstrip .window-options
	        	var action = 'PaginateClick';
	            if (this.node.listen[action] == undefined) {
	                // listen thumbnail size
	                this.node.listen[action] = this.container.delegate('click', 
		                function(e){
		                	var fn, action = e.currentTarget.getAttribute('action').split(':');
		                	try {
					    		switch(action[0]) {
					    			case 'paginate':
					    				fn = GalleryFactory[this.Gallery._cfg.type]['handle_paginate'];
					    				break;
					    		}
			                	try {
			                		// _Y.once('snappi:gallery-refresh-complete', function(g){
			                			// g.setFilmstripWidth();
			                		// });
			                		fn.call(this, action[1], this.Gallery, e);
			                	} catch (e) {}
				    		} catch(e) {
				    			console.error("GalleryFactory.listeners.PaginateClick()");
				    		}	
		                }, 'li.btn', this.node);
				}	        	
	        }, 
	        /**
	         * listen to NavFilmstrip 'snappi:gallery-render-complete', and render prev/next paging
	         */
	        SetPagingControls: function() {
	        	var action = 'SetPagingControls';
	            if (this.node.listen[action] == undefined) {
	                // listen thumbnail size
	                this.node.listen[action] = _Y.on('snappi:gallery-render-complete', 
		                function(g){
							if (g._cfg.type == 'NavFilmstrip') {
								GalleryFactory['NavFilmstrip'].setPagingControls(g);
								g.setFilmstripWidth();
							};
						})	        	
				}
	        }, 
	        Keydown: function(){
	        	var action = 'Hover';
	            if (this.node.listen[action] == undefined) {
	            	try {
		            	var self = this;
		            	var fnKeyDown = GalleryFactory[self._cfg.type].handleKeydown;
		            	if (!fnKeyDown) fnKeyDown = GalleryFactory.actions.handleKeydown;
		            	var keydownBtn = self.header.one('.keydown').get('parentNode');
		            	var stopListening = function(e, className) {
		            		className = className || 'focus';
		            		if (self.node.listen['Keydown']) { 
		            			keydownBtn.removeClass(className);
		            			if (keydownBtn.hasClass('selected')) return;	// skip if sticky
		            			self.node.listen['Keydown'].detach();
		            			delete self.node.listen['Keydown'];
		            			// self.container.all('.focus').removeClass('focus');
		            		}
		            	}; 
		            	var startListening = function(e, className) {
		            		className = className || 'focus';
	// console.log('Listen Keydown for: '+self._cfg.type);	   
							keydownBtn.addClass(className);         		
		            		if (!self.node.listen['Keydown']) {
		            			if (document.stoplistening_Keydown && document.stoplistening_Keydown!== stopListening) 
		            				document.stoplistening_Keydown();
		            			self.node.listen['Keydown'] = _Y.on('keydown', fnKeyDown, document, self);
		            			document.stoplistening_Keydown = stopListening;
		            		}
		            	};
		            	self.node.listen[action] = self.container.on('hover', startListening, stopListening, self);
		            	self.node.listen['Keydown_startListening'] = startListening;
		            	self.node.listen['Keydown_stopListening'] = stopListening;
	            	} catch(e){
	            		console.error('GalleryFactory.listeners.Keydown');
	            	}
	            }
	        },     	
	        
	};
    /**
     * attach gallery.node and gallery.container
     */
    GalleryFactory._attachNodes = function(gallery, cfg){
        gallery.container = null;
		var node = cfg.node instanceof _Y.Node ? cfg.node : _Y.one(cfg.node);
		if (!node && console) console.error('GalleryFactory._attachNodes(): invalid cfg.node. where do we append markup?');
    	try {
    		if (node.Gallery) {
    			gallery.container = node.Gallery.container;
        		var oldGallery = parent.Gallery;
	            // TODO: what do we do here???
        		// reuse existing photoRoll??? or do we need to destroy?	        			
    		} else {
    			if (node.hasClass('gallery') && !node.one('div.container')) {
    				node.prepend('<div class="container grid_16" />');
    			}
    			gallery.container = node.one('div.container');
    		}
    	} catch (e) {}
        if (!gallery.container) {
        	node.append(cfg.MARKUP);
        	node = node.one('.gallery');
        	// node.append(n);
        	if (cfg.isWide) node.addClass('wide');
        	gallery.container = node.one('div.container');
        }	        
        gallery.node = node;
        gallery.node.Gallery = gallery;				// use to avoid closure bug on SNAPPI.io 
        gallery.container.Gallery = gallery;		// is gallery reference necessary?
		gallery.node.dom().Gallery = gallery; 		// for firebug introspection	        
		gallery.header = node.siblings('.gallery-header').shift();
        delete cfg.node;				// use this.container from this point forward    		
    }
    
    GalleryFactory.Photo = {
    	defaultCfg : {
    		type: 'Photo',
			ID_PREFIX: 'uuid-',
			PROVIDER_NAME: 'snappi',
			MARKUP: '<section class="gallery photo container_16">'+
	        			'<div class="container grid_16" />'+
	        			'</section>',
			node: 'div.gallery-container > section.gallery.photo',
			render: true,
			listeners: ['Keydown', 'Mouseover', 'ZoomClick', 'MultiSelect', 'HiddenShotClick', 'Contextmenu', 'WindowOptionClick', 'DisplayOptionClick'],
			draggable: true,
			hideHiddenShotByCSS: true,
			size: 'lm',
			start: null,
			end: null
	    },
	    charCode : {
	        nextPatt: /(^110$)|(^39$)|(^32$)/, // n,right,space,
	        // keypad right
	        prevPatt: /(^112$)|(^37$)|(^8$)/, // p,left,backspace,
	        // keypad left
	        closePatt: /(^27$)/,
	        // escape
	        selectAllPatt: /(^65$)|(^97$)/,
	        // ctrl-a		
	        groupPatt: /(^103$)|(^71$)/,
	        // ctrl-g/G		
	        	
	        downPatt : /(^40)/,
	        // keypad down
	        upPatt : /(^38)/,
	        // kepad up
	        ratingPatt: /(^96$)|(^97$)|(^98$)|(^99$)|(^100$)|(^101$)(^49$)|(^50$)|(^51$)|(^52$)|(^53$)|(^48$)/, // keybd 0-5
	    },
        /*
         * build 
         * - scan for a cfg.node or defaultCfg.node, 
		 * - bind to JS auditions
         * - call AFTER SNAPPI.mergeSessionData(), important for XHR JSON request
         * @params gallery instance of SNAPPI.Gallery
         * @params cfg object, cfg object
         */
    	build: function(gallery, cfg){
            // var self = gallery;		// instance of SNAPPI.Gallery
            cfg = cfg || {};
            
            if (!cfg.size) delete cfg.size;	// merging undefined causes prob with default setting
            if (cfg.type == 'Photo') cfg = _Y.merge(SNAPPI.STATE.displayPage, cfg);
            cfg = _Y.merge(GalleryFactory[cfg.type].defaultCfg, cfg);	
            // check for raw
            var showHidden = SNAPPI.util.getFromNamed('raw');
            if (showHidden) cfg.hideHiddenShotByCSS = false;
            
            try {
            	if (!cfg.castingCall && cfg.castingCall !== false) cfg.castingCall = PAGE.jsonData.castingCall;
            } catch (e){}
	        
	        // .gallery.photo BEFORE init
	        gallery.auditionSH = null;
	        gallery.shots = null; 	
	        
	        // generic gallery BEFORE init
			gallery.providerName = cfg.PROVIDER_NAME;	// deprecate: use this.cfg.providerName
			GalleryFactory._attachNodes(gallery, cfg);
			var thumbSize = gallery.header && gallery.header.one('ul.thumb-size > li.focus');
			if (thumbSize) cfg.size = thumbSize.getAttribute('action').split(':').pop();
	        gallery.init(cfg);
	        
	        // apply SNAPPI.STATE.filters to section.gallery-display-options
	        try {	// not valid for NavFilmstrip
	        	GalleryFactory[cfg.type].apply_filter_settings(SNAPPI.STATE.filters, gallery);	        	
	        } catch(e) {}

	        
	        // .gallery.photo AFTER init methods
	        SNAPPI.Gallery.find[cfg.ID_PREFIX] = gallery;		// add to gallery lookup
	        SNAPPI.Rating.startListeners(gallery.container);
	        _Y.fire('snappi:after_PhotoGalleryInit', this); 
	        return gallery;					// return instance of SNAPPI.Gallery
        },
        apply_filter_settings : function(filters, g) {
        	try {
        		var f, btn, open, Rating,
        			parent = g.header.one('ul.filter');
	        	for ( var i in filters) {
	        		open = open || filters[i]['class'];
	        		switch(filters[i]['class']) {
	        			case 'Tag':
	        				f = parent.one('input.tag').set('value', filters[i]['label'] || filters[i]['uuid'])	
	        				btn = f.ancestor('li.btn', true).addClass('selected');
	        				break;
	        			case 'Rating':
	        				// markup is already initialized in display-options.ctp
	        				f = parent.one('#filter-rating-parent');
	        				Rating = SNAPPI.filter.initRating( f, filters[i]['value']);
	        				btn = f.ancestor('li.btn.rating').addClass('selected');
	        				break;
	        		}
	        	}
        	} catch(e) {}
        	if (!Rating) SNAPPI.filter.initRating( parent.one('#filter-rating-parent'), 0);
        	if (open) UIHelper.nav.setDisplayOptions(open);
        },
        handle_hiddenShotClick : function(e){
        	var thumbnail = e.currentTarget.ancestor('.FigureBox');
			try {
				var audition = SNAPPI.Auditions.find(thumbnail.uuid);
				var gallery = this.Gallery;
				SNAPPI.Helper.Dialog.bindSelected2DialogHiddenShot(gallery, audition);
				return;
			} catch (e) {
			}                	
		},
	};
	
	
    GalleryFactory.Lightbox = {
    	defaultCfg : {
    		type: 'Lightbox',
			ID_PREFIX: 'lightbox-',
			PROVIDER_NAME: 'snappi',
			MARKUP: '<section class="gallery photo container_16">'+
	        			'<div class="container grid_16" />'+
	        			'</section>',
	        tnType: 'Photo',	// thumbnail Type
			node: '#lightbox section.gallery.lightbox',
			render: true,
			listeners: ['MultiSelect', 'WindowOptionClick'],
			draggable: false,
			// droppable: true,			// make Lightbox.node droppable instead
			hideHiddenShotByCSS: false,
			size: 'lbx-tiny',
			perpage: LIGHTBOX_PERPAGE_LIMIT, 
			start: null,
			end: null,
			replace: false,		// do NOT replace with lightbox auditions. causes problems between Group/Usershot parsing
	    },
        /*
         * build 
         * - scan for a cfg.node or defaultCfg.node, 
		 * - bind to JS auditions
         * - call AFTER SNAPPI.mergeSessionData(), important for XHR JSON request
         * @params gallery instance of SNAPPI.Gallery
         * @params cfg object, cfg object
         */
    	build: function(gallery, cfg){
            // var self = gallery;		// instance of SNAPPI.Gallery
            cfg = cfg || {};
            // ???: merge displayPage for lightbox???
            // cfg = _Y.merge(GalleryFactory[cfg.type].defaultCfg, SNAPPI.STATE.displayPage, cfg); 
            cfg = _Y.merge(GalleryFactory[cfg.type].defaultCfg, cfg);
            
	        // .gallery.photo BEFORE init
	        gallery.auditionSH = null;
	        gallery.shots = null; 	
	        
	        // generic gallery BEFORE init
			gallery.providerName = cfg.PROVIDER_NAME;	// deprecate: use this.cfg.providerName
			GalleryFactory._attachNodes(gallery, cfg);
			// get thumbSize from '.gallery-header .window-options'
			var thumbSize = gallery.header && gallery.header.one('ul.thumb-size > li.focus');
			if (thumbSize) cfg.size = thumbSize.getAttribute('action').split(':').pop();
	        gallery.init(cfg);
	        
	        // .gallery.photo AFTER init methods
	        SNAPPI.Gallery.find[cfg.ID_PREFIX] = gallery;		// add to gallery lookup
	        SNAPPI.Rating.startListeners(gallery.container);
	        // _Y.fire('snappi:after_PhotoGalleryInit', this); 
	        return gallery;					// return instance of SNAPPI.Gallery
        },
	};
	
	GalleryFactory.NavFilmstrip = {
		defaultCfg: {
			type: 'NavFilmstrip',
			tnType: 'Photo',	// thumbnail Type
			ID_PREFIX: 'nav-',
			PROVIDER_NAME: 'snappi',
			// TODO: copy from navFilmstrip.ctp
			MARKUP: '<section class="gallery photo filmstrip container_16">'+
	        			'<div class="container grid_16" />'+
	        			'</section>',			
			node: 'section.filmstrip .gallery.photo.filmstrip',
			render: true,
			castingCall: false,
			size: 'sq',                			
			uuid: null,	
			showExtras: true,
			hideHiddenShotByCSS: true,	
			draggable: true,
			// listeners: ['Keydown', 'Mouseover', 'MultiSelect', 'Contextmenu', 'FocusClick', 'WindowOptionClick'],
			listeners: ['Mouseover', 'MultiSelect', 'Contextmenu', 'FocusClick', 'HiddenShotClick', 'WindowOptionClick', 'PaginateClick', 'SetPagingControls'],
		},
		build: GalleryFactory.Photo.build,
		render: function(g, uuid){
			// update castingCall if necessary 
			try {
				uuid = uuid || SNAPPI.STATE.controller.xhrFrom.uuid;
			} catch (e) {
				uuid = null;
			}
			try {
				// if isAlreadyExtended==true, then extend the CC, i.e. get more thumbnails
				// for now, set to false;
				var EXTENDED_PAGE = g.auditionSH.count();  // 240;
				var patt=new RegExp('perpage:'+EXTENDED_PAGE,'i');
				var isAlreadyExtended = patt.test(g.castingCall.CastingCall.Request);
			} catch (e) {
				isAlreadyExtended = false;
			}
			
			// GalleryFactory.listen.SetPagingControls: listen for complete, then add Paging Controls 
			
			/*
			 * load navFilmstrip if not already loaded, or extend cached CC
			 * XHR GET: /photos/neighbors/1330052530/perpage:999/page:1/.json
			 */
			if (isAlreadyExtended && g.auditionSH.count()) {		
				g.render({
					uuid: uuid,
				});		// just render existing CC
			} else {
				try {
					// var uri = PAGE.jsonData.castingCall.CastingCall.Request;
					var uri = '/photos/neighbors/'+ PAGE.jsonData.castingCall.CastingCall.ID + '/.json';
					// get extended castingCall by cacheRefresh
					var i = g.castingCall.auditionSH.indexOfKey(uuid);
					var offset = (SNAPPI.STATE.displayPage.page-1) *  SNAPPI.STATE.displayPage.perpage;
					var page = Math.floor( (i+offset)/EXTENDED_PAGE) + 1;
					var options = {
						uri: uri,
						uuid: uuid,
						perpage: EXTENDED_PAGE,
						page: page,
					};
					g.node.get('parentNode').removeClass('hide');
					g.refresh(options, true);
				} catch (e) {}	
			}
		},
		setPagingControls: function(g) {
			try {
				var cc_PAGES = g.castingCall.CastingCall.Auditions.Pages;
				var cc_PAGE = g.castingCall.CastingCall.Auditions.Page;
				var delta = 0;
				var PREV_PAGE = '<li class="li btn prev orange" action="paginate:prev" title="Get previous page">&#x25C0;</li>';
				if (cc_PAGE > 1) {
					if (!g.container.one('li.btn.prev')) {
						g.container.prepend(PREV_PAGE);
						delta++;
					}
				} else if (g.container.one('li.btn.prev')) {
					g.container.one('li.btn.prev').remove();
					delta--;
				}
				
				var NEXT_PAGE = '<li class="li btn next orange" action="paginate:next" title="Get next page">&#x25B6;</li>'; 
				if (cc_PAGE < cc_PAGES) {
					if (!g.container.one('li.btn.next')) {
						g.container.append(NEXT_PAGE);
						delta++;
					}
				} else if (g.container.one('li.btn.next')) {
					g.container.one('li.btn.next').remove();
					delta--;
				} 
				 
				// get h without li.btn.prev
				if (delta) {
					var fsWidth = g.setFilmstripWidth();
					g.container.setStyle('width', fsWidth+'px');	// force target fsWidth
				}
				var pagingControls = g.container.all('li.btn');
				pagingControls.addClass('hide');	// hide controls to get accurate clientHeight
				var h = g.container.get('clientHeight') - 10;
				pagingControls.setStyle('lineHeight', h+'px').removeClass('hide'); 
			}catch(e){}
			return delta;
		},
		/*
	     * update all components on /photos/home page to match 'selected'
	     */		
	    handle_focusClick: function(e){
	    	var gallery = this.Gallery;
	    	
			if (gallery.node.listen['disable_ThumbnailClick']) {
				// hide contextmenu with this click
				GalleryFactory.nav.toggle_ContextMenu(gallery, e);
        		return;		// allows temp disabling of listener
        	}	    	
        	
	    	var selected = SNAPPI.Auditions.find(e.target.ancestor('.FigureBox').uuid);
	    	// var oldUuid = gallery.getFocus().id;
	    	gallery.auditionSH.setFocus(selected);
	    	gallery.scrollFocus(selected);
	    	try {
		    	var previewBody = _Y.one('.preview-body');
		    	var previewUuid = previewBody.one('.FigureBox.PhotoPreview').uuid;
		    	if (selected.id != previewUuid) {
		    		// SNAPPI.domJsBinder.bindSelected2Page(gallery, selected, oldUuid);
		    		SNAPPI.Factory.Thumbnail.PhotoPreview.bindSelected(selected, previewBody, {gallery: gallery});
		        }
	    	} catch (e) {}
		},
		handle_hiddenShotClick: function(e){
			var shotGallery = SNAPPI.Gallery.find['shot-'];
			// hiddenShot is same as focusClick for navFilmstrip
			GalleryFactory.NavFilmstrip.handle_focusClick.call(this,e);
			// set-display-view to 'one-row'
			SNAPPI.Factory.Thumbnail.PhotoPreview.handle_HiddenShotClick();
		},	
		handle_setDisplayView: function(g, view){
			GalleryFactory.actions.setView(g, view);
			if (view == 'minimize') {
				// show item-header
				try {
					_Y.one('.item-header').removeClass('hide');	
					_Y.one('.properties').removeClass('hide');
				} catch (e) {}
			} else {
				// hide item-header
				try {
					_Y.one('.item-header').addClass('hide');	
					_Y.one('.properties').addClass('hide');
				} catch (e) {}
			}
			if (!g.container.one('.FigureBox')){
				GalleryFactory[g._cfg.type].render(g);	// render gallery if not rendered()
			}
			return;
		},
		handle_paginate: function(direction, g, e) {	// from GalleryFactory.listeners.PaginateClick()
			var pages = g.castingCall.CastingCall.Auditions.Pages;
			var page = g.castingCall.CastingCall.Auditions.Page;
			var perpage = g.castingCall.CastingCall.Auditions.Perpage;
			var i, 
				nextPerpage = Math.min(perpage, 100);	// recalibrate pagesize
			switch(direction){
				case 'next':
					i = page*perpage -1;
					page += 1;	
				break;
				case 'prev':
					i = (page-1)* perpage;
					page -= 1;
				break;
			}
			if ((page > pages) || (page <= 0)) return;
			g.refresh({page:page}, 'force');
		}
	}	
	
	
	
	GalleryFactory.ShotGallery = {
		defaultCfg: {
			type: 'ShotGallery',
			tnType: 'Photo',	// thumbnail Type
			ID_PREFIX: 'shot-',
			PROVIDER_NAME: 'snappi',
			MARKUP: '<section class="gallery photo filmstrip hiddenshots container_16">'+
	        			'<div class="container grid_16" />'+
	        			'</section>',			
			node: 'section#shot-gallery .gallery.photo.filmstrip',
			size: 'sq',
			render: true,
			castingCall: false,
			uuid: null,
			showExtras: true,
			showHiddenShot: false,
			hideHiddenShotByCSS: false,	
			draggable: true,
			droppable: true,	
			listeners: ['MultiSelect', 'Contextmenu', 'FocusClick', 'WindowOptionClick']
		},
		build: GalleryFactory.Photo.build,
		handle_focusClick: function(e){
	    	var gallery = this.Gallery;
			if (gallery.node.listen['disable_ThumbnailClick']) {
				// hide contextmenu with this click
				GalleryFactory.nav.toggle_ContextMenu(gallery, e);
        		return;		// allows temp disabling of listener
        	}	    	
	    	
	    	var selected = SNAPPI.Auditions.find(e.target.ancestor('.FigureBox').uuid);
    		gallery.setFocus(selected);
    		SNAPPI.Factory.Thumbnail.PhotoPreview.bindSelected(selected, null, {gallery:gallery});
		},
		/*
         * Key press functionality of next & previous buttons, rating
         * OVERRIDE DEFAULT IN GalleryFactory.actions.handleKeydown
         */
        handleKeydown: function(e){
// console.warn('>>> handleKeydown (shotGallery)');          	
        	var done, charCode = GalleryFactory[this._cfg.type].charCode;
        	var charStr = e.charCode + '';
        	// key navigation for GalleryFactory.Photo
			var focus = this.container.one('.FigureBox.focus');
			if ( focus == null ) {
				focus = this.container.one(':hover.FigureBox');
				if (focus) {
					this.setFocus(focus);
				}
			}
        	if ( focus == null ) {
				var i = this.auditionSH.indexOf(this.auditionSH.getFocus());
				this.setFocus(i); 
        	}
            if (charStr.search(charCode.nextPatt) == 0) {
                focus = this.next(); done = 1;
            }
            if (charStr.search(charCode.prevPatt) == 0) {
                focus = this.prev(); done = 1;
            }
            if (done) {
            	var selected = SNAPPI.Auditions.find(focus.uuid);
            	var previewBody = this.node.one('.preview-body');
	    		SNAPPI.Factory.Thumbnail.PhotoPreview.bindSelected(selected, previewBody, {gallery:this});
            }
            if (charStr.search(charCode.ratingPatt) == 0) {
            	try {
            		var v = parseInt(charStr) - 48; // 0 - 5
            		if (v > 5) v = parseInt(charStr) - 96; // keybd 0 - 5
            		SNAPPI.Rating.setRating(focus.Rating,v); 
            	} catch(e){}
            	 done = 1;
            }
            if (done) e.preventDefault();
        },
	}
	
	GalleryFactory.DialogHiddenShot = {
		defaultCfg: {
			type: 'DialogHiddenShot',
			tnType: 'Photo',	// thumbnail Type
			ID_PREFIX : 'hiddenshot-',
			PROVIDER_NAME: 'snappi',
			// MARKUP: '<section class="gallery photo filmstrip hiddenshots container_16">'+
	        			// '<div class="container grid_16" />'+
	        			// '</section>',	
			MARKUP: 	'<div id="dialog-hidden-shot" class="container_16" > ' +
	'	<section class="filmstrip filmstrip-bg drop alpha omega"> ' +
	' 		<div class="preview grid_11 alpha-b1 omega-b1"><nav class="toolbar"></nav></div>'+		
	'		<section class="gallery photo filmstrip hiddenshots grid_11 alpha-b1 omega-b1"> ' +
	'			<div class="filmstrip-wrap hidden"><div class="filmstrip"><div class="container"></div></div></div> ' +
	'		</section>	 ' +
	'		<section class="gallery-header alpha-b1 grid_11 omega-b1"> ' +
	'			<ul class="inline cf"> ' +
	'				<li><h3><img src="/static/img/css-gui/info.gif" alt="" align="absmiddle"></h3></li> ' +
	'				<li> ' +
	'					<nav class="toolbar"> ' +
	'						<div> ' +
	'							<ul class="inline menu-trigger"> ' +
	'								<li class="btn white select-all"><span class="menu-open"><input type="checkbox" value="" name=""></span></li> ' +
	'							</ul> ' +
	'						</div> ' +
	'						<h1 class="count">0 Snaps</h1> ' +
	'					</nav> ' +
	'				</li>		 ' +	
	'							<li class="right"> ' +
	'								<nav class="window-options"> ' +
	'									<ul class="thumb-size inline"> ' +
	'										<li class="label">Size</li> ' +
	'										<li class="btn white " action="set-display-size:sq"><img alt="" src="/static/img/css-gui/img_1.gif"></li>' +
	'										<li class="btn white "  action="set-display-size:tn"><img alt="" src="/static/img/css-gui/img_2.gif"></li>' +
	'										<li class="btn white " action="set-display-size:lm"><img alt="" src="/static/img/css-gui/img_3.gif"></li>' +
	'									</ul><ul class="inline"> ' +
	'										<li action="set-display-view:one-row"><img src="/static/img/css-gui/img_zoomin.gif"></li><li action="set-display-view:maximize"><img src="/static/img/css-gui/img_zoomout.gif"></li> ' +
	'									</ul> ' +
	'								</nav> ' +
	'							</li> ' +
	'			</ul> ' +
	'		</section> ' +
	'	</section>	 ' +
	'</div> ',		
			render: true,
			node: 'div#dialog-hidden-shot .gallery.photo.filmstrip'	,
			size: 'lm',
			castingCall: false,
			uuid: null,
			showExtras: true,
			showHiddenShot: false,
			hideHiddenShotByCSS: false,	
			draggable: true,	
			listeners: ['MultiSelect', 'Contextmenu', 'FocusClick', 'WindowOptionClick'],			
		},
		charCode : {
	        nextPatt: /(^110$)|(^39$)|(^32$)/, // n,right,space,
	        // keypad right
	        prevPatt: /(^112$)|(^37$)|(^8$)/, // p,left,backspace,
	        // keypad left
	        closePatt: /(^27$)/,
	        ratingPatt: /(^96$)|(^97$)|(^98$)|(^99$)|(^100$)|(^101$)(^49$)|(^50$)|(^51$)|(^52$)|(^53$)|(^48$)/, // keybd 0-5
	    },
		/*
         * build 
         * - scan for a cfg.node or defaultCfg.node, 
		 * - bind to JS auditions
         * - call AFTER SNAPPI.mergeSessionData(), important for XHR JSON request
         * @params gallery instance of SNAPPI.Gallery
         * @params cfg object, cfg object
         */
    	build: function(gallery, cfg){
            // var self = gallery;		// instance of SNAPPI.Gallery
            cfg = cfg || {};
            
            if (!cfg.size) delete cfg.size;	// merging undefined causes prob with default setting
            cfg = _Y.merge(GalleryFactory[cfg.type].defaultCfg, cfg);	
            
            try {
            	if (!cfg.castingCall && cfg.castingCall !== false) cfg.castingCall = PAGE.jsonData.castingCall;
            } catch (e){}
	        gallery.auditionSH = null;
	        gallery.shots = null; 	
	        
	        // generic gallery BEFORE init
			gallery.providerName = cfg.PROVIDER_NAME;	// deprecate: use this.cfg.providerName
			GalleryFactory._attachNodes(gallery, cfg);
			
			/*
			 *  for DialogHiddenShot, get initial size differently
			 */
			try {
    			var thumbSize = SNAPPI.STATE.thumbSize.DialogHiddenShot;
    		} catch(e){}
    		if (!thumbSize) thumbSize='sq';
			gallery.header.all('ul.thumb-size li.btn.white').some(function(n){
				// initialize header icon
				var action = n.getAttribute('action');
				if (action.match(thumbSize+'$')) {
					n.addClass('focus');
					return true;
				}
				return false;
			}, gallery);
			cfg.size = thumbSize;	// DialogHiddenShot	 
			/*
			 * end 
			 */
			
	        gallery.init(cfg);
	        
	        // apply SNAPPI.STATE.filters to section.gallery-display-options
	        try {	// not valid for NavFilmstrip
	        	GalleryFactory[cfg.type].apply_filter_settings(SNAPPI.STATE.filters, gallery);	        	
	        } catch(e) {}

	        
	        // .gallery.photo AFTER init methods
	        SNAPPI.Gallery.find[cfg.ID_PREFIX] = gallery;		// add to gallery lookup
	        SNAPPI.Rating.startListeners(gallery.container);
	        _Y.fire('snappi:after_PhotoGalleryInit', this); 
	        return gallery;					// return instance of SNAPPI.Gallery
        },
		handle_focusClick: function(e){
			// also check: DialogHelper.bindSelected2DialogHiddenShot(). which one is used?
	    	var gallery = this.Gallery;
			if (gallery.node.listen['disable_ThumbnailClick']) {
				// hide contextmenu with this click
				GalleryFactory.nav.toggle_ContextMenu(gallery, e);
        		return;		// allows temp disabling of listener
        	}	    	
	    	
	    	var selected = SNAPPI.Auditions.find(e.target.ancestor('.FigureBox').uuid);
    		gallery.setFocus(selected);
    		var previewBody = this.ancestor('.preview-body');
    		SNAPPI.Factory.Thumbnail.PhotoPreview.bindSelected(selected, previewBody, {gallery:gallery});
		},
		after_setToolbarOption : function (g, action, setting) {
			var previewBody = g.node.ancestor('.preview-body');
			previewBody.Dialog.refresh(previewBody); 
		},
		handleKeydown : GalleryFactory['ShotGallery'].handleKeydown,
	}
	
	// ShotGallery embedded inside a Photo Thumbnail
	GalleryFactory.ShotGalleryShot= {
		defaultCfg: {
			type: 'ShotGalleryShot',
			tnType: 'Photo',	// thumbnail Type
			ID_PREFIX : 'hiddenshot-',
			PROVIDER_NAME: 'snappi',
			// MARKUP: '<section class="gallery photo filmstrip hiddenshots container_16">'+
	        			// '<div class="container grid_16" />'+
	        			// '</section>',	
			MARKUP: 	
			// '<div class="shot-gallery-shot container_16" > ' +
	'	<section class="filmstrip shot filmstrip-bg drop"> ' +
	'		<section class="gallery photo filmstrip hiddenshots alpha-b1 omega-b1"> ' +
	'			<div class="filmstrip-wrap hidden"><div class="filmstrip"><div class="container"></div></div></div> ' +
	'		</section>	 ' +
	'		<section class="gallery-header alpha-b1 grid_11 omega-b1"> ' +
	'			<ul class="inline cf"> ' +
	'				<li> ' +
	'					<nav class="toolbar"> ' +
	'						<h1 class="count">0 Snaps</h1> ' +
	'                       <ul class="inline"> ' +
	'							<li class="btn orange un-group" action="ungroup-shot">Ungroup</li>' +
	'							<li class="btn orange group disabled hide" action="group-shot">Group</li>' +
	'						</ul> ' +		
	'					</nav> ' +
	'				</li>		 ' +	
	'							<li class="right"> ' +
	'								<nav class="window-options"> ' +
	'									<ul class="thumb-size inline"> ' +
	'										<li class="label">Size</li> ' +
	'										<li class="btn white " action="set-display-size:tn"><img alt="" src="/static/img/css-gui/img_1.gif"></li>' +
	'										<li class="btn white "  action="set-display-size:bs"><img alt="" src="/static/img/css-gui/img_2.gif"></li>' +
	'										<li class="btn white " action="set-display-size:bm"><img alt="" src="/static/img/css-gui/img_3.gif"></li>' +
	'									</ul> ' +
	'								</nav> ' +
	'							</li> ' +
	'			</ul> ' +
	'		</section> ' +
	'	</section>	 ',
	// '</div> ',		
			render: true,
			node: 'div#dialog-hidden-shot .gallery.photo.filmstrip'	,
			size: 'lm',
			castingCall: false,
			uuid: null,
			showExtras: true,
			showHiddenShot: false,
			hideHiddenShotByCSS: false,	
			draggable: true,	
			listeners: ['MultiSelect', 'Contextmenu', 'FocusClick', 'WindowOptionClick', 'ShotGalleryToolbarOptionClick'],			
		},
		charCode : {
	        nextPatt: /(^110$)|(^39$)|(^32$)/, // n,right,space,
	        // keypad right
	        prevPatt: /(^112$)|(^37$)|(^8$)/, // p,left,backspace,
	        // keypad left
	        closePatt: /(^27$)/,
	        ratingPatt: /(^96$)|(^97$)|(^98$)|(^99$)|(^100$)|(^101$)(^49$)|(^50$)|(^51$)|(^52$)|(^53$)|(^48$)/, // keybd 0-5
	    },
		/*
         * build 
         * - scan for a cfg.node or defaultCfg.node, 
		 * - bind to JS auditions
         * - call AFTER SNAPPI.mergeSessionData(), important for XHR JSON request
         * @params gallery instance of SNAPPI.Gallery
         * @params cfg object, cfg object
         */
    	build: function(gallery, cfg){
            // var self = gallery;		// instance of SNAPPI.Gallery
            cfg = cfg || {};
            
            if (!cfg.size) delete cfg.size;	// merging undefined causes prob with default setting
            cfg = _Y.merge(GalleryFactory[cfg.type].defaultCfg, cfg);	
            
            try {
            	if (!cfg.castingCall && cfg.castingCall !== false) cfg.castingCall = PAGE.jsonData.castingCall;
            } catch (e){}
	        gallery.auditionSH = null;
	        gallery.shots = null; 	
	        
	        // generic gallery BEFORE init
			gallery.providerName = cfg.PROVIDER_NAME;	// deprecate: use this.cfg.providerName
			GalleryFactory._attachNodes(gallery, cfg);
			
			try {
    			var thumbSize = SNAPPI.STATE.thumbSize.ShotGalleryShot;
    		} catch(e){
    			thumbSize = cfg.size || 'sq';
    		}
			gallery.header.all('ul.thumb-size li.btn.white').some(function(n){
				// initialize header icon
				var action = n.getAttribute('action');
				if (action.match(thumbSize+'$')) {
					n.addClass('focus');
					return true;
				}
				return false;
			}, gallery);
			cfg.size = thumbSize;	// DialogHiddenShot	 
			/*
			 * end 
			 */
			
	        gallery.init(cfg);
	        
	        // apply SNAPPI.STATE.filters to section.gallery-display-options
	        try {	// not valid for NavFilmstrip
	        	GalleryFactory[cfg.type].apply_filter_settings(SNAPPI.STATE.filters, gallery);	        	
	        } catch(e) {}

	        
	        // .gallery.photo AFTER init methods
	        SNAPPI.Gallery.find[cfg.ID_PREFIX] = gallery;		// add to gallery lookup
	        SNAPPI.Rating.startListeners(gallery.container);
	        _Y.fire('snappi:after_PhotoGalleryInit', this); 
	        return gallery;					// return instance of SNAPPI.Gallery
        },
		handle_focusClick: function(e){
			// also check: DialogHelper.bindSelected2DialogHiddenShot(). which one is used?
	    	var gallery = this.Gallery;
			if (gallery.node.listen['disable_ThumbnailClick']) {
				// hide contextmenu with this click
				GalleryFactory.nav.toggle_ContextMenu(gallery, e);
        		return;		// allows temp disabling of listener
        	}	    	
	    	
	    	var selected = SNAPPI.Auditions.find(e.target.ancestor('.FigureBox').uuid);
    		gallery.setFocus(selected);
    		var previewBody = this.ancestor('.preview-body');
    		SNAPPI.Factory.Thumbnail.PhotoPreview.bindSelected(selected, previewBody, {gallery:gallery});
		},
		after_setToolbarOption : function (g, action, setting) {
			var previewBody = g.node.ancestor('.preview-body');
			previewBody.Dialog.refresh(previewBody); 
		},
		handleKeydown : GalleryFactory['ShotGallery'].handleKeydown,
	}
		
	// TODO: currently unused!!!
	GalleryFactory.PhotoAirUpload = {
    	defaultCfg : {
    		type: 'PhotoAirUpload',
			ID_PREFIX: 'uuid-',
			PROVIDER_NAME: 'snappi',
			MARKUP: '<section class="gallery photo container_16">'+
	        			'<div class="container grid_16" />'+
	        			'</section>',
			node: 'div.gallery-container > section.gallery.photo',
			render: true,
			listeners: ['Keydown', 'Mouseover', 'MultiSelect', 'HiddenShotClick', 'Contextmenu', 'WindowOptionClick', 'ZoomClick'],
			draggable: true,
			hideHiddenShotByCSS: true,
			size: 'lm',
			start: null,
			end: null
	    },
        /*
         * build 
         * - scan for a cfg.node or defaultCfg.node, 
		 * - bind to JS auditions
         * - call AFTER SNAPPI.mergeSessionData(), important for XHR JSON request
         * @params gallery instance of SNAPPI.Gallery
         * @params cfg object, cfg object
         */
    	build: function(gallery, cfg){
            // var self = gallery;		// instance of SNAPPI.Gallery
            cfg = cfg || {};
            // inherit javascript state information from current page, 
            // called AFTER SNAPPI.mergeSessionData();
            // TODO: only merge SNAPPI.STATE.displayPage for "primary" gallery, with paging
            cfg = _Y.merge(GalleryFactory[cfg.type].defaultCfg, SNAPPI.STATE.displayPage, cfg);	
            try {
            	// TODO DEPRECATE. for PhotoAirUpload. use focus in /elelments/photos/header
            	cfg.size = PAGE.jsonData.profile.thumbSize[cfg.ID_PREFIX];
            } catch (e){}
            try {
            	if (!cfg.castingCall && cfg.castingCall !== false) cfg.castingCall = PAGE.jsonData.castingCall;
            } catch (e){}
	        
	        // .gallery.photo BEFORE init
	        gallery.auditionSH = null;
	        gallery.shots = null; 	
	        
	        // generic gallery BEFORE init
			gallery.providerName = cfg.PROVIDER_NAME;	// deprecate: use this.cfg.providerName
			GalleryFactory._attachNodes(gallery, cfg);
	        gallery.init(cfg);
	        
	        // .gallery.photo AFTER init methods
	        SNAPPI.Gallery.find[cfg.ID_PREFIX] = gallery;		// add to gallery lookup
	        SNAPPI.Rating.startListeners(gallery.container);
	        _Y.fire('snappi:after_PhotoGalleryInit', this); 
	        return gallery;					// return instance of SNAPPI.Gallery
        },
	};
	
})();