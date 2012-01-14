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
    var PM = SNAPPI.namespace('SNAPPI.PM');
    var Y = PM.Y;
    
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
        cfg = SNAPPI.util.mergeObj(cfg, _defaultCfg);
        
        /*
         * properties
         */
        this.id;
        this.renderSize; // = {w:0,h:0}; typically in inches
        this.renderUnitsPerInch; // size in inches
        this.minDpi;
        this.previewDpi; // proof dpi, i.e. display
        this.arrangement;
        this.stage;
        // scale multiplier to convert Arrangement/Role sizes to Production
        // sizes in pixels
        this.scale; // this.scale = P.w/Arr.w = P.minDpi * P.renderSize.w *
        // P.renderUnitsPerInch / Arr.w
        this.spacing;
        this.scene = {
            current: null,
            saved: []
        };
        this.init(cfg);
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
            var resp = SNAPPI.PM.Catalog.getCatalog(cfg.provider);
            var check;
        };
        
        /*
         * asynchronous XML load
         */
        SNAPPI.PM.Catalog.loadCatalog({
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
            this.renderUnitsPerInch = cfg.renderUnitsPerInch; // size in
            // inches
            this.minDpi = cfg.minDpi;
            this.previewDpi = cfg.previewDpi; // proof dpi, i.e. display
            this.stage = cfg.stage;
            this.borderColor = cfg.borderColor;
            this.onLoadCallback = cfg.onLoadCallback;
            this.useHints = cfg.useHints || true;
            if (cfg.arrangement) 
                this.setArrangement(cfg.arrangement);
        },
        setArrangement: function(Arr){
            var P = this;
            P.arrangement = Arr;
            Arr.production = this; // back reference used in
            // Casting.setMinSize()
            // are we attached to a Production yet?
            if (P.renderSize.w) {
                P.w = P.minDpi * P.renderSize.w * P.renderUnitsPerInch;
                P.h = P.w / Arr.format;
            }
            else {
                P.h = P.minDpi * P.renderSize.h * P.renderUnitsPerInch;
                P.w = P.h * Arr.format;
            }
            // scale multiplier to convert Arrangement/Role sizes to Production
            // sizes in pixels
            // Arr.w in units, P.w in pixels
            P.scale = P.w / (Arr.w); // = P.minDpi * P.renderSize.w *
            // P.renderUnitsPerInch / Arr.w
            P.size = {
                w: P.scale * Arr.w + 2 * P.spacing,
                h: P.scale * Arr.h + 2 * P.spacing
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
            var markOnlyCastList = true;
            var productionAudition, masterCastList = SNAPPI.PM.performance.tryout.pmAuditionSH;
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
    		this.stage.body.setContent(renderedPerformance);
    		renderedPerformance.unscaled_pageGallery = this.stage.body.get('innerHTML');
    		SNAPPI.PM.PageMakerPlugin.startPlayer();
//        	var url = this.postPageGallery(cfg, scene); // POST cmd to save on server
        },
        postPageGallery: function(cfg, scene){
            /*
             * createStaticPageGallery will issue a POST to save page Gallery on Server
             */
            var url = SNAPPI.PM.util.createStaticPageGallery({
            	content: this.stage.body.one('div.pageGallery').unscaled_pageGallery,
                filename: 'tmp',
                replace: true
            });
            return url;
        },
        /*
         * getPerformance() is the method to actually render a scene into HTML
         */
        getPerformance: function(cfg, scene){
            var P = this;
            var img, extra, stageBody, scale2Rehearsal;
            scale2Rehearsal = this.previewDpi / this.minDpi;
            
            stageBody = P.stage.create('<div></div>').addClass('pageGallery hide').setStyles({
                backgroundColor: cfg.borderColor,
                margin: (cfg.margin) + "px auto"
            });
            
            scene = scene || P.scenes[P.scenes.length - 1];
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
                	// should always be 1, check SNAPPI.PM.Audition.init()
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
                    castSrcCropped = PM.util.addCropSpec(castSrc, cropRect, "bp");
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
                stageBody.append(img);
            }
            /*
             * set size of wrapper to just fit pageGallery photos + margin
             */
            stageBody.setStyles({
                height: outerDim.b - cfg.spacing + 2 * cfg.margin + "px",
                width: outerDim.r - cfg.spacing + 2 * cfg.margin + "px"
            });
            //            Dom.setStyle(stageBody, 'height', outerDim.b - cfg.spacing + 2 * cfg.margin + "px");
            //            Dom.setStyle(stageBody, 'width', outerDim.r - cfg.spacing + 2 * cfg.margin + "px");
            
            if (false && SNAPPI.util3.QUEUE_IMAGES) {
                // just watch for img load, don't queue
                SNAPPI.util3.ImageLoader.loadBySelector(stageBody, 'div#content img', null, 100);
            }
            
            return stageBody;
        },
        rehearse: function(cfg, scene){
            cfg.isRehearsal = true;
            this.perform(cfg, scene);
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
                cfg.stage = cfg.stage || this.stage;
            }
            if (cfg.sceneIndex) {
                // render a saved scene
                if (cfg.sceneIndex < this.scene.saved.length) 
                    scene = this.scene.saved[cfg.sceneIndex];
            }
            else {
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
                scene = Casting.CustomFitArrangement.call(Pr, Pr, cfg);
                /***************************************************************
                 * end Casting
                 **************************************************************/
                if (scene == null) {
                    if (SNAPPI.util.LoadingPanel) 
                        SNAPPI.util.LoadingPanel.hide();
                    return;
                }
            }
            Pr.scene.current = scene;
            Pr.rehearse(cfg, scene);
            return scene;
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
    
    /*
     * publish
     */
    SNAPPI.PM.Production = Production;
    
})();

