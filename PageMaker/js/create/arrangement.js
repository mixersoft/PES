/**
 *
 * Copyright (c) 2009, Snaphappi.com. All rights reserved.
 *
 *
 * @author Michael Lin, info@snaphappi.com
 *
 * Arrangement
 *
 * A layout/template of one or more photos, arranged in Roles
 *
 *
 */
(function(){
	/*
     * shorthand
     */
    // var Y = PM.Y;
	var _Y = null;
	var Plugin = null;
	var PM = SNAPPI.namespace('SNAPPI.PM');
	// Yready init
	PM.onYready.Arrangement = function(Y){
		if (_Y === null) _Y = Y;
		_Y.extend(PicasaArrangement, Arrangement);
    
	    /*
	     * publish to namespace
	     */
	    SNAPPI.PM.Arrangement = Arrangement;
	    SNAPPI.PM.PicasaArrangement = PicasaArrangement;
	} 
    
    //    SNAPPI._dsIO.sendRequest('../../matchmaker/catalog.xml?from=arrangement', {});
    
    /*
     * protected
     */
    var _defaultCfg = {};
    var _catalog = {};
    
    /*
     * Class Arrangement
     */
    var Arrangement = function(cfg){
        this.cfg = SNAPPI.util.mergeObj(cfg, _defaultCfg);
        /*
         * properties
         */
        this.id;
        this.spacing;
        this.orientation;
        this.init(this.cfg);
    };
    
    /*
     * Static Functions
     */
	/*
	 * Get arrangement from the Catalog of arrangements
	 */
    Arrangement.initArrangementFromCatalog = function(oParsedResponse){
    
        // initialize Arrangement properties from xml
        var A = this;
        var n = 0;
        var resp = oParsedResponse.results[cfg.arrangement.perpage][n]; // note: this should is an array of pages
        A.w = parseFloat(resp.W);
        A.h = parseFloat(resp.H);
        A.format = A.w / A.h;
        // initProduction must be called AFTER A.format is set
        A.initProduction();
        
        A.orientation = (resp.W > resp.H) ? 'landscape' : 'portrait'; // ??: what do we want here?
        // parse Roles
        if (A.roleSH === undefined) 
            A.roleSH = new SNAPPI.SortedHash();
        for (var i in resp.roles) {
            var o = resp.roles[i];
            if (o !== undefined) {
                o.arrangement = A;
                var r = new PM.PicasaRole(o);
                A.roleSH.add(r);
            }
        }
        //            A.sort();
        return;
    };
    
    
    /*
     * prototype functions, shared by all instances of object
     */
    Arrangement.prototype = {
        init: function(cfg){
            // initialize properties
            this.production = cfg.production;
            if (this.roleSH) 
                this.roleSH.clear();
            else 
                this.roleSH = new SNAPPI.SortedHash();
            this.roleSH.setDefaultSort([SNAPPI.PM.Role.sort.PROMINENCE]);
            this.onCatalogLoaded = cfg.onSuccess;
            //            this.loadCatalog(cfg);
        },
        loadCatalog: function(cfg){
            if (_catalog[cfg.arrangement.catalog.format] !== undefined) {
                // skip. catalog already loaded
                //                this.onCatalogLoaded();
                this.initCatalogFromParsedResponse(_catalog[this.cfg.arrangement.catalog.format]);
                this.cfg.onSuccess();
            }
            else {
                // parse XML from DS
                
                var myCallback = {
                    success: function(e){
                        var cfg = this.arguments.cfg;
                        _catalog[self.cfg.arrangement.catalog.format] = e.response2.parsedResponse;
                        self.initCatalogFromParsedResponse(e.response2.parsedResponse);
                        self.onCatalogLoaded();
                    },
                    failure: function(e){
                        var check;
                    },
                    arguments: {
                        self: this,
                        cfg: cfg
                    }
                };
                
                // use io to get XML catalog asynchronously
                var ds = new SNAPPI.SnappiXmlCatalog();
                var url = this.xmlSrc || this.baseurl;
                ds.getParsedResponse(url, myCallback);
            }
        },
		
		/*
		 * initProduction moved to Production
		 */
//        initProduction: function(P){
//            if (P) 
//                this.production = P;
//            else 
//                P = this.production;
//            /*
//             * attach Arrangement to Production
//             * sets P.scale
//             * must be called AFTER A.format is set
//             */
//            P.setArrangement(this);
//            
//            
//            // update Role sizes to current P using P.scale
//            if (this.roleSH && this.roleSH.count()) {
//                var r = this.roleSH.first();
//                while (r) {
//                    r.size = {
//                        w: r.sizeNormalized.w * this.w * P.scale,
//                        h: r.sizeNormalized.h * this.h * P.scale
//                    }
//                    r = this.roleSH.next();
//                }
//            }
//        },
        onLoadCatalog: function(oReq, oParsedResponse){
            // initialize Arrangement properties from xml
            //            var A = this.arrangement;
            //            var format = oParsedResponse.meta.format;
            //            A.w = oParsedResponse.w;
            //            A.h = oParsedResponse.h;
            //            A.format = A.w / A.h;
            //            A.initProduction();
            //            A.orientation = oParsedResponse.meta.orientation;
            //            
            //            
            //            // parse Roles
            //            if (A.roleSH) 
            //                A.roleSH.clear();
            //            else 
            //                A.roleSH = new SNAPPI.SortedHash();
            //            
            //            
            //            for (var i in oParsedResponse.results) {
            //                var o = oParsedResponse.results[i];
            //                if (o !== undefined && o.x !== undefined) {
            //                    o.arrangement = this.arrangement;
            //                    var r = new PM.Role(o);
            //                    A.roleSH.add(r);
            //                }
            //            }
            //            A.sort();
            //            A.onSuccessCallback({
            //                roles: true
            //            });
            cfg.onSuccess();
        },
        sort: function(cfg){
            this.roleSH.sort(cfg);
        }
    };
    
    
    
    
    
    
    
    /*
     *
     * PicasaArrangement
     * a subclass of Arrangement
     * initialize with PicasaArrangement properties
     */
    var PicasaArrangement = function(cfg){
    
    
    
        PicasaArrangement.superclass.constructor.call(this, cfg);
        this.init = function(cfg){
            this.baseurl = '../../matchmaker/catalog.xml?';
            this.loadCatalog(cfg);
        };
		
		/*
		 * moved to Catalog
		 */
//        this.initCatalogFromParsedResponse = function(oParsedResponse){
//        
//            // initialize Arrangement properties from xml
//            var A = this;
//            var n = 0;
//            var resp = oParsedResponse.results[cfg.arrangement.perpage][n]; // note: this should is an array of pages
//            A.w = parseFloat(resp.W);
//            A.h = parseFloat(resp.H);
//            A.format = A.w / A.h;
//            // initProduction must be called AFTER A.format is set
//            A.initProduction();
//            
//            A.orientation = (resp.W > resp.H) ? 'landscape' : 'portrait'; // ??: what do we want here?
//            // parse Roles
//            if (A.roleSH === undefined) 
//                A.roleSH = new SNAPPI.SortedHash();
//            for (var i in resp.roles) {
//                var o = resp.roles[i];
//                if (o !== undefined) {
//                    o.arrangement = A;
//                    var r = new PM.PicasaRole(o);
//                    A.roleSH.add(r);
//                }
//            }
//            //            A.sort();
//            return;
//        };
        
        this.init(cfg);
    };
//    YAHOO.lang.extend(PicasaArrangement, Arrangement);
	
    

})();

