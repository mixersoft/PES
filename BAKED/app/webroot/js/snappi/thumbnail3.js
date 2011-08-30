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
	if (!SNAPPI.Thumbnail) {

		var Y = SNAPPI.Y;

		/*
		 * protected
		 */
		var _showSubstitutesCSS; // stylesheet for hiding substitutes
		var _htmlTemplate = // '<li><div class="thumb"><img></li>';
					'<article class="FigureBox">'+
                    '	<figure><img alt="" src=""></figure>'+
                    '    <ul>'+
                    '    	 <li class="rating"></li>'+
                    '        <li class="score">0.0</li>'+
                    '        <li class="icon context-menu"><img alt="" title="actions" src="/css/images/icon2.png"></li>'+
                    '        <li class="icon info"><img alt="more info" src="/css/images/icon1.png"></li>'+
 					'	</ul>'+
					'</article>';		
		var _defaultCfg = {
			className : 'thumbnail',
			size : 'lm',
			// addClass : null,
			ID_PREFIX : '',
			type : 'photo',
			showLabel : false,
			draggable : true,
			queue : false, // ImageLoader queue
			start : null,
			end : null,
			hideSubstituteCSS : false,
			zoomBoxOverlay : null,

			// old cfg props
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
			if (audition)
				this.create(audition, cfg.photoroll);
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
				this._cfg = Y.merge(_defaultCfg, cfg);
			},
			create : function(audition, photoroll) {
				var node = Y.Node.create(_htmlTemplate);
				node.addClass(this._cfg.className).addClass(
						this._cfg.addClass).addClass(this._cfg.size);

				// set id
				var id = audition.id;
				if (this._cfg.ID_PREFIX)
					id = this._cfg.ID_PREFIX + id;
				node.set('id', id);

				var src, linkTo, title, score, votes;
				switch (this._cfg.type) {
				case 'photo':
					SNAPPI.Auditions.bind(node, audition);
					src = audition.getImgSrcBySize(audition.urlbase
							+ audition.src, this._cfg.size);
					linkTo = '/photos/home/' + audition.id;
					// add ?ccid&shotType in photoroll.listenClick()
					title = audition.label;
					score = audition.Audition.Photo.Fix.Score;
					votes = audition.Audition.Photo.Fix.Votes;
					break;
				case 'person':
				case 'group':
					break;
				}

				var img = node.one('img');
				if (this._cfg.queue && SNAPPI.imageloader.QUEUE_IMAGES) {
					img.qSrc = src;
					// SNAPPI.util3.ImageLoader.queueOneImg(img); // defer,
					// queue by selector
				} else {
					img.set('src', src);
				}
				img.setAttribute('linkTo', linkTo).set('title', title).set(
						'alt', title);
				if (this._cfg.draggable)
					img.addClass('drag');


				this.node = node;
				this.img = img;
				node.Thumbnail = this;
				
				// show caption, 
				// TODO: update for .FigureBox
				if (this._cfg.showLabel) {
					var n = node.create('<div class="thumb-label">' + this
							.trimLabel(title) + '</div>');
					node.append(n);
				}
				
				// show hidden shot icons
				try {
					if (/hiddenshot-/.test(node.get('id'))) throw("Skip hidden-shot in Hidden Shot view");
					var icon, tooltip, shot_count = parseInt(audition.Audition.Shot.count);
					tooltip = shot_count + " Snaps in this Shot.";
					if (shot_count > 6) {
						icon = '<div class="hidden-shot" title="'+tooltip+'"></div>';
						node.one('figure').append(icon);						
					} else if (shot_count > 1) {
						icon = '<div class="hidden-shot c'+shot_count+'" title="'+tooltip+'"></div>';
						node.one('figure').append(icon);
					}
				} catch (e) { }
				
				// show extras, i.e. rating, score, info, menu
				if (this._cfg.showExtras === false) {
					node.one('ul').remove();
				} else if (this._cfg.size === 'sq') {
					// don't attach Rating for sq~ (small) thumbnails
				} else {
					// attach Rating
	            	photoroll = photoroll || SNAPPI.Gallery.getFromDom(node);
	            	SNAPPI.Rating.pluginRating(photoroll, node.Thumbnail, node.audition.rating);
	            	// attach Score
	            	if (score !== undefined) {
	            		var title = score + ' out of '+ votes +' vote';
	            		title += votes == '1' ? '.' : 's.';		// add plural as appropriate
	            		node.one('li.score').set('innerHTML', score).setAttribute('title', title);
	            	}	            	
	            }
				return node;
			},
			/**
			 * reuse Thumbnail DOM, reset to new audition TODO: XHR paging is
			 * NOT reusing Thumbnails
			 * 
			 * @param audition
			 * @param node
			 * @return
			 */
			reuse : function(audition, node) {
				node = node || this.node;
				node.set('className', 'FigureBox '+this._cfg.className)
						.addClass(this._cfg.addClass || this._cfg.size);

				// set id
				var id = audition.id;
				if (this._cfg.ID_PREFIX)
					id = this._cfg.ID_PREFIX + id;
				node.set('id', id);

				var src, linkTo, title, score, votes;
				switch (this._cfg.type) {
				case 'photo':
					SNAPPI.Auditions.bind(node, audition);
					src = audition.getImgSrcBySize(audition.urlbase
							+ audition.src, this._cfg.size);
					linkTo = '/photos/home/' + audition.id;
					// add ?ccid&shotType in photoroll.listenClick()
					title = audition.label;
					score = audition.Audition.Photo.Fix.Score;
					votes = audition.Audition.Photo.Fix.Votes;					
					break;
				case 'person':
				case 'group':
					break;
				}

				var img = this.img;
				img.set('src', src).setAttribute('linkTo', linkTo).set('title',
						title).set('alt', title);
				if (this._cfg.draggable)
					img.addClass('drag');

				// show caption
				if (this._cfg.showLabel) {
					var n = node.create('<div class="thumb-label">' + this
							.trimLabel(title) + '</div>');
					node.append(n);
				}

				// show hidden shot icons
				try {
					var icon, tooltip, shot_count = parseInt(audition.Audition.Shot.count);
					var icon = node.one(".hidden-shot");
					tooltip = shot_count + " Snaps in this Shot.";
					if (icon) {
						// reuse
						if (shot_count > 6) {
							icon.set('className','hidden-shot').setAttribute('title', tooltip);
						} else if (shot_count > 1) {
							icon.set('className','hidden-shot').addClass('c'+shot_count).setAttribute('title', tooltip);
						} else {
							icon.remove();
						}
					} else {
						if (shot_count > 6) {
							icon = '<div class="hidden-shot" title="'+tooltip+'"></div>';
							node.one('figure').append(icon);						
						} else if (shot_count > 1) {
							icon = '<div class="hidden-shot c'+shot_count+'" title="'+tooltip+'"></div>';
							node.one('figure').append(icon);
						}
					}
				} catch (e) { }
				
				// update Rating
				if (node.Rating) {
					node.Rating.id = audition.id;
					node.Rating.node.setAttribute('uuid', audition.id).set(
							'id', audition.id + '_ratingGrp');
					node.Rating.render(audition.rating);
				}
				// update Score
            	if (score !== undefined) {
            		var title = score + ' out of '+ votes +' vote';
            		title += votes == '1' ? '.' : 's.';		// add plural as appropriate
            		node.one('li.score').set('innerHTML', score).setAttribute('title', title);
            	}				
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
	}
	SNAPPI.Thumbnail = Thumbnail;
})();
