/**
 *
 * Copyright (c) 2009, Snaphappi.com. All rights reserved.
 *
 *
 *
 * @author Michael Lin, info@snaphappi.com
 *
 * Audition
 *
 * A photo that might be put in a Production
 *
 */
(function(){
	/*
     * shorthand
     */
	var _Y = null;
	var Plugin = null;
	var PM = SNAPPI.namespace('SNAPPI.PM');	// Yready init
	PM.onYready.Audition = function(Y){
		if (_Y === null) _Y = Y;
		
		/*
	     * publish
	     */
	    SNAPPI.PM.Audition = Audition;
	} 
    
    /*
     * protected
     */
    var _defaultCfg = {
        defaultSort: []
    };
    var _prefix = "snappi-pm-audition-";
    var _count = 0;
    
    Audition = function(cfg){
        cfg = _Y.merge(_defaultCfg, cfg);
        
        /*
         * properties
         */
        this.src; // this.src should be the complete (relative) uri
        
        // arrangement hints
        this.location;
        this.focus; // {a:0,b:0};		// (a,b) coordinates of "center" of photo
        this.crops;
        this.tags;
        this.rating;
        this.exif_DateTimeOriginal;
        
        // casting/crop hints
        this.orientation;
        this.size; // {w:0,h:0};
        this.format;
		this.label;
        
        this.isCast; // boolean
        /*
         * initializer
         */
        this.init(cfg);
    };
    
    /*
     * Static Methods
     */
    Audition.sort = {
        RATING: {
            label: 'Rating',
            fn: SNAPPI.Sort.compare.Numeric,
            property: 'rating',
            order: 'desc'
        },
        TIME: {
            label: 'Date Taken',
            fn: SNAPPI.Sort.compare.Time,
            property: 'exif_DateTimeOriginal',
            order: 'asc'
        },
        NAME: {
            label: 'Name',
			property: 'label',
            fn: SNAPPI.Sort.compare.Alpha,
            order: 'asc'
        }
    };
    // deprecate. preview is the default. see PM.Audition.getAsOriginal
    Audition.usePreview = function(o){
    	// o instance of PM.Audition, NOT SNAPPI.Audition
    	try {
    		var photo = o.parsedAudition.Audition.Photo;
    		// preview orientation, NOT exifOrientation
   			o.size = {W:photo.Img.Src.W, H:photo.Img.Src.H};  // use original or preview???
    		/*
    		 * use o.orientation to rotate o.size, o.focusCenter to orientation=1
    		 */
    		if (o.orientation > 4) {
    			// we need to rotate o.size, o.focusCenter from "up" 
    			// to match the orientation of the "rotated" preview image
				o.size = PM.util.rotateDimensions(o.size, o.orientation);
				var tempX = this.focusCenter.X;
				o.focusCenter.X = o.focusCenter.Y; 
				o.focusCenter.Y = tempX; 
    		}
    		o.orientation = 1;
    	} catch (e) {
    	}
    };    
    
    
    /*
     * prototype functions, shared by all instances of object
     */
    Audition.prototype = {
        init: function(cfg){
            this.isCast = false;
            var o = cfg.dataElement;
            if (o) {
        		/*	PM.Audition, audition attr for PageMaker only
        		 *  - also see PM.Audition.parsedAudition instanceof SNAPPI.Audition
        		 *  - use src == o.parsedAudition.urlbase + o.parsedAudition.rootSrc
        		 */
        		this.id = o.id;
        		this.parsedAudition = o;
        		
        		// expose for Tryout.sort()
                this.rating = parseInt(o.rating);	// copy. allow local changes in Story 
                this.exif_DateTimeOriginal = o.exif_DateTimeOriginal;
				this.label = o.label;            		
				
				// o.orientation = SNAPPI.Auditions.orientationSum(o.root_Orientation, o.rotate);
				
				// this.size: corrected value. this.size AFTER applying appropriate orientation value
                if ('use-preview-as-default') { 	// default is preview
                	// o.orientation = SNAPPI.Auditions.orientationSum(o.root_Orientation, o.rotate);
                	this.orientation = o.orientation;	// bp~ orientation
                    this.size = {		
                        w: o.imageWidth,
                        h: o.imageHeight
                    };
					this.size = PM.util.rotateDimensions(this.size, this.orientation );
					if (Math.max(o.imageWidth, o.imageHeight)>640) {
                		console.warn('PM.Audition: warning, rootSrc is still original size from JS upload');	
                		this.size = PM.util.scale2Preview(this.size);
                	}
                	// this.src should be the complete (relative) uri
                	this.src = o.base64Src ? o.base64Src : (o.urlbase + o.rootSrc);
                	this.src = o.getImgSrcBySize(this.src, 'bp');
                	
    				// rotate focusCenter, if necessary
	                this.focusCenter = PM.util.rotateDimensions(o.Audition.LayoutHint.FocusCenter, this.orientation);
	                this.focusCenter = PM.util.scale2Preview(this.focusCenter);
                }
                // update orientation AFTER rotateDimensions() adjustments
                this.orientation = 1; // original still at o.Audition.Photo.ExifOrientation
                
                this.crops = this.getScaledCrops(o.Audition.Photo.Fix.Crops);
                // convert crop from JSON to rect format, and scale to pixel dimensions
                //                this.crops = this.parseJsonCrop();
                this.format = this.size.w / this.size.h;
            }
        },
        getAsOriginal: function(pm_Aud){
        	pm_Aud = pm_Aud || this;
	        if (Math.max(pm_Aud.imageWidth, pm_Aud.imageHeight)<=640) {
        		console.warn('PM.Audition: warning, rootSrc is still preview size. fetch original from AIR???');	
        	}
        	var o = pm_Aud.parsedAudition;
        	pm_Aud.orientation = PM.util.orientationSum(o.exif_Orientation, o.rotate);
        	try {
                pm_Aud.size = {
                    w: o.exif_ExifImageWidth,
                    h: o.exif_ExifImageLength
                };
        	} catch (e) {
        		// exif dimensions undefined
        		pm_Aud.size = {
                        w: o.imageWidth,
                        h: o.imageHeight
                };
        	}
        	// exif sizes, rotated to orientation=1
			pm_Aud.size = PM.util.rotateDimensions(pm_Aud.size, pm_Aud.orientation);
			pm_Aud.crops = pm_Aud.getScaledCrops(o.Audition.Photo.Fix.Crops);
			pm_Aud.src = o.base64RootSrc || o.base64Src || (o.urlbase + o.rootSrc);
			pm_Aud.focusCenter = PM.util.rotateDimensions(o.Audition.LayoutHint.FocusCenter, this.orientation);
			pm_Aud.crops = this.getScaledCrops(o.Audition.Photo.Fix.Crops);
			return pm_Aud;
        },
        makeId: function(prefix){
            prefix = prefix || _prefix;
            var id = prefix + _count++;
            return id;
        },
        hashcode: function(){
            /*
             * NOTE: If we want to mix photos from different Datasources,
             * we should use this.src as the hashcode
             */
            return this.id;
        },
        // Note: converts from Array to Object
        getScaledCrops: function(crops, audition){
            audition = audition || this;
            var arrCrops = (crops && SNAPPI.util.getClass(crops) != 'Array') ? new Array(crops.Crop) : (crops || []);
            var cropRects = {};
            for (var p = 0; p < arrCrops.length; p++) {
                var crop = arrCrops[p];
                var scale = Math.max(audition.size.w, audition.size.h) / crop.Rect.Scale;
                cropRects[crop.Label] = {
                    x: crop.Rect.X * scale, // scale crop from 640
                    y: crop.Rect.Y * scale,
                    w: crop.Rect.W * scale,
                    h: crop.Rect.H * scale
                };
            }
            return cropRects;
        },
        
        // deprecated, use xml instead
        parseJsonCrop: function(audition){
            audition = audition || this;
            if (audition.size && audition.size.w && audition.size.h) {
                //   "{"cr":{"_5x7":"41,0,599,427"}, "s":"640"}"
                try {
                    var cropScale, jsonCrop = YAHOO.lang.JSON.parse(audition.crops);
                    cropScale = jsonCrop["s"] || 640; // json crops all scaled to 640px
                    scale = Math.max(audition.size.w, audition.size.h) / cropScale;
                    if (jsonCrop["cr"]) {
                        var cropRect = {};
                        for (var p in jsonCrop["cr"]) {
                            var rect = jsonCrop["cr"][p].split(',');
                            cropRect[p] = {
                                x: rect[0] * scale, // scale crop from 640
                                y: rect[1] * scale,
                                w: rect[2] * scale,
                                h: rect[3] * scale
                            };
                        }
                        return cropRect;
                    }
                } 
                catch (err) {
                    // do nothing
                }
            }
            return audition.crops;
        }
    };
    
    
})();

