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
	        	// check display mode for filmstrip mode, reset width to fit thumbsize
	        	if (g.container.hasClass('one-row')) {
	        		g.setFilmstripWidth();
	        		g.scrollFocus();
	        		if (g._cfg.type == 'NavFilmstrip') GalleryFactory['NavFilmstrip'].setPagingControls(g);
	        	}
        	}
        	// TODO: save thumbSize to Session Session::write("thumbSize.{g._cfg.size}", g._cfg.size);  		
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
		        		SNAPPI.setPageLoading(true);
		        		var sessKey = 'thumbSize.'+ this.Gallery._cfg.type;
		        		var cfg = {};
		        		cfg[sessKey] = action[1];
		        		SNAPPI.io.writeSession(cfg);
	    			break;	 
	    			case 'toggle-display-options':
	    				SNAPPI.UIHelper.nav.toggleDisplayOptions();
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
         * Key press functionality of next & previous buttons
         */
        handleKeypress: function(e){
        	var charCode = GalleryFactory[this._cfg.type].charCode;
        	var charStr = e.charCode + '';
            if (e.ctrlKey) {
            	// selectAll
                if (charStr.search(charCode.selectAllPatt) == 0) {
                    e.preventDefault();
                    this.selectAll();
                    return;
                }
                // group
                if (charStr.search(charCode.groupPatt) == 0) {
                    e.preventDefault();
                    this.groupAsShot();
                    return;
                }
            }
            
			// key navigation for GalleryFactory.Photo
			var focus = this.container.one('.FigureBox.focus');
			if ( focus == null ) {
				focus = this.container.one(':hover.FigureBox');
				if (focus) focus.addClass('focus');
			}
        	if ( focus == null ) {
				var i = this.auditionSH.indexOf(this.auditionSH.getFocus());
				this.setFocus(i);
				return;
        	}
            if (charStr.search(charCode.nextPatt) == 0) {
                e.preventDefault();
                this.next();
                return;
            }
            if (charStr.search(charCode.prevPatt) == 0) {
                e.preventDefault();
                this.prev();
                return;
            }
            if (charStr.search(charCode.downPatt) == 0) {
            	e.preventDefault();
            	this.down();
            }
            if (charStr.search(charCode.upPatt) == 0) {
            	e.preventDefault();
            	this.up();
            }
            if (charStr.search(charCode.ratingPatt) == 0) {
            	e.preventDefault();
            	try {
            		var v = parseInt(charStr) - 48; // 0 - 5
            		SNAPPI.Rating.setRating(focus.Rating,v); 
            	} catch(e){}
            }
        },
    }
    GalleryFactory.nav = {
    	/**
    	 * @params g SNAPPI.Gallery
    	 * @params e event, needs e.currentTarget
    	 */
    	toggle_ContextMenu : function(g, e) {
	        e.preventDefault();
        	
        	var CSS_ID, TRIGGER;
        	switch(g._cfg.type){
        		case 'Photo':
        		case 'NavFilmstrip': 
        			CSS_ID = 'contextmenu-photoroll-markup';
        			break;
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
        	
        	// load/toggle contextmenu
        	if (!SNAPPI.MenuAUI.find[CSS_ID]) {
        		var contextMenuCfg = {
        			// triggerType: 'photo',				// .gallery.photo
        			triggerRoot: g.container,
        			currentTarget:e.currentTarget,
        			init_hidden: false,
        			host: g,			// add Gallery.ContextMenu backreference
				}; 
        		var menu = SNAPPI.MenuAUI.CFG[CSS_ID].load(contextMenuCfg);
        		g.node.listen['disable_LinkToClick'] = true;
        	} else {
        		var menu = SNAPPI.MenuAUI.toggleEnabled(CSS_ID, e);
        		// if (g._cfg.listeners.indexOf('LinkToClick')> -1) {
        			// toggle LinkToClick listener
	        		if (menu.get('disabled')) {
	        			// TODO: nav to attribute "linkTo"
	        			// Factory.listeners.LinkToClick.call(this);
	        			g.node.listen['disable_LinkToClick'] = false;
	        		} else {
	        			// g.stopLinkToClickListener();
	        			g.node.listen['disable_LinkToClick'] = true;
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
	            			if (this.listen['disable_LinkToClick']) {
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
		                			gallery.stopLinkToClickListener();
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
					    				fn = GalleryFactory[this.Gallery._cfg.type]['handle_'+action[0]];
					    				break;
					    		}
			                	try {
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
							};
						})	        	
				}
	        }, 
	        Keypress: function(){
	        	var action = 'Keypress';
	            if (this.node.listen['Keypress'] == undefined) {
	            	var startListening = function() {
	            		if (!this.node.listen['Keypress']) {
	            			this.node.listen['Keypress'] = _Y.on('keypress', GalleryFactory.actions.handleKeypress, document, this);
	            		}
	            	};
	            	var stopListening = function() {
	            		if (this.node.listen['Keypress']) { 
	            			this.node.listen['Keypress'].detach();
	            			delete this.node.listen['Keypress'];
	            			// hide focus
	            			this.container.all('li.focus').removeClass('focus');
	            		}
	            	}; 
	            	this.container.on('snappi:hover', startListening, stopListening, this);
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
			listeners: ['Keypress', 'Mouseover', 'LinkToClick', 'MultiSelect', 'HiddenShotClick', 'Contextmenu', 'WindowOptionClick', 'DisplayOptionClick'],
			draggable: true,
			hideHiddenShotByCSS: true,
			size: 'lm',
			start: null,
			end: null
	    },
	    charCode : {
	        nextPatt: /(^110$)|(^39$)|(^32$)|(^54$)/, // n,right,space,
	        // keypad right
	        prevPatt: /(^112$)|(^37$)|(^8$)|(^52$)/, // p,left,backspace,
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
	        ratingPatt: /(^48$)|(^49$)|(^50$)|(^51$)|(^52$)|(^53$)/, // keybd 0-5
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
			// listeners: ['Keypress', 'Mouseover', 'LinkToClick', 'MultiSelect', 'HiddenShotClick', 'Contextmenu', 'WindowOptionClick'],
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
			// listeners: ['Keypress', 'Mouseover', 'MultiSelect', 'Contextmenu', 'FocusClick', 'WindowOptionClick'],
			listeners: ['Keypress', 'Mouseover', 'MultiSelect', 'Contextmenu', 'FocusClick', 'HiddenShotClick', 'WindowOptionClick', 'PaginateClick', 'SetPagingControls'],
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
				g.render({uuid: uuid});		// just render existing CC
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
	    	
			if (gallery.node.listen['disable_LinkToClick']) {
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
		    	var previewUuid = previewBody.one('.FigureBox.PhotoPreview').Thumbnail.id;
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
			if (gallery.node.listen['disable_LinkToClick']) {
				// hide contextmenu with this click
				GalleryFactory.nav.toggle_ContextMenu(gallery, e);
        		return;		// allows temp disabling of listener
        	}	    	
	    	
	    	var selected = SNAPPI.Auditions.find(e.target.ancestor('.FigureBox').uuid);
	    	var oldUuid = gallery.getFocus().id;
	    	if (selected.id != oldUuid) {
	    		gallery.setFocus(selected);
	    		SNAPPI.Factory.Thumbnail.PhotoPreview.bindSelected(selected, null, {gallery:gallery});
	        }
		},
	}
	
	GalleryFactory.DialogHiddenShot = {
		defaultCfg: {
			type: 'DialogHiddenShot',
			tnType: 'Photo',	// thumbnail Type
			ID_PREFIX : 'hiddenshot-',
			PROVIDER_NAME: 'snappi',
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
		build: GalleryFactory.ShotGallery.build,
		handle_focusClick: function(e){
			// also check: DialogHelper.bindSelected2DialogHiddenShot(). which one is used?
	    	var gallery = this.Gallery;
			if (gallery.node.listen['disable_LinkToClick']) {
				// hide contextmenu with this click
				GalleryFactory.nav.toggle_ContextMenu(gallery, e);
        		return;		// allows temp disabling of listener
        	}	    	
	    	
	    	var selected = SNAPPI.Auditions.find(e.target.ancestor('.FigureBox').uuid);
	    	var oldUuid = gallery.getFocus().id;
	    	if (selected.id != oldUuid) {
	    		gallery.setFocus(selected);
	    		var previewBody = this.ancestor('.preview-body');
	    		SNAPPI.Factory.Thumbnail.PhotoPreview.bindSelected(selected, previewBody, {gallery:gallery});
	        }
		},
		after_setToolbarOption : function (g, action, setting) {
			var previewBody = g.node.ancestor('.preview-body');
			previewBody.Dialog.refresh(previewBody); 
		}
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
			listeners: ['Keypress', 'Mouseover', 'LinkToClick', 'MultiSelect', 'HiddenShotClick', 'Contextmenu', 'WindowOptionClick'],
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