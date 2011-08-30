/**
 *
 * Copyright (c) 2009, Snaphappi.com. All rights reserved.
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


/*******************************************************************************
 * XHR (Ajax) module SNAPPI.ajax = new Ajax();
 */
(function() {

	/*
	 * Ajax Class (singleton class) - handles paging and other ajax requests,
	 * configured by convention - calls xhrInit() method, if any, embedded in
	 * ajax response Works with CakePHP PaginateHelper output to implement Ajax
	 * paging - searches for 'div.paging-content' to attach delegated click
	 * listener to PaginateHelper <A> elements Use 'div.fragment' markup to
	 * request CakePhp element by Ajax uses the following custom dom attr to
	 * encode src/target for request - ajaxSrc: cakePhp request - xhrTarget:
	 * target for ajax response, default to current DOM element by Id fragment
	 * request is automatically made for all 'div.fragment' markup
	 */
	var Ajax = function() {
		return this; // chainable constructor		
	};
	Ajax.prototype = {
		XHR_PAGE_INIT_DELAY: 500,
		/**
		 * singleton init - attaches delegated click handlers for ajax paging -
		 * launches request for any ajax fragments to fetch after initial page
		 * load
		 */
		init : function() {
			this.fetchXhr();
			this.initPaging();		
			// deprecate, use paginator_aui.js instead
			if (console) console.warn("utils.js: deprecate Ajax.initPaging, use paginator_aui.js: SNAPPI.Paginator instead") 
		},
		/*
		 * search for page fragments to fetch via ajax request
		 */
		fetchXhr : function(n) {
			var Y = SNAPPI.Y;
			if (n && n.hasClass('fragment')) {
				this.requestFragment(n);
			} else {
				var wait = this.XHR_PAGE_INIT_DELAY;
				var fragments = Y.all('div.fragment');
				if (fragments) {
					fragments.each(function(n,i,l) {
						var nodelay = n.getAttribute('nodelay');
						if (nodelay) {
							this.requestFragment(n);							
						} else {
							var delayed = new Y.DelayedTask( function() {
								this.requestFragment(n);
							}, this);
							// executes after XXXms the callback
							delayed.delay(wait);	
							wait += 500;  // +500ms delay for each subsequent fetch
						}
					}, this);
				}
			}
		},

		/**
		 * render ajax request into replaceDiv#id on page load uses the
		 * following custom dom attr to encode src/target for request - ajaxSrc:
		 * cakePhp request - xhrTarget: target for ajax response, default to
		 * current DOM element by Id fragment request is automatically made for
		 * all 'div.fragment' markup
		 * 
		 * @param {Object} n - YUI3 node for 'div.fragment'
		 */
		requestFragment : function(n) {
			var Y = SNAPPI.Y;
			
			var target = n.getAttribute('xhrTarget');
			var nodelay = n.getAttribute('nodelay');
			target = target ? Y.one('#'+target) : n;
			var uri = n.getAttribute('ajaxSrc');
//			var _updateDOM = this._updateDOM;
			// NOTE: key events 
			// 		target.io.afterHostMethod('insert'), use insert instead of setContent for ParseContent
			//		target.io.after('IOPlugin:success')
			//		target.io.after('IOPlugin:activeChange', 
			// 	ParseContent._dispatch() runs from asyncQueue. happens async AFTER IOPlugin:success
			if (!target.io) {
				target.plug(Y.Plugin.IO, {
					uri: uri,
					method: 'GET',
					parseContent: true,
					autoLoad: false
				});
//				target.plug(Y.Plugin.ParseContent);				
			}
			var detach = Y.before(function(){
				console.warn("before Y.Plugin.IO.setContent()");
				detach.detach();
				// new Y.DelayedTask(SNAPPI.ajax.init).delay(100);
			}, target.io, 'setContent', target);        				
//			var detach1 = Y.after(target.io.setContent,
//					function(){
//						console.warn("after Y.Plugin.IO.setContent()");
//						detach1.detach();
//					}, target);			
			target.io.set('uri', uri);
			target.io.start();
			return;			
		},

		/**
		 * search for paginate divs, add delegated listeners - can be called
		 * repeatedly with no side-effects
		 */
		initPaging : function() {
			var Y = SNAPPI.Y;
			var paging = Y.all('div.paging-contentXXX');	// deprecate. using SNAPPI.Paginator
			if (paging) {
				paging.each(function(n) {
					// add event delegate listeners
						if (!n.listen) n.listen={}; 
						if (!n.listen.paging) {
							n.listen.paging = n.delegate('click',
									this.requestPagingContent,
									'.paging-control a', this);
						}
					}, this);
			}
		},
		/**
		 * render new page into 'div.paging-content' - searches for
		 * 'div.paging-content' to attach delegated click listener to
		 * PaginateHelper <A> elements using CSS selector = '.paging-control a' -
		 * ajaxSrc == A.href from e.target.get('href') - replaces innerHTML in
		 * target uses the following custom dom attr - xhrTarget: target for
		 * ajax response, default to 'div.paging-content', referenced by Id
		 * 
		 * @param {Object} e - click event object
		 */
		requestPagingContent : function(e) {
			var Y = SNAPPI.Y;
			e.halt(); // stop event propagation
			//e.container == delegate event container
			var targetId = e.container.getAttribute('xhrTarget') || e.container.get('id');
			var target = Y.one('#'+targetId);
			var uri = e.target.get('href');

			try {
				SNAPPI.STATE.displayPage.page = parseInt(uri.match(
						/page:(\d*)/i).pop());
			} catch (e) {
			}
			if (!target.io) {
				target.plug(Y.Plugin.IO, {
					uri: uri,
					method: 'GET',
					parseContent: true,
					autoLoad: false
				});
			}
			target.io.set('uri', uri);
			target.io.start();
			return;
		},

		
		/**
		 * DEPRECATED - replaces innerHTML of Dom element with responseText
		 * 
		 * @param {Object} id Y.io transaction Id
		 * @param {Object} o response object
		 * @param {Object} args args.sectionId == id of DOM container
		 */
		_updateDOM : function(id, o, args) {
			console.warning("DEPRECATE? SNAPPI.ajax._updateDOM");
			var Y = SNAPPI.Y;
			var data = o.responseText; // Response data.
			var target = args.target || args[0]; // DOM element id to put
													// data
			var node = target.setContent(data);
	
			SNAPPI.ajax.xhrInit(node); // execute js in ajax markup
			Y.fire('snappi:ajaxLoad'); // execute js in script files
		},
		/**
		 * DEPRECATED (?) XHR request should call 
		 * XHR page fragments can add init code by markup like this: 
		 * // <script class='xhrInit' type='text/javascript'> 
		 */
		xhrInit : function(xhrNode) {
			console.warning("DEPRECATE? SNAPPI.ajax.xhrInit: PAGE.init.length="+PAGE.init.length);
			// execute deferred javascript init
			while (PAGE.init.length) {
				var init = PAGE.init.shift();
				init();
			}
		},
		
		
		/**
		 * Not finished
		 * getPageFromCache - check if we have page data already cached in a
		 * sortedHash
		 * 
		 * @param {Object}
		 *            e
		 */
		XXXgetPageFromCache : function(e) {
			/*******************************************************************
			 * disable for now, cakephp paginator doesn't cooperate
			 */
			return false; // disable
			// var Y = SNAPPI.Y;
			//            
			// var sh, ul, sectionId = e.container.get('id');
			// var target = e.target.get('href');
			//            
			// switch (sectionId) {
			// case "paging-photos":
			// sh = SNAPPI.auditions.auditionSH;
			// ul = Y.one('section.gallery.photo > div');
			// }
			// /*
			// * get display paging details, check if we can fill the request
			// from cache
			// */
			// SNAPPI.STATE.displayPage.perpage =
			// SNAPPI.STATE.displayPage.perpage || ul.all('li').size();
			// try {
			// SNAPPI.STATE.displayPage.page =
			// parseInt(target.match(/page:(\d*)/i).pop());
			// }
			// catch (e) {
			// SNAPPI.STATE.displayPage.page = 1;
			// }
			// var first, last;
			// first = (SNAPPI.STATE.displayPage.page - 1) *
			// SNAPPI.STATE.displayPage.perpage;
			// last = first + SNAPPI.STATE.displayPage.perpage - 1;
			// if (sh.size() > last + 1) {
			// // page from Cache
			// Y.fire('snappi:ajaxLoad');
			// return true;
			// }
			// else {
			// // get additional rows for sortedHash by paging request
			// return false;
			// }

		}
	};
	/*
	 * make global
	 */
	SNAPPI.ajax = new Ajax();

	/***************************************************************************
	 * EditMode Static Class - Works with Settings/Edit Actions - searches for
	 * 'div.setting' to attach delegated click listener Notes: - just changes
	 * form fields to write, and submit value='Submit' - NOT really ajax.
	 */
	SNAPPI.EditMode = function(cfg) {
	};
	// make request based on setting DOM id
	SNAPPI.EditMode.requestEditContent = function(e) {
		var Y = SNAPPI.Y;
		var sectionId = e.container.get('id'); // delegate container
												// id='fields'
		var target = e.target;
		if (target.get('value') == 'Edit') {
			e.halt(); // stop event propagation, change to edit mode
			var inputs = e.container.all('input, textarea, select, button');
			inputs.each(function(n) {
				// make inputs writeable
					if (",checkbox,radio".indexOf(n.get('type')) > 0
							&& n.dom().onclick)
						n.dom().onclick = null;
					if (n.get('readOnly'))
						n.set('readOnly', false);
					if (n.get('type') == 'submit')
						n.set('value', 'Submit').get('parentNode').addClass(
								'submit');
					var check;
				});
		}
	};

	SNAPPI.EditMode.init = function() {
		var Y = SNAPPI.Y;
		// add event delegate listeners
		// var submits = Y.all('input[type="submit"]');
		var setting = Y.all('div.setting');
		if (setting) {
			setting.each(function(n) {
				n.delegate('click', SNAPPI.EditMode.requestEditContent,
						'input[type="submit"]');
			});
		}
	};


	/*
	 * placeholder TEMPORARY CLASS DEFINITION, relocate this functionality when
	 * appropriate - also, should have similar function for tags
	 */
	/*
	 * group photos as shot - post group as substitute to backend DB -
	 * updates local JS objects with substitute group data - calls
	 * callback.complete() on success - callback still needs to render
	 * substitute photos as hidden, etc.
	 */
	ShotController = function() {

		this.show = function() {
			SNAPPI.STATE.showSubstitutes = true;
			SNAPPI.Thumbnail.toggleHideSubstitutes(false);
		};
		this.hide = function() {
			SNAPPI.STATE.showSubstitutes = false;
			SNAPPI.Thumbnail.toggleHideSubstitutes(true);
		};
	};
	/*
	 * static methods
	 */
	ShotController.getCCid = function(o) {
		/*
		 * get the CastingCall.ID from the main photoRoll 
		 */
		var CCid, mainPR;
		try {
			if (o instanceof SNAPPI.Lightbox) {
				mainPR = SNAPPI.Y.one('section.gallery.photo').Gallery;
			} else {
				mainPR = (o instanceof SNAPPI.Gallery) ? o :  o.dom().Gallery;
			}
			CCid = mainPr.castingCall.CastingCall.ID;
		} catch (e) {
			CCid = null;
		}		
		return CCid;
	};
	/**
	 * Add auditions to Shot 
	 * 	* keep Shot.count accurate
	 * @param photoRoll instance of SNAPPI.Gallery,  the photoRoll to update, 
	 * 		- not sure if this is required. it might update master List  
	 * @param shotCfg { shotId, bestshotId}  uuids
	 * @param auditionREFs array - [auditionId,...] or [{idref: auditionId}, ...] 
	 * @return shotId
	 */	
	ShotController.markSubstitutes_afterPostSuccess = function(photoRoll, shotCfg, auditionREFs) {
		/*
		 * local processing after successful POST add Substitution to CastingCall
		 */
		var shotType = photoRoll.castingCall.CastingCall.Auditions.ShotType;
		var groupCfg = {
			id : shotCfg.shotId,
//			bestshotId: shotCfg.bestshotId		// not used here
			ShotType: shotType,
			Label : shotCfg.shotId,
			AuditionREF : auditionREFs
		};
		// update auditions & shots,
		var shot = SNAPPI.Auditions.mergeGroup.call(photoRoll, 'Shot', groupCfg, photoRoll.auditionSH);
		return shot;
	};
	/**
	 * remove auditions from Shot
	 * @param shotId uuid
	 * @param auditionREF array - array of auditionIds to remove, [{idref: auditionId}, ...] 
	 * @return shotId
	 */
	ShotController.removeFromShots_afterPostSuccess = function(photoRoll, shotId, auditionREF) {
		var Y = SNAPPI.Y;
		/*
		 * local processing after successful POST add Substitution to
		 * CastingCall
		 */
		var shotType = photoRoll.castingCall.CastingCall.Auditions.ShotType;
		var shotCfg = {
			id : shotId,
			Label : shotId,
			ShotType: shotType,
			AuditionREF : auditionREF,
			remove: true
		};
		// update auditions & shots,
		var shot = SNAPPI.Auditions.mergeGroup.call(photoRoll, 'Substitution', shotCfg, photoRoll.auditionSH);
		return shot;
	};		
	ShotController.prototype = {
		postGroupAsShot : function(aids, callback) {
			// see Photoroll.groupAsShot() for pattern
			var auditionREF = [];
			// reformat for auditionREF
			for ( var i in aids) {
				auditionREF.push( {
					idref : aids[i]
				});
			}
			/*
			 * post Substitution to server
			 */
			var data = {
				'data[Asset][id]' : aids.join(','),
				'data[Asset][group]' : '', // if '', then generate UUID on server
				'data[ccid]' : ShotController.getCCid(this)
			};
			var uri = '/photos/setprop/.json';
			var closure = {
//				shotId : local_UID,
				auditionREF : auditionREF,		// these are the aids to ADD to JS subGroup
				callback : callback
			};
			var post_callback = {
				complete : function(id, o, args) {
					if (o.responseJson && o.responseJson.success == 'true') {
//						var shotId = o.responseJson.response['shot_id'] || closure.shotId;
						var shotId = o.responseJson.response['shot_id'];
						var photoRoll = args.photoRoll || this;
						var shot = ShotController.markSubstitutes_afterPostSuccess(photoRoll, shotId, closure.auditionREF);
						if (shot){
							closure.callback.complete.call(this, shot, o.responseJson);
						}
					}
					var check;
				}
			};
			var pr = (this instanceof SNAPPI.Lightbox) ? this.Gallery : this;
			SNAPPI.io.post.call(this, uri, data, post_callback, {photoRoll:pr});
			return;
		},
		postUnGroup : function(shotIds, callback){
			if (!shotIds) return false;
			var uri = "/photos/setprop/.json";
			var data = {
				'data[Asset][shotId]' : shotIds.join(','),
				'data[Asset][ungroup]' : 1,
				'data[ccid]' : ShotController.getCCid(this)
			};
			var post_callback = {
				complete : function(id, o, args) {
					if (o.responseJson && o.responseJson.success == 'true')  {
						// reload to fetch hidden shots
						window.location.reload();
					}
				},
				failure : function(id, o, args) {
					var check;
				}
			};
			var pr = (this instanceof SNAPPI.Lightbox) ? this.Gallery : this;
			SNAPPI.io.post.call(this, uri, data, post_callback, {photoRoll:pr});		
		},
		postRemoveFromShot : function (aids, callback) {
			if (!aids) return false;
			var auditionREF = [];
			var uri = "/photos/setprop/.json";
			var data = {
				'data[Asset][id]' : aids.join(','),
				'data[Asset][ungroup]' : 1,
				'data[ccid]' : ShotController.getCCid(this)
			};
			// reformat for auditionREF
			for ( var i in aids) {
				auditionREF.push( {
					idref : aids[i]
				});
			}			
			var closure = {
					auditionREF : auditionREF,	// these are the aids to REMOVE from JS subGroup
					callback : callback
				};			
			var post_callback = {
				complete : function(id, o, args) {
					if (o.responseJson && o.responseJson.success == 'true') {
						var photoRoll = args.photoRoll || this;
						var shotIds = o.responseJson.response['removeFromShot']; 
						var shots = [];
						for (var i in shotIds) {
							var shot = ShotController.removeFromShots_afterPostSuccess(photoRoll, shotIds[i], closure.auditionREF);
							shots.push(shot);
						}
						if (shots){
							closure.callback.complete.call(this, shots, o.responseJson);
						}
					}
					var check;
				},
				failure : function(id, o, args) {
					var check;
				}
			};
			var pr = (this instanceof SNAPPI.Lightbox) ? this.Gallery : this;
			SNAPPI.io.post.call(this, uri, data, post_callback, {photoRoll:pr});		
		}		
	};
	SNAPPI.ShotController = ShotController;
	SNAPPI.shotController = new ShotController();

	SNAPPI.sortConfig = new function() {
		this.init = function() {
			this.RATING = {
				fn : SNAPPI.Sort.compare.Numeric,
				property : 'rating',
				order : 'desc'
			};
			this.TIME = {
				fn : SNAPPI.Sort.compare.Time,
				property : 'exif_DateTimeOriginal',
				order : 'asc'
			};
			this.ID = {
				fn : SNAPPI.Sort.compare.Alpha,
				property : 'label',
				order : 'asc'
			};
			this.byRating = [ this.RATING, this.TIME, this.ID ];
			this.byTime = [ this.TIME, this.RATING, this.ID ];
		};
	};

	/*
	 * property manager
	 */
	var PropertyManager = function() {
		this.Y = SNAPPI.Y;
		this.p;
	};

	PropertyManager.prototype = {

		renderAsDialog : function(cfg) {
			this.p = new SNAPPI.Property( {
				data : cfg
			});
			this.p.renderAsDialog();
		},

		renderAsAsyncLoading : function(cfg) {
			this.p = new SNAPPI.Property( {
				data : cfg
			});
			this.p.renderAsAsyncLoading();
		},

		render : function(cfg) {
			this.p = new SNAPPI.Property( {
				data : cfg
			});
			this.p.render();
		},

		renderDialogInPhotoRoll : function(selected) {
			var Y = this.Y;

			var _renderDialogbox = function(selected) {
				var cfg = {
					title : 'photo details'
				};
				var nodeList = [], n;
				var photo = selected.Audition.Photo;
				layoutHint = selected.Audition.LayoutHint;

				var details = [
				// ownder and photostream needs to join table. Chris and I, we
				// are not sure about the process of getting castingCall.
						// so first leave these two properties in controller
						{
							title : 'Owner',
							label : PAGE.jsonData.controller.owner
						}, {
							title : 'Photostream',
							label : PAGE.jsonData.controller.photostream
						}, {
							title : 'Avg Rating',
							label : layoutHint.Rating
						}, {
							title : 'Date Taken',
							label : photo.DateTaken
						}, {
							title : 'Camera Id',
							label : photo.CameraId
						}, {
							title : 'Flash Fired',
							label : photo.ExifFlash == 1 ? 'Yes' : 'No'
						}, {
							title : 'is RGB',
							label : photo.ExifColorSpace == 1 ? 'Yes' : 'No'
						}, {
							title : 'Batch Id',
							label : photo.BatchId
						}, {
							title : 'Caption',
							label : photo.Caption
						}, {
							title : 'Keyword',
							label : photo.Keyword
						}, {
							title : 'Created On',
							label : photo.Created
						} ];

				for ( var i in details) {
					var check = details[i];
					n = Y.Node.create(Y.substitute(
							'<li>{title} : {label}</li>', details[i]));
					nodeList.push(n);
				}

				cfg.body = nodeList;

				this.renderAsDialog(cfg);

				try {
					Y.fire('snappi:completeAsync', Y
							.one('#snappi-dialog .body'));
				} catch (e) {

				}
			};

			var asyncCfg = {
				fn : _renderDialogbox,
				node : Y.one('#snappi-dialog .body'),
				size : 'big',
				args : [ selected ],
				context : this
			};

			this.renderAsAsyncLoading(asyncCfg);
		}
	};

	SNAPPI.propertyManager = new PropertyManager();

})();
