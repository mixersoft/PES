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
	var Y = SNAPPI.Y;
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
					g.container.ancestor('.filmstrip-wrap').removeClass('hide');
					g.container.get('parentNode').removeClass('minimize');
	    			break;
    		}
    		g.view = view;
    	},
    	setSize: function(g, size) {
        	g.renderThumbSize(size);
        	// check display mode for filmstrip mode, reset width to fit thumbsize
        	if (g.container.hasClass('one-row')) g.setFilmstripWidth();
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
	    			break;
	    			case 'set-display-size':
	    				GalleryFactory.actions.setSize(this.Gallery, action[1]);
	    			break;	    			
	    		}
	    		// set window option button to selected value
	    		e.currentTarget.get('parentNode').all('li').removeClass('focus');
	        	e.currentTarget.addClass('focus');
	        	
	        	// check for 'after' event
				var fn = GalleryFactory[this.Gallery._cfg.type]['after_setToolbarOption'];
				if (fn) {
					fn(this.Gallery, action[0], action[1] );
				} 	        		
        	} catch (e) {}
    	},
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
	        Click: function(forceStart) {
	            if (this.node.listen['Click'] == undefined || forceStart ) {
	            	// section.gallery.photo or div.filmstrip.photo
	                this.node.listen['Click'] = this.node.delegate('click', function(e){
	                    var next = e.target.getAttribute('linkTo');
	                    if (this.Gallery.castingCall.CastingCall) {
	                    	next += '?ccid=' + this.Gallery.castingCall.CastingCall.ID;
							try {
								var shotType = e.currentTarget.ancestor('.FigureBox').audition.Audition.Substitutions.shotType;
								if (shotType == 'Groupshot'){
									next += '&shotType=Groupshot';
								}
							} catch (e) {}
	                    }
	                    window.location.href = next;
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
	        	if (this.node.get('parentNode') && !this.node.listen['selectAll']) {
		        	this.node.listen['selectAll'] = this.node.get('parentNode').delegate('click', 
		        	function(e){
		        		var checked = e.currentTarget.get('checked');
		        		if (checked) this.Gallery.container.all('.FigureBox').addClass('selected');
		        		else {
		        			this.Gallery.container.all('.FigureBox').removeClass('selected');
		        			SNAPPI.STATE.selectAllPages = false;
		        		}
		        	},'li.select-all input[type="checkbox"]', this.node);
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
		                			gallery.stopClickListener();
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
	        	if (this.node.listen['Contextmenu'] == undefined){
	        		this.node.listen['ContextMenuClick'] = this.container.delegate('contextmenu', 
	        		function(e){
						this.Gallery.toggle_ContextMenu(e);
	        		}, '.FigureBox', this.node);
	        		
	        		// .FigureBox li.context-menu.icon
	     			this.node.listen['ContextMenuIconClick'] = this.container.delegate('click', 
	     			function(e){
						this.Gallery.toggle_ContextMenu(e);
						e.stopImmediatePropagation();
	        		}, '.FigureBox  figcaption  li.context-menu', this.node);        		
				}        	
	        	return;
	        },        	
        };
    /**
     * attach gallery.node and gallery.container
     */
    GalleryFactory._attachNodes = function(gallery, cfg){
        gallery.container = null;
		var node = cfg.node instanceof Y.Node ? cfg.node : Y.one(cfg.node);
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
			listeners: ['Keypress', 'Mouseover', 'Click', 'MultiSelect', 'HiddenShotClick', 'Contextmenu', 'ThumbSizeClick'],
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
            var Y = SNAPPI.Y;
            // var self = gallery;		// instance of SNAPPI.Gallery
            cfg = cfg || {};
            // inherit javascript state information from current page, 
            // called AFTER SNAPPI.mergeSessionData();
            // TODO: only merge SNAPPI.STATE.displayPage for "primary" gallery, with paging
            cfg = Y.merge(GalleryFactory[cfg.type].defaultCfg, SNAPPI.STATE.displayPage, cfg);	
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
	        Y.fire('snappi:afterGalleryInit', this); 
	        return gallery;					// return instance of SNAPPI.Gallery
        },
        handle_hiddenShotClick : function(e){
        	var thumbnail = e.currentTarget.ancestor('.FigureBox');
			try {
				var audition = thumbnail.audition;
				var gallery = this.Gallery;
				SNAPPI.Helper.Dialog.bindSelected2DialogHiddenShot(gallery, audition);
				return;
			} catch (e) {
			}                	
		},
	};
	
	LIGHTBOX_PERPAGE_LIMIT = 72;
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
			// listeners: ['Keypress', 'Mouseover', 'Click', 'MultiSelect', 'HiddenShotClick', 'Contextmenu', 'WindowOptionClick'],
			listeners: ['MultiSelect', 'WindowOptionClick'],
			draggable: false,
			hideHiddenShotByCSS: false,
			size: 'lbx-tiny',
			perpage: LIGHTBOX_PERPAGE_LIMIT, 
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
            var Y = SNAPPI.Y;
            // var self = gallery;		// instance of SNAPPI.Gallery
            cfg = cfg || {};
            // inherit javascript state information from current page, 
            // called AFTER SNAPPI.mergeSessionData();
            cfg = Y.merge(GalleryFactory[cfg.type].defaultCfg, SNAPPI.STATE.displayPage, cfg);	
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
	        Y.fire('snappi:afterGalleryInit', this); 
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
	    	var selected = e.target.ancestor('.FigureBox').audition;
	    	var oldUuid = gallery.getFocus().id;
	    	gallery.auditionSH.setFocus(selected);
	    	gallery.scrollFocus(selected.id);
	    	// gallery.filmstrip_SetFocus(selected);
	    	if (selected.id != oldUuid) {
	    		// SNAPPI.domJsBinder.bindSelected2Page(gallery, selected, oldUuid);
	    		var previewBody = Y.one('.preview-body');
	    		SNAPPI.Factory.Thumbnail.PhotoPreview.bindSelected(selected, previewBody);
	        }
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
			// update castingCall if necessary 
			try {
				var uuid = SNAPPI.STATE.controller.xhrFrom.uuid;
			} catch (e) {
				uuid = null;
			}
			// var uri = PAGE.jsonData.castingCall.CastingCall.Request;
			var uri = '/photos/neighbors/'+ PAGE.jsonData.castingCall.CastingCall.ID + '/.json';
			// get extended castingCall by cacheRefresh
			uri = SNAPPI.IO.setNamedParams(uri, {perpage: null, page: null});
			g.loadCastingCall(uri, {uuid: uuid});
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
	    	var selected = e.currentTarget.ancestor('.FigureBox').audition;
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
	'			<ul class="inline"> ' +
	'				<li><h3><img src="/css/images/img_setting.gif" alt="" align="absmiddle"></h3></li> ' +
	'				<li> ' +
	'					<nav class="toolbar"> ' +
	'						<div> ' +
	'							<ul class="inline menu-trigger"> ' +
	'								<li class="select-all"><input type="checkbox" value="" name=""><a class="menu-open"> </a></li> ' +
	'							</ul> ' +
	'						</div> ' +
	'						<h1 class="count">0 Snaps</h1> ' +
	'					</nav> ' +
	'				</li>		 ' +	
	'							<li class="right"> ' +
	'								<nav class="window-options"> ' +
	'									<ul class="thumb-size inline"> ' +
	'										<li class="label">Size</li> ' +
	'										<li class="btn" action="set-display-size:sq"><img alt="" src="/css/images/img_1.gif"></li><li class="btn"  action="set-display-size:tn"><img alt="" src="/css/images/img_2.gif"></li><li class="btn" action="set-display-size:lm"><img alt="" src="/css/images/img_3.gif"></li>	 ' +
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
	    	var selected = e.currentTarget.ancestor('.FigureBox').audition;
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
	
	var GalleryHelper = function(cfg) {	};
	
	GalleryHelper.prototype = {
    	setView: function(g, view) {
    		var parent = g.node.get('parentNode');
    		switch(view) {
    			case 'minimize':  
	        		parent.addClass('minimize');
	        		// g.header.one('ul').addClass('hide');      			
	    			break;
    			case 'one-row': 
	        		parent.removeClass('minimize');
	        		g.container.addClass('one-row');
	        		g.header.one('ul').removeClass('hide');
	        		break;
    			case 'maximize': 
    				// from lighbox action.maximize, not tested 
					var MAX_HEIGHT = window.innerHeight - 120;
					var count = Math.min(this.Gallery.auditionSH.count(), _LIGHTBOX_LIMIT);
					var width = this.Gallery.container.one('.FigureBox').get('offsetWidth');
					var rows = Math.ceil(count*width/940);
					var height = this.Gallery.container.one('.FigureBox').get('offsetHeight');
					if (rows*height > MAX_HEIGHT) {
						rows = Math.floor(MAX_HEIGHT/height);
						height = (rows*height)+'px';
					} else {
						height = 'auto';
					}
					this.Gallery.container.setStyles({
						width: 'auto',
						height: height	
					}).removeClass('one-row');;
					this.Gallery.container.ancestor('.filmstrip-wrap').removeClass('hide');
					this.Gallery.container.get('parentNode').removeClass('minimize');
					e.currentTarget.addClass('focus');    			
	    			break;
    		}
    		g.view = view;
    	},
    	setSize: function(g, size) {
        	g.renderThumbSize(size);
        	// check display mode for filmstrip mode, reset width to fit thumbsize
        	if (g.container.hasClass('one-row')) g.setFilmstripWidth();
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
	    			break;
	    			case 'set-display-size':
	    				GalleryFactory.actions.setSize(this.Gallery, action[1]);
	    			break;	    			
	    		}
	    		// set window option button to selected value
	    		e.currentTarget.get('parentNode').all('li').removeClass('focus');
	        	e.currentTarget.addClass('focus');	
        	} catch (e) {}
    	},		
	};
	/*
	 * make global
	 */
	SNAPPI.galleryHelper = new GalleryHelper();
})();