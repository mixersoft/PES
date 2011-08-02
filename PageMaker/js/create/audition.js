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
    var PM = SNAPPI.namespace('SNAPPI.PM');
    var Y = PM.Y;
    
    /*
     * protected
     */
    var _defaultCfg = {
        defaultSort: []
    };
    var _prefix = "snappi-pm-audition-";
    var _count = 0;
    
    Audition = function(cfg){
        cfg = SNAPPI.util.mergeObj(cfg, _defaultCfg);
        
        /*
         * properties
         */
        this.src;
        
        // arrangement hints
        this.location;
        this.focus; // {a:0,b:0};		// (a,b) coordinates of "center" of photo
        // ???: is the focus dependent on the crop???
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
    
    Audition.usePreview = function(o){
    	try {
    		var photo = o.parsedAudition.Audition.Photo;
    		// preview orientation, NOT exifOrientation
    		var orientation = photo.Img.Src.Orientation,
    			rotate = photo.Fix.Rotate || 1;
    		o.orientation = SNAPPI.PM.util.orientationSum(orientation, rotate);
//    		o.size = {W:photo.Img.Src.W, H:photo.Img.Src.H};  // use original or preview???
    		/*
    		 * use o.orientation to rotate o.size, o.focusCenter to orientation=1
    		 */
    		if (o.orientation > 4) {
    			// we need to rotate o.size, o.focusCenter from "up" 
    			// to match the orientation of the "rotated" preview image
				o.size = SNAPPI.PM.util.rotateDimensions(o.size, o.orientation);
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
            	if ('use SNAPPI.Audition._auditionSH auditions') {
            		/*
            		 * use 2nd parse from SNAPPI.Auditions. 
            		 * DELTA: bindTo = [nodes]
            		 *  - use o.parsedAudition.urlbase + o.parsedAudition.src to get src
            		 */
            		this.id = o.id;
            		this.parsedAudition = o;
            		
            		// expose for Tryout.sort()
	                this.rating = o.rating;
	                this.exif_DateTimeOriginal = o.exif_DateTimeOriginal;
					this.label = o.label;            		
					
					this.src = o.urlbase + o.src; // deprecate
            	} else {
	            	/*
	            	 * DEPRECATE
	            	 * old PM.audition, 
	            	 * 	= bindTo is an audition, not an array of Nodes
	            	 */
//	                this.bindTo = o; // this bindTo should still reference Nodes
//	                /*
//	                 * ??? by copy or reference
//	                 * use reference for now
//	                 */
//	                this.id = (o.id) ? o.id : this.makeId();
//	                this.src = o.urlbase + o.src;
//	                if (o.base64Src) 
//	                    this.base64Src = o.base64Src;
//	                
//	                this.rating = o.rating;
//	                this.tags = o.tags;
//	                this.exif_DateTimeOriginal = o.exif_DateTimeOriginal;
//					this.label = o.label;
            	}
				
				// find final exifOrientation
				var orientation, rotate;
				try {
					orientation = o.Audition.Photo.ExifOrientation || 1;
				} catch (e) {
					orientation = 1;
				}
				try {
					rotate = o.Audition.Photo.Fix.Rotate || 1;
				} catch (e) {
					rotate = 1;
				}
                this.orientation = SNAPPI.PM.util.orientationSum(orientation, rotate);
				
				// we assume previews are already auto-rotated. just apply rotate
                if (cfg.previewOnly) { // previewOnly is deprecated
                    this.size = {
                        w: o.imageWidth,
                        h: o.imageHeight
                    };
					this.size = SNAPPI.PM.util.rotateDimensions(this.size, rotate);
                }
                else {
                	try {
//	                	if (o.imageWidth > o.imageHeight && o.exif_ExifImageWidth < o.exif_ExifImageLength) {
//	                		// if exif dimensions do NOT match image, then rotate to match
//	                		this.size = {
//	    	                        w: o.exif_ExifImageLength,
//	    	                        h: o.exif_ExifImageWidth
//	    	                };	                		
//	                		
//	                	} else {
		                    this.size = {
		                        w: o.exif_ExifImageWidth,
		                        h: o.exif_ExifImageLength
		                    };
//	                	}
                	} catch (e) {
                		// exif dimensions undefined
                		this.size = {
                                w: o.imageWidth,
                                h: o.imageHeight
                        };
                	}
                	// exif sizes, rotated to orientation=1
					this.size = SNAPPI.PM.util.rotateDimensions(this.size, this.orientation);
   	            }
                // rotate focusCenter, if necessary
                this.focusCenter = o.Audition.LayoutHint.FocusCenter;
                if (this.orientation > 4) {
					var tempX = this.focusCenter.X;
					this.focusCenter.X = this.focusCenter.Y; 
					this.focusCenter.Y = tempX; 
                }
                // update orientation AFTER adjustments
                this.orientation = 1; // original still at o.Audition.Photo.ExifOrientation
                
                this.crops = this.getScaledCrops(o.Audition.Photo.Fix.Crops);
                // convert crop from JSON to rect format, and scale to pixel dimensions
                //                this.crops = this.parseJsonCrop();
                this.format = this.size.w / this.size.h;
            }
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
    
    
    
    /*
     * publish
     */
    SNAPPI.PM.Audition = Audition;
    
    
    
})();

