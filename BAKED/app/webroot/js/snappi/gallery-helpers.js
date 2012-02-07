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
	
    SNAPPI.namespace('SNAPPI.onYready');
    SNAPPI.onYready.GalleryFactory = function(Y){
		if (_Y === null) _Y = Y;
		LIGHTBOX_PERPAGE_LIMIT = 72;
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
    					g.setFilmstripWidth();
    				} else {
    					parent.removeClass('minimize');
	        			g.container.addClass('one-row');
	        			g.header.one('ul').removeClass('hide');
	        		}
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
		gallery.header = node.siblings('.gallery-header');
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
			try {	// merge SNAPPI.STATE.displayPage for primary gallery, Photo=photos, home
				if (/[photos|snaps|home]/.test( SNAPPI.STATE.controller.action ) 
					|| SNAPPI.STATE.controller.name+'/'+SNAPPI.STATE.controller.action == "Assets/all"
				) {
					cfg = _Y.merge(SNAPPI.STATE.displayPage, cfg);
					// check for valid size, type=Photo
					if (/^(sq|lm|ll)$/.test(SNAPPI.STATE.previewSize)) {
						cfg.size = SNAPPI.STATE.previewSize;	// override
						delete PAGE.jsonData.profile.thumbSize[cfg.ID_PREFIX];
					}
				}
			} catch (e) { 	}
            cfg = _Y.merge(GalleryFactory[cfg.type].defaultCfg, cfg);	
            try {
            	cfg.size = PAGE.jsonData.profile.thumbSize[cfg.ID_PREFIX];
            } catch (e){  }
            
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
            // inherit javascript state information from current page, 
            // called AFTER SNAPPI.mergeSessionData();
            cfg = _Y.merge(GalleryFactory[cfg.type].defaultCfg, SNAPPI.STATE.displayPage, cfg);	
            try {
            	cfg.size = PAGE.jsonData.profile.thumbSize[cfg.ID_PREFIX];
            } catch (e){}
            try {
            	if (!cfg.castingCall && cfg.castingCall !== false) cfg.castingCall = PAGE.jsonData.lightbox.castingCall;
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
			listeners: ['Keypress', 'Mouseover', 'MultiSelect', 'Contextmenu', 'FocusClick', 'HiddenShotClick', 'WindowOptionClick'],
		},
		build: GalleryFactory.Photo.build,
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
		    		SNAPPI.Factory.Thumbnail.PhotoPreview.bindSelected(selected, previewBody);
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
			// update castingCall if necessary 
			try {
				var uuid = SNAPPI.STATE.controller.xhrFrom.uuid;
			} catch (e) {
				uuid = null;
			}
			try {
				var EXTENDED_PAGE = 999;
				var i = g.castingCall.auditionSH.indexOfKey(uuid);
				var offset = (SNAPPI.STATE.displayPage.page-1) *  SNAPPI.STATE.displayPage.perpage;
				var page = Math.floor( (i+offset)/EXTENDED_PAGE) + 1;
				var perpage = EXTENDED_PAGE;
				var isExtended = /perpage:999/.test(g.castingCall.CastingCall.Request);
			} catch (e) {
				isExtended = false;
			}
			
			/*
			 * load navFilmstrip if not already loaded
			 */
			var photoPreview = _Y.one('.preview-body .FigureBox.PhotoPreview');
			var loadFilmStrip = g.container.all('.FigureBox').size() < g.auditionSH.count();
			if (uuid && loadFilmStrip ){
				try {
					// var uri = PAGE.jsonData.castingCall.CastingCall.Request;
					var uri = '/photos/neighbors/'+ PAGE.jsonData.castingCall.CastingCall.ID + '/.json';
					// get extended castingCall by cacheRefresh
					var named = {perpage: perpage, page: page};
					uri = SNAPPI.IO.setNamedParams(uri, named);
					var options = _Y.merge({
						uuid: uuid,
					}, named);
					g.loadCastingCall(uri, options);
					// autoScroll default=true				
					photoPreview.one('figcaption input[type=checkbox].auto-advance').set('checked', true);
				} catch (e) {}	
			}
			// set PhotoPreview autoScroll, as necessary
			try {
				var isAutoScroll = photoPreview.one('figcaption input[type=checkbox].auto-advance').get('checked');
				SNAPPI.Factory.Thumbnail.PhotoPreview.set_AutoScroll(isAutoScroll, photoPreview, g);
			} catch (e) {}
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
	    		SNAPPI.Factory.Thumbnail.PhotoPreview.bindSelected(selected);
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
	'	<section class="filmstrip drop alpha omega"> ' +
	' 		<div class="preview grid_11 alpha-b1 omega-b1"><nav class="toolbar"></nav></div>'+		
	'		<section class="gallery photo filmstrip hiddenshots grid_11 alpha-b1 omega-b1"> ' +
	'			<div class="filmstrip-wrap hidden"><div class="filmstrip"><div class="container"></div></div></div> ' +
	'		</section>	 ' +
	'		<section class="gallery-header alpha-b1 grid_11 omega-b1"> ' +
	'			<ul class="inline cf"> ' +
	'				<li><h3><img src="/css/images/img_setting.gif" alt="" align="absmiddle"></h3></li> ' +
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
	'										<li class="btn white " action="set-display-size:sq"><img alt="" src="/css/images/img_1.gif"></li>' +
	'										<li class="btn white "  action="set-display-size:tn"><img alt="" src="/css/images/img_2.gif"></li>' +
	'										<li class="btn white " action="set-display-size:lm"><img alt="" src="/css/images/img_3.gif"></li>' +
	'									</ul><ul class="inline"> ' +
	'										<li action="set-display-view:one-row"><img src="/css/images/img_zoomin.gif"></li><li action="set-display-view:maximize"><img src="/css/images/img_zoomout.gif"></li> ' +
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
	    		SNAPPI.Factory.Thumbnail.PhotoPreview.bindSelected(selected, previewBody);
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