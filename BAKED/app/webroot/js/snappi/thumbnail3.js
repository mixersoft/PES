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
	var _Y = null;
    SNAPPI.namespace('SNAPPI.onYready');
    SNAPPI.onYready.Thumbnail = function(Y){
		if (_Y === null) _Y = Y;
		
		Factory = SNAPPI.Factory.Thumbnail;
		
		SNAPPI.Thumbnail = Thumbnail;
	}
	
	
	

	/*
	 * protected
	 */
	var Factory = null;
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
		if (!audition) return; 
		this.create(audition, cfg);
	};
	
	/*
	 * static methods
	 */
	Thumbnail.hideSubstitutes = true;
	Thumbnail.toggleHideSubstitutes = function(value) {
		if (_showSubstitutesCSS === undefined) {
			_showSubstitutesCSS = new _Y.StyleSheet('hideSubstitutes');
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
				this._cfg = _Y.merge(Factory[cfg.type].defaultCfg, cfg);
			} catch (e) {}
			// override _defaultCfg properties as necessary
			for (var attr in _defaultCfg) {
				if (!this._cfg[attr]) this._cfg[attr] = _defaultCfg[attr];  
			}
		},
		listen: function(status, cfg){
        	// if (this.node.listen == undefined) this.node.listen = {};
            status = (status == undefined) ? true : status;
            var k,v;
            if (status) {
            	cfg = cfg || this._cfg.listeners || [];
            	for (k in cfg){
            		try {
            			Factory.listeners[cfg[k]].call(this); 
            		} catch(e) {}
            	}
            }
            else {
            	cfg = cfg || this.node.listen;
                for (k in cfg) {
                	v = cfg[k];
                	if (!this.node.listen[v].detach) continue;
                    this.node.listen[v].detach();
                    delete (this.node.listen[v]);
                }
            }
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
			// node.addClass(this._cfg.type);
			// set id
			this._cfg = _Y.merge(this._cfg,cfg);
			
			var node = _Y.Node.create(Factory[this._cfg.type].markup);
			this.node = node;
			// id references set by SNAPPI.Auditions.bind()
			SNAPPI.Auditions.bind(this.node, audition);
			this.img = this.node.one('img');
			node.set('id', this._cfg.ID_PREFIX + audition.id);
			node.Thumbnail = this;
			node.dom().Thumbnail = this; 		// add for firebug
			Factory[this._cfg.type].renderElementsBySize.call(this, this._cfg.size, audition, cfg);
			
			// start listeners, 
			this.node.listen = {};
			var listeners = this._cfg.listeners;
			if (listeners) {
				this.listen(true, listeners);	
			}
			return node;
		},
		/**
		 * reuse Thumbnail DOM, reset to new audition 
		 * 
		 * @param audition
		 * @param node
		 * @return
		 */
		reuse : function(audition, cfg) {
			// this = SNAPPI.Thumbnail
			audition = audition || SNAPPI.Auditions.find(this.uuid);
			// set id
			SNAPPI.Auditions.bind(this.node, audition);
			this.node.set('id', this._cfg.ID_PREFIX + audition.id);
        	Factory[this._cfg.type].renderElementsBySize.call(this, this._cfg.size, audition, cfg);				
			return this.node;
		},
		isReady : function(){
			if (this.img.qSrc) {
				// TODO: incomplete. listen for Imageloader event
				return true;
			} else {
				var src = this.img.get('src');
				this.img.set('src', null); // naturalHeight == 0
				var detach = this.img.on('load', function(){
					detach.detach();
					_Y.fire('snappi:img-ready', this);
				});
				this.img.set('src', src);
			}
			return this.img.get('naturalHeight');
		},
		remove: function() {
			SNAPPI.Auditions.unbind(this.node);
			this.node.empty().remove();
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
		 * not currently used, use .selected class instead
		 */
		select: function(value) {
			if (value == undefined) this.node.toggleClass('selected');
			else if (value) this.node.addClass('selected');
			else if (!value) this.node.removeClass('selected');
		},
		/**
		 * setRating()
		 * @param v	int value
		 * @param silent if TRUE, just render value, do not update the DB or bindTo
		 */
		setRating : function(v, audition, silent) {
			var r = this.node.Rating;
			if (!r) return;
			if (silent) {
				if (this._cfg.showSizeByRating) {
					var old_v = r.value;
					this.img.replaceClass('rating' + old_v, 'rating' + v);
				}
				r.render(v);
			} else {
				// deprecated(?)
				r.onClick(v, silent);
			}
		},	
		setScore: function(audition){
			Factory.Thumbnail.setScoreNode(this, audition);
		},
		setHiddenShot: function(audition){
			Factory.Thumbnail.setHiddenShotNode(this, audition);
		},
		setSubGroupHide: function (hide) {
			if (hide === false || hide=='show') {
				this.node.replaceClass('hiddenshot-hide', 'hiddenshot-show');
			} else {
				this.node.replaceClass('hiddenshot-show', 'hiddenshot-hide');
				// either Thumbnail.remove() or move to back so it doesn't affect scrollFocus 
				// this.node.get('parentNode').append(this.node);
			}
		},
		legacy : {
			/*******************************************************************
			 * legacy methods
			 */
			makeSubstitutionGroupsDroppable : function(value) {
				if (console) console.warn("deprecate: makeSubstitutionGroupsDroppable()")
				_Y.all('section.gallery.photo > div').each(function(ul) {
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
			setRatingDbValue : function(value) {
				var t = _Y.one(this).ancestor('.thumb-wrapper');
				t.dom().data.set( {
					rating : value
				});
			},
			setTagDbValue : function(value) {
				var LI;
				if (this.data && this.data.tags !== undefined) {
					LI = this;
				} else {
					LI = _Y.one(this).ancestor('.thumb-wrapper');
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
		},

	};

})();
