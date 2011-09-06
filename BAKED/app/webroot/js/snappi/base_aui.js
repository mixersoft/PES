/*
 * SNAPPI util module
 */
(function(){
    /*********************************************************************************
     * Globals
     */
	namespace = function(){
        var a = arguments, o = null, i, j, d;
        for (i = 0; i < a.length; i = i + 1) {
            d = a[i].split(".");
            o = window;
            for (j = 0; j < d.length; j = j + 1) {
                o[d[j]] = o[d[j]] || {};
                o = o[d[j]];
            }
        }
        return o;
    };
    
    /*
     * init *GLOBAL* SNAPPI as root for namespace
     */
    if (typeof SNAPPI == "undefined" || !SNAPPI) {
        SNAPPI = {
            id: "SNAPPI",
            name: 'Snaphappi',
            namespace: namespace
        };
    }
    

    /*
     * LEGACY init handler, DEPRECATE
     * 	- CHANGED TO PAGE.init array
     */
    PAGE.init = PAGE.init || [];
    
	/*
	 * protected
	 */
    
    /***********************************************************************************
     * Config - bootstrap config methods
     */
	var Config = function(){
	};    
	Config.prototype = {};
	SNAPPI.Config = Config;	// make global

	// static methods
	/**
	 * getHost - infers correct host config depending on startup mode [localhost|server|AIR]
	 */
	Config.getHostConfig = function() {
	    var o = {};		
	    try {
	        // get host from AIR bootstrap
	        host = SNAPPI.isAIR ? SNAPPI.AIR.host : window.location.host;
	    } catch (e) {
	        host = window.location.host;	// hostname:port number
	    }
        //                console.log("host=" + host);
	    o.host = host;
	    o.isLocalhost = !(/snaphappi.com/.test(host)); // live vs dev site	
	    if (o.isLocalhost) {
	    	o.snappi_comboBase = 'baked/app/webroot&';
	    	o.snappi_useCombo = false;
	    	o.pagemaker_useCombo = true;
	    	o.alloy_useCombo = true;
//	    	o.alloy_useCombo = false;
	    } else {
	    	o.snappi_comboBase = 'app/webroot&';
	    	o.snappi_useCombo = false;	// TODO: combo doesn't work..
	    	o.pagemaker_useCombo = true;
	    	o.alloy_useCombo = true;	    	
	    }
		return o;
	};    
	/**
	 * config loading for AlloyUI module, 
	 * @param hostCfg
	 * @return
	 */
	Config.addModule_alloy = function(hostCfg) {
	    /*
	     * use COMBO LOADING for alloy js lib
	     * 	NOTES:
	     * 	- requires javascript bootstrap: 'alloy-1.0.1/build/aui/aui.js'
	     * 	- use AUI, not YUI 
	     * 	- set filter in yuiConfig.yui 
	     */
		var yuiConfig_alloy;
		if (hostCfg.alloy_useCombo == true) {
			yuiConfig_alloy = {
	            combine: true,
	            base: 'http://' + hostCfg.host + '/svc/lib/'+ALLOY_VERSION+'/build/',
	            comboBase: 'http://' + hostCfg.host + '/combo/js?baseurl=svc/lib/'+ALLOY_VERSION+'/build&',
	            root: '/',		// base for combo loading, combo load uri = comboBase+root+[module-name]
	//	        filter: "MIN",    // filter ['MIN'|'DEBUG'|'RAW'], set in yuiConfig.yui    
	            filter:'MIN',            
	            modules: AUI.AUI_config.groups.alloy.modules,
	            name: 'alloy'
		    };
		} else {
			// default is combine==false, filter='RAW'
			yuiConfig_alloy = AUI.AUI_config.groups.alloy;
		}
	    return yuiConfig_alloy;
	}; 	
	/**
	 * snappi javascript module
	 * @param hostCfg
	 * @return
	 */
	Config.addModule_snappi = function(hostCfg) {
		hostCfg = hostCfg || Config.getHostConfig();
	    var yuiConfig_snappi = {
            combine: hostCfg.snappi_useCombo,
            base: 'http://' + hostCfg.host + '/js/snappi/',
            comboBase: 'http://' + hostCfg.host + '/combo/js?baseurl='+hostCfg.snappi_comboBase,
            root: 'js/snappi/',						// base for combo loading, combo load uri = comboBase+root+[module-name]
            modules: {
	    		'snappi-event-hover': {
	    			path: 'event_hover.js',
	    			requires:["event-synthetic"]
	    		},
        		'snappi-toolbutton': {
        			path:'toolbuttons.js',
        			requires:['node']
        		},
        		'snappi-property' : {
        			path : 'property.js',
        			requires : ['node', 'base']
        		},
        		'snappi-dialog-aui': {
        			path: 'dialog_aui.js',
        			requires:['node', 'aui-aria', 'aui-dialog', 'aui-overlay-manager', 'dd-constrain']
        		},
        		'snappi-menu-aui': {
        			path: 'menu_aui.js',
        			// BUG: requires A.Plugin.IO, found in "aui-io", but not available
        			requires:['aui-io', 'aui-aria', 'aui-overlay-context', 'aui-overlay-manager']
        		},        		
        		'snappi-paginator': {
        			path: 'paginator_aui.js',
        			requires:['aui-io', 'aui-paginator']
        		},              		
        		'snappi-dialogbox': {
        			path: 'dialogbox.js',
        			requires:['node']
        		},        		
        		'snappi-dialogboxCfg': {
           			path: 'dialogboxcfg.js',	
           			requires:['node', 'snappi-dialogbox']
           		},
        		'snappi-menu': {
        			path: 'menu.js',
        			requires:['node']
        		},
        		'snappi-menuitem': {
        			path: 'menuitem.js',
        			requires:['node', 'substitute']
        		},
        		'snappi-menucfg': {
        			path: 'menucfg.js',
        			requires:['node', 'node-event-simulate', 'snappi-menu', 'snappi-menuitem']
        		},
        		'snappi-zoom': {
    				path: 'zoom.js',
    				requires:['node']
    			},
                'snappi-tabs': {
                    path: 'tabs.js',
                    requires: ['node']
                },
                'snappi-sort': {
                    path: 'sort.js',
                    requires: ['node']
                },
                'snappi-sortedhash': {
                    path: 'sortedhash.js',
                    requires: ['node', 'snappi-sort', 'gallery-data-element']
                },
                'snappi-dragdrop': {
                    path: 'dragdrop3.js',
                    requires: ['node', 'event', 'dd', 'dd-delegate', 'dd-plugin', 'dd-drop-plugin', 'anim']
                },
                'snappi-imageloader': {
                    path: 'imageloader.js',
                    requires: ['node', 'async-queue', 'event'] // also references SNAPPI.util.LoadingPanel
                },	                
                'snappi-rating': {
                    path: 'rating.js',
                    requires: ['node', 'event', 'stylesheet', 'snappi-thumbnail', 'aui-loading-mask']
                },
                'snappi-thumbnail': {
                    path: 'thumbnail3.js',
                    requires: ['node', 'substitute', 'stylesheet', 'event', 'overlay', 'gallery-util', 'snappi-rating', 'gallery-group', 'snappi-dragdrop', 'snappi-imageloader']
                    //'gallery-util' SNAPPI.util.hash(bindTo) may be deprecated 
                },
                'snappi-thumbnail-helpers': {
                    path: 'thumbnail-helpers.js',
                    requires: []
                },
                'snappi-gallery': {
                    path: 'gallery.js',
                    requires: ['node', 'event', 'event-key', 'snappi-event-hover', 'snappi-utils', 'snappi-rating', 
                               'snappi-dialog-aui', 'snappi-menu-aui', 'snappi-paginator', 'snappi-thumbnail-helpers'] // snappi-util -> SNAPPI.shotController(move)
                },                                       
                // 'snappi-photoroll': {
                    // path: 'photo-roll.js',
                    // requires: ['node', 'event', 'event-key', 'snappi-event-hover', 'snappi-utils', 'snappi-rating', 
                               // 'snappi-dialog-aui', 'snappi-menu-aui', 'snappi-paginator', 'snappi-thumbnail-helpers'] // snappi-util -> SNAPPI.shotController(move)
                // },
                'snappi-domJsBinder': {
                    path: 'domJsBinder.js',
                    requires: ['node', 'event-custom', 'io', 'gallery-datasource', 'gallery-auditions', 'snappi-sort', 'snappi-gallery']
                },
                'snappi-lightbox': {
                    path: 'lightbox.js',
                    requires: ['node', 'substitute', 'event', 'io', 'dd', 'dd-plugin', 'snappi-utils', 'snappi-sortedhash', 'snappi-gallery', 'snappi-dragdrop', 'snappi-domJsBinder', 'snappi-rating', 'pagemaker-base',
                               /*
                                * experimental
                                */
//	                               'yui2-resize'
                               // 'resize' // yui3 resize
                               ]
                    // snappi-util -> SNAPPI.shotController, SNAPPI.ratingManager, SNAPPI.io (move)
                },
                'snappi-utils': {
                    path: 'utils.js',
                    requires: ['node', 'event-custom', 'io', 'substitute',
                               'aui-io',
                               'snappi-rating', 'snappi-gallery', 'snappi-lightbox']
                },
                'snappi-io': {
                    path: 'io.js',
                    requires: ['node', 'io', 'json', 'aui-io', 'aui-loading-mask']
                },  
                'snappi-io-helpers': {
                    path: 'io_helpers.js',
                    requires: ['async-queue', 'node', 'substitute', 'snappi-io']
                },           
                'snappi-session': {
                    path: 'helper_session.js',
                    requires: ['snappi-io']
                },
                'snappi-filter': {
                    path: 'filter.js',
                    requires: ['node', 'snappi-rating']
                }
            }
        };
	    return yuiConfig_snappi;
	};
	/**
	 * legacy module, move to snappi
	 * @param hostCfg
	 * @return
	 */
	Config.addModule_gallery = function(hostCfg) {
		hostCfg = hostCfg || Config.getHostConfig();	
	    var yuiConfig_gallery = {
	            combine: hostCfg.snappi_useCombo,
	            base: 'http://' + hostCfg.host + '/js/gallery/',
	            comboBase: 'http://' + hostCfg.host + '/combo/js?baseurl='+hostCfg.snappi_comboBase,
	            root: 'js/gallery/',						// base for combo loading, combo load uri = comboBase+root+[module-name]
	            modules: {
	                'gallery-util': {
	                    path: 'util.js',
	                    requires: ['node', 'yui2-container']
	                },
	                'gallery-data-element': {
	                    path: 'dataelement.js',
	                    requires: ['node', 'yui2-container']
	                },
	                'gallery-group': {
	                    path: 'groups3.js',
	                    requires: ['node', 'snappi-sortedhash', 'snappi-dragdrop']
	                },
	                'gallery-datasource': {
	                    path: 'datasource3.js',
	                    requires: ['node', 'async-queue', 'io', 'datatype-xml', 'gallery-util', 'gallery-data-element', 'gallery-group']
	                },
	                'gallery-auditions': {
	                    path: 'auditions.js',
	                    requires: ['node', 'gallery-util', 'gallery-group', 'snappi-sortedhash']
	                }
	            }
	        };
	     		
	    return yuiConfig_gallery;
	};   
	/**
	 * Pagemaker module, bootstrap file for Pagemaker module 
	 * @param hostCfg
	 * @return
	 */
	Config.addModule_pagemaker = function(hostCfg) {
		hostCfg = hostCfg || Config.getHostConfig();	
	    var yuiConfig_pagemaker = {
            combine: hostCfg.pagemaker_useCombo,
            base: 'http://' + hostCfg.host + '/app/pagemaker/js/create/',
            comboBase: 'http://' + hostCfg.host + '/combo/js?baseurl=PAGEMAKER&',
            root: 'js/create/',
            modules: {
                'pagemaker-base': {
                    path: 'base.js',
                    requires: ['node', 'io', 'fleegix_xml']
                }
            }
        };
	    return yuiConfig_pagemaker;
	}; 	
	
	/**
	 * utility and external modules, 
	 * @param hostCfg
	 * @return
	 */
	Config.addModule_jsLib = function(hostCfg) {
		hostCfg = hostCfg || Config.getHostConfig();	
	    var yuiConfig_jsLib = {
            combine: false,
            base: 'http://' + hostCfg.host + '/js/lib/',
            comboBase: null,
            root: 'js/lib/',
            modules: {
                'fleegix_xml': {
                    path: 'xml.js'
    		    },	
    		    'datejs': {
    		    	path: 'datejs.js'
    	    	},
    	        'snappi-debug': {
    	        	path: 'debug.js'
                }
            }
        };
	    return yuiConfig_jsLib;
	}; 	
	/**********************************************************************************
	 * end static Class Configure
	 **********************************************************************************/
	
	
	
	
	/*
	 * bootstrap YUI, Alloy, and snaphappi javascript
	 */
	var hostCfg = Config.getHostConfig();
    var Y;
	var yuiConfig = { // GLOBAL
    	// AUI will set base for yui load	
//      base: "http://yui.yahooapis.com/combo?3.1.1/build/",  
        timeout: 10000,
        loadOptional: false,
        combine: hostCfg.alloy_useCombo,	// yui & alloy combine values will match 
        allowRollup: true,
//      filter: "MIN",		// ['MIN','DEBUG','RAW'], default='RAW'        
//		filter: "DEBUG",
        filter: hostCfg.alloy_useCombo ? 'MIN' : "RAW",
        insertBefore: 'css-start',
		groups: {
    		alloy: Config.addModule_alloy(hostCfg),
    		snappi: Config.addModule_snappi(hostCfg),
    		gallery: Config.addModule_gallery(hostCfg),
    		pagemaker: Config.addModule_pagemaker(hostCfg),
    		jsLib: Config.addModule_jsLib(hostCfg)
    	}
    };
    SNAPPI.yuiConfig = yuiConfig;		// make global
	
    /*
     * YUI3 init, use External Module Loading
     * - base.js dependencies = 'node', 'event-custom', 'node-menunav', "yui2-container", 'snappi-utils', 'snappi-io',  'snappi-dragdrop', 'snappi-domJsBinder'
     *
     */
	AUI(SNAPPI.yuiConfig).use(    
	/*
     * required by base
     */
    'node', 'event', 'event-custom', 
    
    /*
     * add custom/synthetic hover event
     */
    "event-synthetic", "event-mouseenter", 
    
	/*
     * 'async-queue': required by snappi-imageloader, singleton init()
     */
    'async-queue',    

	/*
     * 'yui2-container'
     * - required by gallery-data-element static function
     * - gallery-util loading panel (deprecated)
     */
    'yui2-container', 
    
    'snappi-debug',
    
    /*
     * callback function
     */
    function(Y, result){
	    if (!result.success) {
			
			Y.log('Load failure: ' + result.msg, 'warn', 'Example');
			
		}    	
	    
	    Y.on("domready", function() {
//console.log('domready 1 fired');	    	
	    	SNAPPI.domready = true;
	    });
	    
        /*
         * Helper Functions
         */
        Y.Node.prototype.dom = function(){
            return Y.Node.getDOMNode(this);
        };
        Y.Node.prototype.ynode = function(){
            return this;
        };
        try {	// ie8 incompatibility
	        HTMLElement.prototype.dom = function(){
	            return this;
	        };
	        HTMLElement.prototype.ynode = function(){
	            return Y.one(this);
	        };
        } catch(e) {}
        
   

        // make global
        SNAPPI.Y = Y;
        YAHOO = SNAPPI.Y.YUI2; // YUI2 deprecate when possible	
        /*
         * ADD modules to existing Y instance
         */	
        
        Y.ready(
        		/*
        		 * primary scripts
        		 */
        		'snappi-event-hover',
        		'snappi-dragdrop', 'snappi-tabs', 'snappi-domJsBinder', 
        		'snappi-lightbox', 'snappi-gallery', 'snappi-thumbnail',  
        		'snappi-filter', 
        		/*
        		 * pagemaker
        		 */
        		'pagemaker-base', 
        		/*
        		 * util scripts
        		 */
        		'snappi-imageloader', 'snappi-utils', 'snappi-io',  'snappi-io-helpers',  'snappi-session',
        		'snappi-toolbutton', 'snappi-menu', 'snappi-menuitem', 'snappi-dialogbox', 'snappi-zoom', 
        		'snappi-menucfg', 'snappi-dialogboxCfg','snappi-property',
        		
        		/*
        		 * aui modules
        		 */
        		'aui-skin-classic-all', 'aui-delayed-task',
        	    'aui-io', 'aui-loading-mask',   
        	    // 'aui-resize',      		

        		     		
		/*
		 * 
		 */
		function(Y, result){
		    if (!result.success) {
			
				Y.log('Load failure: ' + result.msg, 'warn', 'Example');
				
			}
			else {
				/*
				 * all script files loaded, begin init
				 */

				if (SNAPPI.domready) {
//console.log('snappi JS ready fired after domready');					
					main();		// domready event already called
				} else {
//console.log('snappi JS ready fired BEFORE domready');					
					var detach = Y.on("domready", function() {
//console.log('domready 2 fired');
						detach.detach();
				    	main();
				    });
				}
			}
        });
    });
    
    
    
    
    
    
    
    
    
    
    /*************************************************************************************
     * 	MODULE Startup/Init
     */

   	/*
	 * merge session/state data
	 * 	use PAGE.jsonData.STATE (global) to pass default session/state data via HTTP GET/XHR
	 *  use SNAPPI.STATE (global) to store local session/state across XHR gets
	 */    
    SNAPPI.mergeSessionData = function(){
    	var Y = SNAPPI.Y;
        try {
            SNAPPI.STATE = SNAPPI.STATE  || {}; // stores session data across XHR calls
        	SNAPPI.STATE = Y.merge(PAGE.jsonData.STATE, SNAPPI.STATE);
        	PAGE.jsonData.STATE = {};
    		// merge PAGE into SNAPPI.STATE
    		try {
	    		var ccPage = PAGE.jsonData.castingCall.CastingCall.Auditions;
	    		SNAPPI.STATE.displayPage = {
	    				page: ccPage.Page,
	    				perpage: ccPage.Perpage,    				
	    				pageCount: ccPage.Pages,
	    				total: ccPage.Total
	    		};
    		} catch (e) {}
        } catch (e) { }
        try {
        	SNAPPI.STATE.filters = SNAPPI.STATE.filters || [];
        	SNAPPI.STATE.filters = PAGE.jsonData.filter; // this is an array
        } catch(e) {}
        	
        if (SNAPPI.STATE.controller === undefined) SNAPPI.STATE.controller = PAGE.jsonData.controller;
        
        
        
      
        return SNAPPI.STATE;
    };
    
    
    // PRIVATE METHODS
        
    
    var pageInit = function(){        // execute embedded PAGE.init scripts
	    while (PAGE.init.length) {
	        var init = PAGE.init.shift();
	        try {
	        	init();	
	        } catch (e) {}
	        
	    }   
	};
    
    /*
     * Snappi Global Init
     */
    var main = function(){
//console.log('main()');    	
        var Y = SNAPPI.Y;
        SNAPPI.mergeSessionData();

        SNAPPI.MenuAUI.initMenus();

        /****************************************************************************
         * add additional onload events
         */        
        // rename event to snappi:onXhrLoad
        Y.on('snappi:ajaxLoad', function(){
        	var check;
        });
        
        /*
         * these methods reference audition property
         */
        Y.on('snappi:afterPhotoRollInit', function(){
            /**
             * 	- source: domJsBinder.bindAuditions2Photoroll(),
             * 		also Y.io for JSON request
             */
        });
        
        /****************************************************************************
         * init singletons AFTER ALL SCRIPTS HAVE BEEN LOADED
         */
        
        /*
         *  SNAPPI.sortConfig:  config defaults for SNAPPI.sortConfig.byRating, etc.
         */
        SNAPPI.sortConfig.init();	
        
        var event;
        if (PAGE.jsonData.castingCall) event = 'snappi:afterPhotoRollInit';
        else if (PAGE.jsonData.lightbox) event = 'snappi:afterMain';
        SNAPPI.Lightbox.loadonce(null, event);
        
        /*
         * filter bar
         */
        SNAPPI.filter.renderBar(SNAPPI.STATE.filters);
        
        
        /*
         * embedded PAGE.init scripts
         */ 
        pageInit(); 
        SNAPPI.ajax.fetchXhr();

        
        /*
         * initialize SNAPPI.TabNav 
         * 		- checks PAGE.section global
         */
        try {
            // set tab, if any are defined
            if (PAGE.section) SNAPPI.TabNav.selectByName(PAGE);
        } 
        catch (e) {
        }
        
//        SNAPPI.dialogbox.init(SNAPPI.dialogbox.cfg);
//        SNAPPI.cfg.MenuCfg.renderHeaderMenus();
        
        var check;
        
        /**********************************************************
         * optional inits
         * - there should be room to optimize what we init for each page
         */
        // TODO: restore Lightbox is useful only if we are saving to desktop cookie
        // 			right now default is SERVER cookie
        //		SNAPPI.lightbox.restoreLightboxByCookie();	
        
        // Pagemaker default cfg  
        try {
        	SNAPPI.PM.cfg = {
                    fn_DISPLAY_SIZE: function(){
                        return {
                            h: 600
                        };
                    }
                };
        }catch (e){
        	
        }
        
        Y.fire('snappi:afterMain');
        
    };
})();

