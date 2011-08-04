/**
 * 
 * Copyright (c) 2009, Snaphappi.com. All rights reserved.
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
 * Rating
 * 
 * a VIEW of a rating value
 * 
 */
(function() {
	/*
	 * protected methods and variables
	 */
	var Y = SNAPPI.Y;

	var _ratingCSS;

	var _getStarValueFromEvent = function(ev) {
		// 14 = width of 1 smiley -1 (i.e. 15-1=14)
		return Math.max(1, Math.ceil((ev.clientX - ev.target.getX() - 1) / 14));
	};

	/*
	 * DEPRECATE. use Rating._setRatingSprite()
	 * move background rating sprite to indicate given rating
	 */
	var _setRatingSprite = function(mixed, value) {
		if (mixed instanceof SNAPPI.Rating) {
			mixed = mixed.node;
		}
		// 14 = width of 1 smiley -1 (i.e. 15-1=14)
		var position = '-' + (70 - 14 * value) + 'px 0px';
		mixed.setStyle('backgroundPosition', position);
	};

	/*
	 * Constructor
	 */
	var Rating = function(cfg) {
		if (Rating.doClassInit) Rating.classInit();
		this.init(cfg);
	};
	
	/*
	 * static properties
	 */
	Rating.doClassInit = true;
	Rating.listen = {};

	/*
	 * static functions
	 */
	Rating.classInit = function(){
		var Y = SNAPPI.Y;
		// custom listeners, global
		var eventString = 'snappi:ratingChanged';
		if (!Rating.listen[eventString]) {
			Rating.listen[eventString] = Y.on(eventString, function(r){
				r.loadingmask.hide();
			});		
		}		
		Rating.doClassInit = false;
	};
	
	Rating.toggleShowRating = function(value) {
		if (value) {
			Rating.toggleShowSizeByRating(true);
			_ratingCSS.unset('div.ratingGroup', 'display');
		} else {
			Rating.toggleShowSizeByRating(false);
			_ratingCSS.set('div.ratingGroup', {
				display : 'none'
			});
		}
		_ratingCSS.enable();
	};

	Rating.pluginRating = function(container, mixed, v) {
		var thumbnail, uuid;
		if (mixed instanceof SNAPPI.Thumbnail) {
			parent = mixed.node;
			uuid = parent.audition.id;
		} else if (mixed.audition) {
			parent = mixed;
			uuid = mixed.audition.id;
		} else if (mixed.getAttribute('uuid')) {
			parent = mixed;
			uuid = mixed.getAttribute('uuid');
		} else {
			try {
				parent = mixed;
				uuid = mixed.dom().audition.id;
			} catch(e) {
				// legacy, uuid embedded in id
				uuid = mixed.get('id') ? mixed.get('id') : mixed._yuid;
				if (container._cfg && container._cfg.ID_PREFIX) uuid = uuid.replace(container._cfg.ID_PREFIX, '');
			}
		}
		
		var postRatingChangeAndCleanup = function(v, uuid) {
			// plugin loading mask then call XHR POST
			if (!this.loadingmask) {
				this.plug(Y.LoadingMask, {
					strings: {loading:''}, 	// BUG: A.LoadingMask
//					target: this.get('parentNode'),
					end: null
				});
				// BUG: A.LoadingMask does not set target properly
				this.loadingmask._conf.data.value['target'] = this.get('parentNode');
				this.loadingmask.overlayMask._conf.data.value['target'] = this.loadingmask._conf.data.value['target'];
			}
			this.loadingmask.show();
			// check if we are in the HiddenShots dialog
			// if so, updateBestshot
			var options = {};
			if (this.ancestor('ul.substitutes')) {
				options.updateBestshot = 1;
			} else if (this.get('id')=='menuItem-contextRatingGrp') {
				options = {
					thumbnail: this.Rating.thumbnail,
					updateBestshot: 1
				};
			}
			SNAPPI.AssetRatingController.postRating.call(this.Rating, v, uuid, options);
			return;
		};

		var ratingCfg = {
			v : v,
			uuid : uuid,
			setDbValueFn : postRatingChangeAndCleanup,
			listen : false
		};
		Rating.attach(parent, ratingCfg);
		if (parent.one('img')) parent.one('img').addClass('rating' + v);
		if (parent.one('div.thumb-label')) parent.one('div.thumb-label').addClass('hide');
	};

	/*
	 * use attach() to attach new/used Rating component to thumbnail, etc.
	 */
	/**
	 * @params mixed - 'div.RatingGroup'.get('parentNode')
	 */
	Rating.attach = function(mixed, cfg) {
		var parent;
		if (mixed instanceof SNAPPI.Thumbnail) {
			parent = mixed.node;
		} else parent = mixed;
		var _cfg = {
			v : 0,
			id : '',
			className : 'ratingGroup',
			listen : true
		};
		_cfg = Y.merge(_cfg, cfg);

		var ratingGroup;
		if (mixed.Rating) {	
			// REUSE this ratingGroup
			var r = mixed.Rating;
			ratingGroup = r.node;
			if (_cfg.uuid) {
				ratingGroup.setAttribute('uuid', _cfg.uuid);
				SNAPPI.Auditions.bind(parent, _cfg.uuid); 
			}			
			if (_cfg.applyToBatch) {
				r.applyToBatch = _cfg.applyToBatch;
				r.setDbValueFn = null;
			}
			if (_cfg.setDbValueFn) {
				r.applyToBatch = null;
				r.setDbValueFn = _cfg.setDbValueFn;
			}
			var r = ratingGroup.Rating;
			r.id = _cfg.uuid;
			r.render(_cfg.v);
		} else {
			// CREATE new ratingGroup
			var ratingGroup = parent.create(Y.substitute(
					"<div id='{id}' class='{className}'></div>", _cfg));
			try {
				parent.one('ul > li.stars').append(ratingGroup);	
			}	catch (e) {
				parent.append(ratingGroup);
			}	
			_cfg.node = ratingGroup;
			if (_cfg.uuid) {
				ratingGroup.setAttribute('uuid', _cfg.uuid);
				SNAPPI.Auditions.bind(parent, _cfg.uuid); 
			}
			var r = new SNAPPI.Rating(_cfg);
			if (_cfg.applyToBatch)
				r.applyToBatch = _cfg.applyToBatch;
			if (_cfg.setDbValueFn)
				r.setDbValueFn = _cfg.setDbValueFn;
			mixed.Rating = r;
			if (_cfg.listen) Rating.startListeners(parent);
		}
		return mixed.Rating;
	};
	Rating.handleClick = function(e) {
		var r = e.target.Rating;
		// r.onClick(_getStarValueFromEvent(ev));
		// return;
		if (r.getValue() == 0 || r.rerate) {
			var v = _getStarValueFromEvent(e);
			if (r.applyToBatch && Y.Lang.isFunction(r.applyToBatch)) {
				r.applyToBatch(v);
			} else if (r.setDbValueFn && Y.Lang.isFunction(r.setDbValueFn)) {
				// var silent = silent || false;
				var uuid = r.node.getAttribute('uuid') || r.node.get('id');
				/*
				 * set by DataElement, fires onChange event
				 */
				r.setDbValueFn.call(r.node, v, uuid);
			} else {
				r.render(v);
			}
		}
	};

	/**
	 * startListeners()
	 * @param delegateContainer Y.Node or CSS selector
	 * @param selector	CSS selector
	 */
	Rating.startListeners = function(delegateContainer, selector) {
		var selector = selector || 'div.ratingGroup';

		if (Y.Lang.isString(delegateContainer)) {
			delegateContainer = Y.one(delegateContainer);
		}
		delegateContainer.delegate('click', Rating.handleClick, selector);
		
		// Y.one(delegateContainer).delegate("mouseover",
		// Rating.handleMouseOver, selector);
		// Y.one(delegateContainer).delegate("mouseout",
		// Rating.handleMouseOut, selector);
	};
	Rating.stopListeners = function(delegateContainer) {
		/*
		 * Change to stop delegated listener
		 */
		if (Y.Lang.isString(delegateContainer))
			delegateContainer = Y.one(delegateContainer);
		Y.detach("click", Rating.handleClick, delegateContainer);
	};
	
	
	
	
	/*
	 * class prototype
	 */
	Rating.prototype = {
		/*
		 * static attributes for managing shared event listeners
		 */
		// container: null,
		init : function(cfg) {
			var Y = SNAPPI.Y;
			var _cfg = {
				el : null,
				v : 0,
				uuid : null,
				max : 5,
				rerate : true
			// change an existing rating
			};
			_cfg = Y.merge(_cfg, cfg);
			this.id = (_cfg.uuid || (_cfg.uuid === false)) ? _cfg.uuid
					: _cfg.node.get('id');
			this.node = _cfg.node;
			this.stars = _cfg.max;
			this.setValue(_cfg.v);
			this.rerate = _cfg.rerate;
			this.setRatingSprite();
			this.node.Rating = this; // add backreference, also add reference
										// to section.thumbnail
		},

		mouseOver : function(rating) {
			if (this.rerate) {
				this.setRatingSprite(rating);
			}
		},
		/*
		 * 
		 */
		mouseOut : function() {
			if (this.rerate) {
				this.setRatingSprite();
			}
		},
		/**
		 * setRatingSprite() 
		 * move background rating sprite to indicate given rating
		 * @param value int [0-5] or null
		 */
		setRatingSprite : function(value) {
			if (!value) value = this.value;
			// 14 = width of 1 smiley -1 (i.e. 15-1=14)
			var position = '-' + (70 - 14 * value) + 'px 0px';
			this.node.setStyle('backgroundPosition', position);
		},
		// DEPRECATE
		renderStars : function(units) {
			this.setRatingSprite (units);
		},

		/**
		 * REPLACED BY Rating.handleClick() ?????
		 * onClick() 
		 * @param value
		 * @param silent if TRUE, do not update DB, just render new value 
		 */
		onClick : function(value, silent) {
			silent = silent || false;
			if (!silent && this.setDbValueFn) {
				var uuid = this.node.getAttribute('uuid')
						|| this.node.get('id');
				this.setDbValueFn.call(this.node, value, this.id);
				/*
				 * set by DataElement, fires onChange event
				 */
			} else {
				this.render(value);
			}
		},
		render : function(value) {
			this.setValue(value);
			this.setRatingSprite();
		},
		setValue : function(v) {
			if (v > this.stars)
				v = this.stars;
			if (v < 0 || !v)
				v = 0;
			this.value = Math.round(v);
		},
		/*
		 * set the value of the DataElement bound to this rating. usually in a
		 * sibling or parent object I suppose it would be better to just bind
		 * the rating to the DataElement
		 */
		setDbValueFn : function() {
		},

		getValue : function() {
			return this.value;
		}
	}; // end Rating.prototype
	
	SNAPPI.Rating = Rating;
})();
/*
 * UNUSED METHODS
 *  
 *  // TODO: use snappi:hover event here
	Rating.XXXhandleMouseOver = function(e) {
		e.stopPropagation();
		var r, eTarget = e.target;
		var starValue = _getStarValueFromEvent(e);
		r = eTarget.parentNode.Rating;
		if (r.getValue() == 0 || r.rerate)
			r.mouseOver(starValue);
		// Y.on('mousemove', Rating.handleMouseOver, e.target);
	};
	Rating.XXXhandleMouseOut = function(e) {
		e.stopPropagation();
		// Y.detach('mousemove', Rating.handleMouseOut, e.target);
		var r, eTarget = e.target;
		r = eTarget.parentNode.Rating;
		if (r.getValue() == 0 || r.rerate)
			r.mouseOut();
	};
	

	Rating.toggleShowSizeByRating = function(value) {
		if (!_ratingCSS) {
			_ratingCSS = new Y.StyleSheet('ratingCSS');
			_ratingCSS.disable();
		}
		SNAPPI.Thumbnail.showSizeByRating = value
				|| !SNAPPI.Thumbnail.showSizeByRating;
		if (SNAPPI.Thumbnail.showSizeByRating) {
			_ratingCSS.unset('ul.photoSet li img.rating4',
					[ 'margin', 'height' ]);
			_ratingCSS.unset('ul.photoSet li img.rating3',
					[ 'margin', 'height' ]);
			_ratingCSS.unset('ul.photoSet li img.rating2',
					[ 'margin', 'height' ]);
			_ratingCSS.unset('ul.photoSet li img.rating1',
					[ 'margin', 'height' ]);
		} else {
			var fullsize = {
				margin : 0,
				height : 'auto'
			};
			_ratingCSS.set('ul.photoSet li img.rating4', fullsize);
			_ratingCSS.set('ul.photoSet li img.rating3', fullsize);
			_ratingCSS.set('ul.photoSet li img.rating2', fullsize);
			_ratingCSS.set('ul.photoSet li img.rating1', fullsize);
		}
		;
		_ratingCSS.enable();
	};	
 *
 */


( function (){
	/********************************************************
	 * AssetRatingController class
	 * 	singleton class, make static
	 *  manages DB Post and Cleanup for a ratingChanged event.
	 */
	var AssetRatingController = function() {
	};
	AssetRatingController.prototype = {
	};

	AssetRatingController.postRating = function(value, id, options) {
			var node = this;
			if (this instanceof SNAPPI.Rating) {
				node = this.node;
			}
			id = id || node.getAttribute('uuid');
			
			var uri = "/photos/setprop/.json";
			var data = {
				'data[Asset][id]' : id,
				'data[Asset][rating]' : value
			};
			if (options && options.updateBestshot) {
				data['data[updateBestshot]'] = 1;
			}
	
			var closure = {
				node : node,
				rating : value
			};
	
			var callback = {
				complete : function(id, o, args) {
	
					if (o.responseJson && o.responseJson.success == 'true') {
						var msg = o.responseJson.message;
						if (SNAPPI.timeout && SNAPPI.timeout.flashMsg) {
							SNAPPI.timeout.flashMsg.cancel();
						}
						// SNAPPI.flash.flash(msg); // don't flashMsg on success.
						SNAPPI.AssetRatingController.onRatingChanged(closure.node,
								closure.rating);
						 
						try {
							var audition, shotPhotoRoll;
							try {
								audition = closure.node.ancestor('section.thumbnail').audition;
								shotPhotoRoll = closure.node.ancestor('ul.substitutes').PhotoRoll;
							} catch (e) {
								audition = options.thumbnail.audition;
								shotPhotoRoll = options.thumbnail.ancestor('ul.substitutes').PhotoRoll;
							}
							var bestShot = audition.Audition.Substitutions.best;
							var selected = shotPhotoRoll.selected;
							// confirm showHidden bestShot is in main photoroll
							if (bestShot !== selected) {
								var photoroll = SNAPPI.Y.one('ul.photo-roll').PhotoRoll;
								// splice into original location
								var result = photoroll.auditionSH.replace(selected, bestShot);
								if (result) {
									shotPhotoRoll.selected = bestShot;
									photoroll.render();	
									for (var i in photoroll.shots) {
										var shot = photoroll.shots[i]; 
										photoroll.applyShotCSS(shot);
									}
									
								}
							}
						} catch (e) {}
					} else {
						var msg;
						try {
							msg = o.responseJson.message;
						} catch (e) {
							msg = o.statusText;
						}
						if (SNAPPI.timeout && SNAPPI.timeout.flashMsg) {
							SNAPPI.timeout.flashMsg.cancel();
						}
						SNAPPI.flash.flash(msg);
					}
					var check;
				}
			};
	
			SNAPPI.io.post(uri, data, callback);
			return;
		};
		/**
		 * update rating component, called after successful POST
		 * TODO: should be using custom and ATTR events here
		 * @param r Y.Node with r.Rating  
		 * @param v new rating value
		 * @return
		 */
		AssetRatingController.onRatingChanged = function(r, v) {
			var Y = SNAPPI.Y;

			var _updateRatingChange = function(audition, v) {
				// update audition object with new rating
				audition.rating = v;
				audition.Audition.Photo.Fix.Rating = v;
				
				// update all nodes in audition.bindTo
				var ul, photoRolls = {}, thumbs = audition.bindTo || [];
				for ( var j in thumbs) {
					var n2 = thumbs[j];
					try {
						if (n2.Thumbnail) {
							// TODO: use Thumbnail.setRating()
							n2.Thumbnail.setRating(v, "silent");
						} else if (n2.Rating) {
							// TODO: push nodes into audition.bindTo
							n2.Rating.render(v);
						}
						ul = n2.ancestor('ul.photo-roll');
						photoRolls[ul.get('id')] = ul.dom().PhotoRoll;
					} catch (e) {
					}
				}
				// resort substitutes and check findBest()
				if (audition.substitutes) {
					audition.substitutes.findBest();
					for ( var i in photoRolls) {
						if (!photoRolls[i]._cfg.hideSubstituteCSS) {
							// WARNING: this is causing a POST group on every rating change. WHY? 
							photoRolls[i].groupAsShot(audition.substitutes);
						}
					}
				}
			};

			// get audition from ratingGroup
			switch (r.get('id')) {
			case 'lbx-rating': // apply rating to lightbox.getSelected()
				// event handler div#lightbox > ul.toolbar > li#lbx-rating
				var batch = r.ancestor('#lightbox').dom().Lightbox.getSelected();
				batch.each(function(audition) {
					v = v || r.Rating.value;
					_updateRatingChange(audition, v);
					r.Rating.render(v);	// r not listed in audition.bindTo
				}, this);
				Y.fire('snappi:ratingChanged', r.one('#lbx_ratingGrp'));
				break;
			case "photos-home-rating": // rating for IMG.preview
				try {
					v = v || r.Rating.value;
					var auditionSH = Y.one('div#neighbors > ul.filmstrip')
							.dom().PhotoRoll.auditionSH;
					var audition = auditionSH.get(r.Rating.id);
					_updateRatingChange(audition, v);
				} catch (e) {
				}
				break;
			case 'zoom_ratingGrp':
				try {
					var audition = r.ancestor('#snappi-zoomBox').dom().audition;
					v = v || r.Rating.value;
					_updateRatingChange(audition, v);
				} catch (e) {
				}
				Y.fire('snappi:ratingChanged', r);
				break;
			case 'menuItem-contextRatingGrp': // right-click over section.thumbnail
				var audition = SNAPPI.Auditions._auditionSH.get(r.getAttribute('uuid'));
				v = v || r.Rating.value;
				_updateRatingChange(audition, v);
				Y.fire('snappi:ratingChanged', r);
				// hide contextMenu
				// use aui-delayed-task and/or aui-debounce
				Y.later(2000, null, function(){tn.dom().Menu.getNode().addClass('hide');});
				break;
			default: // photoRoll section.thumbnail ratingGroup
				try {
					var tn = r.ancestor('section.thumbnail').dom();
					var audition = tn.dom().audition;
					v = v || r.Rating.value;
					_updateRatingChange(audition, v);
				} catch (e) {
				}
				Y.fire('snappi:ratingChanged', r);
				break;
			}
		};
	SNAPPI.AssetRatingController = AssetRatingController;
	
})();