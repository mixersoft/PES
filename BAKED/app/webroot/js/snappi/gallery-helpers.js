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
	        	
	        	// check for 'after' event
				var fn = GalleryFactory[this.Gallery._cfg.type]['after_setToolbarOption'];
				if (fn) {
					fn(this.Gallery, action[0], action[1] );
				} 	        		
        	} catch (e) {}
    	},
    }
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
        // handle_hiddenShotClick : function(e){
        	// var thumbnail = e.currentTarget.ancestor('.FigureBox');
			// try {
				// var audition = thumbnail.audition;
				// var gallery = this.Gallery;
				// var shotType = audition.Audition.Substitutions.shotType;
				// if (!shotType) shotType = /^Groups/.test(SNAPPI.STATE.controller.name) ? 'Groupshot' : 'Usershot';
				// gallery.showHiddenShotsInDialog(audition, shotType);
			// } catch (e) {
			// }                	
		// },
       
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
			listeners: ['Keypress', 'Mouseover', 'MultiSelect', 'Contextmenu', 'FocusClick', 'WindowOptionClick'],
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
	    		SNAPPI.domJsBinder.bindSelected2Page(gallery, selected, oldUuid);
	        }
		},
		
		
		
		
		
		
		
		
		
        handle_hiddenShotClick : function(e){
        	e.stopImmediatePropagation();
        	var thumbnail = e.currentTarget.ancestor('.FigureBox');
			try {
				var audition = thumbnail.audition;
				var gallery = this.Gallery;
				
				
				// new pattern, reuse Thumbnail.PhotoPreview
				SNAPPI.Helper.Dialog.bindSelected2DialogHiddenShot(gallery, audition);
				return;
				
				
				
				var shotType = audition.Audition.Substitutions.shotType;
				if (!shotType) shotType = /^Groups/.test(SNAPPI.STATE.controller.name) ? 'Groupshot' : 'Usershot';
				gallery.showHiddenShotsInDialog(audition, shotType);
			} catch (e) {
			}                	
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
	
	var PreviewHelper = function(cfg) {	};
	PreviewHelper.DialogHiddenShot = {
		/*
		 * render different preview templates, based on preview size
		 */
		renderPreview: function(container, selected, size) {
			switch (size) {
				case "tn":
				case "bs":
				case "bm":
					// show photo properties for smaller previews
				case "bp":
		    		var img = container.one('img.preview');
		    		if (!img) {
		    			container.append('<img class="preview"/>');
		    			img = container.one('img.preview');
						if (!container.listen) container.listen = {};
						container.listen['imgOnLoad'] = img.on('load', function(e){
							container.loadingmask.hide();
							Y.fire('snappi:preview-change', container);
						}); 
					}		    		
					var src = selected.getImgSrcBySize(selected.urlbase + selected.src, size);
					container.loadingmask.show(); 
					img.set('src', src).set('title', selected.label);
				break;
			}
		},
		listen: function(filmstrip){
			if (!filmstrip.node.listen['preview-change']) {
				filmstrip.node.listen['preview-change'] = Y.on('snappi:preview-change', 
	            	function(previewBody){
	            		var dialog_ID = 'dialog-photo-roll-hidden-shots';
						var dialog = SNAPPI.Dialog.find[dialog_ID];
	            		var body = dialog.get('boundingBox');
	            		if (body && (body.get('id') == dialog_ID)) {
	            			var height = body.one('section.filmstrip').get('clientHeight');
		                    dialog.set('height', height + 60);
	                    }
	        	});
			}			
		}
		
	}

})();