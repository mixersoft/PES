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
	var MultiSelect2 = function(cfg) {
		this.selectHandler = function(e) {
			var target = e.target;
			if (!e.ctrlKey && !e.shiftKey) {
				if (target.get('parentNode').hasClass('context-menu')) {
					// let ContextMenu listner handle this click.
					return;
				}
				// No shift key - remove all selected images,
				var selected = target.ancestor('.container').all('.FigureBox.selected');
				if (selected.size()) {
					e.stopImmediatePropagation(); // halt click if we are clearing selected
					selected.removeClass('selected');
					return;
				}
			} 
			
			target = e.currentTarget; 	// .FigureBox
			if (e.shiftKey) {
				this.selectContiguousHandler(target);
				e.stopImmediatePropagation(); 
			} else if (e.ctrlKey) {
				// Check if the target is an image and select it.
				target.toggleClass('selected');
				if (target.Thumbnail && target.Thumbnail.select) target.Thumbnail.select();
				// save selction to Session for lightbox
				if (target.ancestor('#lightbox')) SNAPPI.lightbox.save();
				e.stopImmediatePropagation(); 
			}
		};
	};
	
	MultiSelect2.prototype = {
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
		selectAll : function(nodeUL) {
			nodeUL.all('> .FigureBox').each(function(n, i, l) {
				// select
					n.addClass('selected');
					if (n.Thumbnail && n.Thumbnail.select) n.Thumbnail.select(true);
				});
		},
		clearAll : function(nodeUL) {
			nodeUL.all('> .FigureBox').each(function(n, i, l) {
				// select
					n.removeClass('selected');
					if (n.Thumbnail  && n.Thumbnail.select) n.Thumbnail.select(false);
				});
		},
		listen : function(container, status) {
			var Y = SNAPPI.Y;
			status = (status == undefined) ? true : status;
			container = container || 'section.gallery.photo > div';
			if (status) {
				// listen
				Y.all(container).each( function(n) {
						if (!n.listener_multiSelect) {
							n.listener_multiSelect = n.delegate('click',
								this.selectHandler, '.FigureBox',
								this
							);
						}
					}, this
				);
				// SNAPPI.lightbox.listen(true); // listen in base.js
			} else {
				// stop listening
				Y.all(container).each(function(n) {
						if (n.listener_multiSelect) {
							try {
								n.listener_multiSelect.detach();
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
	SNAPPI.MultiSelect2 = MultiSelect2;
	SNAPPI.multiSelect = new MultiSelect2();

	/**
	 * factory class for creating instance of Thumbnail, i.e. Photo Group or Person
	 */
	var ThumbnailFactory = function(){};
	SNAPPI.namespace('SNAPPI.Factory');
	SNAPPI.Factory.Thumbnail = ThumbnailFactory;
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
									exists = '<li><div class="hidden-shot" title="'+tooltip+'"></div></li>';
									thumbnail.node.one('figure figcaption li.context-menu').insert(exists, 'before');						
								} else if (shotCount > 1) {
									exists = '<li><div class="hidden-shot c'+shotCount+'" title="'+tooltip+'"></div></li>';
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
	ThumbnailFactory.listeners = {
		ThumbsizeClick: function(){
			return this.node.delegate('click',
					ThumbnailFactory[this._cfg.type].handle_ThumbsizeClick
            	, 'figcaption ul.sizes li.btn', this.node)
		},

		HiddenShotClick: function() {
			return this.node.delegate('click',
					ThumbnailFactory[this._cfg.type].handle_HiddenShotClick
            	, 'figcaption .hidden-shot', this.node)
		},
		ActionsClick: function() {
			return this.node.one('figcaption .context-menu').on('click',
					ThumbnailFactory[this._cfg.type].handle_ActionsClick
            	, this.node)			
		},
		RatingClick: function() {
			var r = this.node.one('.rating');
			return SNAPPI.Rating.startListeners(r);
		},
		PreviewImgLoad: function() {
			// plugin/show loadingmask in SNAPPI.Factory.Thumbnail.PhotoPreview.bindSelected()
			return this.node.one('figure > img').on('load',
				function(e) {
					// hide loading indicator
					if (this.loadingmask) this.loadingmask.hide();
					else {
						try {
							this.ancestor('.preview-body').loadingmask.hide();							
						} catch (e) {}
					}
				}, this.node)			
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
    		listeners: ['ActionsClick', 'ThumbsizeClick', 'HiddenShotClick', 'RatingClick', 'PreviewImgLoad'],
    	},
    	// TODO: update Sizes to use action="set-display-size:[size]" format
		markup: '<article class="FigureBox PhotoPreview">'+
                '<figure>'+
                '    <figcaption><ul class="extras">'+
                '    	 <li>My Rating</li><li class="rating"></li>'+
                '        <li>Score</li><li class="score">0.0</li>'+
                '		 <li><nav class="settings">' +
                '        <ul class="sizes inline">' +
                '<li class="label">Sizes</li><li class="btn" size="tn"><a>XS</a></li><li class="btn" size="bs"><a>S</a></li><li class="btn" size="bm"><a>M</a></li><li class="btn" size="bp"><a>L</a></li>' +
                ' 		 </ul>'+                
                '		 </nav></li>' +
                '        <li class="icon context-menu"><img alt="" title="actions" src="/css/images/icon2.png"></li>'+
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
			this._cfg = Y.merge(this._cfg, sizeCfg, cfg);
			if (this._cfg.addClass) node.addClass(this._cfg.addClass);

			// set src to the correct size
			var img = node.one('figure > img');
			src = audition.getImgSrcBySize(audition.urlbase + audition.src, sizeCfg.size);
			if (this._cfg.queue && SNAPPI.imageloader.QUEUE_IMAGES) {
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
			exists = node.one("figcaption > .label");
			if (this._cfg.showLabel) {
				if (!exists) {
					node.one('ul.extras').insert('<div class="label">' + this.trimLabel(title) + '</div>', 'before');
				} else {
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
	            	SNAPPI.Rating.pluginRating(gallery, node.Thumbnail, audition.rating);
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
		bindSelected: function(selected, previewBody, size) {
    		var Y = SNAPPI.Y;
			if (!selected.id) {
        		selected = SNAPPI.Auditions.get(selected);
        	} 	    		
    		var previewBody = previewBody || Y.one('.photo .preview-body');
    		if (!previewBody) return;
    		
    		var cfg, uuid, size, auditionSH;
    		cfg = {
    			type: 'PhotoPreview',
    		}
    		// create/reuse Thumbnail
    		var t, node = previewBody.one('.FigureBox.PhotoPreview');
    		if (!node) {
	    		try {
	    			// TODO: get initial size, save size as property of Thumbnail object
	    			cfg.size = size || PAGE.jsonData.profile.thumbSize[cfg.type];
	    		} catch (e) {
	    			// default init size
	    			if ( previewBody.getAttribute('size')) cfg.size = previewBody.getAttribute('size');	
	    		}
	    		// create PhotoPreview thumbnail	    		
    			t = new SNAPPI.Thumbnail(selected, cfg);	
    			previewBody.prepend(t.node);
    			node = t.node;
    			
	    		if (!previewBody.loadingmask) {
	    			var loadingmaskTarget = node;
					// plugin loadingmask to Thumbnail.PreviewPhoto
					previewBody.plug(Y.LoadingMask, {
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
    			
    		} else {
    			node.Thumbnail.reuse(selected);
    		}
    		previewBody.loadingmask.refreshMask();
    		previewBody.loadingmask.show(); 
    		ThumbnailFactory.PhotoPreview.bindShotGallery2Preview(selected, previewBody);
        },		
        bindShotGallery2Preview: function(selected, previewBody, gallery) {
        	try {
	        	gallery = gallery || previewBody.one('.gallery.photo.filmstrip').Gallery;
	        	if (!gallery) {
		        	// note: gallery should be initialized in 
		        	// 		- DialogHelper.bindSelected2DialogHiddenShot()
		        	// 		- ThumbnailFactory.PhotoPreview.handle_HiddenShotClick()
		        	return;	// gallery not yet opened, skip
	        	}
	        	        		
				if (!selected.id) {
	        		selected = SNAPPI.Auditions.find(selected) || SNAPPI.Auditions.find(previewBody.one('.FigureBox').uuid);
	        	} 	        	
			    if (selected.Audition.Shot.id) {
			    	if (!gallery.view || gallery.view == 'minimize') {
			    		SNAPPI.Factory.Gallery.actions.setView(gallery, 'one-row');
			    	}   			    	
	    			gallery.showShotGallery(selected);
	        	} else {
	        		gallery.container.ancestor('.filmstrip-wrap').addClass('hidden');
	        		gallery.container.all('.FigureBox').addClass('hide');
	        	}
        	} catch(e) {}
        },
		handle_ThumbsizeClick: function(e){
			var target = e.currentTarget;
			var size = target.getAttribute('size');
			this.Thumbnail.resize(size);
			// set focus in renderElementsBySize()
			
			// refresh Dialog, if necessary
			try {
				target.ancestor('.preview-body').Dialog.refresh();
			}catch(e){}
			// save preview size to Session
			// PAGE.jsonData.profile.thumbSize[cfg.ID_PREFIX];
		},
		handle_HiddenShotClick: function(e) {
			var parent = SNAPPI.Y.one('#shot-gallery');
			var selected = SNAPPI.Auditions.find(parent.get('parentNode').one('.FigureBox').uuid);
        	var shotGallery = SNAPPI.Gallery.find['shot-'];
        	if (!shotGallery) {
				shotGallery = new SNAPPI.Gallery({
					type: 'ShotGallery',
					node: parent.one('.gallery'),
					render: false,
				});
        	}   
        	if (!shotGallery.view || shotGallery.view == 'minimize') {
        		SNAPPI.Factory.Gallery.actions.setView(shotGallery, 'one-row');
        	}      	
        	shotGallery.showShotGallery(selected);
		},
		handle_ActionsClick: function(e) {
			// console.log("Preview Thumbnail Actions Click;");
			// show navFilmstrip for now
			var navFilmstrip = SNAPPI.Gallery.find['nav-'];  
			navFilmstrip.render();
			SNAPPI.Factory.Gallery.actions.setView(navFilmstrip, 'filmstrip');
		},
		set_AutoScroll: function(value, thumbnail, gallery) {
			// node == previewThumbnail node, .FigureBox.PreviewPhoto
			value = value || false;
			var node = thumbnail.node;
			var listener = 'AutoScroll';
			if (value && !node.listen[listener]) {
				var previewBody = node.ancestor('.preview-body');
				var g = gallery || SNAPPI.Gallery.find['nav-'];
				node.listen[listener] = SNAPPI.Y.on('snappi:ratingChanged', 
					function(r){
						if (g && previewBody.one('.FigureBox.PhotoPreview').contains(r.node)) {
							var selected = g.auditionSH.next();
							g.scrollFocus(selected);
							// TODO: debug g.next(), g.prev(), etc.
							ThumbnailFactory.PhotoPreview.bindSelected(selected, previewBody);
						}
					});
			}
			if (!value) node.listen[listener].detach();		
		},
	};	
	
	var Y = SNAPPI.Y;
	/*
	 * Photo Thumbnail
	 */
	ThumbnailFactory.Photo = {
		markup: '<article class="FigureBox Photo">'+
                '	<figure><img alt="" src="">'+
                '    <figcaption><ul class="extras">'+
                '    	 <li class="rating"></li>'+
                '        <li class="score">0.0</li>'+
                '        <li class="icon context-menu"><img alt="" title="actions" src="/css/images/icon2.png"></li>'+
                '        <li class="icon info"><img alt="more info" src="/css/images/icon1.png"></li>'+
				'	</ul></figcaption></figure>'+
				'</article>',
		renderElementsBySize : function (size, audition, cfg){
			cfg = cfg || {};
			/*
			 * set attributes based on thumbnail size
			 */
			audition = audition || SNAPPI.Auditions.get(this.id);
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
			this._cfg = Y.merge(this._cfg, sizeCfg, cfg);
			if (this._cfg.addClass) node.addClass(this._cfg.addClass);

			// set src to the correct size
			var img = node.one('img');
			src = audition.getImgSrcBySize(audition.urlbase + audition.src, sizeCfg.size);
			if (this._cfg.queue && SNAPPI.imageloader.QUEUE_IMAGES) {
				img.qSrc = src;
				// SNAPPI.util3.ImageLoader.queueOneImg(img); // defer,
				// queue by selector
			} else {
				img.set('src', src);
			}		
			img.setAttribute('linkTo', linkTo);
			
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
						// update rating
						node.Rating.id = audition.id;
						node.Rating.node.setAttribute('uuid', audition.id).set(
								'id', audition.id + '_ratingGrp');
						node.Rating.render(audition.rating);
					}
				} else {
					// attach Rating
	            	var gallery = this._cfg.gallery || SNAPPI.Gallery.getFromDom(node);
	            	SNAPPI.Rating.pluginRating(gallery, node.Thumbnail, audition.rating);
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
		}
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
                '       	<li class="icon context-menu"><img alt="" title="actions" src="/css/images/icon2.png"></li>'+	
                '       	<li class="icon info"><img alt="more info" src="/css/images/icon1.png"></li>'+
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
			src = o.getImgSrcBySize(o.urlbase + o.src, size);
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
			this._cfg = Y.merge(this._cfg, sizeCfg);
			
			// set CSS classNames
			node.set('className', 'FigureBox').addClass(this._cfg.type).addClass(sizeCfg.size);
			if (this._cfg.addClass) node.addClass(this._cfg.addClass);
			
			// set src to the correct size
			var img = node.one('figure > img');
			if (this._cfg.queue && SNAPPI.imageloader.QUEUE_IMAGES) {
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