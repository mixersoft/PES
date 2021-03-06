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

(function() {
	var _Y = null;
    SNAPPI.namespace('SNAPPI.onYready');
    SNAPPI.onYready.ShotController = function(Y){
		if (_Y === null) _Y = Y;
		
		SNAPPI.ShotController = ShotController;
		SNAPPI.shotController = new ShotController();
		
		SNAPPI.AssetPropertiesController = AssetPropertiesController;
	}
	/***************************************************************************
	 * EditMode Static Class - Works with Settings/Edit Actions - searches for
	 * 'div.setting' to attach delegated click listener Notes: - just changes
	 * form fields to write, and submit value='Submit' - NOT really ajax.
	 */
	SNAPPI.EditMode = function(cfg) { };
	// make request based on setting DOM id
	SNAPPI.EditMode.requestEditContent = function(e) {
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
						n.set('value', 'Submit').replaceClass('green','orange');
					var check;
				});
		}
	};

	SNAPPI.EditMode.init = function() {
		// add event delegate listeners
		// var submits = _Y.all('input[type="submit"]');
		var setting = _Y.all('div.setting');
		if (setting) {
			setting.each(function(n) {
				n.delegate('click', SNAPPI.EditMode.requestEditContent,
						'input[type="submit"]');
			});
		}
	};
	
	/*
	 * placeholder, methods for calling /assets/setprop
	 * called by: 
	 * 	- CFG_Dialog_Select_Privacy: apply() handler
	 */
	AssetPropertiesController = function() {}
	AssetPropertiesController.setPrivacy = function(batch, value, loading, callbacks){
		// copied from Lightbox.applyPrivacyInBatch, and modified
		var asset_ids = [];
		batch.each(function(audition) {
			asset_ids.push(audition.Audition.id);
		});
		/***********************************************************************
		 * - cakePHP POST
		 */
		var uri = "/photos/setprop/.json";
		var data = {
			'data[Asset][privacy]' : value,
			'data[Asset][id]' : asset_ids.join(',')
		};
		var args = {
			privacy : value,
			loadingNode: loading,
		};
		AssetPropertiesController.setProperties(this, uri, data, args, callbacks);		
	};
	/**
	 * called by Gallery.deleteThumbnail(), MenuItems.preview_delete_click(), via dialog
	 * @params container _Y.Node, container for loadingmask 
	 * @params ids Array, asset UUIDs
	 * @params properties obj, {rating:, rotate:, }
	 * @params actions obj, {updateBestshot:1, }
	 */
	AssetPropertiesController.deleteByUuid = function(container, cfg){
		var uri = "/photos/delete/.json";
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
		var args = {
    		loadingNode: container,
    		uri: uri,
    	};		
    	context = cfg.context || container;	
		AssetPropertiesController.setProperties(context, uri, postData, args, cfg.callbacks)
	};
	AssetPropertiesController.setProperties = function(context, uri, data, args, callbacks){
		context = context || this;
		var loadingNode = args.loadingNode;
		if (loadingNode.io == undefined) {
			var ioCfg = SNAPPI.IO.pluginIO_RespondAsJson({
				uri: uri ,
				parseContent:true,
				autoLoad: false,
				method: 'POST',
				qs: data,
				dataType: 'json',
				context: context,	
				arguments: args, 
				on: {
					successJson : function(e, id, o, args) {
						_Y.fire('snappi:set-property-complete', args);
						var success = callbacks && callbacks.successJson || callbacks.success;
						if (success) return success.call(this, e, args);
						else return false;
					}
				}
			});
			// set loadingmask to parent
			loadingNode.plug(_Y.LoadingMask, {
				strings: {loading:'One moment...'}, 	// BUG: A.LoadingMask
				target: loadingNode,
			});    			
			loadingNode.loadingmask._conf.data.value['target'] = loadingNode;
			loadingNode.loadingmask.overlayMask._conf.data.value['target'] = loadingNode.loadingmask._conf.data.value['target'];
			loadingNode.loadingmask.set('zIndex', 10);
			loadingNode.loadingmask.overlayMask.set('zIndex', 10);			
            loadingNode.plug(_Y.Plugin.IO, ioCfg );
		} else {
			loadingNode.io.set('data', data);
			loadingNode.io.set('context', context);
			loadingNode.io.set('uri', uri);
			loadingNode.io.set('arguments', args);
        }
        loadingNode.loadingmask.refreshMask();
		loadingNode.loadingmask.show();	
		loadingNode.io.start();
	}

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
			if (o instanceof SNAPPI.Lightbox) o = o.Gallery;
			if (o instanceof SNAPPI.Gallery) {
				CCid = o.castingCall.CastingCall.ID;
			}
		} catch (e) {}		
		return CCid;
	};
	/**
	 * Add/Update audition.Shot object
	 * 	* keep Shot.count accurate
	 * @param g instance of SNAPPI.Gallery,  the g to update, 
	 * 		- not sure if this is required. it might update master List  
	 * @param shotCfg { shotId, bestshotId}  uuids
	 * @param options.aids = auditionREFs array - [auditionId,...] or [{idref: auditionId}, ...] 
	 * 			options.shotType
	 * @return shotId
	 */	
	ShotController.markSubstitutes_afterPostSuccess = function(g, shotCfg, options) {
		/*
		 * local processing after successful POST add Substitution to CastingCall
		 */
		var shotType, auditionREFs = options.aids;
		if (/ShotGalleryShot/.test(g._cfg.type)) {
			//need to append OLD shot_id to audition key to update audition
			auditionREFs = [];
			for (var i in options.aids) {
				auditionREFs.push(options.aids[i]+'_'+g.Shot.id);
			}
		}
		try {
			shotType = options.shotType || g.castingCall.CastingCall.Auditions.ShotType;
		} catch (e) {
			throw new Exception("ERROR: not sure what shot type to use here");
		}
		var newShotCfg = {
			id : shotCfg.shotId,
//			bestshotId: shotCfg.bestshotId		// not used here
			ShotType: shotType,
			Label : shotCfg.shotId,
			AuditionREF : auditionREFs,
			move : !options.isBestShot,		// just move, don't MERGE, audition
		};
		
		// update auditions & shots,  ???: g.auditionSH or options.auditions?
		// TODO: need to check args.isBestShot to know if we should move or merge Shot
		var shot = SNAPPI.Auditions.mergeGroup.call(g, 'Shot', newShotCfg, g.auditionSH);
		return shot;
	};
	/**
	 * remove auditions from Shot
	 * @param shotId uuid
	 * @param auditionREF array - array of auditionIds to remove, [{idref: auditionId}, ...] 
	 * @return shotId
	 */
	ShotController.removeFromShots_afterPostSuccess = function(photoRoll, shotId, auditionREF) {
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
		// @deprecate (?), call lightbox.Gallery.groupAsShot() instead?
		postGroupAsShot : function(aids, callback) {
			// called from lightbox, 
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

})();
