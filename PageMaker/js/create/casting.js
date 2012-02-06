/**
 *
 * Copyright (c) 2009, Snaphappi.com. All rights reserved.
 *
 *
 *
 * @author Michael Lin, info@snaphappi.com
 *
 * Casting
 *
 * A Photo assigned to a Role in a Production, (i.e. a spot in a template)
 *
 *
 */
(function(){
    /*
     * shorthand
     */
    var PM = SNAPPI.namespace('SNAPPI.PM');
    var Y = PM.Y;
    /*
     * protected
     */
    var _defaultCfg = {
		CASTING_CHUNKSIZE: 36
	};
	var localCfg;
    /*
     * check to see if format is close enough to match Role
     * Hardcoded FORMAT_WITHIN = w/h within 25% of Role format
     */
    var _FORMAT_WITHIN = 0.2;
    
    
    Casting = function(cfg){
        cfg = SNAPPI.util.mergeObj(cfg, _defaultCfg);
        
        /*
         * properties
         */
        this.id;
        this.audition;
        this.role;
        //		this.production;
        //		this.arrangement;
        
        this.minSize = {
            w: 0,
            h: 0
        };
        this.renderSize = {
            w: 0,
            h: 0
        };
        this.crop = {
            x: 0,
            y: 0,
            w: 0,
            h: 0,
            focus: {
                a: 0,
                b: 0
            },
            minSize: {
                x: 0,
                y: 0
            },
            scale2Max: undefined,
            dpi: 0,
            isValid: false
        };
        this.location;
        this.scaleRole2Production;
        this.cropVariance = _FORMAT_WITHIN;
        
        /*
         * methods which access private properties
         */
        this.getP = function(){
            return p;
        };
        this.setP = function(s){
            p = s;
        };
        
        /*
         * constructor
         */
        this.init();
    };
    
    
    /*
     * Static Methods
     */
    Casting.SimpleCast = function(Pr, cfg){
        if (Pr.arrangement == undefined) {
        	/*
        	 * get arrangement from catalog of fixed templates
        	 */
            var A = Pr.catalog.getArrangement({
                perpage: cfg.roleCount
            });
            Pr.setArrangement(A);
        }
        var myScene = cfg.scene ||
        {
            arrangement: Pr.arrangement,
            cast: []
        };
        
        if (cfg && cfg.tryout) 
            Pr.tryout = cfg.tryout;
        var auditionSH = cfg.pmAuditionSH || (cfg.tryout && cfg.tryout.pmAuditionSH) || Pr.tryout.pmAuditionSH;
        switch (cfg.sort) {
            case 'name':
                auditionSH.sort([SNAPPI.PM.Audition.sort.NAME]);
                break;
            case 'rating':
            default:
                auditionSH.sort([SNAPPI.PM.Audition.sort.RATING]);
                break;
        }
        
        // not sure if we should check cfg.useHints here or 
        // higher up is the stack
        if (cfg.anyMatch == undefined) {
            if (cfg.useHints) {
                localCfg = Y.merge(cfg, {
                    anyMatch: false
                });
            }
            else {
                localCfg = Y.merge(cfg, {
                    anyMatch: true
                });
            }
        }
        
        if (cfg.hideRepeats===false) {
        	// reset isCast locally
        	auditionSH.each(function(a){
        		a.isCast = false;
        	});
        }
        
        
        var noMoreRoles = true, noMoreAuditions;
        var R = Pr.arrangement.roleSH.setFocus(0);
        var Aud, cropOk;
        Aud = auditionSH.setFocus(0);
        while (R && Aud) {
        
            if (R.isCast) {
                R = Pr.arrangement.roleSH.next();
                continue;
            }
            
            var c = new Casting();
            if (R.suggestedPhotoId) Aud = auditionSH.get(R.suggestedPhotoId);
            c.audition = Aud;
            c.role = R;
            c.setMinSize();
            cropOk = false;
            while (
            	c.audition.parsedAudition.isCast			// cast in saved Scene 
            	|| (c.audition.isCast)	// cast in current Scene
				|| (cropOk = c.setCropSize(cfg)) == false) 	// crop won't fit role
            	
			{
				if (R.suggestedPhotoId) {
					noMoreRoles = true;	// using suggested photos, don't iterate
					cropOk = true;
					break; 				// done when we are done
				}
                c.audition = auditionSH.next();
                if (c.audition == null || c.audition == false ) {
                    break; // no more auditions                        
                }
            }
            if (cropOk) {		// set by c.setCropSize(cfg);
                //                    Pr.addToCast(c);
                c.role.isCast = true;
                c.audition.isCast = true;
                myScene.cast.push(c);
            }
            else { // no matches, skip to the next role
                noMoreRoles = false;
                if (noMoreAuditions) 
                    break;
            }
            c = undefined;
            R = Pr.arrangement.roleSH.next();
            Aud = auditionSH.setFocus(0);
        }
        
        var done = noMoreRoles ;
        if (!done) {
            noMoreAuditions = true;
            Aud = auditionSH.setFocus(0);
            do {
                if (Aud.parsedAudition.isCast != true) {
                    noMoreAuditions = false;
                }
                Aud = auditionSH.next();
            }
            while (Aud && noMoreAuditions);
            /*
             * recycle photos once every photo has been cast
             */
            if (noMoreAuditions) {
                // should clear auditionSH
                Pr.tryout.clearCast(Pr.cast);
            }
        }
        myScene.done = done;
        return myScene;
    };
    
    Casting.TwoPassSimpleCast = function(Pr, cfg){
        var auditionSH = cfg.pmAuditionSH || (cfg.tryout && cfg.tryout.pmAuditionSH) || Pr.tryout.pmAuditionSH;
        auditionSH.sort([SNAPPI.PM.Audition.sort.RATING]); // default sort is by Rating
        localCfg = Y.merge(cfg, {
            anyMatch: false,
            sort: 'rating'
        });
        
        // first pass
        var scene = Casting.SimpleCast(Pr, localCfg);
        
        // if we are not done Casting, do 2nd pass
        if (!scene.done) {
            localCfg.anyMatch = true;
            localCfg.scene = scene;
            // still sort by Rating..56
            scene = Casting.SimpleCast(Pr, localCfg);
        }
        return scene;
    };
    /*
     * uses SimpleCast with custom arrangement from server
     * 		SYNCHRONOUS XHR
     */
    Casting.CustomFitArrangement = function (Pr, cfg) {
    	// this is a syncronous call
    	var A = SNAPPI.PM.Catalog.getCustomFitArrangement.call(Pr, Pr, cfg.roleCount, {
    		success:function(A){
    			var check;
    		},
    		failure:function(o) {
    			// timeout
    			var check;
    			alert("Sorry, there was a problem building this Story. Please try again with a different mix of Snaps");
    		}
    	});
		var check;
		if (Pr.arrangement) {
			var scene = Casting.ChronoCast(Pr, cfg);
	    	return scene;
		} else return null;		
    };

    /*
     * Cast by chunk chronologically
     */
    Casting.ChronoCast = function(Pr, cfg){
        if (cfg.hideRepeats == undefined) cfg.hideRepeats = Y.one('.hide-repeats .cb').get('checked');
        
        /*
         * need to use mean-shift to determine optimal chuncksize
         * should be aware of hide repeats
         */
        var chunkSize = _defaultCfg.CASTING_CHUNKSIZE;
        var start = 0; // i;
        //            var chunk = Pr.tryout.pmAuditionSH.slice(i, i + chunkSize);
        var chunk = Pr.tryout.getChunk(start, chunkSize, cfg.hideRepeats);
        if (chunk.count() == 0) {
            // recycle all photos
            Pr.tryout.clearCast();
            return null;
        }
        /*
         *  pick Arrangement from Catalog
         * 		- skip for dynamic catalogs, i.e. SnappiCustomFit;
         */
        var pickArr = Pr.catalog.arrangements.length;	
        if (pickArr && Pr.arrangement == undefined) {
            /*
             * find matching arrangement from catalog 
             */
        	Pr.arrangement = Pr.catalog.getArrangementByScore(chunk, cfg.roleCount);
            if (Pr.arrangement == null) {
                // recycle all photos
                Pr.tryout.clearCast();
                return null;
            }
            Pr.setArrangement(Pr.arrangement);
        }
        
        // SimpleCast that chunk
        var localCfg = Y.merge(cfg, {
            auditionSH: chunk,
            anyMatch: !cfg.useHints,
            sort: 'rating'
        });
        
        var scene = Casting.SimpleCast(Pr, localCfg);

        // if we are not done Casting, do 2nd pass
        if (!scene.done) {
            localCfg.anyMatch = true;
            localCfg.scene = scene;
            // still sort by Rating..56
            scene = Casting.SimpleCast(Pr, localCfg);
        }        
        
        // mark chunk as cast locally
        // will be marked globally (at .parsedAudition) when saved 
        // so it will not be reused
        var stop = chunk.count();
        for (var i = 0; i < stop; i++) {
            chunk.get(i).isCast = true;
            //                chunk.get(i).parsedAudition.isCast = true;
        }
        return scene;
    };
    
    
    /*
     * prototype functions, shared by all instances of object
     */
    Casting.prototype = {
        init: function(){
        },
        setProductionScale: function(){
        
            var R = this.role;
            var Arr = this.role.arrangement;
            var P = this.role.arrangement.production;
            
            if (P.scale) 
                this.scaleRole2Production = P.scale;
            else {
                if (P.renderSize.w) {
                    // P.renderSize.w to fix renderSize
                    this.scaleRole2Production = (P.renderSize.w * P.renderUnitsPerInch * P.minDpi) / Arr.w;
                }
                else 
                    if (P.renderSize.h) {
                        // use P.renderSize.h
                        this.scaleRole2Production = (P.renderSize.h * P.renderUnitsPerInch * P.minDpi) / Arr.h;
                    }
                    else {
                        // error, Production size not available.
                        alert("error: Production size not available");
                    }
            }
            return this.scaleRole2Production;
        },
        /*
         * the minimum crop size in px to meet Production.minDpi requirements
         */
        setMinSize: function(){
            var R = this.role;
            var Pr = this.role.arrangement.production;
            if (!this.scaleRole2Production) 
                this.setProductionScale();
            this.minSize.w = R.size.w * Pr.scale;
            this.minSize.h = this.minSize.w / R.format;
            
            // size in Production rendered units, i.e. inches
            this.renderSize.w = this.minSize.w / Pr.minDpi;
            this.renderSize.h = this.minSize.h / Pr.minDpi;
            
            
            // also set position of Role in Production
            this.position = {
                x: R.position.x * R.arrangement.w * Pr.scale,
                y: R.position.y * R.arrangement.h * Pr.scale
            };
            
        },
        /**
         * find best cropsize for audition/role, or false
         * @param cfg
         * 		cfg.isRehearsal - uses Src.previewSrc if true
         * @return
         */
        setCropSize: function(cfg){
        	if (cfg.isRehearsal) SNAPPI.PM.Audition.usePreview(this.audition);
        	
            // find best crop
            var crops, bestCrop, bestMatch, formatDiff, cropVariance, anyMatch;
            var Aud = this.audition;
            var ABS = Math.abs, MIN = Math.min;
            
            anyMatch = cfg && cfg.anyMatch || false;
            crops = (cfg && cfg.crops) ? cfg.crops : (Aud.crops || null);
            
            
            /*
             * find the closest crop, or use fullframe if no crop
             */
            bestMatch = {
                label: 'fullframe',
                format: (Aud.size.w / Aud.size.h)
            };
            /*
             * only check existing crops if we are using hints
             * ???????????????? this is supposed to check for matching format
             */
            if (cfg.useHints) {
                for (var cr in crops) {
                    /*
                     * check if we are in render=a mode and showing CROPPED photos
                     */
                    var render = SNAPPI.util.getFromQs('render');
                    //                if (render == 'a') {
                    //                    // assume FIRST crop is full frame
                    //                    bestMatch['label'] = "alreadyCropped";
                    //                    crops[bestMatch['label']] = {
                    //                        x: 0,
                    //                        y: 0,
                    //                        w: crops[cr].w,
                    //                        h: crops[cr].h,
                    //                    };
                    //                    break;
                    //                }
                    
                    // check if the crop is close enough to the full frame format to use instead
                    cropVariance = crops[cr].w / crops[cr].h / bestMatch.format - 1;
                    if (bestMatch['label'] == 'fullframe' && Math.abs(cropVariance) < this.cropVariance) {
                        bestMatch['label'] = cr;
                        bestMatch.format = crops[bestMatch['label']].w / crops[bestMatch['label']].h;
                    }
                    else {
                        if (ABS((crops[cr].w / crops[cr].h) - this.role.format) <= Math.abs(bestMatch.format - this.role.format)) {
                            bestMatch['label'] = cr;
                            bestMatch.format = crops[bestMatch['label']].w / crops[bestMatch['label']].h;
                        };
                                            }
                }
            }
            
            
            bestCrop = (bestMatch['label'] !== "fullframe") ? crops[bestMatch['label']] : {
                x: 0,
                y: 0,
                w: Aud.size.w,
                h: Aud.size.h
            };
            if (bestMatch['label'] === "fullframe") 
                bestCrop.focusCenter = Aud.focusCenter;
            
            // compare bestMatch format with role format
            cropVariance = Math.abs((bestCrop.w / bestCrop.h) / this.role.format - 1);
            if (!anyMatch && (cropVariance > this.cropVariance)) {
                /*
                 * check to see if format is close enough to match
                 * Hardcoded FORMAT_WITHIN = w/h within 15% of Role format
                 */
                this.crop.isValid = false;
                return this.crop.isValid;
            }
            
            /*
             * for render=a, resize audition to crop dimensions
             */
            if (bestMatch['label'] == "alreadyCropped") 
                Aud.size = {
                    w: bestCrop.w,
                    h: bestCrop.h
                };
            
            
            /*
             * set the focus of the crop
             */
            if (bestCrop.focusCenter) {
                var scale = Math.max(Aud.size.w, Aud.size.h) / bestCrop.focusCenter.Scale;
                this.crop.focus = {
                    a: bestCrop.focusCenter.X * scale,
                    b: bestCrop.focusCenter.Y * scale
                };
            }
            else {
                this.crop.focus = {
                    a: bestCrop.x + bestCrop.w / 2,
                    b: bestCrop.y + bestCrop.h / 2
                };
            }
            
            /*
             * approximate the best crop rect, by constraining size and focus.
             * we'll let the user adjust
             */
            var minHalfW, minHalfH;
            var focusInMiddle = 1.5;
            // scale factor to keep focus in middle third of crop
            // get min distance from focus to photo edge
            minHalfW = MIN(this.crop.focus.a, MIN((Aud.size.w - this.crop.focus.a), MIN((this.crop.focus.b * this.role.format), ((Aud.size.h - this.crop.focus.b) * this.role.format))));
            minHalfH = minHalfW / this.role.format;
            
            
            /*
             * get min crop size of photo, keeping focus in middle third
             */
            this.crop.minSize.w = MIN(this.minSize.w, 2 * focusInMiddle * minHalfW);
            this.crop.minSize.h = this.crop.minSize.w / this.role.format;
            
            
            /*
             * get scale for max crop size, keeping focus in middle third
             */
            if (this.crop.minSize.w >= this.minSize.w) {
                // crop satisfies minDpi for this.production, 
                
                
                // see if we have an close crop match within 10%;
                if (cropVariance < 0.10) {
                    // exact match, scale cropRect to match exact crop, will check size later
                    this.crop.scale2Max = MIN(bestCrop.w, Aud.size.w) / (this.crop.minSize.w);
                }
                
                // find scale2Max, the min ratio for "Aud.radius/Cr.radius"
                if (this.crop.scale2Max === undefined || this.crop.scale2Max < 1) {
                    /*
                     * // get scale to max crop size, keeping focus in middle third
                     * DOES NOT AJUST for middle third!!  needs to be check for accuracy
                     */
                    this.crop.scale2Max = MIN(this.crop.focus.a / (this.crop.minSize.w / 2), MIN((Aud.size.w - this.crop.focus.a) / (this.crop.minSize.w / 2), MIN(this.crop.focus.b / (this.crop.minSize.h / 2), (Aud.size.h - this.crop.focus.b) / (this.crop.minSize.h / 2))));
                    
                }
                // set crop rect to max crop;
                this.crop.isValid = this.setCropRect(this.crop.scale2Max);
            }
            else {
                // photo does not meet this.minSize requirements
                this.crop.dpi = this.crop.minSize.w / this.renderSize.w;
                this.crop.isValid = false;
            }
            return this.crop.isValid;
        },
        /*
         * set Crop Rect to scale*crop.minSize, default to scale = crop.scale2Max
         */
        setCropRect: function(scale){
            scale = scale || this.crop.scale2Max || 1;
            var Aud = this.audition;
            var x, y, w, h;
            // scale to a valid crop
            w = this.crop.minSize.w * scale;
            h = this.crop.minSize.h * scale;
            
            // determine crop rect, make crop is positioned inside photo
            var x = (this.crop.focus.a - w / 2);
            x = (x < 0) ? 0 : ((this.crop.focus.a + w / 2 > Aud.size.w) ? (Aud.size.w - w) : x);
            var y = (this.crop.focus.b - h / 2);
            y = (y < 0) ? 0 : ((this.crop.focus.b + h / 2 > Aud.size.h) ? (Aud.size.h - h) : y);
            this.crop.x = x;
            this.crop.y = y;
            this.crop.w = w;
            this.crop.h = h;
            this.crop.dpi = this.crop.w / this.renderSize.w;
            this.crop.maxDim = Math.max(Aud.size.w, Aud.size.h);
            return (this.crop.w >= this.minSize.w);
        }
    };
    
    /*
     * publish
     */
    SNAPPI.PM.Casting = Casting;
    
    /*
     * Static Methods
     */
    Casting.test = function(){
        var PM = SNAPPI.PM;
        var P = {
            renderSize: {
                w: 6.5 // only 1 value, w or h, required
            },
            renderUnitsPerInch: 1,
            minDpi: 300
        };
        var Arr = {
            w: 6, // use format?
            h: 4,
            production: P
        };
        var R = {
            sizeNormalized: { // normalized
                w: 0.323529,
                h: 0.333333
            },
            scale: undefined,
            format: undefined,
            arrangement: Arr
        };
        R.size = {
            w: R.sizeNormalized.w * Arr.w,
            h: R.sizeNormalized.h * Arr.h
        };
        R.format = R.size.w / R.size.h;
        var Aud = {
            size: {
                w: 3000,
                h: 2000
            }
        };
        var c = new Casting();
        c.role = R;
        c.audition = Aud;
        c.setProductionScale();
        c.setMinSize();
        var crops = {
            "1:1": {
                x: 50,
                y: 100,
                w: 1000,
                h: 1000
            },
            "5:7": {
                x: 20,
                y: 20,
                w: 500,
                h: 700
            },
            "6:4": {
                x: 20,
                y: 20,
                w: 1800,
                h: 1200
            }
        };
        c.setCropSize(crops);
    };
})();

