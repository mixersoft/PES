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
	/*
	 * protected
	 */
	var _Y = null;
    SNAPPI.namespace('SNAPPI.onYready');
    SNAPPI.onYready.ThumbnailFactory = function(Y){
		if (_Y === null) _Y = Y;
		
		// MultiSelect
		SNAPPI.MultiSelect = MultiSelect;
		SNAPPI.multiSelect = new MultiSelect();
		
		// Thumbnail Factory
		SNAPPI.namespace('SNAPPI.Factory');
		SNAPPI.Factory.Thumbnail = ThumbnailFactory;

	}	
	
	// // find only visible elements. copied from SNAPPI.util.isDOMVisible(n);
    var _isDOMVisible = function(n){
    	return n.getComputedStyle('display') != 'none' && n.getComputedStyle('visibility') != 'hidden';
	};

	// find multiSelect boundary element for shift-click
	var _boundary = function(n) {
		var found = n.hasClass('selected');
		found = found && (n.hasClass('FigureBox'));
		found = found && _isDOMVisible(n);
		return found;
	};
	
	var MultiSelect = function(cfg) {
		if (MultiSelect.instance) return MultiSelect.instance;		// singleton
		MultiSelect.instance = this;
	};
	
	/*
	 * static methods
	 * 	choose select handler for listen
	 */
	MultiSelect.singleSelectHandler = function(e) {
		var target = e.target;
		if (target.get('parentNode').hasClass('context-menu')) {
			// let ContextMenu listner handle this click.
			return;
		}
		
		target = e.currentTarget; 	// .FigureBox
		var selected = target.hasClass('selected');
		// Check if the target is an image and select it.
		this.clearAll(target.ancestor('.container'));
		if (selected) target.removeClass('selected');
		else target.addClass('selected');
		e.halt();	// intercepts/stops A.click action
	};
	MultiSelect.multiSelectHandler = function(e) {
		SNAPPI.last_action_ms = new Date().getTime();
		var active, target = e.target;
		active = (_Y.UA.os == "macintosh") ? (e.metaKey || e.shiftKey) : (e.ctrlKey || e.shiftKey)
		if (!active) {
			if (target.get('parentNode').hasClass('context-menu')) {
				// let ContextMenu listner handle this click.
				return;
			}
			
			try {
				// if contextMenu is open, close it first
				var g = target.ancestor('section.gallery').Gallery;
				if (g.ContextMenu.get('disabled')==false) return;	
			} catch(e) {}
			
			
			// No shift key - remove all selected images,
			var selected = target.ancestor('.container').all('.FigureBox.selected');
			if (selected.size()) {
				e.halt();	// intercepts/stops A.click action
				e.stopImmediatePropagation();
				selected.removeClass('selected');
				return;
			}
		} 
		
		target = e.currentTarget; 	// .FigureBox
		if (e.shiftKey) {
			this.selectContiguousHandler(target);
			e.stopImmediatePropagation(); 
		} else if (active) {	// either Win:ctrlKey or Mac:metaKey
			// Check if the target is an image and select it.
			target.toggleClass('selected');
			// save selction to Session for lightbox
			if (target.ancestor('#lightbox')) SNAPPI.lightbox.save();
			e.stopImmediatePropagation(); 
		}
	};
	
	
	MultiSelect.prototype = {
				
		selectContiguousHandler : function(n) {
			// select target regardless
			n.addClass('selected');
	
			var end, start = n.previous(_boundary);
			if (!start) {
				start = n;
				end = start.next(_boundary);
			} else {
				end = n;
			}
			if (end) {
				var node = start;
				do {
					node = node.next(_isDOMVisible);
					if (node && node.hasClass('FigureBox')) {
						node.addClass('selected');
						if (node.Thumbnail && node.Thumbnail.select) node.Thumbnail.select(true);
					}
				} while (node && node != end);
			}
		},
		selectAll : function(node) {
			node = node || _Y.one('section.gallery .container');
			node.all('.FigureBox').addClass('selected');
		},
		clearAll : function(node) {
			node = node || _Y.one('section.gallery .container');
			node.all('.FigureBox').removeClass('selected');
		},
		listen : function(container, status, handler) {
			handler = handler || MultiSelect.multiSelectHandler
			status = (status == undefined) ? true : status;
			container = container || 'section.gallery.photo .container';
			if (status) {
				// listen
				_Y.all(container).each( 
					function(n) {
						n.listen = n.listen || {};
						if (!n.listen['MultiSelect']) {
							n.listen['MultiSelect'] = n.delegate('click',
								handler, 		// default: this.multiSelectHandler		 
								'.FigureBox',
								this	// context
							);
						}
					}, this
				);
				// SNAPPI.lightbox.listen(true); // listen in base.js
			} else {
				// stop listening
				_Y.all(container).each(function(n) {
						if (n.listen['MultiSelect']) {
							try {
								n.listen['MultiSelect'].detach();
								delete (n.listen['MultiSelect']);
							} catch (e) {}
						}
					}, this
				);
			}
		}		
	};
	/*
	 * make global
	 */
	
	var SingleSelect

	/**
	 * factory class for creating instance of Thumbnail, i.e. Photo Group or Person
	 */
	var ThumbnailFactory = function(){};
	
	/*
	 * static methods
	 */
	// DEFAULT handlers for ThumbnailFactory class.
	ThumbnailFactory.setScoreNode = function(thumbnail, audition ){
		try {
			var score, node, exists;
			switch (thumbnail._cfg.type) {
				case 'PhotoPreview':
					exists = thumbnail.node.one('figcaption li.score');
				break;
				case 'Photo':
				 	exists = thumbnail.node.one('figcaption li.score');
				break;
			}
			if (exists) {
				score = audition.Audition.Photo.Fix.Score || "0.0";
				votes = audition.Audition.Photo.Fix.Votes;
        		var title = score + ' out of '+ votes +' vote';
        		title += votes == '1' ? '.' : 's.';		// add plural as appropriate
        		exists.set('innerHTML', score).setAttribute('title', title);					
			} else {
				throw new Error('score markup missing from Thumbnail');
			}
		} catch (e) {}
	};
	/**
	 * 
	 */
	ThumbnailFactory.setHiddenShotNode = function(thumbnail, audition){
			// show hidden shot icons
			try {
				var exists = thumbnail.node.one('figure .hidden-shot');
				if (thumbnail._cfg.showHiddenShot) {
					shotCount = parseInt(audition.Audition.Shot.count);
					tooltip = shotCount + " Snaps in this Shot.";
					if (exists) {
						// reuse
						if (shotCount > 6) {
							exists.set('className','hidden-shot').setAttribute('title', tooltip);
						} else if (shotCount > 1) {
							exists.set('className','hidden-shot').addClass('c'+shotCount).setAttribute('title', tooltip);
						} else {
							exists.remove();
						}
					} else {
						// create hiddenShotNode in the right place
						switch(thumbnail._cfg.type){
							case 'PhotoPreview':
								if (shotCount > 6) {
									exists = '<li class="wrap-bg-sprite" action="show-hiddenshot"><div class="hidden-shot" title="'+tooltip+'"></div></li>';
									thumbnail.node.one('figure figcaption li.context-menu').insert(exists, 'before');						
								} else if (shotCount > 1) {
									exists = '<li class="wrap-bg-sprite" action="show-hiddenshot"><div class="hidden-shot c'+shotCount+'" title="'+tooltip+'"></div></li>';
									thumbnail.node.one('figure figcaption li.context-menu').insert(exists, 'before');	
								}							
								break;
							case 'Photo':
								if (shotCount > 6) {
									exists = '<div class="hidden-shot" title="'+tooltip+'"></div>';
									thumbnail.node.one('figure figcaption').insert(exists, 'before');						
								} else if (shotCount > 1) {
									exists = '<div class="hidden-shot c'+shotCount+'" title="'+tooltip+'"></div>';
									thumbnail.node.one('figure figcaption').insert(exists, 'before');	
								}							
								break;
						}
						// skip if we don't have an insertion point
					}						
					exists.removeClass('hide');
				} else {
					exists.addClass('hide');
				}
			} catch (e) { }			
			return;
    };
    ThumbnailFactory.actions = {
    	handle_PreviewExtrasClick : function(e){	// context==Thumbnail.node
    		var t = this.Thumbnail,
    			action = e.currentTarget.getAttribute('action');
    		if (!action) return;
    		else action = action.split(':');
    		switch(action[0]) {
	    			case 'set-display-size': // ThumbsizeClick preview size
	    				ThumbnailFactory[t._cfg.type].handle_ThumbsizeClick.call(this, e, action[1]);
	    				break;
	    			case 'show-hiddenshot': //  '.hidden-shot'
	    				ThumbnailFactory[t._cfg.type].handle_HiddenShotClick.call(this, e);
	    				break;	
	    			case 'show-contextmenu': // '.context-menu'
	    				ThumbnailFactory[t._cfg.type].handle_ActionsClick.call(this, e);
	    				break;	
	    			case 'nav':  // 'li.btn.next, li.btn.prev'
	    				var direction = e.currentTarget.hasClass('next') ? 'next' : 'prev';
						var g = this.Gallery || SNAPPI.Gallery.find['nav-'];	// this.Gallery from Factory.PhotoPreview.bindSelected();
						// same for PhotoPreview or PhotoZoom only
						ThumbnailFactory['PhotoPreview'].next.call(this, direction, g, this);
	    				break;	
	    			case 'toggle-keydown':	
	    				if (e.currentTarget.hasClass('selected')) {
	    					this.listen['Keydown_stopListening'](null, 'selected');
	    				} else {
	    					this.listen['Keydown_startListening'](null, 'selected');
	    				}
	    				break;
	    	}
    	}
    };
	ThumbnailFactory.listeners = {
		PreviewExtrasClick: function() {
			var action = 'PreviewExtrasClick';
            if (this.node.listen[action] == undefined) {
            	var delegate_container = this.node.one('figcaption .extras');
				this.node.listen[action] ==  delegate_container.delegate('click',
					ThumbnailFactory.actions.handle_PreviewExtrasClick,
            		'li, .auto-advance', this.node);
            }			
		},
		// for PhotoPreview/PhotoZoom. deprecate.set_AutoScroll_RatingClick_Listener()
		AutoScrollRatingClick: function(g, previewBody){
			var action = 'AutoScrollRatingClick';		
			if (this.node.listen[action] == undefined) {
				this.node.listen[action] = _Y.on('snappi:ratingChanged', function(r){
					// context == .FigureBox == Thumbnail.node
					try {
						var isAutoScroll = this.one('.extras input.auto-advance').get('checked'),
							g = this.Gallery;	
					} catch(e) {
						return;		// not found
					}
					if (isAutoScroll && g) {
						if (r && r.node.getAttribute('uuid') == this.uuid) {
							ThumbnailFactory['PhotoPreview'].next.call(this, 'next', g, this);
						}
					}
				}, this.node);
			}
		},
		RatingClick: function() {	
			var action = 'RatingClick';
            if (this.node.listen[action] == undefined) {	
				var r = this.node.one('.rating');
				this.node.listen[action] = SNAPPI.Rating.startListeners(r);
			}
		},
		PreviewImgLoad: function() {
			var action = 'PreviewImgLoad';		// show loadingmask for PhotoPreview/PhotoZoom
            if (this.node.listen[action] == undefined) {
				this.node.listen[action] = this.node.one('figure > img').on('load',
					function(e) {
						// hide loading indicator
						var node = this;
						if (node.loadingmask) node.loadingmask.hide();
						else if ((node = this.get('parentNode')) && node &&  node.loadingmask){
							node.loadingmask.hide();
						} else if ((node = this.ancestor('.preview-body')) && node && node.loadingmask){
							node.loadingmask.hide();
						}
						_Y.fire('snappi:preview-change', this);	
						// also see PreviewHelper.DialogHiddenShot, which is currently not being used
					}, this.node);			
			}
		},
		Keydown: function(){ // for PhotoPreview, PhotoZoom
        	var action = 'Hover';
            if (this.node.listen[action] == undefined) {
            	var self = this;	// context == Thumbnail
            	var keydownBtn = self.node.one('.extras .keydown').get('parentNode');
            	var stopListening = function(e, className) {
		            className = className || 'focus';
		            keydownBtn.removeClass(className);  
            		if (self.node.listen['Keydown']) { 
            			keydownBtn.removeClass(className);
            			if (keydownBtn.hasClass('selected')) return;	// skip if sticky
            			self.node.listen['Keydown'].detach();
            			delete self.node.listen['Keydown'];
            		}
            	}; 
            	var startListening = function(e, className) {
		            className = className || 'focus';
		            keydownBtn.addClass(className);  
            		if (!self.node.listen['Keydown']) {
            			if (document.stoplistening_Keydown && document.stoplistening_Keydown!== stopListening)
            				document.stoplistening_Keydown();
            			self.node.listen['Keydown'] = _Y.on('keydown', ThumbnailFactory['PhotoPreview'].handleKeydown, document, self);
            			document.stoplistening_Keydown = stopListening;
            		}
            	};
            	var delegateNode = self.node.ancestor('.preview-body') || _Y.one('.preview-body');
            	self.node.listen[action] = delegateNode.on('snappi:hover', startListening, stopListening, self);
            	self.node.listen['Keydown_startListening'] = startListening;
		        self.node.listen['Keydown_stopListening'] = stopListening;
            }
        },	
	};
	
	
	ThumbnailFactory.PhotoPreview = {
		defaultCfg: {
			type: 'PhotoPreview',
    		ID_PREFIX: 'preview-',
    		size: 'bp',
    		showExtras: true,
    		showRatings: true,
    		showSizes: true,
    		draggable: true,
    		queue: false,
    		listeners: ['PreviewExtrasClick', 'RatingClick', 'AutoScrollRatingClick', 'PreviewImgLoad', 'Keydown'],
    	},
    	charCode : {
	        nextPatt: /(^110$)|(^39$)|(^32$)|(^54$)/, // n,right,space,
	        // keypad right
	        prevPatt: /(^112$)|(^37$)|(^8$)|(^52$)/, // p,left,backspace,
	        // keypad left
	        closePatt: /(^27$)/,
	        ratingPatt: /(^96$)|(^97$)|(^98$)|(^99$)|(^100$)|(^101$)/, // keybd 0-5
	    },
		markup: '<article class="FigureBox PhotoPreview">'+
                '<figure>'+
                '    <figcaption><ul class="extras inline rounded-5">'+
                '    	 <li class="label">My Rating</li><li class="rating wrap-bg-sprite"></li>' +
                '        <li class="label">Score</li><li class="score">0.0</li>' +
                '		 <li><nav class="settings">' +
                '<ul class="inline filmstrip-nav">'+
                '<li class="li btn prev orange" action="nav">&#x25C0;</li><li class="li btn orange radius-0 XXXauto-advance" title="Automatically advance to the next Snap after each click"><input type="checkbox" class="auto-advance" action="toggle-autoscroll" value="" title="Automatically advance to the next Snap after each click"></li><li class="li btn next orange"  action="nav">&#x25B6;</li>' +
                '<li></li> <li class="btn white" action="toggle-keydown"><span class="keydown">&nbsp;</span></li>'+ 
                '</ul> ' +
                '        <ul class="sizes inline">' +
                '<li class="label">Sizes</li><li class="btn white" action="set-display-size:tn" size="tn">XS</li><li class="btn white" action="set-display-size:bs" size="bs">S</li><li class="btn white" action="set-display-size:bm" size="bm">M</li><li class="btn white" action="set-display-size:bp" size="bp">L</li>' +
                ' 		 </ul>'+                
                '		 </nav></li>' +
                '        <li class="icon context-menu" action="show-contextmenu"><img alt="" title="actions" src="/static/img/css-gui/icon2.png"></li>'+
				'	</ul></figcaption>' +
				'	<img alt="" src="">' +
				'</figure>'+
				'</article>',
		renderElementsBySize : function (size, audition, cfg){
			cfg = cfg || {};
			/*
			 * set attributes based on thumbnail size
			 */
			audition = audition || SNAPPI.Auditions.find(this.id);
			var node = this.node;
			
			var src, linkTo, title, score, votes, exists, tooltip, shotCount, sizeCfg;
			SNAPPI.Auditions.bind(node, audition);
			linkTo = '/photos/home/' + audition.id;
			// add ?ccid&shotType in photoroll.listenClick()
			title = audition.label;
			score = audition.Audition.Photo.Fix.Score;
			votes = audition.Audition.Photo.Fix.Votes;	
			sizeCfg = {
				size: size,
			};			
			switch (size) {
				case 'bp':
				default:
					sizeCfg.showExtras = true; 
					sizeCfg.showRatings = true;
					sizeCfg.sizes = true;
					sizeCfg.showLabel = true;
					sizeCfg.showHiddenShot = true;
					break;
			}
	
			// set CSS classNames
			node.set('className', 'FigureBox').addClass(this._cfg.type).addClass(sizeCfg.size);
			if (sizeCfg.addClass) node.addClass(sizeCfg.addClass);
			delete(sizeCfg.addClass);		// keep size classes local
			
			// addClass from this._cfg
			this._cfg = _Y.merge(this._cfg, sizeCfg, cfg);
			if (this._cfg.addClass) node.addClass(this._cfg.addClass);

			// set src to the correct size, and listen for onload
			var img = node.one('figure > img');
			src = audition.getImgSrcBySize(audition.urlbase + audition.rootSrc, sizeCfg.size);
			src = SNAPPI.util.useStaticHost(src);
			if (this._cfg.queue && SNAPPI.Imageloader.QUEUE_IMAGES) {
				img.qSrc = src;
				// SNAPPI.util3.ImageLoader.queueOneImg(img); // defer,
				// queue by selector
			} else {
				img.set('src', src);
			}		
			// img.setAttribute('linkTo', linkTo);
			
			// set draggable	
			if (this._cfg.draggable) {
				img.addClass('drag');
			} else {
				img.removeClass('drag');
			}
			
			// show caption, 
			exists = node.one("figcaption .caption");
			if (exists) {
				if (this._cfg.showLabel) {
					exists.set('innerHTML', this.trimLabel(title));
					exists.removeClass('hide');
				}
			} else {
				node.one('figure > img').set('title', title);
				if (exists) exists.addClass('hide');
			}
			
			// set size focus
			node.all('figcaption ul.sizes li.btn').each(function(n,i,l){
				var size = this._cfg.size;
				if (n.getAttribute('size')==size) n.addClass('focus');
				else n.removeClass('focus');
			}, this);
            			
			// show hidden shot icons
			ThumbnailFactory.setHiddenShotNode(this, audition);
			
			// rating
			exists = node.one('ul li.rating');
			if (this._cfg.showRatings) {
				if (exists && node.Rating) {
					if (node.Rating.id != audition.id) {
						// update rating
						node.Rating.id = audition.id;
						node.Rating.node.setAttribute('uuid', audition.id).set(
								'id', audition.id + '_ratingGrp');
						node.Rating.render(audition.rating);
					}
				} else {
					// attach Rating
	            	var gallery = this._cfg.gallery || SNAPPI.Gallery.getFromDom(node);
	            	SNAPPI.Rating.pluginRating(gallery, node.Thumbnail, audition.rating, {id:'photoPreview-ratingGroup'});
	            }
	            if (exists) exists.removeClass('hide');	  
	       } else {
	       		if (exists) exists.addClass('hide');
	       }
	
			// show extras, i.e. rating, score, info, menu
			if (this._cfg.showExtras) {
				// update Score, show hide in showExtras
				ThumbnailFactory.setScoreNode(this, audition);
				node.one('ul').removeClass('hide');
			} else {
				node.one('ul').addClass('hide');
			}		
			
			return this;
		},
		/**
		 * create/reuse PhotoPreview/PhotoZoom thumbnail
		 */
		bindSelected2PreviewThumbnail: function(selected, previewBody, cfg){
			// create/reuse Thumbnail
    		var t, node = previewBody.one('.FigureBox.'+cfg.type);
    		if (!node) {
    			// default init size
    			cfg.size = cfg.size || previewBody.getAttribute('size');  	// set from SessionKey==thumbsize.PhotoPreview_Snap	
	    		if (!cfg.size) delete cfg.size;
	    		// create PhotoPreview/Zoom thumbnail	    		
    			t = new SNAPPI.Thumbnail(selected, cfg);	
    			previewBody.prepend(t.node);
    			node = t.node;
    			
	    		if (!previewBody.loadingmask) {
	    			var loadingmaskTarget = node;
					// plugin loadingmask to Thumbnail.PreviewPhoto
					previewBody.plug(_Y.LoadingMask, {
						strings: {loading:''}, 	// BUG: A.LoadingMask
						target: loadingmaskTarget,
						end: null
					});
					// BUG: A.LoadingMask does not set target properly
					previewBody.loadingmask._conf.data.value['target'] = loadingmaskTarget;
					previewBody.loadingmask.overlayMask._conf.data.value['target'] = previewBody.loadingmask._conf.data.value['target'];
					previewBody.loadingmask.set('zIndex', 10);
		    		previewBody.loadingmask.overlayMask.set('zIndex', 10);    			
	    		}    			
    			previewBody.loadingmask.refreshMask();
    			previewBody.loadingmask.show(); 
    		} else {
    			// warning: node.uuid and node.Thumbnail.id out of sync
    			if (selected.id !== node.uuid) {
    				node.Thumbnail.reuse(selected);
	    			previewBody.loadingmask.refreshMask();
	    			previewBody.loadingmask.show(); 
	    		}
    		} 
    		if (cfg.gallery) {
    			if (!node.orig_Gallery) {
    				if (cfg.gallery._cfg.type == 'ShotGallery'){
    					node.orig_Gallery = SNAPPI.Gallery.find['NavFilmstrip'];
    				} else node.orig_Gallery = cfg.gallery;
    			} node.Gallery = cfg.gallery;
    		}
    		return node;
		},
		bindSelected: function(selected, previewBody, cfg) {
			var previewBody = previewBody || (cfg.gallery && cfg.gallery.node.one('.preview-body')) || _Y.one('.preview-body');
    		if (!previewBody) return;
    		if (!selected) return;	// possible boundary element, i.e. first or last
    		
    		var copy = selected;
			if (_Y.Lang.isString(selected)) selected = SNAPPI.Auditions.find(selected);
        	try {
        		if (!selected) {
        			// PhotoPreview only, binds to NavFilmstrip, or ShotGallery.
        			// NavFilmstrip may not be initialized
	        		var onDuplicate = SNAPPI.Auditions.onDuplicate_REPLACE;
		        	SNAPPI.Auditions.parseCastingCall(PAGE.jsonData.castingCall, null, null, onDuplicate);
		        	selected = SNAPPI.Auditions.get(copy);
	        	} 
        	} catch(e) {
        		console.warn('ERROR: ThumbnailFactory.PhotoPreview.bindSelected() cannot find audition for uuid='+selected);
        	}
	        	
    		// var uuid, auditionSH;
    		cfg = cfg || {};
			cfg.type = 'PhotoPreview';
			// create/reuse Thumbnail
	    	var node = ThumbnailFactory['PhotoPreview'].bindSelected2PreviewThumbnail(selected, previewBody, cfg);
    		ThumbnailFactory.PhotoPreview.bindShotGallery2Preview(selected, previewBody, null);
        },		
        bindShotGallery2Preview: function(selected, previewBody, focusGallery) {
        	// type='DialogHiddenShot' or 'ShotGallery'
        	try {
        		var shotGallery = previewBody.get('parentNode').one('.gallery').Gallery;
	        	if (!shotGallery) {
		        	// note: gallery should be initialized in 
		        	// 		- DialogHelper.bindSelected2DialogHiddenShot()
		        	// 		- ThumbnailFactory.PhotoPreview.handle_HiddenShotClick()
		        	return;	// gallery not yet opened, skip
	        	}
	        	if (_Y.Lang.isString(selected)) selected = SNAPPI.Auditions.find(selected);        		
			    if (selected && selected.Audition.Substitutions) {
			    	if (!shotGallery.view || shotGallery.view == 'minimize') {
			    		SNAPPI.Factory.Gallery.actions.setView(shotGallery, 'one-row');
			    	}   			    	
	    			shotGallery.showShotGallery(selected);
	        	} else {
	        		shotGallery.container.ancestor('.filmstrip-wrap').addClass('hidden');
	        		shotGallery.container.all('.FigureBox').addClass('hide');
	        	}
        	} catch(e) {}
        },
		handle_ThumbsizeClick: function(e, size){
			var target = e.currentTarget;
			var action = e.currentTarget.getAttribute('action').split(':');
    		switch(action[0]) {
    			case 'set-display-size':
    				size = size || action[1];
    				break;
			}
			SNAPPI.setPageLoading(true);
			this.Thumbnail.resize(size);
			// refresh Dialog, if necessary
			try {
				target.ancestor('.preview-body').Dialog.refresh();
			}catch(e){}
			 // save Preview thumbSize
			var sessKey;
			if (target.ancestor('#dialog-photo-roll-hidden-shots')) {
				sessKey = 'PhotoPreview_HiddenShot';
			} else sessKey = 'PhotoPreview_Snap';	// from /snaps/home page
			SNAPPI.io.savePreviewSize(sessKey, size);
		},
		handle_HiddenShotClick: function(e) {
			var selected = SNAPPI.Auditions.find(this.uuid);
			var previewBody = this.ancestor('.preview-body');
			var shotGalleryNode = previewBody.get('parentNode').one('.gallery'),
				shotGallery = shotGalleryNode.Gallery;
        	if (!shotGallery) {
				shotGallery = new SNAPPI.Gallery({
					type: 'ShotGallery',
					node: shotGalleryNode,
					render: false,
				});
        	}   
        	if (!shotGallery.view || shotGallery.view == 'minimize') {
        		SNAPPI.Factory.Gallery.actions.setView(shotGallery, 'one-row');
        	}      	
        	shotGallery.showShotGallery(selected);
		},
		handle_ActionsClick: function(e) {
			if (!SNAPPI.MenuAUI.find['menu-photoPreview-actions']) {
				SNAPPI.MenuAUI.initMenus({'menu-photoPreview-actions':1});	
			}
		},
		next: function(direction, g, figureBox){
			// context = article.FigureBox == figureBox
			var selected, 
				g = g || SNAPPI.Gallery.find['nav-'];
				direction = direction || 'next',
				type = this.Thumbnail._cfg.type;
			if (g){
				if (!g.container.one('.FigureBox.Photo')) {
					// initialize auto-advance for NavFilmstrip
					SNAPPI.setPageLoading(true);
					_Y.once('snappi:gallery-render-complete', function(){
						SNAPPI.setPageLoading(false);
						ThumbnailFactory['PhotoPreview'].next.call(this, direction, g, this);
					}, this);
					var f = SNAPPI.Factory.Gallery.NavFilmstrip;
					f.handle_setDisplayView(g, 'one-row');
					return;
				}
				if (this.ancestor('#dialog-photo-roll-hidden-shots')
					&& !(g._cfg.type == 'DialogHiddenShot' || g._cfg.type == 'ShotGallery') 
				) {
					// skip to next hiddenShot
					var current,
						found, 
						shots = [];
					try {
						current = SNAPPI.Auditions.find(this.uuid).Audition.Substitutions.id;
					} catch(e){
						console.error('DialogHiddenShot has empty Shotid');
						current = g.getFocus();
					}
					for (var i in g.shots) {
						shots.push(i);
					}
					found = shots.indexOf(current);
					if (direction=='prev')	found--;
					else found++
					if (found >= 0 && found < shots.length) {
						selected = g.shots[shots[found]].best;
					} else {
						// no more hidden shots
						return;
					}
				} else {
					var copy = selected;
					if (direction=='prev') {
						selected = g.auditionSH.prev();
					} else {
						 selected = g.auditionSH.next();
					}
					if (copy == selected 
						&& (g._cfg.type == 'DialogHiddenShot' || g._cfg.type == 'ShotGallery')
					) {	// we are at the end, switch g to Photo
						 this.Gallery = this.orig_Gallery;
					}
				}
				if (g.container.hasClass('filmstrip')) {
					g.scrollFocus(selected);	
				} else g.setFocus(selected);
				parent = this.ancestor('.preview-body');
				ThumbnailFactory[type].bindSelected(selected, parent, {gallery: g});
			}
		},
		/*
         * Key press functionality of next & previous buttons
         */
        handleKeydown: function(e){
        	var charCode = ThumbnailFactory['PhotoPreview'].charCode;
        	var charStr = e.charCode + '';
        	try {
        		var g = this.node.Gallery || this._cfg.gallery || SNAPPI.Gallery.find['nav-'];	// this.Gallery from Factory.PhotoPreview.bindSelected();	
        	} catch (e) {}
            if (!g) g = SNAPPI.Gallery.find['uuid-'] || SNAPPI.Gallery.find['nav-'];
            if (charStr.search(charCode.nextPatt) == 0) {
                e.preventDefault();
					// same for PhotoPreview or PhotoZoom only
				ThumbnailFactory['PhotoPreview'].next.call(this.node, 'next', g, this.node);
                return;
            }
            if (charStr.search(charCode.prevPatt) == 0) {
                e.preventDefault();
				// same for PhotoPreview or PhotoZoom only
				ThumbnailFactory['PhotoPreview'].next.call(this.node, 'prev', g, this.node);
                return;
            }
            if (charStr.search(charCode.ratingPatt) == 0) {
            	e.preventDefault();
            	try {
            		var r = this.node.Rating;
            		var v = parseInt(charStr) - 96; // 0 - 5
            		SNAPPI.Rating.setRating(r,v); 
            	} catch(e){}
            }
        },
	};	
	
	
	
	ThumbnailFactory.PhotoZoom = {
		defaultCfg: {
			type: 'PhotoZoom',
    		ID_PREFIX: 'zoom-',
    		size: 'bp',
    		showExtras: true,
    		showRatings: true,
    		showLabel: true,
    		addClass: 'PhotoPreview', 	// inherit CSS props of PhotoPreview
    		showHiddenShot: false,		// ???: show icon, but don't listen for click?
    		showSizes: false,
    		draggable: false,
    		queue: false,
    		listeners: ['PreviewExtrasClick', 'RatingClick',  'AutoScrollRatingClick', 'PreviewImgLoad', 'Keydown'],
    	}, 
    	charCode : {
	        nextPatt: /(^110$)|(^39$)|(^32$)|(^54$)/, // n,right,space,
	        // keypad right
	        prevPatt: /(^112$)|(^37$)|(^8$)|(^52$)/, // p,left,backspace,
	        // keypad left
	        closePatt: /(^27$)/,
	        ratingPatt: /(^96$)|(^97$)|(^98$)|(^99$)|(^100$)|(^101$)/, // keybd 0-5
	    },
		markup: '<article class="FigureBox PhotoZoom">'+
                '<figure>'+
                '    <figcaption><ul class="extras inline rounded-5">'+
                '<li class="label caption"></li>' +
                '    	 <li class="label">My Rating</li><li class="rating wrap-bg-sprite"></li>' +
                '        <li class="label">Score</li><li class="score">0.0</li>' +
                '		 <li><nav class="settings">' +
                '<ul class="inline">'+
                // '<li class="li btn prev orange" action="nav">&#x25C0;</li><li class="li btn next orange"  action="nav">&#x25B6;</li>' +
'<li class="li btn prev orange" action="nav">&#x25C0;</li><li class="li btn orange radius-0 XXXauto-advance" title="Automatically advance to the next Snap after each click"><input type="checkbox" class="auto-advance" action="toggle-autoscroll" value="" title="Automatically advance to the next Snap after each click"></li><li class="li btn next orange"  action="nav">&#x25B6;</li>' +                
                '</ul></li>' +
                '<li></li> <li class="btn white" action="toggle-keydown"><span class="keydown">&nbsp;</span></li>'+
				'	</ul></figcaption>' +
				'	<img alt="" src="">' +
				'</figure>'+
				'</article>',
		renderElementsBySize : function (size, audition, cfg){
			cfg = cfg || {};
			/*
			 * set attributes based on thumbnail size
			 */
			audition = audition || SNAPPI.Auditions.find(this.id);
			var node = this.node;
			
			var src, linkTo, title, score, votes, exists, tooltip, shotCount, sizeCfg;
			SNAPPI.Auditions.bind(node, audition);
			// linkTo = '/photos/home/' + audition.id;
			// add ?ccid&shotType in photoroll.listenClick()
			title = audition.label;
			score = audition.Audition.Photo.Fix.Score;
			votes = audition.Audition.Photo.Fix.Votes;	
	
			// set CSS classNames
			var sizeCfg = _Y.merge(ThumbnailFactory.PhotoZoom.defaultCfg, cfg);
			sizeCfg.size = size || sizeCfg.size;
			node.set('className', 'FigureBox').addClass(this._cfg.type).addClass(sizeCfg.size);
			if (sizeCfg.addClass) node.addClass(sizeCfg.addClass);
			delete(sizeCfg.addClass);		// keep size classes local
			
			// addClass from this._cfg
			this._cfg = _Y.merge(this._cfg, sizeCfg, cfg);
			if (this._cfg.addClass) node.addClass(this._cfg.addClass);

			// set src to the correct size
			var img = node.one('figure > img');
			var detach = img.on('load', function(e){
				detach.detach();
				_Y.fire('snappi:preview-zoom-loaded', img);
			});
			src = audition.getImgSrcBySize(audition.urlbase + audition.rootSrc, sizeCfg.size);
			src = SNAPPI.util.useStaticHost(src);
			if (this._cfg.queue && SNAPPI.Imageloader.QUEUE_IMAGES) {
				img.qSrc = src;
				// SNAPPI.util3.ImageLoader.queueOneImg(img); // defer,
				// queue by selector
			} else {
				img.set('src', src);
			}		
			// img.setAttribute('linkTo', linkTo);
			
			// set draggable	
			if (this._cfg.draggable) {
				img.addClass('drag');
			} else {
				img.removeClass('drag');
			}
			
			// show caption, 
			exists = node.one("figcaption .caption");
			if (exists) {
				if (this._cfg.showLabel) {
					exists.set('innerHTML', this.trimLabel(title));
					exists.removeClass('hide');
				}
			} else {
					node.one('figure > img').set('title', title);
					if (exists) exists.addClass('hide');
			}
            			
			// show hidden shot icons
			ThumbnailFactory.setHiddenShotNode(this, audition);
			
			// rating
			exists = node.one('ul li.rating');
			if (this._cfg.showRatings) {
				if (exists && node.Rating) {
					if (node.Rating.id != audition.id) {
						// update rating
						node.Rating.id = audition.id;
						node.Rating.node.setAttribute('uuid', audition.id).set(
								'id', audition.id + '_ratingGrp');
						node.Rating.render(audition.rating);
					}
				} else {
					// attach Rating
	            	var gallery = this._cfg.gallery || SNAPPI.Gallery.getFromDom(node);
	            	SNAPPI.Rating.pluginRating(gallery, node.Thumbnail, audition.rating, {id:'photoPreview-ratingGroup'});
	            }
	            if (exists) exists.removeClass('hide');	  
	       } else {
	       		if (exists) exists.addClass('hide');
	       }
	
			// show extras, i.e. rating, score, info, menu
			if (this._cfg.showExtras) {
				// update Score, show hide in showExtras
				ThumbnailFactory.setScoreNode(this, audition);
				node.one('ul').removeClass('hide');
			} else {
				node.one('ul').addClass('hide');
			}		
			
			return this;
		},
		bindSelected: function(selected, previewBody, cfg) {
			var previewBody = previewBody || _Y.one('#dialog-alert .preview-body');
    		if (!previewBody) return;
    		
    		cfg = cfg || {};
			cfg.type = 'PhotoZoom';
			var uuid, size, auditionSH;
			if (!selected.id) {
        		selected = SNAPPI.Auditions.get(selected);
        	} 	    		
    		
    		// create/reuse Thumbnail
    		var node = ThumbnailFactory['PhotoPreview'].bindSelected2PreviewThumbnail(selected, previewBody, cfg);
    		previewBody.listen = previewBody.listen || {};
    		if (!previewBody.listen['PreviewChange']) {
    			// ???: should the Dialog.getStdModNode('body') be listening to this event?
    			previewBody.listen['PreviewChange'] = _Y.on('snappi:preview-change', 
		        	function(thumb){	// refresh dialog after preview-change
		        		if (thumb.Thumbnail._cfg.type == 'PhotoZoom' ) {
		        			var dialog = SNAPPI.Dialog.find['dialog-alert'];
		        			_Y.fire('snappi:dialog-body-rendered', dialog);
		        		}
		        	}, '.FigureBox.PhotoZoom figure > img');
    		}
    		if (node.Gallery) {
    			node.Gallery.setFocus(selected);
    		}
        },
	};
	
	/*
	 * Photo Thumbnail
	 */
	ThumbnailFactory.Photo = {
		defaultCfg: {
			type: 'Photo',
			draggable: true,
			size: 'lm',
    		listeners: null,  // 'ActionsClick' initialized by GalleryFactory.listeners
    	},
		markup: '<article class="FigureBox Photo">'+
                '	<figure><img alt="" src="">'+
                '    <figcaption><ul class="extras">'+
                '    	 <li class="rating wrap-bg-sprite"></li>'+
                '        <li class="score">0.0</li>'+
                '        <li class="icon context-menu"><img alt="" title="actions" src="/static/img/css-gui/icon2.png"></li>'+
                '        <li class="icon info"><img alt="more info" src="/static/img/css-gui/icon1.png"></li>'+
				'	</ul></figcaption></figure>'+
				'</article>',
		renderElementsBySize : function (size, audition, cfg){
			cfg = cfg || {};
			/*
			 * set attributes based on thumbnail size
			 */
			audition = audition || SNAPPI.Auditions.get(this.uuid);
			var node = this.node;
			
			var src, linkTo, title, score, votes, exists, tooltip, shotCount, sizeCfg;
			SNAPPI.Auditions.bind(node, audition);
			linkTo = '/photos/home/' + audition.id;
			// add ?ccid&shotType in photoroll.listenClick()
			title = audition.label;
			score = audition.Score;
			votes = audition.Votes;	
			sizeCfg = {
				size: size,
			};			
			switch (size) {
				case 'lm':
				case 'tn':
					sizeCfg.showLabel = false;
					sizeCfg.showExtras = true;
					sizeCfg.showHiddenShot = true;
					sizeCfg.showRatings = true;
					break;
				case 'lbx-tiny': 
					sizeCfg.size = 'sq';			// use sq thumbnail size
					sizeCfg.addClass = 'lbx-tiny';	// CSS to resize
					sizeCfg.showLabel = false;
					sizeCfg.showExtras = false;
					sizeCfg.showHiddenShot = true;
					sizeCfg.showRatings = false;
					break;
				case 'sq':
					sizeCfg.showLabel = false;
					sizeCfg.showExtras = true;
					sizeCfg.showHiddenShot = true;
					sizeCfg.showRatings = false;
					break;				
				case 'll':
					sizeCfg.showLabel = true;
					sizeCfg.showExtras = true;
					sizeCfg.showHiddenShot = true;
					sizeCfg.showRatings = true;
					break;
			}
			// override for shot-, hiddenshot- filmstrip
			if (/shot-/.test(node.get('id'))) sizeCfg.showHiddenShot = false;
			
			// set CSS classNames
			node.set('className', 'FigureBox Photo').addClass(sizeCfg.size)
			node.size = sizeCfg.size;
			if (sizeCfg.addClass) node.addClass(sizeCfg.addClass);
			delete(sizeCfg.addClass);		// keep size classes local
			
			// addClass from this._cfg
			this._cfg = _Y.merge(this._cfg, sizeCfg, cfg);
			if (this._cfg.addClass) node.addClass(this._cfg.addClass);

			// set src to the correct size
			src = audition.getImgSrcBySize(audition.urlbase + audition.rootSrc, sizeCfg.size);
			src = SNAPPI.util.useStaticHost(src);
			if (this._cfg.queue && SNAPPI.Imageloader.QUEUE_IMAGES) {
				this.img.qSrc = src;
				// SNAPPI.util3.ImageLoader.queueOneImg(img); // defer,
				// queue by selector
			} else {
				this.img.set('src', src);
			}		
			this.img.setAttribute('linkTo', linkTo);
			
			// set draggable	
			if (this._cfg.draggable) {
				this.img.addClass('drag');
			} else {
				this.img.removeClass('drag');
			}
			
			// show caption, 
			exists = node.one('figcaption > .label');
			if (this._cfg.showLabel) {
				if (!exists) {
					node.one('ul').insert('<div class="label">' + this.trimLabel(title) + '</div>', 'before');
				} else {
					exists.set('innerHTML', this.trimLabel(title));
					exists.removeClass('hide');
				}
			} else {
				node.one('figure > img').set('title', title);
				if (exists) exists.addClass('hide');
			}
			
			// show hidden shot icons
			ThumbnailFactory.setHiddenShotNode(this, audition);

			// rating
			exists = node.one('ul li.rating');
			if (this._cfg.showRatings) {
				if (exists && node.Rating) {
					if (node.Rating.id != audition.id) {
						// update rating uuid
						node.Rating.id = audition.id;
						node.Rating.node.setAttribute('uuid', audition.id).set(
								'id', audition.id + '_ratingGrp');
					}
					if (size == 'll') node.Rating.node.addClass('wide');
					else node.Rating.node.removeClass('wide');
					node.Rating.render(audition.rating);
				} else {
					// attach Rating
	            	var gallery = this._cfg.gallery || SNAPPI.Gallery.getFromDom(node);
	            	SNAPPI.Rating.pluginRating(gallery, node.Thumbnail, audition.rating);
	            	if (size == 'll') {
	            		node.Rating.node.addClass('wide');
	            		node.Rating.render(audition.rating);
	            	}
	            }
	            if (exists) exists.removeClass('hide');	  
	       } else {
	       		if (exists) exists.addClass('hide');
	       }
	
			// show extras, i.e. rating, score, info, menu
			if (this._cfg.showExtras) {
				// update Score, show hide in showExtras
				ThumbnailFactory.setScoreNode(this, audition);
				node.one('ul').removeClass('hide');
			} else {
				node.one('ul').addClass('hide');
			}				
			return this;
		},
		handle_ActionsClick: function(e) {
			console.log("Photo Thumbnail Actions Click;");
		},
	};
	ThumbnailFactory.PhotoAirUpload = {
		markup: '<article class="FigureBox PhotoAirUpload">'+
                '	<figure>'+
                '		<img src="">' +
                '   	<figcaption>' + 
				 		"<div class='progress-wrap'>" +
				 		"	<div class='progress'>" +
				 		"	<div class='border'><div class='bar'></div></div>" +
				 		"	</div></div>" +       
				 		'<div class="cancel" title="Cancel upload."></div>' +         
                '		<ul class="hide extras">'+
                '    		<li class="hide rating"></li>'+
                '       	<li class="hide score">0.0</li>'+
                '       	<li class="icon context-menu"><img alt="" title="actions" src="/static/img/css-gui/icon2.png"></li>'+	
                '       	<li class="icon info"><img alt="more info" src="/static/img/css-gui/icon1.png"></li>'+
				'		</ul></figcaption></figure>'+
				'</article>',
		defaultCfg: {
			type: 'PhotoAirUpload',
    		ID_PREFIX: 'progress-',
    		size: 'sq',
    		uuid: null,
    		label: null,
    		showExtras: true,
    		showRatings: false,
    		// showSizes: true,
    		draggable: false,
    		queue: false,
    		opacity: 100,
    		listeners: ['PreviewImgLoad'],
    	},				
		renderElementsBySize : function (size, audition, cfg){
			// cannot use audition here
		}
	};	
	ThumbnailFactory.Group = {
		markup: '<article class="FigureBox Group">'+
                '	<figure><a><img alt="" src=""></a>'+
                '		<figcaption>'+
                '		 <div class="label"></div>'+
                '		 <ul class="inline extras">'+
                '		 	<li class="privacy"></li>'+
                '		 	<li class="members"><a></a></li>'+
                '		 	<li class="snaps"><a></a></li>'+
				'		</ul></figcaption>'+
				'</figure></article>',
		renderElementsBySize: function(size, o) {
			/*
			 * set attributes based on thumbnail size
			 */
			size = size;
			var node = this.node;
			
			var src, linkTo, title, privacy, memberCount, snapCount, exists, tooltip, sizeCfg;
			src = o.getImgSrcBySize(o.urlbase + o.rootSrc, size);
			linkTo = '/groups/home/' + o.id;
			title = o.label;
			privacy = 'admin';
			memberCount = ' Members';
			snapCount = ' Snaps';
			size = 'sq';
			
			sizeCfg = {
				size: size,
			};			
			switch (size) {
				case 'sq':
				case 'lm':
					sizeCfg.showLabel = true;
					sizeCfg.showExtras = true;
					break;
				case 'll':
					sizeCfg.showLabel = true;
					sizeCfg.showExtras = true;
					break;
			}
			this._cfg = _Y.merge(this._cfg, sizeCfg);
			
			// set CSS classNames
			node.set('className', 'FigureBox').addClass(this._cfg.type).addClass(sizeCfg.size);
			if (this._cfg.addClass) node.addClass(this._cfg.addClass);
			
			// set src to the correct size
			var img = node.one('figure > img');
			if (this._cfg.queue && SNAPPI.Imageloader.QUEUE_IMAGES) {
				img.qSrc = src;
				// SNAPPI.util3.ImageLoader.queueOneImg(img); // defer,
				// queue by selector
			} else {
				img.set('src', src);
			}		
			
			// set draggable	
			if (this._cfg.draggable) {
				img.addClass('drag');
			} else {
				img.removeClass('drag');
			}
			
			// show caption, 
			exists = node.one('figcaption > .label');
			if (this._cfg.showLabel) {
				if (!exists) {
					node.one('.extras').insert('<div class="label">' + this.trimLabel(title) + '</div>', 'before');
				} else {
					exists.set('innerHTML', this.trimLabel(title));
					exists.removeClass('hide');
				}
			} else {
				node.one('figure > img').set('title', title);
				if (exists) exists.addClass('hide');
			}
			
			// show extras
			if (this._cfg.showExtras) {
				node.one('.extras .privacy').addClass(privacy);
				node.one('.extras .members a').set('innerHTML', memberCount);
				node.one('.extras .snap a').set('innerHTML', snapCount);
				node.one('.extras').removeClass('hide');
			} else {
				node.one('.extras').addClass('hide');
			}				
			return this;
		}
	};
	ThumbnailFactory.GroupCoverPreview = {
		markup: '<article class="FigureBox Group">'+
                '	<figure><a><img alt="" src=""></a>'+
                '		<figcaption>'+
                '		 <div class="label"></div>'+
                '		 <ul class="inline extras">'+
                '		 	<li class="privacy"></li>'+
                '		 	<li class="members"><a></a></li>'+
                '		 	<li class="snaps"><a></a></li>'+
				'		</ul></figcaption>'+
				'</figure></article>',
		renderElementsBySize: function(size, o) {
			/*
			 * set attributes based on thumbnail size
			 */
			size = size;
			var node = this.node;
			
			var src, linkTo, title, privacy, memberCount, snapCount, exists, tooltip, sizeCfg;
			src = o.getImgSrcBySize(o.urlbase + o.rootSrc, size);
			linkTo = '/groups/home/' + o.id;
			title = o.label;
			privacy = 'admin';
			memberCount = ' Members';
			snapCount = ' Snaps';
			size = 'sq';
			
			sizeCfg = {
				size: size,
			};			
			switch (size) {
				case 'sq':
				case 'lm':
					sizeCfg.showLabel = true;
					sizeCfg.showExtras = true;
					break;
				case 'll':
					sizeCfg.showLabel = true;
					sizeCfg.showExtras = true;
					break;
			}
			this._cfg = _Y.merge(this._cfg, sizeCfg);
			
			// set CSS classNames
			node.set('className', 'FigureBox').addClass(this._cfg.type).addClass(sizeCfg.size);
			if (this._cfg.addClass) node.addClass(this._cfg.addClass);
			
			// set src to the correct size
			var img = node.one('figure > img');
			if (this._cfg.queue && SNAPPI.Imageloader.QUEUE_IMAGES) {
				img.qSrc = src;
				// SNAPPI.util3.ImageLoader.queueOneImg(img); // defer,
				// queue by selector
			} else {
				img.set('src', src);
			}		
			
			// set draggable	
			if (this._cfg.draggable) {
				img.addClass('drag');
			} else {
				img.removeClass('drag');
			}
			
			// show caption, 
			exists = node.one('figcaption > .label');
			if (this._cfg.showLabel) {
				if (!exists) {
					node.one('.extras').insert('<div class="label">' + this.trimLabel(title) + '</div>', 'before');
				} else {
					exists.set('innerHTML', this.trimLabel(title));
					exists.removeClass('hide');
				}
			} else {
				node.one('figure > img').set('title', title);
				if (exists) exists.addClass('hide');
			}
			
			// show extras
			if (this._cfg.showExtras) {
				node.one('.extras .privacy').addClass(privacy);
				node.one('.extras .members a').set('innerHTML', memberCount);
				node.one('.extras .snap a').set('innerHTML', snapCount);
				node.one('.extras').removeClass('hide');
			} else {
				node.one('.extras').addClass('hide');
			}				
			return this;
		}
	};	
	ThumbnailFactory.Person = {
		markup: '<article class="FigureBox Person">'+
                '	<figure><a><img alt="" src=""></a>'+
                '		<figcaption>'+
                '		 <div class="label"></div>'+
                '		 <ul class="inline extras">'+
                '		 	<li class="snaps"><a></a></li>'+
                '		 	<li class="circles last"><a></a></li>'+
				'		</ul></figcaption>'+
				'</figure></article>',
		renderElementsBySize: function(size, o) {
			
		}
	};
	
	
	
	
	
	
	
	/*
	 * currently unused
	 */
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
							_Y.fire('snappi:preview-change', container);
						}); 
					}		    		
					var src = selected.getImgSrcBySize(selected.urlbase + selected.rootSrc, size);
					container.loadingmask.show(); 
					img.set('src', src).set('title', selected.label);
				break;
			}
		},
		listen: function(filmstrip){
			if (!filmstrip.node.listen['preview-change']) {
				filmstrip.node.listen['preview-change'] = _Y.on('snappi:preview-change', 
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