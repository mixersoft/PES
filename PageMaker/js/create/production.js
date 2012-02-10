/**
 *
 * Copyright (c) 2009, Snaphappi.com. All rights reserved.
 *
 *
 *
 * @author Michael Lin, info@snaphappi.com
 *
 * Production
 *
 * A rendering of an Arrangement for display or printing
 *
 *
 */
(function(){
	/*
     * shorthand
     */
	var _Y = null;
	var Plugin = null;
	var PM = SNAPPI.namespace('SNAPPI.PM');
	// Yready init
	PM.onYready.Production = function(Y){
		if (_Y === null) _Y = Y;
	    /*
	     * publish
	     */
	    PM.Production = Production;
	    Plugin = PM.PageMakerPlugin.instance;
	} 
    
    /*
     * protected
     */
    var _defaultCfg = {
        renderUnitsPerInch: 1,
        minDpi: 300,
        previewDpi: 72,
        /*
         * Staging properties
         */
        spacing: 4, // "padding" in pixels
        borderColor: "gray",
        isRehearsal: false,
        onLoadCallback: null,
        /*
         * Casting Properties
         */
        sortCfg: null
    };
    
    
    Production = function(cfg){
        this.cfg = _Y.merge(_defaultCfg, cfg);
        
        /*
         * properties
         */
        this.id;
        this.renderSize; // = {w:0,h:0}; typically in inches
        this.renderUnitsPerInch; // size in inches
        this.minDpi;
        this.previewDpi; // proof dpi, i.e. display
        this.arrangement;
        // this.stage;	// use Plugin.stage
        // scale multiplier to convert Arrangement/Role sizes to Production
        // sizes in pixels
        this.scale; // this.scale = P.w/Arr.w = P.minDpi * P.renderSize.w *
        // P.renderUnitsPerInch / Arr.w
        this.spacing;
        this.scene = {
            current: null,
            saved: []
        };
        this.init(this.cfg);
    };
    
    
    /*
     * Static Methods
     */
    Production.loadCatalog = function(cfg){
        cfg = cfg ||
        {
            provider: 'Picasa'
        };
        var onCatalogLoaded = function(resp){
            var resp = PM.Catalog.getCatalog(cfg.provider);
            var check;
        };
        
        /*
         * asynchronous XML load
         */
        PM.Catalog.loadCatalog({
            provider: cfg.provider,
            success: onCatalogLoaded,
            failure: function(resp){
                var error;
            }
        });
    };
    
    /*
     * prototype functions, shared by all instances of object
     */
    Production.prototype = {
        init: function(cfg){
            // this.id;
            this.cast = [];
            this.spacing = cfg.spacing || 0;
            this.margin = cfg.margin || 0;
            this.displayDpi = cfg.displayDpi || 72;
            var displayConstraintInPx = (cfg.fitWithin && cfg.fitWithin.h) ? cfg.fitWithin.h : cfg.fitWithin.w;
            // allow for margin/padding
            // not sure why we need the extra 10px
            displayConstraintInPx -= (2 * (this.spacing + this.margin + 8)); // iframe
            // border/margin
            if (cfg.fitWithin && cfg.fitWithin.h) {
                this.renderSize = {
                    h: displayConstraintInPx / this.displayDpi
                };
            }
            else 
                if (cfg.fitWithin && cfg.fitWithin.w) {
                    this.renderSize = {
                        w: displayConstraintInPx / this.displayDpi
                    };
                }
            this.renderUnitsPerInch = cfg.renderUnitsPerInch; // size in inches
            
            this.minDpi = cfg.minDpi;
            this.previewDpi = cfg.previewDpi; // proof dpi, i.e. display
            // this.stage = cfg.stage;
            this.borderColor = cfg.borderColor;
            this.onLoadCallback = cfg.onLoadCallback;
            this.useHints = cfg.useHints || true;
            if (cfg.arrangement) 
                this.setArrangement(cfg.arrangement);
        },
        setCatalog: function(C) {
        	this.catalog = C;
        },
        setArrangement: function(Arr){
            var Pr = this;
            Pr.arrangement = Arr;
            Arr.production = this; // back reference used in
            // Casting.setMinSize()
            // are we attached to a Production yet?
            if (Pr.renderSize.w) {
                Pr.w = Pr.minDpi * Pr.renderSize.w * Pr.renderUnitsPerInch;
                Pr.h = Pr.w / Arr.format;
            }
            else {
                Pr.h = Pr.minDpi * Pr.renderSize.h * Pr.renderUnitsPerInch;
                Pr.w = Pr.h * Arr.format;
            }
            // scale multiplier to convert Arrangement/Role sizes to Production
            // sizes in pixels
            // Arr.w in units, Pr.w in pixels
            Pr.scale = Pr.w / (Arr.w); // = Pr.minDpi * Pr.renderSize.w *
            // Pr.renderUnitsPerInch / Arr.w
            Pr.size = {
                w: Pr.scale * Arr.w + 2 * Pr.spacing,
                h: Pr.scale * Arr.h + 2 * Pr.spacing
            };
        },
        /*
         * Casting methods, i.e. assigning XXXAuditions to Roles
         */
        addToCast: function(c){
            c.role.isCast = true;
            c.audition.isCast = true;
            /*
             * stack._dataElementSH[i].parsedAudition.isCast: will not cast same
             * audition in multiple productions save isCast to audition.parsedAudition
             * when the user explicitly saves.
             */
            // c.audition.parsedAudition.isCast = true;
            this.cast.push(c);
        },
        saveScene: function(scene, index){
            scene = scene || this.scene.current;
            if (index) {
                this.scene.saved.splice(index, 0, scene);
            }
            this.scene.saved.push(scene);
            
            /*
             * TODO: fix hardcoded switch, pass by cfg
             */
            var tryout, 
            	masterCastList,
            	productionAudition,
            	markOnlyCastList = true;
            try {
            	masterCastList = PM.pageMakerPlugin.production.tryout.pmAuditionSH;
            } catch (e) {}	
            if (markOnlyCastList) {
	            // mark scene cast list as cast
            	var castList = this.tryout.pmAuditionSH;
	            for (c in scene.cast) {
	            	var audition = scene.cast[c].audition;
		            audition.isCast = true;
		            audition.parsedAudition.isCast = true;
		            try {
			            // also save to masterTryout
			            productionAudition = masterCastList.get(audition);
			            productionAudition.isCast = true;
			            productionAudition.parsedAudition.isCast = true;
		            } catch (e) {}
	             }
            } else {
	            // mark entire chunk as cast
	            var castList = this.tryout.pmAuditionSH;
	            var i, audition, stop = castList.count();
	            for (i = 0; i < stop; i++) {
	               
	            	audition = castList.get(i);
	                if (audition.isCast == true) {
	                    audition.parsedAudition.isCast = true;
			            try {
				            // also save to masterTryout
				            productionAudition = masterCastList.get(audition);
				            productionAudition.isCast = true;
				            productionAudition.parsedAudition.isCast = true;
			            } catch (e) {}	                    
	                }
	            }
            }
        },
        bindCast: function(cast){
            cast = cast || this.cast;
            for (var i in cast) {
                cast[i].audition.isCast = true;
                cast[i].audition.parsedAudition.isCast = true;
            }
        },
        // allow cast member to be recast in subsequent scenes
        releaseCast: function(scene){
            var sceneList = (scene) ? [scene] : this.scenes;
            for (var s in sceneList) {
                var cast = sceneList[s].cast;
                for (var i in cast) {
                    cast[i].audition.isCast = false;
                    cast[i].audition.parsedAudition.isCast = false;
                }
            }
        },
        /*
         * Performance methods, i.e. rendering a production for consumption
         */
        perform: function(cfg, scene){
            /*
             * start the sequence to POST page gallery and load
             */
            cfg.isRehearsal = true;
            var renderedPerformance = this.getPerformance(cfg, scene);
    		Plugin.stage.body.setContent(renderedPerformance);
    		renderedPerformance.unscaled_pageGallery = Plugin.stage.body.get('innerHTML');
    		PM.PageMakerPlugin.startPlayer();
//        	var url = this.postPageGallery(cfg, scene); // POST cmd to save on server
			return renderedPerformance;
        },
        postPageGallery: function(cfg, scene){
            /*
             * createStaticPageGallery will issue a POST to save page Gallery on Server
             */
            var url = PM.util.createStaticPageGallery({
            	content: Plugin.stage.body.one('div.pageGallery').unscaled_pageGallery,
                filename: 'tmp',
                replace: true
            });
            return url;
        },
        /*
         * getPerformance() is the method to actually render a scene into HTML
         */
        getPerformance: function(cfg, scene){
            var Pr = this;
            var img, extra, pageGallery, scale2Rehearsal;
            scale2Rehearsal = this.previewDpi / this.minDpi;
            
            pageGallery = Plugin.stage.create('<div></div>');
            pageGallery.addClass('pageGallery hidden').setStyles({
                // backgroundColor: cfg.borderColor,
                margin: (cfg.margin) + "px auto"
            });
            scene = scene || Pr.scenes[Pr.scenes.length - 1];
            /*
             * absolute placement of each cast member
             */
            var outerDim = {
                r: 0,
                b: 0
            };
            for (var i = 0; i < scene.cast.length; i++) {
                img = document.createElement("IMG");
                var cast = scene.cast[i];
                if (cast.audition.orientation != 1) {
                	// should always be 1, check PM.Audition.init()
                    var check;
                }
                if (cast.audition.rotate != 1) {
                    // manually rotate
                    // should be able to combine orientation and rotate
                    var check;
                }
                var cropRect, castSrc, castSrcCropped;
                if (cast.audition.parsedAudition.base64RootSrc) {
                    castSrc = cast.audition.parsedAudition.base64RootSrc;
                }
                else {
                    castSrc = cast.audition.base64Src ? cast.audition.base64Src : cast.audition.src;
                }
                
                if (cfg.isRehearsal) {
                	if (cast.audition.parsedAudition.Audition.Photo.Img.previewSrc) {
                	// use cast.audition.parsedAudition.Audition.Photo.Img.Src
                		var a = cast.audition.parsedAudition;
                		var baseurl = a.urlbase;
                		var src = a.Audition.Photo.Img.Src.previewSrc;
	                	castSrc = baseurl + src;
                	}
                    cast.minSize.h *= scale2Rehearsal;
                    cast.minSize.w *= scale2Rehearsal;
                    cast.position.x *= scale2Rehearsal;
                    cast.position.y *= scale2Rehearsal;
                    if (cfg.spacing) {
                        // add gaps
                        cast.position.x += cfg.spacing;
                        cast.position.y += cfg.spacing;
                        cast.minSize.h -= 2 * cfg.spacing;
                        cast.minSize.w -= 2 * cfg.spacing;
                        
                        /*
                         * NOTE: we may not need to adjust crop for borders, the
                         * brower can automatically resize. but will it be
                         * proportional?
                         */
                        cast.crop.x += cfg.spacing;
                        cast.crop.y += cfg.spacing;
                        cast.crop.h -= 2 * cfg.spacing;
                        cast.crop.w -= 2 * cfg.spacing;
                    }
                    cropRect = PM.util.getCropSpec(cast.crop, true);
					// var thumbnail_prefix = cfg.thumbPrefix || 'bp';	// bp == 640px
                	var thumbnail_prefix = this.getThumbPrefix(cast.crop, cfg);
                    castSrcCropped = PM.util.addCropSpec(castSrc, cropRect, thumbnail_prefix);
                }
                else {
                    if (cfg.spacing) {
                        // add gaps in final dimensions
                        
                        cast.position.x += renderSizeSpacing.spacing;
                        cast.position.y += renderSizeSpacing.spacing;
                        cast.minSize.h -= 2 * renderSizeSpacing.spacing;
                        cast.minSize.w -= 2 * renderSizeSpacing.spacing;
                        
                        /*
                         * NOTE: we may not need to adjust crop for borders, the
                         * brower can automatically resize. but will it be
                         * proportional?
                         */
                        cast.crop.x += renderSizeSpacing.spacing;
                        cast.crop.y += renderSizeSpacing.spacing;
                        cast.crop.h -= 2 * renderSizeSpacing.spacing;
                        cast.crop.w -= 2 * renderSizeSpacing.spacing;
                    }
                    cropRect = PM.util.getCropSpec(cast.crop);
                    castSrcCropped = PM.util.addCropSpec(castSrc, cropRect);
                }
                img.ynode().setStyles({
                    height: (cast.minSize.h) + "px",
                    width: (cast.minSize.w) + "px",
                    left: (cast.position.x) + "px",
                    top: (cast.position.y) + "px",
                    position: "absolute",
                    border: cfg.spacing + "px solid " + cfg.borderColor,
                    cursor: 'pointer'
                });
                img.ynode().setAttrs({
                    src: castSrcCropped,
//                    linkTo: castSrc,
                    title: cast.role.id + '-' + cropRect
                });
                outerDim.r = Math.max(outerDim.r, cast.position.x + cast.minSize.w);
                outerDim.b = Math.max(outerDim.b, cast.position.y + cast.minSize.h);
                
                /*
                 * add rendered PageGallery to DIV
                 */
                pageGallery.append(img);
            }
            /*
             * set size of wrapper to just fit pageGallery photos + margin
             */
            pageGallery.setStyles({
                height: outerDim.b - cfg.spacing + 2 * cfg.margin + "px",
                width: outerDim.r - cfg.spacing + 2 * cfg.margin + "px"
            });
            //            Dom.setStyle(pageGallery, 'height', outerDim.b - cfg.spacing + 2 * cfg.margin + "px");
            //            Dom.setStyle(pageGallery, 'width', outerDim.r - cfg.spacing + 2 * cfg.margin + "px");
            
            if (false && SNAPPI.util3.QUEUE_IMAGES) {
                // just watch for img load, don't queue
                SNAPPI.util3.ImageLoader.loadBySelector(pageGallery, 'div#content img', null, 100);
            }
            
            return pageGallery;
        },
        getThumbPrefix: function(crop, cfg){
        	if (!cfg.isMontage) return cfg.isRehearsal ? 'bp' : '';
        	try {
        		var longest = Math.max(crop.minSize.h, crop.minSize.w);
        		if (longest <= 120) return 'tn';
        		if (longest <= 240) return 'bs';
        		if (longest <= 320) return 'bm';
        	} catch (e) {}
			return 'bp';
        },
        rehearse: function(cfg, scene){
            cfg.isRehearsal = true;
            return this.perform(cfg, scene);
        },
        _addPerformanceTab: function(id, name){
            id = id || 'tab_create';
            name = name || 'Create';
            var perfTab = new YAHOO.widget.Tab({
                label: name,
                content: "<div id='" + id + "'></div>",
                href: "#tab_" + name
            });
            SNAPPI.TabView.content_tabView.addTab(perfTab);
            return perfTab;
        },
        renderScene: function(cfg){
            var scene, Pr = this;
            /*
             * override display properties for this render only
             */
            if (cfg) {
                cfg.previewDpi = cfg.previewDpi || this.previewDpi; // proof
                // dpi, i.e.
                // display
                cfg.spacing = cfg.spacing || this.spacing;
                cfg.margin = cfg.margin || this.margin;
                cfg.borderColor = cfg.borderColor || this.borderColor;
                cfg.label = cfg.label || this.label;
                cfg.useHints = (cfg.useHints !== undefined) ? cfg.useHints : this.useHints;
                cfg.stage = cfg.stage || Plugin.stage;
            }
            if (cfg.sceneIndex) {
                // render a saved scene
                if (cfg.sceneIndex < this.scene.saved.length) 
                    scene = this.scene.saved[cfg.sceneIndex];
            } else {
                // cast a new scene
                /*
                 * hack: clearCast on useHints change
                 */
                Pr.resetCastOnToggle(cfg.useHints);
                
                
                
                /***************************************************************
                 * cast Production/Scene should be able to choose Casting
                 * algorithm here
                 */
                // TODO: need to pass cfg.isRehearsal here 
                // so we know whether to use autorotated previewSrc
                cfg.isRehearsal = true;
                var scene;
//                scene = Casting.SimpleCast(Pr, cfg);
//                scene = Casting.ChronoCast(Pr, cfg);
// NOTE: CustomFitArrangement will reset Pr.tryout & Pr.arrangement here
// called from Pr.renderScene: 
				if (!cfg.arrangement) {
					// get arrangement from server XHR
					var callback = {
						success: function(scene){
							Pr.scene.current = scene;
            				scene.performance = Pr.rehearse(cfg, scene);
							if (cfg.callback && cfg.callback.success) cfg.callback.success(scene);
						},
						failure: function(){
							if (cfg.callback && cfg.callback.failure) cfg.callback.failure();
						}
					}
					var cfg2 = _Y.merge(cfg, {callback:callback});
					Casting.CustomFitArrangement.call(Pr, Pr, cfg2);
					return;	// XHR async return using callback
				}
				PM.Catalog.parseCustomFitArrangement(cfg.arrangement, Pr);
				// delete cfg.arrangement;
				scene = Casting.ChronoCast(Pr, cfg);
                /***************************************************************
                 * end Casting
                 **************************************************************/
            }
            Pr.scene.current = scene;
            scene.performance = Pr.rehearse(cfg, scene);
            // syncronous return path, no XHR
            if (cfg.callback && cfg.callback.success) cfg.callback.success(scene);	
            else return scene;
        },
        resetCastOnToggle: function(checked){
            /*
             * hack: clearCast on useHints change
             */
            if (this.tryout && this.tryout.stack && this.tryout.stack.useHints != checked) {
                this.tryout.clearCast(); // reset cast if useHints changes
                this.tryout.stack.useHints = checked;
            }
        }
    };
    
    
})();

