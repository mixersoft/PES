/**
 *
 * Copyright (c) 2009, Snaphappi.com. All rights reserved.
 *
 *
 *
 * @author Michael Lin, info@snaphappi.com
 * 
 * main3.js 
 * - main script for PageMaker module
 * - loads all PageMaker js scripts
 * - provides PM.main.go()
 * - loaded by base.js, SNAPPI.PageMakerPlugin.load()
 *
 */
(function(){
	/*
     * shorthand
     */
	var _Y = null;
	var Plugin = null;
	var PM = SNAPPI.namespace('SNAPPI.PM');
	SNAPPI.namespace('PM.onYready');
	// Yready init
	PM.onYready.Main = function(Y){
		if (_Y === null) _Y = Y;
    	PM.main = main;
    	Plugin = PM.PageMakerPlugin.instance;
	} 
	
    var main = new function() { 
    	var _self = this;
    	this.launch = function(cfg){
    		var Plugin = PM.pageMakerPlugin;
    		if (cfg.io) {
    			this.ioCfg = cfg.io;
    			delete cfg.io;
    		}
    		if (cfg.scene) {
    			this.sceneCfg = cfg.scene;
    			delete cfg.scene;
    		}
    		this.cfg = _Y.merge(this.cfg, cfg);
    		
    		
            var retry = function() {
            	_self.launch.call(_self);
            };
			
            /****
             * load Picasa catalog of arrangements
             * - Asynch Y.io, uses callbacks
             */
            var catalogHost = PM.pageMakerPlugin.getHost();
            var catalogCfg = {
                provider: 'Snappi',
                id: 'Picasa',
                format: 'Picasa',
                src: "http://"+catalogHost+"/pagemaker/catalog.xml",
            	request: "perpage=all",
            	syncIo: true,
            	success: function(){
	                //TODO: FIRE onCatalogReady event 
	            	_self.catalogLoadStatus = 'complete';
	            	retry();
	            }
            };
            /***********
             * load SnappiCustomFit
             * - SYNC IO
             */
			var catalogCfg = {
				provider: 'Snappi',	// 'SnappiMagicLayout
				id: 'CustomFit',
				format: 'snappi',
				label: 'CustomFit',
        		syncIo: true,
				end: null
			};	            
            
            if (this.catalogLoadStatus == undefined) {
            	this.catalogLoadStatus = PM.Catalog.getCatalog(catalogCfg);
            }
            if (this.catalogLoadStatus == 'loading') {
                return;	// retry
            }
            Plugin.catalog = new PM.Catalog(catalogCfg); 
            Plugin.catalogCfg = catalogCfg;		// TODO: save cfg and lookup
            // calls Catalog.getCatalog() in constructor
            // remember to call: Plugin.production.setCatalog(catalog);
            // END loadCatalogAsynch synch/Asynch processing
			
			
            /****
             * get sortedhash, Tryout from SNAPPI.Auditions._auditionSH, if available, 
             * 		or from datasource (Asynch Y.io)
             */
            var datasource = this.target ? this.target : this.ioCfg;
            try {
            	// check if we already have a tryout from /my/pagemaker
            	if (SNAPPI.Auditions._auditionSH.count() == 0) throw new Exception();
            	this.tryoutSortedhash = SNAPPI.Auditions._auditionSH;
            } catch(e) {
	            if (this.tryoutLoadStatus == undefined) {
	            	console.log('this codepath has not completed refactor for SNAPPI.Auditions.');
	            	/*
	            	 * TODO: make sure PM.Audition reuses master index from  SNAPPI.Auditions._auditionSH
	            	 */
	                this.tryoutLoadStatus = this.getTryout(datasource, retry);
	                if (this.tryoutLoadStatus != 'complete') {
	                    return;
	                }
	            }
	            else 
	                if (this.tryoutLoadStatus == 'loading') {
	                    return;
	                }
            } 
            // load auditions into SNAPPI.Tryout._pmAuditionSH(), master list
            var tryout = new PM.Tryout({
                sortedhash: this.tryoutSortedhash
            });
			// END getTryout synch/Asynch processing, this.tryoutSortedhash = SH	
            
        
		
		
		    
            /***
             * show PageGallery home page
             */
            var sceneCfg = {
                sortedhash: tryout.pmAuditionSH,
                tryout: tryout
            };
            PM.node.startListeners();
            
            // scene display config for designer mode
            var previewDisplayCfg = {
                label: "Pagemaker",
                // stage: sceneCfg.stage,
            };
            Plugin.sceneCfg = _Y.merge(this.sceneCfg, sceneCfg, previewDisplayCfg);
            
            // hack: double-checks
            sceneCfg = Plugin.sceneCfg;
            
            // NOTE: scale pageGallery with in production w/ NATIVE_PAGE_GALLERY_H constant
            if (!sceneCfg.fnDisplaySize) sceneCfg.fnDisplaySize = PM.cfg.fn_DISPLAY_SIZE;
            // hack: done
            
            /****
             * set Production staging params
             */
            var spacing = 3, margin = 6;
            /********************************************
             * choose minimum displayDpi for rendering
             */
            var displayDpi = 300; // for print rendering
            displayDpi = 72; // for monitor rendering, low res photos
            
            
            var productionCfg = {
                    fitWithin: {
                        h: PM.util.NATIVE_PAGE_GALLERY_H // only 1 value, w or h, required, 6.5
                    },
                    minDpi: displayDpi,
                    borderColor: "lightgray",
                    spacing: 3,		// img.border 
                    margin: 10,		// pageGallery.margin-top/bottom
                    stage: Plugin.stage,
                    useHints: this.useHints,
                    // catalog: catalog,		// use Pr.setCatalog()
                    // arrangement: Arr,
                    onLoadCallback: function(){
                       if (SNAPPI.util.LoadingPanel)  SNAPPI.util.LoadingPanel.hide();
                    }
                }; 
            /*
             * compensate for flickr low-res images
             */
            if (SNAPPI.util.getFromQs('host') == 'flickr') {
                /*
                 * NOTE: currently set max size of Flickr root to LARGE, not original, see $flickr_controller->__getRootPhoto()
                 */
                productionCfg.minDpi = 40;
            }  
            
            Plugin.production = new PM.Production(productionCfg);
            Plugin.production.setCatalog(Plugin.catalog);
            Plugin.stage.production = Plugin.production;	// hack: save in Plugin, or recreate by stage.production.cfg?
            
            /****
             * set staging for Performance
             */
            // just set Performance and show Create Tab
            var performanceCfg = {
                sceneCfg: sceneCfg,
                stage: Plugin.stage,
            }
            // if (preload = 0) {	// not required for '#pg-getting-started'
            	// performanceCfg.catalog = catalog;
            	// performanceCfg.tryout = sceneCfg.tryout;
            // }
            var performance = new PM.Performance(performanceCfg);
            
            performance.setStaging(Plugin.stage);
            Plugin.performance = performance;
            // performance.tryout == tryout;
            // performance.tryout.dataSource = original, parsed sortedhash of Auditions
            // performance.tryout.pmAuditionSH (copy) = performance.tryout.getAuditionsFromSortedHash(null)
            
            
            // NOTE: performance.getScene() will clear
            // sceneCfg.stage.body
            /*
             * Page Gallery Getting Started
             */
            var pgGettingStarted = _Y.Node.create("<div id='pg-getting-started'><div><h1>Getting Started with PageMaker</h1><p>Page Galleries are automatically generated photo collages based on page templates.</p><p>To get started, just choose how many photos you would like to see on a page. A matching page template will be randomly chosen and a Page Gallery automatically created using your top-rated photos. For now, we just offer a selection of simple page templates.</p><p>If you would like to try a different template, just click again.</p><p>Once you see a page you like, just click <b><i>Save Page</i></b> to add this page to an online album - new pages will be added to the end. You can view your album from the <b><i>Preview</i></b> tab, where you will also find a link for easy sharing.</p><p>To begin, just click one of the buttons above.</p><br /></div></div>");
            Plugin.stage.body.append(pgGettingStarted);
            // use external_Y
            _Y.fire('snappi-pm:after-launch', Plugin.stage);
            PM.pageMakerPlugin.external_Y.fire('snappi-pm:after-launch', Plugin.stage);
            
            
        };  
        
        this.getTryout = function(ioCfg, fnContinue){
            /*
             * fetch CastingCall as asynch JSON from server using 
             *  - Y.io, uri = /photos/getCC
             */
//        	var _self = this;
            var callback = {
                success : function(i, o, args){
            		_self.tryoutLoadStatus = 'complete';
                    
                    // parse o.response to get sortedhash
                    var sh = new SNAPPI.SortedHash();
                    // finish init here
                    var response = eval('(' + o.responseText + ')');
                    // parse castingCall into auditions
                    
                    // case "snappi":
                    // 1st parse from gallery/js/datasource3.js, in SNAPPI, *NOT* SNAPPI.PM
                    var schemaParser = SNAPPI.AuditionParser_Snappi;
                    var parsed = schemaParser.parse(response.castingCall);
                    var parsedResults = parsed.results;
                    
                    
                    
                    // now we have an "audition"
                    for (var i in parsedResults) {
                        // 2nd parse
                        var audition = SNAPPI.Auditions.extractSnappiAuditionAttr(parsedResults[i]);
                        sh.add(audition);
                    }
                    
                    
                    _self.tryoutSortedhash = sh;
                    fnContinue();
                }
            };
            
            
            if (ioCfg && ioCfg.method) {
                // this is an ioCfg
                var _ioCfg = {
                    method: "POST",
                    data: '',
                    on: {
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        complete: callback.success
                    },
                    timeout: 2000,
                    context: this,
                    arguments: {}
                };
                ioCfg = Y.merge(_ioCfg, ioCfg);
                
                ioCfg.arguments.uri = ioCfg.uri;
                ioCfg.arguments.ioCfg = ioCfg;
                Y.io(ioCfg.uri, ioCfg);
                return "loading";
            }
            else {
				// TODO: this is a legacy codepath. DEPRECATE
                if (ioCfg && ioCfg.ynode) {
                    var datasource = ioCfg.ancestor('div.photo-roll');
                    /**
                     * NOTE: this codepath is for the Gallery Project, 
                     * - where we are passing a node with a reference to a stack 
                     * TODO: rewrite this code path to cleanly separate PAGEMAKER from
                     * GALLERY project. this method should not know the DOM tree of the calling project
                     * pass an ioCfg instead
                     */
                    if (datasource) {
                        // sortedhash should be valid
                        this.tryoutSortedhash = datasource.dom().PhotoRoll.auditionSH;
                        return 'complete';
                    }
                }
            }
        };
        
        /*
         * called by node3.js:onPageGalleryReady()
         * - not used by gallery
         */
        this.onCatalogReady = function(sceneCfg, catalogCfg){
            if (sceneCfg.roleCount) {
                // make actual PageGallery
                PM.main.makePageGallery(sceneCfg, catalogCfg);
            }
            else {
                /*
                 * initialize PageGallery Module, "homepage"
                 */
                var catalog = new PM.Catalog(catalogCfg);
                // just set Performance and show Create Tab
                var performance = new PM.Performance({
                    sceneCfg: sceneCfg,
                    catalog: catalog,
                    // stack: sceneCfg.stack || SNAPPI.StackManager.getFocus(), //deprecate
                });
                // PM.pageMakerPlugin.setScene({performance : performance});
                performance.setStaging();
                // NOTE: performance.getScene() will clear
                // sceneCfg.stage.body
                /*
                 * Page Gallery Getting Started
                 */
                var pgGettingStarted = Y.Node.create("<div id='pg-getting-started'><div><h1>Getting Started with Page Galleries</h1><p>Page Galleries are automatically generated photo collages based on page templates.</p><p>To get started, just choose how many photos you would like to see on a page. A matching page template will be randomly chosen and a Page Gallery automatically created using your top-rated photos. For now, we just offer a selection of simple page templates.</p><p>If you would like to try a different template, just click again.</p><p>Once you see a page you like, just click <b><i>Save Page</i></b> to add this page to an online album - new pages will be added to the end. You can view your album from the <b><i>Preview</i></b> tab, where you will also find a link for easy sharing.</p><p>To begin, just click one of the buttons above.</p><br /></div></div>");
                Plugin.stage.body.append(pgGettingStarted);
                
                // why do we create the performance, but don't use it????
                SNAPPI.TabView.gotoTab('Create');
                SNAPPI.util.LoadingPanel.hide();
            }
        };
        
        this.makePageGallery = function(sceneCfg, catalogCfg){
        	/*
        	 * called AFTER main.launch()
        	 * Pr = Plugin.production, set in main.launch()
        	 * Pr.catalog  
        	 * Pr.tryout
        	 * Pr.sceneCfg.arrangement <- Pr.catalog
        	 * Pr.sceneCfg.auditions <- Pr.tryout
        	 */
        	var Plugin = PM.pageMakerPlugin;
        	if (sceneCfg) {
        		sceneCfg = _Y.merge(sceneCfg, Plugin.sceneCfg);
        		Plugin.sceneCfg = sceneCfg;
        	} else sceneCfg = Plugin.sceneCfg;
        	if (catalogCfg) {
        		try {	// refresh catalog, as necessary
        			catalogCfg = _Y.merge(Plugin.production.catalog.cfg, catalogCfg);
        		}catch(e) {	}
        		Plugin.production.catalog = new PM.Catalog(catalogCfg);
        	}
        	try {
        		if (Plugin.production.catalog.cfg.label == "SnappiCustomFit") {
        			// prepare for new performance
        			// set in Catalog.getCustomFitArrangement()
        			delete Plugin.production.tryout;
        			delete Plugin.production.arrangement;
        			delete Plugin.production.stage;		// delete this?
        		} 
        	} catch(e) {}
        	
			sceneCfg.performance = null;	// reset
			var performance = new PM.Performance({
                sceneCfg: sceneCfg,
                production: Plugin.production,
                catalog: Plugin.production.catalog,
            });
			sceneCfg.performance = performance;
			
            // NOTE: we should really have a performance.update(sceneCfg) method
			var auditions = sceneCfg.auditions;
			if (auditions) {
				try {	// tryout from auditions
					sceneCfg.tryout = new PM.Tryout({
		                sortedhash: auditions,
		                masterTryoutSH: PM.Tryout._pmAuditionSH
		            });
				} catch (e) {
					sceneCfg.tryout = performance.tryout;
				}  
			} else sceneCfg.tryout = performance.tryout;
            /*
			 * set Stage from Plugin.stage
			 */
			// var stage = Plugin.stage;
        	// stage.setContent('');		// empty stage
            performance.setStaging(Plugin.stage, sceneCfg.noHeader);
            
            if (sceneCfg.roleCount) {
                performance.roleCount = sceneCfg.roleCount;
            }
            performance.getScene(sceneCfg);	// implicitly loads Plugin.production
            var check;
        };
        
    };
    
})();
