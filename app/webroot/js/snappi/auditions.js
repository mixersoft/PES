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
 * Auditions.
 * Manage collections of Auditions throughout page lifecycle.
 * processes all calls to datasource3.js
 * merge Groupings with CastingCalls by AuditionREF
 *
 */
(function(){
	if (typeof SNAPPI.Auditions !== 'undefined') return; 	// firefox/firebug 1.9.1 bug
    var _Y = null;
    SNAPPI.namespace('SNAPPI.onYready');
    SNAPPI.onYready.Audition = function(Y){
		if (_Y === null) _Y = Y;
		SNAPPI.AuditionParser = AuditionParser;
    
	    /*
	     * Audition master list, key on AuditionREF
	     */
	    _auditionSH = new SNAPPI.SortedHash({
	        'isDataElement': false
	    });
	    _shotsSH = new SNAPPI.SortedHash();
	    
	    Auditions._auditionSH = _auditionSH;		// expose for debugging
    	Auditions._shotsSH = _shotsSH;		// enforced shot uniqueness

	    
	    SNAPPI.Auditions = Auditions;	// expose Static properties and methods
	}
    
    
    /**
     * Auditions constructor
     */
    var Auditions = function(){};
    
    /*
     * static properties and methods
     */
    var _auditionSH = null;
    var _shotsSH = null;
    var _defaultCfg = {};
    
    // convenience function
    Auditions.get = function(id){
    	return Auditions._auditionSH.get(id);
    };
    Auditions.find = function(key){
    	if (key.hashcode) key = key.hashcode();
    	return Auditions._auditionSH._data[key];
    };
    Auditions.onDuplicate_ORIGINAL = function(a,b) {
		return a; // return original, do not replace
	}; 
    Auditions.onDuplicate_REPLACE = function(a,b) {
		return b; // return original, do not replace
	}; 
	Auditions.add_SHOT_to_key_by_hashcode = function() {
		return this.Audition.id+'_'+this.Audition.Shot.id;
	}
	Auditions.onDuplicate_CHECK_SHOT = function(a,b) {
		try {
			if (a.Audition.Shot.id == b.Audition.Shot.id) return a;	// ORIGINAL
			else return b;	// not a duplicate, but remember to add ShotId to sortedhash key
		} catch (e) {
			throw new Exception("Error processing duplicate audition, is Shot missing?");
		}
	};
	// do exif orientation math between exifOrientation, and subsequent rotate
    Auditions.orientationLookup = {
	        1: {
	            1: 1,
	            3: 3,
	            6: 6, 
	            8: 8
	        },
	        8: {
	            1: 8,
	            3: 6,
	            6: 1,
	            8: 3
	        },
	        6: {
	            1: 6,
	            3: 8,
	            6: 3,
	            8: 1
	        },
	        3: {
	            1: 3,
	            3: 1,
	            6: 6,
	            8: 8
	        }
	   };
    Auditions.orientationSum = function(orientation, rotate){
        return Auditions.orientationLookup[orientation][rotate];
    };
    /**
     * @param castingCall (by reference)
     * @param providerName string, [snappi | flickr | or castingCall.providerName ]
     * @param sh by reference, or null for new sh
     * @param onDuplicate function - action if audition has already been loaded. 
     * 			calls o = onDuplicate(old, new) and adds o , Default = NO REPLACE
     */
    Auditions.parseCastingCall = function(castingCall, providerName, sh, onDuplicate){
    	if (onDuplicate == undefined) onDuplicate = Auditions.onDuplicate_ORIGINAL;
    	sh = sh || new SNAPPI.SortedHash({
            'isDataElement': false		// what does this do?
        });
    	castingCall.providerName = providerName || castingCall.CastingCall.ProviderName || 'snappi';
    	castingCall.shots = {};
    	castingCall.auditionSH = sh;
        switch (castingCall.providerName) {
	        case 'snappi':
	        	var shotType = castingCall.CastingCall.Auditions.ShotType;
	        	castingCall.schemaParser = AuditionParser.snappi;
	            var parsed = castingCall.schemaParser.parse(castingCall);
	            castingCall.parsedResults = parsed.results;	// TODO: deprecate???
	            sh.clear();
	            // TODO: showSubstitutes should be set in castingCall
//	            var hasHiddenShots = castingCall.CastingCall.ShowHidden;
	            var stale = castingCall.CastingCall.ShowHidden != true;	// force POST to get hidden shots
	            for (var i in parsed.results) {
	            	var o = parsed.results[i];
	            	/*
	            	 * extract frequently used attrs from Audition
	            	 */
	            	Auditions.extractSnappiAuditionAttr(o, castingCall.schemaParser);
	            	if (onDuplicate == Auditions.onDuplicate_CHECK_SHOT) {
	            		o.hashcode = Auditions.add_SHOT_to_key_by_hashcode;
	            		o.id = o.hashcode();
	            	}
	            	var aud_B = o;
	            	var aud_A = _auditionSH.get(o);
	            	if (aud_A) {
	            		// audition o already exists in master list
	            		if (onDuplicate)  {
	            			// get shot_A from raw audition BEFORE onDuplicate
	            			/*
	            			 * which datasourceis more accurate SNAPPI.Auditions, or o.responseJson ???
	            			 */
	            			o = onDuplicate(aud_A, aud_B);
	            			if (!o.bindTo && aud_A.bindTo) o.bindTo = aud_A.bindTo;
	            			_auditionSH.add(o);	
	            		} else o = _auditionSH.addIfNew(o);	// add to master copy first TODO: include ShotId
	            	} else {
	            		// NEW audition o,  DOES NOT exist in master list
	            		o = _auditionSH.addIfNew(o);
	            	}
	            	// audition o has now been added to master list, we can now use audition for shots
	            	
	            	var shotId = o.Audition.SubstitutionREF;		// set by castingCall JSON
	        		if (shotId){
	        			// TODO: need to match up ShotType somehow. 
	        			// 		lightbox messes things up because shotType is not determinate
	        			// case of aud_A...SubstitutionREF == null, but aud_B...SubstitutionREF == shotId 
	        			var s = Auditions.addAuditionToShot(o, shotId, shotType, stale, 'init');
	        			// add shot_extras for /workorders/shots views
	        			if (castingCall.shot_extras) {
	        				var extras = castingCall.shot_extras[s.id];
	        				s.owner_id = extras.owner_id;
	        				s.priority = extras.priority;
	        				s.active = extras.active;
	        			}
	        			castingCall.shots[s.id] = s;
	        		}
	            	sh.add(o);		// subset of auditions in master copy, SNAPPI.Auditions._auditionSH
	            }
	            // if bestshot is available, set
	            if (castingCall.CastingCall.Auditions.Bestshot.length) {
	            	var bestshot = castingCall.CastingCall.Auditions.Bestshot;
	            	for (var shotId in bestshot) {
	            		var bestshot_audition = sh.get(bestshot[shotId]);
	            		if (castingCall.shots[shotId] && castingCall.shots[shotId].setBest)
	            			castingCall.shots[shotId].setBest(bestshot_audition);
	            	}
	            }
	            
	            if (SNAPPI.STATE.displayPage) {
	            	// Total should be set by SNAPPI.mergeSessionData();
	            	// SNAPPI.STATE.displayPage.total = castingCall.CastingCall.Auditions.Total;
	            }
	            break;
	        case 'flickr':
	            break;
	        case 'picasaweb':
	            break;
	    }    
        return sh;
    };
    /**
     * extract commonly used attrs from raw Snappi audition
     * 	- this is AFTER AuditionParser.snappi.parse() in gallery/js/datasource3.js
     * TODO: merge attr extraction
     * @param o
     * @param schemaParser - need for o.getImgSrcBySize()
     * @param stale
     * @return
     */
    Auditions.extractSnappiAuditionAttr = function(o, schemaParser) {
    	try {
            o.id = o.Audition.id;
            o.isOwner = o.Audition.Photo.isOwner;
            o.pid = o.Audition.Photo.id;	// ???:how is this different from o.id?
            o.rootSrc = o.Audition.Photo.Img.Src.rootSrc;
            // dim from $exif['COMPUTED'] or hosted/preview file, 
            // root W/H: preview/autorot for AIR upload, orig/NOautorot for JS upload
            // checked against root file, still accurate after autorotate
            // TODO: use rotate + orientation and swap W/H as necessary
            o.imageWidth = parseInt(o.Audition.Photo.Img.Src.W);
            o.imageHeight = parseInt(o.Audition.Photo.Img.Src.H);
            o.root_Orientation = parseInt(o.Audition.Photo.Img.Src.Orientation);
            // ORIGINAL W/H: desktop for AIR upload, or root for JS upload 
            //		if autorotate applied, Orientation correct, but 
            // 		exif W/H will be WRONG in file, but corrected in DB  
            o.exif_ExifImageWidth = parseInt(o.Audition.Photo.W);
            o.exif_ExifImageLength = parseInt(o.Audition.Photo.H);
            o.exif_Orientation = parseInt(o.Audition.Photo.ExifOrientation) || 1;
            // Fix.Rotate in DB only, not in file
            var Fix = o.Audition.Photo.Fix;
            o.rotate = Fix.Rotate = parseInt(Fix.Rotate || 1);
            o.score = Fix.Score = parseFloat(Fix.Score || 0);
            /*
             * NOTE: render Audition.rating = Fix.Rating || Fix.Score
             * show score as smileys if user has not added individual rating 
             */
            o.rating = Fix.Rating  = parseFloat(Fix.Rating || Fix.Score || 0);
			o.votes = Fix.Votes = parseInt(Fix.Votes || 0);
			o.orientation = Auditions.orientationSum(o.root_Orientation, o.rotate);
			try {
				o.exif_DateTimeOriginal = o.Audition.Photo.DateTaken.replace(/T/, ' ');	
			} catch(ex) {
				o.exif_DateTimeOriginal = '1970-01-01 00:00:00';
console.error('ERROR: problem getting DateTaken value. using 1970-01-01 00:00:00');
// 2010-08-10 19:11:39
// {"Make":"Panasonic","Model":"DMC-TZ3","Orientation":1,"ExposureTime":"10\/300","FNumber":"33\/10","ISOSpeedRatings":100,"ExifVersion":"0221","DateTimeOriginal":"2010:08:10 19:11:39","Flash":25,"ColorSpace":1,"ExifImageWidth":3072,"ExifImageLength":2304,"InterOperabilityIndex":"R98","InterOperabilityVersion":"0100","ApertureFNumber":"f\/3.3","isFlash":1,"root":{"imageWidth":640,"imageHeight":480,"isRGB":true}}
				
			}
            
            o.ts = parseInt(o.Audition.Photo.TS);
            o.tags = o.Audition.Tags && o.Audition.Tags.value || null;
            o.label = o.Audition.Photo.Caption;
    		// o.getImgSrcBySize = schemaParser.getImgSrcBySize; // move to schemaParser.parse()
    	} catch(e) {
    		var check;
    	}    
    	return o;
    };
    
    
    /**
     * add audition or audition.Audition.Substitutions to existing or new Shot as SNAPPI.SubstitutionGroupData 
     * - track all SNAPPI.SubstitutionGroupData in private _shotsSH
     * @param audition
     * @param shotId - uuid
     * @param shotType string Usershot or Groupshot
     * @param stale boolean - if true, then shot._sh is incomplete. POST to get all shot auditions
     * @param move boolean, default false, move existing audition shot into new shot, do NOT merge
     * 		for ShowHidden=true or ShotGalleryShot, DialogHiddenShot operations
     * @return SNAPPI.SubstitutionGroupData, reference to a shot
     */
    Auditions.addAuditionToShot = function(audition, shotId, shotType, stale, move) {
    	var stale = stale || false;
    	move = !!move;	// default false
    	var shotId = shotId || audition.Audition.SubstitutionREF;
    	if (!shotId) return;
    	
    	var hiddenShot_count = parseInt(audition.Audition.Shot.count || 0);
    	var oldShot = audition.Audition.Substitutions;	// save oldShot, if any
		// find or create new shots (SubstitutionGroupData)
		// tracks all active shots in local _shotsSH
    	var s = _shotsSH.get(shotId);
        if (!s) {
        	stale = stale || (hiddenShot_count > 1);
        	s = new SNAPPI.SubstitutionGroupData({
				id: shotId,
				shotType: shotType,
				stale: stale		// stale==false: shot includes hidden shots, or stale==true: shot only includes the best shot
			});
        	_shotsSH.add(s);		// add shot to master list
        	// add shotType
        }
    	if (!move && oldShot && oldShot.id !== shotId) {				
    		// if the audition belonged to another Shot, merge when ShowHidden==0
    		s.stale = s.stale || oldShot.stale;
    		s.importGroup(oldShot);			
    	} else s.add(audition);			// just add audition to Shot
    	s.stale = s.stale || (s._sh.count() != hiddenShot_count);
        audition.Audition.Substitutions = s;	// back reference, add shot to audition
        audition.substitutes = audition.Audition.Substitutions; // legacy, DEPRECATE
        return s;
	};
	
    /**
     * remove audition or audition.Audition.Substitutions to existing or new Shot as SNAPPI.SubstitutionGroupData 
     * - track all SNAPPI.SubstitutionGroupData in private _shotsSH
     * @params audition
     * @params shotId - uuid
     * @params deprecate
     * @params stale boolean - if true, then shot._sh is incomplete. POST to get all shot auditions
     * @return SNAPPI.SubstitutionGroupData, reference to the updated shot, of null on error
     */
    Auditions.removeAuditionFromShot = function(audition, shotId, deprecate, stale) {
    	if (!shotId) return;
    	
    	var oldShot = audition.Audition.Substitutions;	// save oldShot, if any
    	if (oldShot && oldShot.id !== shotId) {	
        	// the shot was not found, OR
        	// the audition was NOT found in the shot
        	return null;
        } else {
        	oldShot.stale = oldShot.remove(audition);
        	// TODO: create NEW shot for removed audition. 
        	// problem: new ShotId is on the server, see /photos/setprop or  ShotController.removeFromShots_afterPostSuccess
        	audition.Audition.SubstitutionREF = null;
            audition.Audition.Substitutions = null;	// back reference, add shot to audition
            audition.substitutes = audition.Audition.Substitutions; // legacy, DEPRECATE
        }
        return oldShot;
	};	
	
	
	/**
	 * 
	 * for groupType == 'Substitution': 
	 * 		make a new shot group from group.AuditionREF 
	 * 
     * @param groupType string = [Tag | Cluster | Substitution]
     * @param group object - {
				id : uuid,
				Label : shotId,
				AuditionREF : [{idref: auditionId}, ...],
				shotType: [Usershot|Groupshot],
				remove: // if true, remove from existing group, do NOT add
				move: // if true, then just move to new Shot, do not MERGE
			};
     * @param auditionSH source for auditions 
	 */
	Auditions.mergeGroup = function(groupType, group, auditionSH){
		auditionSH = auditionSH || this.auditionSH;
        var groupTypePlural, j, AuditionREFs, key, audition, o, label, shotType, retval= null;
        shotType = group.ShotType || 'Usershot';
        groupTypePlural = groupType + 's';
        AuditionREFs = group.AuditionREF || [];
        AuditionREFs = _Y.Lang.isArray(AuditionREFs) ? AuditionREFs : [AuditionREFs];
        // passed by reference objects, shared by objects in the same group
        for (j in AuditionREFs) {
            
            // get audition from AuditionREF/key
            key = typeof AuditionREFs[j] == 'string' ? AuditionREFs[j] : AuditionREFs[j].idref;
            audition = auditionSH.get(key);		
            if (!audition) {
            	// TODO: for /workorders/shots we need id_Shot.id, but we need the OLD Shot and we have the NEW shot
            	console.error('WARNING: existing audition not found g.auditionSH, if ShotGalleryShot, append shot.id to audition key');
            }
            
            // "merge" by groupType
            if (audition) {
                // attr storage format by groupings
                switch (groupType) {
                    case 'Tag': // store as object properties, audition.Tag = {} to enforce unique key
                        audition.Audition[groupTypePlural] = audition.Audition[groupTypePlural] || {};
                        o = audition.Audition[groupTypePlural];
                        o[group.Label] = group.id;
                        // legacy
                        audition['tags'] = SNAPPI.util.joinObjKeys(o, ';'); // as string
                        break;
                    case 'Cluster':
                        // audition.Cluster.event=[], 
                        // audition.Cluster.chunk=[], 
                        audition.Audition[groupTypePlural] = audition.Audition[groupTypePlural] || {};
                        o = audition.Audition[groupTypePlural]; // audition.Audition.Clusters
                        // TODO: please check c, should it be updated to this.clusterGroups={}????
						// se Substitution for example
                        var c = c || {};
                        c[group.id] = c[group.id] || {};
                        c[group.id][key] = 1;
                        var type = group.Type;
                        o[type] = o[type] || {};
                        o[type][group.id] = c[group.id];
                        break;
                    case 'Substitution':
                    case 'Shot':
                        if (AuditionREFs.length == 1 && !(group.remove == true)) {
                            break; // skip if there is only one element in this shot group
                        }
                        if (group.remove) {
                        	retval = Auditions.removeAuditionFromShot(audition, group.id, null, false);
                        	audition.Audition.Shot = {};
                        } else {
	                        if (audition.Audition.SubstitutionREF != group.id) {
	//                        	audition.Audition.SubstitutionREF = group.id;
	                        	retval = Auditions.addAuditionToShot(audition, group.id, shotType, false, group.move);
	                        } else {
	                        	retval = audition.Audition.Substitutions;
	                        }
                        }
                        break;
                    case 'Set':
                    default:
                        break;
                }
            }
        }
		return retval;
    };	
    
    Auditions.unbind = function(node) { 
       	try {
			var audition = SNAPPI.Auditions.find(node.uuid);
    		audition.bindTo.splice(audition.bindTo.indexOf(node), 1);
    		delete node.uuid;
    		if (node.Thumbnail) delete node.Thumbnail.uuid;
    		delete node.dom().uuid;
    	} catch(e) {
    	}
    };
    /**
     * bind node to audition
     * NOTE: bind will update node.uuid
     * 	node.uuid == audition.hashcode(), NOT audition.Audition.id
     * @param node, Y.Node
     * @param mixed, audition object, or lookup from STRING = audition.hashcode() 
     */
    Auditions.bind = function(node, audition) {
    	if (typeof audition == 'string' && audition.length>10) {	// binary16 or char36
    		audition = Auditions.find(audition);
    	}
    	if (!audition) return false;
    	
    	audition.bindTo = audition.bindTo || [];
    	if (audition.bindTo.indexOf(node) > -1) {
    		// node is already bound to audition
    	} else {
    		Auditions.unbind(node);
    		audition.bindTo.push(node);
    	}
    	node.uuid = audition.hashcode ? audition.hashcode() : audition.id;
    	if (node.Thumbnail) node.Thumbnail.uuid = node.uuid;
    	node.dom().uuid = node.uuid;
    	node.dom().aud = audition;	// for firebug
    	return audition;
    };    
    
    Auditions.mergeGroupings = function(cc){
        var Tags = cc.Tags && cc.Tags.Tag || [];
        var Substitutions = cc.Substitutions && cc.Substitutions.Substitution || [];
        var Clusters = cc.Clusters && cc.Clusters.Cluster || [];
        var Sets = cc.Sets && cc.Sets.Set || []; // flickr
        // All: id, Label, [Type], AuditionREF
        // Sets: Total, Perpage, Pages, Page
        var groupTypes = {
            Tag: 1,
            Substitution: 1,
            Cluster: 1,
            Set: 1
        };
        var g, groupTypePlural, i, j, AuditionREFs, key, audition, o, label, group;
        for (g in groupTypes) {
            groupTypePlural = g + 's';
            if (!(cc[groupTypePlural] && cc[groupTypePlural][g])) 
                break;
            
            
            for (i in cc[groupTypePlural][g]) {
                group = cc[groupTypePlural][g][i];
                Audition.mergeGroup(g, group);
                continue;
                
                
                
//                AuditionREFs = group.AuditionREF || [];
//                AuditionREFs = _Y.Lang.isArray(AuditionREFs) ? AuditionREFs : [AuditionREFs];
//                
//                // passed by reference objects, shared by objects in the same group
//                for (j in AuditionREFs) {
//                    key = AuditionREFs[j].idref;
//                    if (audition = SNAPPI.Auditions.get(key)) {
//                        // attr storage format by groupings
//                        switch (g) {
//                            case 'Tag': // store as object properties, audition.Tag = {} to enforce unique key
//                                audition.Audition[groupTypePlural] = audition.Audition[groupTypePlural] || {};
//                                o = audition.Audition[groupTypePlural];
//                                o[group.Label] = group.id;
//                                // legacy
//                                audition['tags'] = SNAPPI.util.joinObjKeys(o, ';'); // as string
//                                break;
//                            case 'Cluster':
//                                // audition.Cluster.event=[], 
//                                // audition.Cluster.chunk=[], 
//                                audition.Audition[groupTypePlural] = audition.Audition[groupTypePlural] || {};
//                                o = audition.Audition[groupTypePlural]; // audition.Audition.Clusters
//                                var c = c || {};
//                                c[group.id] = c[group.id] || {};
//                                c[group.id][key] = 1;
//                                var type = group.Type;
//                                o[type] = o[type] || {};
//                                o[type][group.id] = c[group.id];
//                                break;
//                            case 'Substitution':
//                                if (AuditionREFs.length == 1) {
//                                    break; // skip if there is only one element in this substitution group
//                                }
//                                if (!audition.Audition[groupTypePlural]) {
//                                    var c = c || {};
//                                    c[group.id] = c[group.id] || new SNAPPI.SubstitutionGroupData();
//                                    audition.Audition[groupTypePlural] = c[group.id];
//                                }
//                                audition.Audition[groupTypePlural].add(audition);
//                                // legacy
//                                audition['substitutes'] = audition.Audition[groupTypePlural]; // by reference
//                                break;
//                            case 'Set':
//                            default:
//                                break;
//                        }
//                    }
//                }
            }
        }
    };
    

	/* 
	 * Static class
	 * @access protected
     * parse Auditions for different  Datasources
     */
	var AuditionParser = function(){};
	
	AuditionParser.snappi = {
		//        uri: '../../snappi/castingCall.xml?',
        uri: '../../snappi/castingCall.json?',
        xmlns: 'sn',
        rootNode: 'CastingCall',
        qsOverride: { //                perpage: '100',
		},
		hashcode : function(){
            return this.Audition ? this.Audition.id : this.id;
		},
        parse: function(rootNode){
            //            _xml2JsTidy(rootNode);
            var p, audition, arrAuditions, baseurl, node, results = [];
            if (!this.getImgSrcBySize) {	// initialize here
	    		this.getImgSrcBySize = SNAPPI.util.getImgSrcBySize;
	    	}
            if (rootNode.CastingCall && rootNode.CastingCall.Auditions && rootNode.CastingCall.Auditions.Audition) {
                arrAuditions = rootNode.CastingCall.Auditions.Audition;
                baseurl = rootNode.CastingCall.Auditions.Baseurl;
                // organize catalog by number of photos
                for (p in arrAuditions) {
                    node = {};
                    audition = arrAuditions[p];
                    // extract additional properties from array
                    node['Audition'] = audition;
                    node.hashcode = this.hashcode;
                    node.id = node.hashcode();
                    node.urlbase = baseurl || audition.Photo.Img.Src.Baseurl || '';
                    node.getImgSrcBySize = this.getImgSrcBySize;
                    
                    
                    // node.src = this.getImgSrcBySize(node.rootSrc, 'tn');	// deprecate
                    // node.tags = audition.Tags && audition.Tags.value || null;
                    // node.label = audition.Photo.Caption;
					try {
						var src = audition.Photo.NativePath;
						node.albumName = this.getAlbumName(node, src);
					} catch(e) {
						node.albumName = this.getAlbumName(node);
					}
                    results.push(node);
                }
            }
            return {
                results: results
            };
        },
        getAlbumName: function getAlbumName(o, src){
            var parts, name;
			src = src || o.src;
			try {
	            parts = src.replace(/\\/g, "/").split('/');
	            parts.pop(); // discard filename
	            if ((name = parts[parts.length - 1]) == '.thumbs') 
	                parts.pop();
	            if (o.urlbase) {
	                return parts.join('/');
	            }
	            else {
	                return parts[parts.length - 1];
	            }
	         } catch (ex) {
	         	return '';
	         }
        },
        getImgSrcBySize: null, // set in SNAPPI.onYready.Audition SNAPPI.util.getImgSrcBySize,
	};
	AuditionParser.AIR= {
		datasource: null,
		hashcode : function(){
            return this.Audition.id;
		},
        parse: function(rootNode){
            /*
             * this == AuditionParser_AIR
             * rootNode == e.response
             */
 console.log(" ************* AuditionParser_AIR ************");
            var p, audition, arrAuditions, baseurl, node, results = [];
            if (rootNode.CastingCall && rootNode.CastingCall.Auditions && rootNode.CastingCall.Auditions.Audition) {
                arrAuditions = rootNode.CastingCall.Auditions.Audition;
                baseurl = rootNode.CastingCall.Auditions.Baseurl;
                // organize catalog by number of photos
                for (p in arrAuditions) {
                    node = {};
                    audition = arrAuditions[p];
                    // extract additional properties from array
                    node['Audition'] = audition;
                    node.hashcode = this.hashcode;
                    node.id = node.hashcode();
                    node.urlbase = baseurl || audition.Photo.Img.Src.Baseurl || '';
                    node.rootSrc = audition.Photo.Img.Src.rootSrc;
                    // node.src = audition.Photo.Img.Src.Src;
                    // try {
                    	// node.src = audition.Photo.Img.Src.rootSrc; // should be flickr base url, size='m'
                    // } catch(e) {
                    	// alert('change AIR db call to output audition.Photo.Img.Src.rootSrc');
                    // }                    
                    node.tags = audition.Tags && audition.Tags.value || null;
                    node.albumName = this.getAlbumName(node);
                    //                        console.log(" ************* albumName=" + node.albumName);
                    results.push(node);
                }
                console.log(" ************* count=" + results.length);
            }
            return {
                results: results
            };
        },
        /**
         * getImgSrcBySize() called by Thumbnail3.js to set IMG.src
         * NOTE: this takes the unmangled/original audition.src as input for now,
         * 			but it should be changedaudition.id
         * @param {Object} or String node
         * @param String size
         */
        getImgSrcBySize: function(node, size, callback){
            var id = (node && node.id) ? node.id : node;
			var options = {create:true, autorotate:true, replace:false};
			if (callback) options.callback = callback;
//console.log(" ***** datasource.getImgSrcBySize()  id="+id+"  size="+size+"  src=" + this.datasource.getImgSrcBySize(id, size, options));		
            return this.datasource.getImgSrcBySize(id, size, options);
        },
        getAlbumName: function(node){
            var parts, name;
//console.log(" ***** datasource.getAlbumName()  src="+node.src);
			try {	
	            parts = (node.urlbase+'/'+node.src).replace(/\\/g, "/").split('/');
	            parts.pop(); // discard filename
	            if ((name = parts[parts.length - 1]) == '.thumbs') 
	                parts.pop();
	            if (node.urlbase) {
	                return parts.join('/');
	            }
	            else {
	                return parts.pop();
	            }
         	} catch (ex) {
	        	return '';
	        }
        }
	}
	AuditionParser.flickr = {
		       uri: '../../flickr/castingCall.xml?',
        xmlns: 'sn',
        rootNode: 'CastingCall',
        qsOverride: { //                perpage: '100',
		},
		hashcode : function(){
            return this.Audition ? this.Audition.id : this.id;
		},
        parse: function(rootNode){
            //            _xml2JsTidy(rootNode);
            var p, audition, arrAuditions, baseurl, proxyCacheBaseurl, node, results = [];
            if (rootNode.CastingCall && rootNode.CastingCall.Auditions && rootNode.CastingCall.Auditions.Audition) {
                arrAuditions = rootNode.CastingCall.Auditions.Audition;
                baseurl = rootNode.CastingCall.Auditions.Baseurl;
                proxyCacheBaseurl = rootNode.CastingCall.Auditions.ProxyCacheBaseurl;
                // organize catalog by number of photos
                for (p in arrAuditions) {
                    node = {};
                    audition = arrAuditions[p];
                    // extract additional properties from array
                    node['Audition'] = audition;
                    node.hashcode = this.hashcode;
                    node.id = node.hashcode();
                    node.pid = audition.Photo.id;
                    node.imageWidth = parseInt(audition.Photo.Img.Src.W);
                    node.imageHeight = parseInt(audition.Photo.Img.Src.H);
                    node.exif_DateTimeOriginal = audition.Photo.DateTaken.replace(/T/, ' ');
                    node.ts = parseInt(audition.Photo.TS);
                    node.exif_ExifImageWidth = parseInt(audition.Photo.W);
                    node.exif_ExifImageLength = parseInt(audition.Photo.H);
                    node.exif_Orientation = parseInt(audition.Photo.ExifOrientation) || null;
                    node.exif_Flash = audition.Photo.ExifFlash;
                    // node.src = audition.Photo.Img.Src.Src; // deprecate
                    // try {
                    	// node.src = audition.Photo.Img.Src.rootSrc; // should be flickr base url, size='m'
                    // } catch(e) {
                    	// alert('change flickr component to output audition.Photo.Img.Src.rootSrc');
                    // }
                    node.base64Src = proxyCacheBaseurl + audition.Photo.Img.Src.base64Src; // for manipulating external imgs
                    node.rootSrc = audition.Photo.Img.Src.rootSrc;
                    node.base64RootSrc = proxyCacheBaseurl + (audition.Photo.Img.Src.base64RootSrc || audition.Photo.Img.Src.base64Src);
                    node.rating = parseInt(audition.Photo.Fix.Rating || 0);
                    node.tags = audition.Tags && audition.Tags.value || null;
                    node.urlbase = baseurl || audition.Photo.Img.Src.Baseurl || '';
                    node['Fix'] = audition.Photo.Fix;
                    node['LayoutHint'] = audition.LayoutHint;
                    //                        node['Tags'] = audition.Tags && audition.Tags.Tag || [];
                    node.albumName = this.getAlbumName(audition.Photo.Photoset);
                    results.push(node);
                }
            }
            return {
                results: results
            };
        },
        getAlbumName: function getAlbumName(photoset){
            var account = SNAPPI.util.getFromQs('account');
            var tags = SNAPPI.util.getFromQs('tags');
            if (!account && !tags) 
                tags = 'recent photos';
            var arr = ['flickr'];
            if (account) 
                arr.push(account);
            if (tags) 
                arr.push(tags);
            return arr.join(': ');
        },
        getImgSrcBySize: function(src, size, dataElement){
            // should change suffixes if present
            switch (size) {
                case 's':
                case 'sq':
                    src = src.replace('.jpg', '_s.jpg');
                    break;
                case 't':
                case 'tn':
                    src = src.replace('.jpg', '_t.jpg');
                    break;
                case 'm':
                case 'bs':
                    src = src.replace('.jpg', '_m.jpg');
                    break;
                case 'o':
                case 'b':
                case 'br':
                    if (dataelement) {
                        src = (dataElement.rootSrc) ? dataElement.rootSrc : dataElement.src;
                    }
                    else {// just guess 'large' photo
                        src = src.replace('.jpg', '_b.jpg');
                    }
                    break;
                case 'bp':
                default:
                    // size m
                    break;
            };
            return src;
        }
	}
	AuditionParser.facebook = {
		
	}
		
})();