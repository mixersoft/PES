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
	var _Y = null;
    SNAPPI.namespace('SNAPPI.onYready');
    SNAPPI.onYready.Rating = function(Y){
		if (_Y === null) _Y = Y;
		if (SNAPPI.Rating) return;
		// custom listeners, global
		var eventString = 'snappi:ratingChanged';
		if (!Rating.listen[eventString]) {
			Rating.listen[eventString] = _Y.on(eventString, function(r){
				r.node.loadingmask.hide();
			});		
		}		
		SNAPPI.Rating = Rating;
	}
	
	
	var _ratingCSS;
	var _getStarValueFromEvent = function(ev) {
		// 14 = width of 1 smiley -1 (i.e. 15-1=14)
		var w = ev.currentTarget.hasClass('wide') ? 23 : 14;
		return Math.max(1, Math.ceil((ev.clientX - ev.target.getX() - 1) / w));
	};

	/*
	 * DEPRECATE. use Rating._setRatingSprite()
	 * move background rating sprite to indicate given rating
	 */
	var XXX_setRatingSprite = function(mixed, value) {
		if (mixed instanceof SNAPPI.Rating) {
			mixed = mixed.node;
		}
		// 14 = width of 1 smiley -1 (i.e. 15-1=14)
		var position = '-' + (71 - 14 * value) + 'px bottom';
		if (this.node.ancestor('.FigureBox.ll')) {
			position = value ? (115 - 22 * value) : 115;
		}
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
	Rating.listen = {};

	/*
	 * static functions
	 */
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

	Rating.postRatingChangeAndCleanup = function(v, r) {
		// check if we are in the HiddenShots dialog
		// if so, updateBestshot
		var options = {};
		if (r.node.ancestor('section.gallery.hiddenshots')) {
			options.updateBestshot = 1;
		} else if (r.node.get('id')=='menuItem-contextRatingGrp') {
			options = {
				thumbnail: r.thumbnail,
				updateBestshot: 1
			};
		} else if (r.node.get('id')=='photoPreview-ratingGroup') {  
			options = {
				thumbnail: r.thumbnail,
				updateBestshot: 1
			};
		}
		SNAPPI.AssetRatingController.postRating(v, null, r, options);
		return;
	};	
	Rating.pluginRating = function(container, mixed, v, cfg) {
		var thumbnail, uuid;
		if (mixed instanceof SNAPPI.Thumbnail) {
			parent = mixed.node;
			uuid = mixed.node.uuid;
		} else if (mixed.hasClass && mixed.hasClass('FigureBox')) {
			parent = mixed;
			uuid = mixed.uuid;
		} else if (mixed.getAttribute('uuid')) {
			parent = mixed;
			uuid = mixed.getAttribute('uuid');
		} else {
			try {
				parent = mixed;
				uuid = mixed.uuid || (mixed.node && mixed.node.uuid);
			} catch(e) {
				if (console) console.error('ERROR: cant find uuid for Rating');
				return false;
			}
		}
		
		var ratingCfg = {
			v : v,
			uuid : uuid,
			setDbValueFn : Rating.postRatingChangeAndCleanup,
			listen : false
		};
		ratingCfg = _Y.merge(ratingCfg, cfg);
		Rating.attach(parent, ratingCfg);
		// if (parent.one('figure > img')) parent.one('figure > img').addClass('rating' + v);
		// if (parent.one('div.thumb-label')) parent.one('div.thumb-label').addClass('hide');
	};

	/*
	 * use attach() to attach new/used Rating component to thumbnail, etc.
	 */
	/**
	 * @params mixed - 'div.RatingGroup'.get('parentNode')
	 * 	NOTE: ratingGroup.getAttribute('uuid') == audition.hashcode(), NOT audition.Audition.id
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
		_cfg = _Y.merge(_cfg, cfg);

		var ratingGroup;
		if (parent.Rating) {	
			// REUSE this ratingGroup
			var r = parent.Rating;
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
			var ratingGroup = parent.one('.'+_cfg.className);
			if (!ratingGroup) ratingGroup = parent.create(_Y.Lang.sub(
					"<div class='{className}'></div>", _cfg));
			try {
				parent.one('ul > li.rating').append(ratingGroup);	
			}	catch (e) {
				parent.append(ratingGroup);
			}	
			_cfg.node = ratingGroup;
			if (_cfg.uuid) {
				ratingGroup.setAttribute('uuid', _cfg.uuid);	// asset_id
				SNAPPI.Auditions.bind(parent, _cfg.uuid); 		// domId
			}
			if (_cfg.id) ratingGroup.set('id', _cfg.id);
			var r = new SNAPPI.Rating(_cfg);
			if (_cfg.applyToBatch)
				r.applyToBatch = _cfg.applyToBatch;
			if (_cfg.setDbValueFn)
				r.setDbValueFn = _cfg.setDbValueFn;
			parent.Rating = r;
			if (_cfg.listen) Rating.startListeners(parent);
		}
		return mixed.Rating;
	};
	Rating.handleClick = function(e) {
		var r = e.target.Rating;
		var v = _getStarValueFromEvent(e);
		Rating.setRating(r,v, e);
		return;
	};
	/*
	 * manually set Rating, called by handleClick
	 * @params r SNAPPI.Rating
	 * @params value int
	 * TODO: deprecate params e
	 */
	Rating.setRating = function(r, v, e){		
		// plugin loading mask then call XHR POST
		if (!r.node.loadingmask) {
			loadingmaskTarget = r.node.get('parentNode');
			if (loadingmaskTarget.get('clientHeight') == 0) {
				loadingmaskTarget = loadingmaskTarget.get('parentNode');		
			}
			r.node.plug(_Y.LoadingMask, {
				strings: {loading:''}, 	// BUG: A.LoadingMask
				target: loadingmaskTarget,
				end: null
			});
			// BUG: A.LoadingMask does not set target properly
			r.node.loadingmask._conf.data.value['target'] = loadingmaskTarget;
			r.node.loadingmask.overlayMask._conf.data.value['target'] = r.node.loadingmask._conf.data.value['target'];
		} else {
			r.node.setStyle('zIndex', 0);
			r.node.loadingmask.set('zIndex', 20);
		}
		r.node.loadingmask.show();
		// post rating value
		if (r.getValue() == 0 || r.rerate) {
			if (r.applyToBatch && _Y.Lang.isFunction(r.applyToBatch)) {
				r.applyToBatch(v, r.node);			
			} else if (r.setDbValueFn && _Y.Lang.isFunction(r.setDbValueFn)) {
				// var silent = silent || false;
				/*
				 * set by DataElement, fires onChange event
				 */
				r.setDbValueFn(v, r, e);
			} else {
				r.render(v);
			}
		}
	};

	/**
	 * startListeners()
	 * @param delegateContainer _Y.Node or CSS selector
	 * @param selector	CSS selector
	 * 
	 * * Note: Filter Rating listener started by SNAPPI.filter.initRating();
	 */
	Rating.startListeners = function(delegateContainer, selector) {
		var selector = selector || 'div.ratingGroup';
if (!_Y) {
	_Y = SNAPPI.Y;
	console.warn('Had to fix _Y in Rating.startListeners');
}		
		if (!delegateContainer instanceof _Y.Node) {
			delegateContainer = _Y.one(delegateContainer);
		}
		var id = delegateContainer.get('id') || delegateContainer._yuid;
		var name = id + '-' + selector;
		if (!Rating.listen[name]) {
			Rating.listen[name] = delegateContainer.delegate('click', Rating.handleClick, selector);	
			delegateContainer.listen['RatingClick'] = Rating.listen[name];
		}
		// _Y.one(delegateContainer).delegate("mouseover",
		// Rating.handleMouseOver, selector);
		// _Y.one(delegateContainer).delegate("mouseout",
		// Rating.handleMouseOut, selector);
		return Rating.listen[name];
	};
	Rating.stopListeners = function(delegateContainer) {
		if (!delegateContainer instanceof _Y.Node) {
			delegateContainer = _Y.one(delegateContainer);
		}
		var detach = delegateContainer.get('id') + selector;
		if (Rating.listen[detach]) Rating.listen[detach].detach();
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
			var _cfg = {
				// el : null,
				id: null,
				v : 0,
				uuid : null,
				max : 5,
				rerate : true
			// change an existing rating
			};
			_cfg = _Y.merge(_cfg, cfg);
			this.id = _cfg.id || _cfg.uuid || _cfg.node.get('id');
			// this.id = (_cfg.uuid || (_cfg.uuid === false)) ? _cfg.uuid
					// : _cfg.node.get('id');
			this.node = _cfg.node;
			this.stars = _cfg.max;
			this.setValue(_cfg.v);
			this.rerate = _cfg.rerate;
			this.setRatingSprite();
			this.node.Rating = this; // add backreference, also add reference
										// to .FigureBox
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
			var position;
			if (value === -1) {
				position = -1*_cfg.max;
			}
			if (this.node.hasClass('wide')) {
				// or 23 = width for wide smiley, iOS touch padding
				position = value ? (115 - 23 * value) : 116;
			} else {
				position = value ? (70 - 14 * value) : 71;	
			} 
			position = '-' + position + 'px bottom';
			this.node.setStyle('backgroundPosition', position);
		},
		// DEPRECATE
		renderStars : function(units) {
			this.setRatingSprite (units);
		},

		render : function(value) {
			this.setValue(value);
			this.setRatingSprite();
		},
		setValue : function(v) {
			if (v > this.stars)
				v = this.stars;
			if (v < -1 || !v)
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
})();
/*
 * UNUSED METHODS
 *  
 *  // TODO: use yui hover event here
	Rating.XXXhandleMouseOver = function(e) {
		e.stopPropagation();
		var r, eTarget = e.target;
		var starValue = _getStarValueFromEvent(e);
		r = eTarget.parentNode.Rating;
		if (r.getValue() == 0 || r.rerate)
			r.mouseOver(starValue);
		// _Y.on('mousemove', Rating.handleMouseOver, e.target);
	};
	Rating.XXXhandleMouseOut = function(e) {
		e.stopPropagation();
		// _Y.detach('mousemove', Rating.handleMouseOut, e.target);
		var r, eTarget = e.target;
		r = eTarget.parentNode.Rating;
		if (r.getValue() == 0 || r.rerate)
			r.mouseOut();
	};
	

	Rating.toggleShowSizeByRating = function(value) {
		if (!_ratingCSS) {
			_ratingCSS = new _Y.StyleSheet('ratingCSS');
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
	var _Y = null;
    SNAPPI.namespace('SNAPPI.onYready');
    SNAPPI.onYready.AssetRatingController = function(Y){
		if (_Y === null) _Y = Y;
		// custom listeners, global
		SNAPPI.AssetRatingController = AssetRatingController;
	}	
	var AssetRatingController = function() {};
	
	/**
	 * 
	 * TODO: move to differnt class, io_helpers(?)
	 * @params container _Y.Node, container for loadingmask 
	 * @params cfg.ids Array, asset UUIDs
	 * @params cfg.properties obj, {rating:, rotate:, }
	 * @params cfg.actions obj, {updateBestshot:1, }
	 * @params cfg.callbacks
	 */
	AssetRatingController.setProp = function(container, cfg){
		var uri;
		if (SNAPPI.STATE.controller['class'] == 'workorder') {
			uri = "/workorders/setprop/.json";
		} else {
			uri = "/photos/setprop/.json";
		}
		var postData = {
			'data[Asset][id]' : cfg.ids,
		};	
		var postKey;	
		for (var k in cfg.properties) {
			postKey = 'data[Asset]['+k+']';
			postData[postKey] = cfg.properties[k];
		}
		for (var k in cfg.actions) {
			postKey = 'data['+k+']';
			postData[postKey] = cfg.actions[k];
		}
		postData = AssetRatingController.addWorkorderData(postData);
    	/*
		 * plugin _Y.Plugin.IO
		 */
		var ioCfg;
    	var args = {
    		node: container,
    		uri: uri,
    	};	
    	args = _Y.merge(args, cfg.properties);		
		if (!container.io) {
			ioCfg = {
   					// uri: args.uri,
					parseContent: false,
					autoLoad: false,
					context: container,
					arguments: args, 
					method: "POST",
					dataType: 'json',
					qs: postData,
					on: cfg.callbacks,
			};
			// var loadingmaskTarget = container.get('parentNode');
			var loadingmaskTarget = container;
			// set loadingmask to parent
			container.plug(_Y.LoadingMask, {
				strings: {loading:''}, 	// BUG: A.LoadingMask
				target: loadingmaskTarget,
			});    			
			container.loadingmask._conf.data.value['target'] = loadingmaskTarget;
			container.loadingmask.overlayMask._conf.data.value['target'] = container.loadingmask._conf.data.value['target'];
			// container.loadingmask.set('target', target);
			// container.loadingmask.overlayMask.set('target', target);
			container.loadingmask.set('zIndex', 10);
			container.loadingmask.overlayMask.set('zIndex', 10);
			container.plug(_Y.Plugin.IO, SNAPPI.IO.pluginIO_RespondAsJson(ioCfg));
		} else {
			ioCfg = {
				arguments: args, 
				method: "POST",
				qs: postData,
			}
			ioCfg = SNAPPI.IO.pluginIO_RespondAsJson(ioCfg); 
			container.io.set('data', ioCfg.data);
		}
		args.loadingmask = container.loadingmask;
		// get CC via XHR and render
		container.io.set('uri', args.uri);
		container.io.set('arguments', args);
		container.loadingmask.refreshMask();
		container.loadingmask.show();		//DEBUG: loadingmask is NOT showing here
		container.io.start();			
	}
	AssetRatingController.addWorkorderData = function (data){
    	var role = SNAPPI.STATE.controller.ROLE;
    	var auth = /(EDITOR|MANAGER)/.test(role);
		if (!auth) return data;
		if (/Workorder|TasksWorkorder/.test(SNAPPI.STATE.controller['class'])) {
			var woid = SNAPPI.STATE.controller.xhrFrom.uuid;
			data['data[Workorder][type]'] = SNAPPI.STATE.controller['class']; 
			data['data[Workorder][woid]'] = woid;
		}
		return data;
   	};
	AssetRatingController.postRating = function(value, ids, r, options) {
		// r = SNAPPI.Rating, node = r.node. for cleanup of loadingMask?
			var node = r.node;
			if (!ids) {
				var a = SNAPPI.Auditions.find(node.getAttribute('uuid'));
				ids = a.Audition.id;
			}
			var properties = {
				id: ids,
				rating: value,
			}
			var actions = {};
			if (options && options.updateBestshot) {
				actions['updateBestshot'] = 1;
			}
			var callbacks = {
				failure: function(e, id, o, args) {
					SNAPPI.flash.flashJsonResponse(o);
					return false;
				},
				successJson : function(e, id, o, args) {
					var msg = o.responseJson.message;
					// if (SNAPPI.timeout && SNAPPI.timeout.flashMsg) {
						// SNAPPI.timeout.flashMsg.cancel();
					// }
					// SNAPPI.flash.flash(msg); // don't flashMsg on success.
					SNAPPI.AssetRatingController.onRatingChanged(r,	args.rating);
					 
					try { // update shot, if necessary
						var audition, shotPhotoRoll;
						try {
							audition = SNAPPI.Auditions.find(r.node.ancestor('.FigureBox').uuid);
							shotPhotoRoll = r.node.ancestor('ul.hiddenshots').Gallery;
						} catch (e) {}
						try {
							audition = audition || SNAPPI.Auditions.find(options.thumbnail.uuid);
							shotPhotoRoll = options.thumbnail.ancestor('ul.hiddenshots').Gallery;
						} catch (e) {}

						var bestShot = audition.Audition.Substitutions.best;
						var selected = shotPhotoRoll.selected;
						// confirm showHidden bestShot is in main photoroll
						if (bestShot !== selected) {
							var photoroll = _Y.one('section.gallery.photo').Gallery;
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
					return false;	// for Plugin.IO
				}
			};
			
			// SNAPPI.io.post(uri, data, callbacks);
			AssetRatingController.setProp(node, {
				properties: properties,
				actions: actions,
				callbacks: callbacks
			})
			return;
		};
		/**
		 * update rating component, called after successful POST
		 * TODO: should be using custom and ATTR events here
		 * @param r instance of SNAPPI.Rating  
		 * @param v new rating value
		 * @return
		 */
		AssetRatingController._updateAuditionRatingScore = function(audition, new_rating){
			try {
				// update audition
				var old_rating, total, score, votes;
				old_rating = parseInt(audition.Audition.Photo.Fix.Rating) || 0;
				if (old_rating != new_rating) {
					score = parseFloat(audition.Audition.Photo.Fix.Score)  || 0;
					votes = parseInt(audition.Audition.Photo.Fix.Votes)  || 0;
					total = (score * votes) - old_rating + new_rating;
					if (!old_rating) votes++;
					score = SNAPPI.util.roundNumber(total/votes,1);
					// this is done in AssetRatingController.onRatingChanged() _updateRatingChange()
					audition.Audition.Photo.Fix.Rating = new_rating;	
					audition.rating = new_rating;
					audition.Audition.Photo.Fix.Score = score;
					audition.Audition.Photo.Fix.Votes = votes;
				}
			} catch (e) {}
			return audition;
		};
		AssetRatingController._updateRatingChange = function(audition, v) {
			// update audition rating and score
			AssetRatingController._updateAuditionRatingScore(audition, v);
			
			// update all nodes in audition.bindTo
			var ul, photoRolls = {}, thumbs = audition.bindTo || [];
			for ( var j in thumbs) {
				var n2 = thumbs[j];
				try {
					if (n2.Thumbnail) {
						// TODO: use Thumbnail.setRating()
						n2.Thumbnail.setRating(v, audition, "silent");
						n2.Thumbnail.setScore(audition);
					} else if (n2.Rating) {
						// TODO: push nodes into audition.bindTo
						n2.Rating.render(v);
					}
					var pr = n2.ancestor('section.gallery.photo').Gallery;
					photoRolls[pr.get('id')] = pr.Gallery;
				} catch (e) {
				}
			}
			// resort substitutes and check findBest()
			if (audition.substitutes) {
				audition.substitutes.findBest();
				for ( var i in photoRolls) {
					if (photoRolls[i]._cfg.hideHiddenShotByCSS) {
						// WARNING: this is causing a POST group on every rating change. WHY? 
						photoRolls[i].groupAsShot(audition.substitutes);
					}
				}
			}
		};
		AssetRatingController.onRatingChanged = function(r, v) {
			// get audition from parent of ratingGroup
			switch (r.node.get('id')) {
			case 'lbx-rating-group': // apply rating to lightbox.getSelected()
				// r = #lbx-rating-group.ratingGroup
				var batch = SNAPPI.lightbox.getSelected();
				batch.each(function(audition) {
					v = v || r.value;
					AssetRatingController._updateRatingChange(audition, v);
					r.render(v);	// r not listed in audition.bindTo
				}, this);
				_Y.fire('snappi:ratingChanged', r);
				break;
			case 'menuItem-contextRatingGrp': // right-click over .FigureBox
				var audition = SNAPPI.Auditions.find(r.id);
				v = v !== undefined ? v : r.value;
				AssetRatingController._updateRatingChange(audition, v);
				_Y.fire('snappi:ratingChanged', r);
				break;
			default: // .FigureBox ratingGroup
				try {
					var tn = r.node.ancestor('.FigureBox');
					var audition = SNAPPI.Auditions.find(tn.uuid);
					v = v !== undefined ? v : r.value;
					AssetRatingController._updateRatingChange(audition, v);
				} catch (e) {
				}
				_Y.fire('snappi:ratingChanged', r);
				break;
			}
		};
	
	
})();