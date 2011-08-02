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
 * - provides SNAPPI.PM.main.go()
 * - loaded by base.js, SNAPPI.PageMakerPlugin.load()
 *
 */
// TODO: should rename to base.js for consistency in the naming scheme
(function(){
    var PM = SNAPPI.namespace('SNAPPI.PM');
    var Y = PM.Y;
    
    
    
    var main = new function() { 
    	var _self = this;
        /*
         * launch plugin
         */
    	this.go = function(ioCfg) {
    		var Y = PM.Y;
            if (ioCfg && ioCfg.method) 
                this.ioCfg = ioCfg;    		
            var stage = Y.one('#content > div#pagemaker');
            if (!stage) {
                stage = Y.Node.create('<div id="pagemaker" class="placeholder"></div>');
                Y.one('#content').append(stage);
            }    	
            this.stage = stage;
            SNAPPI.PageGalleryPlugin.stage = stage;
			var asyncCfg = {
				fn : _self.launch,
				node : stage,
				context : _self,
				size: 'huge',
				args : []
			};
			var async = new SNAPPI.AsyncLoading(asyncCfg);
			stage.setStyle('height', '400px');
			async.execute();            
    	};
    	
    	this.launch = function(){
    		
            var retry = function() {
            	_self.launch.call(_self);
            };
			
            /****
             * load Picasa catalog of arrangements
             * - Asynch Y.io, uses callbacks
             */
            var catalogHost = SNAPPI.PageMakerPlugin.getHost();
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
            	this.catalogLoadStatus = SNAPPI.PM.Catalog.getCatalog(catalogCfg);
            }
            if (this.catalogLoadStatus == 'loading') {
                return;	// retry
            }
            var catalog = new SNAPPI.PM.Catalog(catalogCfg); // calls Catalog.getCatalog() in constructor
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
            var tryout = new SNAPPI.PM.Tryout({
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
            
            var stage = this.stage || SNAPPI.PageGalleryPlugin.stage;
            SNAPPI.PM.node.startListeners();
            
            // scene display config for designer mode
            var previewDisplayCfg = {
                label: "Pagemaker",
                stage: stage
            };
			try {
                // NOTE: scale pageGallery with in production w/ NATIVE_PAGE_GALLERY_H constant
				previewDisplayCfg.fnDisplaySize = PM.cfg.fn_DISPLAY_SIZE;
			} catch (e) {
				previewDisplayCfg.fnDisplaySize = _defaultCfg.fn_DISPLAY_SIZE;
			}			
            sceneCfg = Y.merge(sceneCfg, previewDisplayCfg);
            
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
                    spacing: spacing,
                    margin: margin,
                    stage: stage,
                    useHints: this.useHints,
                    //                    arrangement: Arr,
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
            stage.productionCfg = productionCfg;
            stage.production = new PM.Production(productionCfg);
            
            
            /****
             * set staging for Performance
             */
            // just set Performance and show Create Tab
            var performance = new PM.Performance({
                sceneCfg: sceneCfg,
                catalog: catalog,
                tryout: sceneCfg.tryout
            });
            
            stage = performance.setStaging();
            SNAPPI.PM.performance = performance;
            // performance.tryout == tryout;
            // performance.tryout.dataSource = original, parsed sortedhash of Auditions
            // performance.tryout.pmAuditionSH (copy) = performance.tryout.getAuditionsFromSortedHash(null)
            
            
            // NOTE: performance.getScene() will clear
            // sceneCfg.stage.body
            /*
             * Page Gallery Getting Started
             */
            var pgGettingStarted = Y.Node.create("<div id='pg-getting-started'><div><h1>Getting Started with PageMaker</h1><p>Page Galleries are automatically generated photo collages based on page templates.</p><p>To get started, just choose how many photos you would like to see on a page. A matching page template will be randomly chosen and a Page Gallery automatically created using your top-rated photos. For now, we just offer a selection of simple page templates.</p><p>If you would like to try a different template, just click again.</p><p>Once you see a page you like, just click <b><i>Save Page</i></b> to add this page to an online album - new pages will be added to the end. You can view your album from the <b><i>Preview</i></b> tab, where you will also find a link for easy sharing.</p><p>To begin, just click one of the buttons above.</p><br /></div></div>");
            sceneCfg.stage.body.append(pgGettingStarted);
            
            SNAPPI.Y.fire('snappi-pm:after-launch', stage);
            
			try {
				SNAPPI.Y.fire('snappi:completeAsync', stage);
				stage.dom().Async.removeLoading();
			} catch (e) {
				var check;
			}
        };  
        
        this.getTryout = function(ioCfg, fnContinue){
        	var Y = PM.Y;
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
        
        
        this.onCatalogReady = function(sceneCfg, catalogCfg){
            if (sceneCfg.roleCount) {
                // make actual PageGallery
                SNAPPI.PM.main.makePageGallery(sceneCfg, catalogCfg);
            }
            else {
                /*
                 * initialize PageGallery Module, "homepage"
                 */
                var catalog = new SNAPPI.PM.Catalog(catalogCfg);
                // just set Performance and show Create Tab
                var performance = new SNAPPI.PM.Performance({
                    sceneCfg: sceneCfg,
                    catalog: catalog,
                    stack: sceneCfg.stack || SNAPPI.StackManager.getFocus()
                });
                
                performance.setStaging();
                // NOTE: performance.getScene() will clear
                // sceneCfg.stage.body
                /*
                 * Page Gallery Getting Started
                 */
                var pgGettingStarted = Y.Node.create("<div id='pg-getting-started'><div><h1>Getting Started with Page Galleries</h1><p>Page Galleries are automatically generated photo collages based on page templates.</p><p>To get started, just choose how many photos you would like to see on a page. A matching page template will be randomly chosen and a Page Gallery automatically created using your top-rated photos. For now, we just offer a selection of simple page templates.</p><p>If you would like to try a different template, just click again.</p><p>Once you see a page you like, just click <b><i>Save Page</i></b> to add this page to an online album - new pages will be added to the end. You can view your album from the <b><i>Preview</i></b> tab, where you will also find a link for easy sharing.</p><p>To begin, just click one of the buttons above.</p><br /></div></div>");
                sceneCfg.stage.body.append(pgGettingStarted);
                
                // why do we create the performance, but don't use it????
                SNAPPI.TabView.gotoTab('Create');
                SNAPPI.util.LoadingPanel.hide();
            }
        };
        
        this.makePageGallery = function(sceneCfg, catalogCfg){
        	// moved from node3.js:_makePageGallery()
			var stage = SNAPPI.PageGalleryPlugin.stage;
			var performance = stage ? stage.performance : null;
            /*
             * produce and render Production
             */
            if (!performance) {
                var catalog = new SNAPPI.PM.Catalog(catalogCfg);
                performance = new SNAPPI.PM.Performance({
                    sceneCfg: sceneCfg,
                    catalog: catalog,
//                    stack: sceneCfg.stack || SNAPPI.StackManager.getFocus()
                    end: null
                });
            }
			if (!sceneCfg.performance) sceneCfg.performance = performance;
			
			// get performance Tryout from Lightbox.getSelected();
            // NOTE: we should really have a performance.update(sceneCfg) method
			var auditions = sceneCfg.auditions || SNAPPI.lightbox.getSelected();
			try {
				sceneCfg.tryout = new SNAPPI.PM.Tryout({
	                sortedhash: auditions,
	                masterTryoutSH: SNAPPI.PM.Tryout._pmAuditionSH
	            });
			} catch (e) {
				sceneCfg.tryout = performance.tryout;
			}  
            
            if (sceneCfg.stage) {
                performance.setStaging(sceneCfg.stage, sceneCfg.noHeader);
            }
            
            if (sceneCfg.roleCount) {
                performance.roleCount = sceneCfg.roleCount;
                // // remove getting-started div
                // var n = Y.one('div#pg-getting-started')
                // if (n)
                // n.remove();
            }
            
//			//  DEPRECATE codepath for launch from gallery project DEPRECATE
//            if (sceneCfg.stack) {
//                performance.setAuditions({
//					oStack: sceneCfg.stack
//				});
//            }
//			//  DEPRECATE codepath for launch from BAKED
//            if (sceneCfg.tryout && !sceneCfg.stack) {
//				// refresh tryout.pmAuditionSH
//				sceneCfg.tryout.getAuditionsFromSortedHash();
//            }	
            
            performance.getScene(sceneCfg);
            var check;
        };
        
    };
    
    PM.main = main;
    
})();
