/**
 * 
 * Copyright (c) 2009-2011, Snaphappi.com. All rights reserved.
 * 
 * This program is free software: you can redistribute it and/or modify it under
 * the terms of the Affero GNU General Public License as published by the Free
 * Software Foundation, either version 3 of the License, or (at your option) any
 * later version.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the Affero GNU General Public License for more
 * details.
 * 
 * You should have received a copy of the Affero GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * 
 * 
 * @author Michael Lin, info@snaphappi.com
 * 
 * Thumbnail - factory class makes a LI element which wraps an IMG and
 * additional behaviors and includes many static functions
 * 
 */
(function() {
	if (SNAPPI.Thumbnail) return;

	var Y = SNAPPI.Y;

	/*
	 * protected
	 */
	var _showSubstitutesCSS; // stylesheet for hiding substitutes
	var _defaultCfg = {
		// className : 'thumbnail',
		size : 'lm',
		addClass : null,
		ID_PREFIX : '',
		type : 'Photo',			// [ should match Factory.getTypes() ]
		showExtras: true,
		showHiddenShot: true,
		showLabel : false,
		draggable : true,
		queue : false, // ImageLoader queue
		// start : null,
		// end : null,


		// old cfg props
		hideHiddenShotByCSS : true,
		zoomBoxOverlay : null,
		deferLoad : false,
		droppable : false,
		hideRepeats : false,
		hideSubstitutes : true, // same as hideRepeats?????
		showSizeByRating : false
	};

	/*
	 * Constructor
	 */
	var Thumbnail = function(audition, cfg) {
		this.init(cfg);
		if (audition) this.create(audition, cfg);
		
	};
	
	/*
	 * static methods
	 */
	Thumbnail.hideSubstitutes = true;
	Thumbnail.toggleHideSubstitutes = function(value) {
		if (_showSubstitutesCSS === undefined) {
			_showSubstitutesCSS = new Y.StyleSheet('hideSubstitutes');
			_showSubstitutesCSS.disable();
			_showSubstitutesCSS.set(
					'section.gallery.photo > div > .Figurebox.hiddenshot-hide', {
						display : 'block'
					});
		}
		Thumbnail.hideSubstitutes = value;
		if (value) {
			_showSubstitutesCSS.disable();
		} else {
			_showSubstitutesCSS.enable();

		}
		// make shots droppable when visible
//			Thumbnail.makeSubstitutionGroupsDroppable(!value);
	}
	
	/*
	 * prototype
	 */
	Thumbnail.prototype = {
		init : function(cfg) {
			this._cfg = {};
			
			try {
				this._cfg = SNAPPI.Y.merge(Factory[cfg.type].defaultCfg, cfg);
			} catch (e) {}
			
			// TODO: deprecate. use Factory[cfg.type].defaultCfg instead
			// override _defaultCfg properties as necessary
			for (var attr in _defaultCfg) {
				this._cfg[attr] = (cfg[attr] !== undefined) ? cfg[attr] : _defaultCfg[attr];  
			}
			var check;
		},
		trimLabel: function(label, length) {
        	length = length || 15;
        	if (label.length > length) {
        		if (label.length < length-3) {
        			return label.substr(0,length);
        		} else return label.substr(0,length-3)+'...';
        	} else return label;
        }, 
		resize : function(size) {
			return Factory[this._cfg.type].renderElementsBySize.call(this, size);
			// this.renderElementsBySize(size);
		},
		create : function(audition, cfg) {
			var markup = Factory[this._cfg.type].markup;
			var node = Y.Node.create(markup);
			// node.addClass(this._cfg.type);

			// set id
			var id = audition.id;
			this.id = id;
			this.img = node.one('img');
			this.node = node;
			node.set('id', this._cfg.ID_PREFIX + id);
			node.Thumbnail = this;
			Factory[this._cfg.type].renderElementsBySize.call(this, this._cfg.size, audition, cfg);
			return node;
		},
		/**
		 * reuse Thumbnail DOM, reset to new audition 
		 * 
		 * @param audition
		 * @param node
		 * @return
		 */
		reuse : function(audition, node, cfg) {
			audition = audition || SNAPPI.Auditions.get(this.id);
			node = node || this.node;
			// set id
			var id = audition.id;
			this.id = id;
			node.set('id', this._cfg.ID_PREFIX + id);
        	Factory[this._cfg.type].renderElementsBySize.call(this, this._cfg.size, audition, cfg);				
			return node;
		},
		setData : function(dataElement) {
			// replace dataElement for existing Thumbnail
			// not yet implemented
		},

		/*******************************************************************
		 * additional Thumbnail behaviors
		 */
		getFocus : function() {
			this.img.replaceClass('blur', 'focus');
		},
		loseFocus : function() {
			this.img.replaceClass('focus', 'blur');
		},
		/**
		 * select/un-select Thumbnail, adds DOM elements for showing selection
		 * @param value, toggle if null
		 */
		select: function(value) {
			return;
		},
		/**
		 * setRating()
		 * @param v	int value
		 * @param silent if TRUE, just render value, do not update the DB or bindTo
		 */
		setRating : function(v, silent) {
			if (silent) {
				if (this._cfg.showSizeByRating) {
					var oldvalue = this.Rating.value;
					this.img.replaceClass('rating' + oldvalue, 'rating' + v);
				}
				this.Rating.render(v);
			} else
				this.Rating.onClick(v);
		},	
		setSubGroupHide: function (hide) {
			if (hide === false || hide=='show') {
				this.node.replaceClass('hiddenshot-hide', 'hiddenshot-show');
			} else {
				this.node.replaceClass('hiddenshot-show', 'hiddenshot-hide');
			}
		},
		/*******************************************************************
		 * legacy methods
		 */
		makeSubstitutionGroupsDroppable : function(value) {
			if (console) console.warn("deprecate: makeSubstitutionGroupsDroppable()")
			Y.all('section.gallery.photo > div').each(function(ul) {
				if (value) {
					SNAPPI.DragDrop.pluginDrop(ul);
				} else {
					/*
					 * this is a bug. ul is a node, but it isn't the node
					 * with the dd property until after this forced
					 * conversion
					 */
					ul.dom().node.unplug('drop'); // have to
					// TODO: has this bug been fixed in yui???? lookup the _plugin property
				}
			});
		},
		// TODO: addToSubGroup needs to be updated to use latest SNAPPI.SubstitutionGroup
		addToSubGroup : function(subGroup, nodeList) {
			subGroup.dom().subGr.move(nodeList);
			return;
		},
		// TODO: removeFromSubGroup needs to be updated to use latest SNAPPI.SubstitutionGroup			
		removeFromSubGroup : function(dropTarget, nodeList) {
			nodeList.each(function(n) {
				var subGroup = n.ancestor('ul.substitutionGroup');
				subGroup.dom().subGr.remove(nodeList);
			});
			return;
		},
		setRating : function(i, silent) {
			silent = silent || false;
			if (silent) {
				if (Thumbnail.showSizeByRating) {
					var oldvalue = this.Rating.value;
					this.img
							.replaceClass('rating' + oldvalue, 'rating' + i);
				}
				this.node.Rating.render(i);
			} else
				this.node.Rating.onClick(i, silent);
		},
		setRatingDbValue : function(value) {
			var t = Y.one(this).ancestor('.thumb-wrapper');
			t.dom().data.set( {
				rating : value
			});
		},
		setTagDbValue : function(value) {
			var LI;
			if (this.data && this.data.tags !== undefined) {
				LI = this;
			} else {
				LI = Y.one(this).ancestor('.thumb-wrapper');
			}
			if (LI) {
				var aTags, curTags = LI.data.tags;
				if (curTags) {
					aTags = curTags.split(';');
					for ( var i = 0; i < aTags.length; i++) {
						if (aTags[i] == value) {
							return;
						}

						if (aTags[i] == '') {
							aTags.splice(i, 1);
						}
					}
				} else {
					aTags = [];
				}
				aTags.push(value.trim());
				var strTags = aTags.join(';');
				LI.data.set( {
					tags : strTags + ';'
				});
			}
		},
		_styleSubstituteGroup : function() {
			if (this.data.substitutes) {
				var subGr = this.parentNode.subGr;
				if (subGr) {
					subGr.findBest();
					SNAPPI.SubstitutionGroup.styleElements(subGr);
				} else {
					var subGrData = this.data.substitutes;
					subGrData.findBest();
					SNAPPI.SubstitutionGroup.styleElements(subGrData);
				}

			}
		},
		syncChanges : function(bindTo, change) {
			if (change.rating !== undefined) {
				this.setRating(bindTo.rating, 'silent');
				var s = SNAPPI.Stack.getStackFromChild(this.dom());
				if (s._dataElementSH.defaultSortCfg[0].property == 'rating') {
					// if sort by Rating, do we resort??
				}
				this._styleSubstituteGroup(); // mark best after rating
			}
			if (change.substitutes !== undefined) {
				this.data.substitutes = change.substitutes;
			}
		}

	};
	SNAPPI.Thumbnail = Thumbnail;
	
	/**
	 * factory class for creating instance of Thumbnail, i.e. Photo Group or Person
	 */
	var Factory = function(){};
	SNAPPI.namespace('SNAPPI.Factory');
	SNAPPI.Factory.Thumbnail = Factory;
	/*
	 * static methods
	 */
	// DEFAULT handlers for Factory class.
	Factory.actions = {
		listen_action: function(action, node) {
			// this instanceof Thumbnail
			if (node.listen == undefined) node.listen = {};
			try {
	            if (node.listen[action] == undefined) {
	                var fn = Factory[this._cfg.type]['listen_'+action];
	                node.listen[action] = fn.call(this);
				}
        	} catch (e) {
        		if (console) console.error('ThumbnailFactory.listen_action() for action='+action);
        	}				
        },		
		do_action: function(e, action){
			// this instanceof Thumbnail.node
        	try {
        		var fn = Factory[this.Thumbnail._cfg.type]['handle_'+action];
        		fn.call(this, e);
        	} catch (e) {
        		if (console) console.error('ThumbnailFactory.do_action() for action='+action);
        	}
    	},
    }
	
	Factory.PhotoPreview = {
		defaultCfg: {
			type: 'PhotoPreview',
    		ID_PREFIX: 'preview-',
    		size: 'bp',
    		showExtras: true,
    		showRatings: true,
    		showSizes: true,
    		draggable: true,
    		queue: false,
    	},
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
			audition = audition || SNAPPI.Auditions.get(this.id);
			var node = this.node;
			node.dom().Thumbnail = this; 		// add for firebug
			
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
				if (n.getAttribute('size')==size) n.addClass('selected');
				else n.removeClass('selected');
			}, this);
            			
			// show hidden shot icons
			try {
				exists = node.one("figure .hidden-shot");
				if (this._cfg.showHiddenShot) {
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
						if (shotCount > 6) {
							exists = '<li><div class="hidden-shot" title="'+tooltip+'"></div></li>';
							node.one('figure figcaption li.context-menu').insert(exists, 'before');						
						} else if (shotCount > 1) {
							exists = '<li><div class="hidden-shot c'+shotCount+'" title="'+tooltip+'"></div></li>';
							node.one('figure figcaption li.context-menu').insert(exists, 'before');	
						}
					}						
					if (exists) exists.removeClass('hide');
				} else {
					if (exists) exists.addClass('hide');
				}
			} catch (e) { }
			
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
	            	SNAPPI.Rating.pluginRating(gallery, node.Thumbnail, node.audition.rating);
	            }
	            if (exists) exists.removeClass('hide');	  
	       } else {
	       		if (exists) exists.addClass('hide');
	       }
	
			// show extras, i.e. rating, score, info, menu
			if (this._cfg.showExtras) {
				// update Score, show hide in showExtras
				exists = node.one('li.score');
				score = score || '0';
	    		if (!exists) {
	    			throw new Error('score markup missing from Thumbnail');
	    		} else {
	        		title = score + ' out of '+ votes +' vote';
	        		title += votes == '1' ? '.' : 's.';		// add plural as appropriate
	        		exists.set('innerHTML', score).setAttribute('title', title);
	    		}
				node.one('ul').removeClass('hide');
			} else {
				node.one('ul').addClass('hide');
			}		
			
            // start Thumbnail listeners for preview Thumbnail
            var listeners = ['ActionsClick', 'ThumbsizeClick', 'HiddenShotClick', 'RatingClick'];
            for (var i in listeners) {
            	Factory.actions.listen_action.call(this, listeners[i], this.node);
            }
			return this;
		},
		bindSelected: function(selected, parent) {
    		var Y = SNAPPI.Y;
    		var parent = parent || Y.one('.photo .preview-body');
    		if (!parent) return;
    		
    		var cfg, uuid, size, auditionSH;
    		cfg = {
    			type: 'PhotoPreview',
    		}
    		
    		try {
    			cfg.size = PAGE.jsonData.profile.thumbSize[cfg.ID_PREFIX];
    		} catch (e) {
    			if ( parent.getAttribute('size')) cfg.size = parent.getAttribute('size');	
    		}
    		
    		// create/reuse Thumbnail
    		var t, node = parent.one('.FigureBox.PhotoPreview');
    		if (!node) {
    			t = new SNAPPI.Thumbnail(selected, cfg);	
    			parent.prepend(t.node);
    			node = t.node;
    		} else {
    			node.Thumbnail.reuse(selected);
    		}
        },		
		listen_ThumbsizeClick: function(){
			return this.node.delegate('click',
					Factory[this._cfg.type].handle_ThumbsizeClick
            	, 'figcaption ul.sizes li.btn', this.node)
		},
		handle_ThumbsizeClick: function(e){
			var target = e.currentTarget;
			var size = target.getAttribute('size');
			this.Thumbnail.resize(size);
			// set focus
			target.all('figcaption ul.sizes li.btn').each(function(n,i,l){
				var size = this.Thumbnail._cfg.size;
				if (n.getAttribute('size')==size) n.addClass('selected');
				else n.removeClass('selected');
			}, target);
			
			// save preview size to Session
			// PAGE.jsonData.profile.thumbSize[cfg.ID_PREFIX];
		},
		listen_HiddenShotClick: function() {
			return this.node.one('figcaption .hidden-shot').on('click',
					Factory[this._cfg.type].handle_HiddenShotClick
            	, this.node)
		},
		handle_HiddenShotClick: function(e) {
			var parent = SNAPPI.Y.one('#shot-gallery');
			var selected = parent.get('parentNode').one('.FigureBox').audition;
        	var shotGallery = SNAPPI.Gallery.find['shot-'];
        	if (!shotGallery) {
				shotGallery = new SNAPPI.Gallery({
					type: 'ShotGallery'
				});
        	}         	
        	
        	if (parent.hasClass('minimize')) {
        		SNAPPI.Factory.Gallery.actions.setView(shotGallery, 'filmstrip');
        	} else {
        		SNAPPI.Factory.Gallery.actions.setView(shotGallery, 'minimize');
        	}
        	
        	shotGallery.showShotGallery(selected);
		},
		listen_ActionsClick: function() {
			return this.node.one('figcaption .context-menu').on('click',
					Factory[this._cfg.type].handle_ActionsClick
            	, this.node)			
		},
		handle_ActionsClick: function(e) {
			console.log("Preview Thumbnail Actions Click;")
		},
		listen_RatingClick: function() {
			var r = this.node.one('.rating');
			return SNAPPI.Rating.startListeners(r);
		}
	};	
	
	
	/*
	 * Photo Thumbnail
	 */
	Factory.Photo = {
		markup: '<article class="FigureBox Photo">'+
                '	<figure><img alt="" src="">'+
                '    <figcaption><ul class="extras">'+
                '    	 <li class="rating"></li>'+
                '        <li class="score">0.0</li>'+
                '        <li class="icon context-menu"><img alt="" title="actions" src="/css/images/icon2.png"></li>'+
                '        <li class="icon info"><img alt="more info" src="/css/images/icon1.png"></li>'+
				'	</figcaption></ul></figure>'+
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
			// override for hiddenShot filmstrip
			
			// set CSS classNames
			node.set('className', 'FigureBox Photo').addClass(sizeCfg.size);
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
			try {
				exists = node.one(".hidden-shot");
				if (this._cfg.showHiddenShot) {
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
						if (shotCount > 6) {
							exists = '<div class="hidden-shot" title="'+tooltip+'"></div>';
							node.one('figure figcaption').insert(exists, 'before');						
						} else if (shotCount > 1) {
							exists = '<div class="hidden-shot c'+shotCount+'" title="'+tooltip+'"></div>';
							node.one('figure figcaption').insert(exists, 'before');	
						}
					}						
					if (exists) exists.removeClass('hide');
				} else {
					if (exists) exists.addClass('hide');
				}
			} catch (e) { }
			
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
	            	SNAPPI.Rating.pluginRating(gallery, node.Thumbnail, node.audition.rating);
	            }
	            if (exists) exists.removeClass('hide');	  
	       } else {
	       		if (exists) exists.addClass('hide');
	       }
	
			// show extras, i.e. rating, score, info, menu
			if (this._cfg.showExtras) {
				// update Score, show hide in showExtras
				exists = node.one('li.score');
				score = score || '0';
	    		if (!exists) {
	    			throw new Error('score markup missing from Thumbnail');
	    		} else {
	        		title = score + ' out of '+ votes +' vote';
	        		title += votes == '1' ? '.' : 's.';		// add plural as appropriate
	        		exists.set('innerHTML', score).setAttribute('title', title);
	    		}
				node.one('ul').removeClass('hide');
			} else {
				node.one('ul').addClass('hide');
			}				
			return this;
		}
	};
	Factory.Group = {
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
})();
