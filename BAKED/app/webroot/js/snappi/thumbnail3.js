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
	// var _htmlTemplate = // '<li><div class="thumb"><img></li>';
				// '<article class="FigureBox">'+
                // '	<figure><img alt="" src=""></figure>'+
                // '    <ul>'+
                // '    	 <li class="rating"></li>'+
                // '        <li class="score">0.0</li>'+
                // '        <li class="icon context-menu"><img alt="" title="actions" src="/css/images/icon2.png"></li>'+
                // '        <li class="icon info"><img alt="more info" src="/css/images/icon1.png"></li>'+
				// '	</ul>'+
				// '</article>';		
	var _defaultCfg = {
		// className : 'thumbnail',
		size : 'lm',
		addClass : null,
		ID_PREFIX : '',
		type : 'Photo',			// [ should match Factory.getTypes() ]
		showLabel : false,
		draggable : true,
		queue : false, // ImageLoader queue
		// start : null,
		// end : null,


		// old cfg props
		hideSubstituteCSS : false,
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
		if (audition) this.create(audition);
		
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
		create : function(audition) {
			var markup = Factory[this._cfg.type].markup;
			var node = Y.Node.create(markup);
			node.addClass(this._cfg.type).addClass(this._cfg.addClass).addClass(this._cfg.size);

			// set id
			var id = audition.id;
			this.id = id;
			this.img = node.one('img');
			this.node = node;
			node.set('id', this._cfg.ID_PREFIX + id);
			node.Thumbnail = this;
			
			// this.renderElementsBySize(this._cfg.size, audition);
			Factory[this._cfg.type].renderElementsBySize.call(this, this._cfg.size, audition);
			return node;
		},
		/**
		 * reuse Thumbnail DOM, reset to new audition 
		 * 
		 * @param audition
		 * @param node
		 * @return
		 */
		reuse : function(audition, node) {
			audition = audition || SNAPPI.Auditions.get(this.id);
			node = node || this.node;
			node.set('className', 'FigureBox ').addClass(this._cfg.type).addClass(this._cfg.addClass || this._cfg.size);

			// set id
			var id = audition.id;
			this.id = id;
			node.set('id', this._cfg.ID_PREFIX + id);

        	// this.renderElementsBySize(this._cfg.size, audition);
        	Factory[this._cfg.type].renderElementsBySize.call(this, this._cfg.size, audition);				
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
		renderElementsBySize : function (size, audition){
			/*
			 * set attributes based on thumbnail size
			 */
			size = size;
			audition = audition || SNAPPI.Auditions.get(this.id);
			var node = this.node;
			
			var src, linkTo, title, score, votes, exists, tooltip, shotCount, sizeCfg;
			SNAPPI.Auditions.bind(node, audition);
			src = audition.getImgSrcBySize(audition.urlbase + audition.src, size);
			linkTo = '/photos/home/' + audition.id;
			// add ?ccid&shotType in photoroll.listenClick()
			title = audition.label;
			score = audition.Audition.Photo.Fix.Score;
			votes = audition.Audition.Photo.Fix.Votes;	
						
			switch (size) {
				case 'lm':
					sizeCfg = {
						showLabel: false,
						showExtras: true,
						showHiddenShot: true,
						showRatings: true
					}
					break;
				case 'sq':
					sizeCfg = {
						showLabel: false,
						showExtras: true,
						showHiddenShot: true,
						showRatings: false
					}
					break;
				case 'll':
					sizeCfg = {
						showLabel: true,
						showExtras: true,
						showHiddenShot: true,
						showRatings: true
					}
					break;
			}
			this._cfg = Y.merge(this._cfg, sizeCfg);
	
			// set CSS
			node.removeClass(this._cfg.size).addClass(size);
			this._cfg.size = size;
			
			// set src to the correct size
			var img = node.one('img');
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
			exists = node.one(".label");
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
				if (/hiddenshot-/.test(node.get('id'))) {
					throw("Skip hidden-shot in Hidden Shot view");
				}
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
			
						
			switch (size) {
				case 'sq':
				case 'lm':
					sizeCfg = {
						showLabel: true,
						showExtras: true
					}
					break;
				case 'll':
					sizeCfg = {
						showLabel: true,
						showExtras: true
					}
					break;
			}
			this._cfg = Y.merge(this._cfg, sizeCfg);
	
			// set CSS
			node.removeClass(this._cfg.size).addClass(size);
			this._cfg.size = size;
			
			// set src to the correct size
			var img = node.one('img');
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
			exists = node.one(".label");
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
