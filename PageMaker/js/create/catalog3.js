/**
 *
 * Copyright (c) 2009, Snaphappi.com. All rights reserved.
 *
 *
 *
 * @author Michael Lin, info@snaphappi.com
 *
 * Catalog
 *
 *
 */
(function(){
	/*
     * shorthand
     */
	var _Y = null;
	var PM = SNAPPI.namespace('SNAPPI.PM');
	SNAPPI.namespace('SNAPPI.PM.onYready');
	// Yready init
	PM.onYready.Catalog = function(Y){
		if (_Y === null) _Y = Y;
		  /*
	     * declare globals in namespace
	     */
	    PM.Catalog = Catalog;	
	} 
    /*
     * protected
     */
    var _defaultCfg = {};
    var _catalog = {};
    
    var _getKeyFromCfg = function(cfg){
        var key = (cfg.provider || '') + (cfg.id || '');
        return key || cfg.format;
    };
    
    /*
     * Class Catalog
     */
    var Catalog = function(cfg){
    	this.cfg = {};
    	this.arrangements = [];
        this.init(cfg);
    };
    
    /*
     * Static Functions
     */
    /**
     * get custom fit arrangement from Server, and add to Production
     * 		calls Pr.setArrangement(A)
     * 		called by Casting.CustomFitArrangement()
     * 	SYNCHRONOUS XHR
     */
    Catalog.getCustomFitArrangement = function (Pr, roleCount, callback) {
        /*
         * get arrangement from server, 
         * resets Plugin.production here
         */
        var uri, auditions, baseurl, data, post_callback;
        auditions = new Array();
        uri = "/pagemaker/arrangement/.json";
        roleCount = parseInt(roleCount);
        
        Pr.tryout.pmAuditionSH.some(function(o){
        	// make "simple" auditions for cluster_collage
        	// remove Subsitition circular references
        	if (!o.parsedAudition.Audition.Substitutions) {
        		auditions.push(o.parsedAudition.Audition);
        	} else {
	        	var copy = _Y.merge(o.parsedAudition.Audition);
	        	delete(copy.Substitutions);
	        	auditions.push(copy);
        	}
        	if (!baseurl) baseurl = o.parsedAudition.urlbase;
        	if (auditions.length >= roleCount) {
        		return true;
        	} else return false;
        });
        var Auditions = {
        		Audition: auditions,
        		Baseurl: baseurl
        };
        var rawJsonAuditions = _Y.JSON.stringify(Auditions);
        data = {
        	'data[CastingCall][Auditions]':rawJsonAuditions,
        	'data[role_count]': auditions.length
        };
        var A, rawA;
        post_callback = {};
        post_callback.success = function (id, o, args){
        	if (o.responseJson.success) {
        		rawA = o.responseJson.response.arrangement;
        		/*
        		 * create snappi Roles from rawRoles
        		 */
        		A = {
    	            w: rawA.W,
    	            h: rawA.H,
    	            format: rawA.W/rawA.H,
    	            roleSH: new SNAPPI.SortedHash()
        		};
        		var rawRole, snappiRole;
                for (var i in rawA.Roles) {
                    var rawRole = rawA.Roles[i];
                    if (rawRole !== undefined) {
                        var r = new PM.SnappiCustomFitRole(rawRole, A);
                        A.roleSH.add(r);
                    }
                }
                A.roleSH.setDefaultSort([PM.Role.sort.PROMINENCE]);
                A.roleSH.sort();
                var i = 0;
                A.roleSH.each(function(R){
                	R.index = i++;
                });
                Pr.setArrangement(A);
        		callback.success.call(this, A);
        	} else 
        		callback.failure.call(this, o);
        };
        post_callback.failure = function (id, o, args){
        	// timeout
        	callback.failure.call(this, o);
        };
        
        /*
         * use SNAPPI.IO.getIORequest if we need a loading mask
         */
        var o = SNAPPI.io.post.call(this, uri, data, post_callback, {}, sync = true);
        // A set in post_callback via closure
        return A;
    };

    /*
     * load static catalog of arrangements
     * SYNCHRONOUS XHR
     */
    Catalog.loadCatalog_SnappiCustomFit = function(cfg){

    };    
    
    Catalog.loadCatalog = function(cfg){
        var _cfg = _Y.merge({
            provider: 'Snappi',
            id: 'CustomFit',
            format: 'snappi',
            label: 'CustomFit',
            syncIo: true,
            src: '',
            request: '',
            reload: false, // force reload
            perpage: null,
            success: null,
            failure: null
        }, cfg);
        
        var key = cfg.key || _getKeyFromCfg(cfg);
    	if (cfg.reload == false && _catalog[key] ) {
    		if (cfg.syncIo) return 'complete';
    		else return _catalog[key];
    	}
    	switch (key) {
	    	case 'SnappiCustomFit':
	    		// sync io
	            _catalog[key] = {
	            	src: "/pagemaker/arrangement/.json",
	            	request: null,
	            	label: 'SnappiCustomFit'
	            };
	    		break;
	    	default:
	    		// load from catalog, same for sync/async
	    	
		        // parse XML from DS
		        var myCallback = {
		            // this == myCallback
		            success: function(e){
		                var cfg = this.arguments.cfg;
		                var catalog = e.response2.parsedResponse.results;
		                var request = e.response2.parsedResponse.requestUri.split('?');
		                catalog.src = request.shift();
		                catalog.request = request.shift();
		                catalog.label = _cfg.label;
		                _catalog[key] = catalog;
		                cfg.success(_catalog[key]);
		            },
		            failure: function(e){
		                cfg.failure(e.response);
		            },
		            arguments: {
		                cfg: cfg
		            }
		        };
		        
		        // use io to get XML catalog asynchronously
		        var ds = new PM.SnappiXmlCatalog({sync:cfg.syncIo});
		        var url = _cfg.src + (_cfg.request ? '?' + _cfg.request : '');
		        /*
		         * OVERRIDE location of catalog.xml
		         */
		        //       ds.xmlSchemaParser.uri = "./catalog.xml?";
		        ds.getParsedResponse(url, myCallback);
		        if (cfg.syncIo == false) return 'loading';
				break;
		}
    	return _catalog[key]; 
    };
    
    Catalog.getCatalog = function(strOrCfg){
        if (_Y.Lang.isString(strOrCfg)) {
            var key = strOrCfg;
            return _catalog[key] || null;
        }
        else {
            var cfg = strOrCfg;
            var key = _getKeyFromCfg(cfg);
            if (_catalog[key] === undefined) {
                var cfg = strOrCfg;
                if (!key) {
                    if (_Y.Lang.isFunction(cfg.failure)) {
                        cfg.failure();
                    } 
                    return null;
                } else {
                	cfg.key = key;
                	return Catalog.loadCatalog(cfg);  
//                	switch (key) {
//	                	case 'SnappiCustomFit':
////	                		key += cfg.id;
//	                        _catalog[key] = {
//	                        	src: "/pagemaker/arrangement/.json",
//	                        	request: null,
//	                        	label: 'SnappiCustomFit'
//	                        };
//	                     // SYNC return
//	                		break;
//	                	default:
//	                		// ASYNC catalog load
//	                        // load Catalog if callback is provided
//	                        var onCatalogLoaded = function(resp){
//	                            var resp = Catalog.getCatalog(key);
//	                            return cfg.success(resp);
//	                        };
//	                        
//	                        /*
//	                         * asynchronous XML load
//	                         */
//	                        var _cfg = _Y.merge(cfg, {
//	                            success: onCatalogLoaded
//	                        });
//	                        return Catalog.loadCatalog(_cfg);                		
//                		break;
//                	}
                }
            } 
            return _catalog[key];
        }
    };
    Catalog.getArrangementScoreXX = function(Arr, sortedAud){
        var r, a, rFormat = null, aFormat = null, score = 0;
        roleLoop: for (var iR in Arr.roles) {
            r = Arr.roles[iR];
            auditionLoop: for (var iA in sortedAud) {
                for (var iB in sortedAud[iA]) {
                    a = sortedAud[iA][iB];
                    if (a.isScored === true) 
                        continue;
                    /*
                     * note: we should add role.format during catalog.parse
                     */
                    rFormat = (r.H * Arr.H > r.W * Arr.W) ? 'T' : 'W';
                    aFormat = a.format < 1 ? 'T' : 'W';
                    if (rFormat == aFormat) {
                        score += parseInt(a.rating) * parseInt(r.Prominence / 10000);
                        a.isScored = true;
                        break auditionLoop;
                    }
                }
            }
        }
        return score;
    };
    Catalog.getArrangementScore = function(Arr, auditionSH){
        // auditionSH sorted by sortedHash.sort([PM.Audition.sort.RATING]); 
        var r, a, rFormat = null, aFormat = null, score = 0;
        // reset isScored
        var a = auditionSH.setFocus(0);
        do {
            a.isScored = false;
        }
        while (a = auditionSH.next());
        roleLoop: for (var iR in Arr.roles) {
            r = Arr.roles[iR];
            rFormat = r.format < 1 ? 'T' : 'W';
            a = auditionSH.setFocus(0);
            do {
                if (a.isScored === true) 
                    continue;
                aFormat = a.format < 1 ? 'T' : 'W';
                if (rFormat == 'T') 
                    var check;
                if (rFormat === aFormat) {
                    score += _generateScore(a, r);
                    a.isScored = true;
                    break;
                }
            }
            while (a = auditionSH.next());
        }
        return score;
    };
    
    
    /*
     * protected methods
     */
    var _generateScore = function(a, r){
        var score = Math.round(parseInt(a.rating) * Math.sqrt(r.Prominence));
        return score;
    };
    var _getRawArrangementByPerpage = function(perpage){
        /*
         * gets a random arrangement from catalog
         */
        var As = this.catalog.arrangements[perpage];
        var random = Math.floor(Math.random() * As.length);
        var A = _Y.Lang.isArray(As) ? As[random] : As;
        return A;
    };
    
    var _initRawArrangement = function(rawA){
        if (!rawA) 
            return null;
        var A = {
            w: rawA.W,
            h: rawA.H,
            format: rawA.format,
            roleSH: new SNAPPI.SortedHash()
        };
        for (var i in rawA.roles) {
            var o = rawA.roles[i];
            if (o !== undefined) {
                o.arrangement = A;
                var r = new PM.PicasaRole(o);
                A.roleSH.add(r);
            }
        }
        return A;
    };
    
    /*
     * Class Methods
     */
    Catalog.prototype = {
        init: function(cfg){
        	this.cfg = _Y.merge(_defaultCfg, cfg);
            // getCatalog will auto load catalog xml if not available
            // note: key=catalog set in xmlArrangementParser_Snappi.parse()
            this.catalog = Catalog.getCatalog(cfg);		
        },
        getArrangement_All: function(){
            return this.catalog.arrangements;
        },
        getArrangementByScore: function(sortedHash, roleCount){
            sortedHash.sort([PM.Audition.sort.RATING]); // sort by rating
            // sort auditions by ratings so we get a rough idea of how many good photos
            var sortedByRating = [], o = sortedHash.setFocus(0), topRating = o.rating;
            do {
                if (sortedByRating[topRating - o.rating] == undefined) 
                    sortedByRating[topRating - o.rating] = [];
                sortedByRating[topRating - o.rating].push(o);
            }
            while (o = sortedHash.next());
            
            // score arrangements
            var score, arrScores2 = {}, Cat = this.catalog.arrangements;
            perPageLoop: for (var pp in Cat) {
                if (roleCount && pp != roleCount) 
                    continue;
                if (pp / 2 > sortedHash.count()) 
                    continue; // make sure we can fill at least 1/2 the spots ???
                for (var a in Cat[pp]) {
                    score = Catalog.getArrangementScore(Cat[pp][a], sortedHash);
                    arrScores2[score] = Cat[pp][a];
                }
            }
            var nextScore = 0, topScore = 0;
            for (var n in arrScores2) {
            	topScore = Math.max(topScore, n);
            	if (this.lastScore && this.lastScore <= n) {
            		continue;
            	}
            	nextScore = Math.max(nextScore, n);
            }
            this.lastScore = nextScore ? nextScore : topScore;
            return _initRawArrangement(arrScores2[this.lastScore]);
        },
        getArrangement: function(cfg){
            var rawA = null, A = {};
            if (cfg && cfg.rawArr) {
                rawA = cfg.rawArr;
            }
            if (!rawA && cfg && cfg.perpage) {
                // get arrangement by photos per page
                rawA = _getRawArrangementByPerpage.call(this, cfg.perpage);
            }
            if (!rawA) 
                return null;
            return _initRawArrangement(rawA);
        }
    };
    
    
})();

