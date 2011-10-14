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
	var Factory = SNAPPI.Factory.Thumbnail;

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
	

})();
