/**
 *
 * Copyright (c) 2009, Snaphappi.com. All rights reserved.
 *
 *
 *
 * @author Michael Lin, info@snaphappi.com
 *
 * Role
 *
 * A pre-defined slot for a photo in a layout/template
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
        arrangement: undefined,
        size: undefined
    };
    var _prefix = "snappi-audition-role-";
    var _count = 0;
    
    var Role = function(cfg){
        cfg = SNAPPI.util.mergeObj(cfg, _defaultCfg);
        
        /*
         * properties
         */
        this.id;
        this.size; // {w: 0,h: 0,};
        this.format;
        this.isLandscape;
        
        // props for positioning role
        this.position; // (x,y)
        this.arrangement;
        
        // props for determining tryout best fit
        this.area;
        this.prominence;
        this.location; // [1-9]		
        this.isCast; // boolean
        /*
         * constructor
         */
        this.init(cfg);
    };
    
    
    /*
     * Static Methods
     */
    Role.sort = {
        PROMINENCE: {
            label: 'Prominence',
            fn: SNAPPI.Sort.compare.Numeric,
            property: 'prominence',
            order: 'desc'
        }
    };
    
    /*
     * prototype functions, shared by all instances of object
     */
    Role.prototype = {
        init: function(cfg){
            this.isCast = (cfg.isCast == 1); // boolean
            this.arrangement = cfg.arrangement;
            this.size = {
                w: cfg.W,
                h: cfg.H
            };
            this.format = this.size.w / this.size.h;
            this.isLandscape = this.format >= 1; //boolean
            this.area = this.size.w * this.size.h;
            this.prominence = this.area;
        },
        makeId: function(prefix){
            prefix = prefix || _prefix;
            var id = prefix + _count++;
            return id;
        },
        hashcode: function(){
            return this.id;
        }
    };
    
    
    /*
     * SnappiCustomFitRole is a subclass of Role
     * initialize with Snappi custom fit properties
     */
    var SnappiCustomFitRole = function(rawRole, arrangement){
    	// rawArrangement.H/W/Roles/quality/way/production
    	// rawRole.H/W/X/Y
    	var cfg = {
    		arrangement: arrangement
    	};
    	cfg = Y.merge(cfg, rawRole);
        SnappiCustomFitRole.superclass.constructor.call(this, cfg);
    };
    Y.extend(SnappiCustomFitRole, Role);
    SnappiCustomFitRole.prototype.init = function(cfg){
        var R = this, A = cfg.arrangement, P = A.production;
        R.arrangement = cfg.arrangement;
        R.isCast = (cfg.isCast == 1); // boolean
        R.id = R.makeId();
        R.index = cfg.Index || null;
        R.sizeNormalized = {
            w: cfg.W,
            h: cfg.H
        };
        R.position = {
            x: cfg.X,
            y: cfg.Y
        };
        if (cfg.arrangement) {
            A = cfg.arrangement;
            R.size = {
                w: (R.sizeNormalized.w * A.w), // * cfg.Scale   ??????
                h: (R.sizeNormalized.h * A.h)
            };
            
            R.format = R.size.w / R.size.h;
            R.isLandscape = R.format >= 1; //boolean
            R.area = R.size.w * R.size.h;
            //                R.prominence = R.area;
//            R.scale = parseFloat(cfg.Scale);  // scale is deprecated(?)
            R.prominence = R.area;
        }
    }; 
    
    
    
    
    
    
    /*
     * PicasaRole is a subclass of Role
     * initialize with PicasaArrangement properties
     */
    var PicasaRole = function(cfg){
        PicasaRole.superclass.constructor.call(this, cfg);
        // additional REQUIRED init properties
        this.sizeNormalized;
    };
    Y.extend(PicasaRole, Role);
    PicasaRole.prototype.init = function(cfg){
        this.arrangement = cfg.arrangement;
        var R = this, A = this.arrangement, P = A.production;
        R.isCast = (cfg.isCast == 1); // boolean
        R.id = R.makeId();
        R.index = cfg.Index;
        R.sizeNormalized = {
            w: parseFloat(cfg.W),
            h: parseFloat(cfg.H)
        };
        R.position = {
            x: parseFloat(cfg.X),
            y: parseFloat(cfg.Y)
        };
        if (cfg.arrangement) {
            A = cfg.arrangement;
            R.size = {
                w: R.sizeNormalized.w * A.w, // * cfg.Scale   ??????
                h: R.sizeNormalized.h * A.h
            };
            //                if (P) {
            //                    R.size = {
            //                        /*
            //                         * ??? maybe we should NOT scale role, and scale in Cast instead
            //                         */
            //                        w: R.sizeNormalized.w * A.w * P.scale,
            //                        h: R.sizeNormalized.h * A.h * P.scale
            //                    }
            //                }
            //                else {
            //                    // no Production, so default to 300dpi, A.w in inches
            //                    R.size = {
            //                        w: R.sizeNormalized.w * A.w * 300,
            //                        h: R.sizeNormalized.h * A.h * 300
            //                    }
            //                }
            
            R.format = R.size.w / R.size.h;
            R.isLandscape = R.format >= 1; //boolean
            R.area = R.size.w * R.size.h;
            //                R.prominence = R.area;
            R.scale = parseFloat(cfg.Scale);
            R.prominence = parseFloat(cfg.Prominence);
        }
    }; 
    
//    function picasaRole_proto(){
//    }
//    picasaRole_proto = {
//        init: function(cfg){
//            this.arrangement = cfg.arrangement;
//            var R = this, A = this.arrangement, P = A.production;
//            
//            R.id = R.makeId();
//            R.index = cfg.Index;
//            R.sizeNormalized = {
//                w: parseFloat(cfg.W),
//                h: parseFloat(cfg.H)
//            };
//            R.position = {
//                x: parseFloat(cfg.X),
//                y: parseFloat(cfg.Y)
//            };
//            if (cfg.arrangement) {
//                A = cfg.arrangement;
//                R.size = {
//                    w: R.sizeNormalized.w * A.w, // * cfg.Scale   ??????
//                    h: R.sizeNormalized.h * A.h
//                };
//                //                if (P) {
//                //                    R.size = {
//                //                        /*
//                //                         * ??? maybe we should NOT scale role, and scale in Cast instead
//                //                         */
//                //                        w: R.sizeNormalized.w * A.w * P.scale,
//                //                        h: R.sizeNormalized.h * A.h * P.scale
//                //                    }
//                //                }
//                //                else {
//                //                    // no Production, so default to 300dpi, A.w in inches
//                //                    R.size = {
//                //                        w: R.sizeNormalized.w * A.w * 300,
//                //                        h: R.sizeNormalized.h * A.h * 300
//                //                    }
//                //                }
//                
//                R.format = R.size.w / R.size.h;
//                R.isLandscape = R.format >= 1; //boolean
//                R.area = R.size.w * R.size.h;
//                //                R.prominence = R.area;
//                R.scale = parseFloat(cfg.Scale);
//                R.prominence = parseFloat(cfg.Prominence);
//            }
//        }
//    };
////    YAHOO.lang.augmentProto(PicasaRole, picasaRole_proto, true);
//	PicasaRole.prototype.init = picasaRole_proto.init;
    
    /*
     * publish
     */
    SNAPPI.PM.Role = Role;
    SNAPPI.PM.PicasaRole = PicasaRole;
    SNAPPI.PM.SnappiCustomFitRole = SnappiCustomFitRole;
    
})();

