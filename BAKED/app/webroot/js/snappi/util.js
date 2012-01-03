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
				mainPR = _Y.one('section.gallery.photo').Gallery;
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
	 * @param options.aids = auditionREFs array - [auditionId,...] or [{idref: auditionId}, ...] 
	 * 			options.shotType
	 * @return shotId
	 */	
	ShotController.markSubstitutes_afterPostSuccess = function(photoRoll, shotCfg, options) {
		/*
		 * local processing after successful POST add Substitution to CastingCall
		 */
		var shotType, auditionREFs = options.aids;
		try {
			shotType = photoRoll.castingCall.CastingCall.Auditions.ShotType;
		} catch (e) {
			// for lightbox.Gallery,
			// WARNING: not sure this is a valid way to identify shotType. can lighbox MIX shotTypes?
			shotType = options.shotType;
		}
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
		xxxpostGroupAsShot : function(aids, callback) {
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
