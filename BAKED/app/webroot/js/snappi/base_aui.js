/*
 * SNAPPI util module
 */
(function(){
        
    /*
     * 	
     */
    var _pageInit = function(){        // execute embedded PAGE.init scripts
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
    var _main = function(){
// console.log('_main()');    	
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
        var once1 = Y.on('snappi:after_GalleryInit', function(){
            /**
             * fired by: /groups/roll, /member/roll initOnce()
             */
            once1.detach();
			SNAPPI.UIHelper.markup.set_ItemHeader_WindowOptions();
			SNAPPI.setPageLoading(false);
        });
        var once2 = Y.on('snappi:after_PhotoGalleryInit', function(){
            /**
             * 	- fired by: GalleryFactory.Photo.build(), GalleryFactory.PhotoAIR,
             */
            once2.detach();
        	var delayed = new Y.DelayedTask( function() {
				SNAPPI.lightbox = new SNAPPI.Lightbox();
			});
			delayed.delay(1000);	
			SNAPPI.UIHelper.markup.set_ItemHeader_WindowOptions();
			SNAPPI.setPageLoading(false); 
        });        
        Y.on('snappi:afterLightboxInit', function(){
            /**
             * 	- fired by: new Lightbox.init(),
             */
        });        
        
        /****************************************************************************
         * init singletons AFTER ALL SCRIPTS HAVE BEEN LOADED
         */
        
        /*
         *  SNAPPI.sortConfig:  config defaults for SNAPPI.sortConfig.byRating, etc.
         */
        SNAPPI.sortConfig.init();	
        
        // var event;
        // if (PAGE.jsonData.castingCall) event = 'snappi:after_PhotoGalleryInit';
        // else if (PAGE.jsonData.lightbox) event = 'snappi:afterMain';
        // SNAPPI.Lightbox.loadonce(event);
        
        /*
         * filter bar
         */
        SNAPPI.filter.renderBar(SNAPPI.STATE.filters);
        
        /*
         * embedded PAGE.init scripts
         */ 
        _pageInit(); 
        SNAPPI.xhrFetch.fetchXhr(null, {delay:2000});

		// start document UI listeners
		var listeners = {
			'DragDrop': 1,
		};
		for (var listen in listeners) {
			if (listeners[listen]!==false) SNAPPI.UIHelper.listeners[listen](listeners[listen]);
		}        	
    	
        
        // ready now, or after Gallery init   
        var isXhrGet =  Y.one('#body-container .xhr-get');
        if (!isXhrGet) {
        	SNAPPI.setPageLoading(false);
        }                   
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
	
	// convenience func to put Y.use() at the top of file
	var _afterLoad = function(){
		namespace('CFG');
		CFG = {		// frequently used startup Config params 
			DEBUG : {	// default when hostname==git*
	    		snappi_comboBase: 'baked/app/webroot&',
	    		snappi_useCombo: 0,					// <-- TESTING SNAPPI useCombo
	    		pagemaker_useCombo: true,
	    		alloy_useCombo: true,
	    		yahoo_CDN: 0,
	    		YUI_VERSION: '3.3.0',	// this is actually set in aui.js
	    		// yui_CDN == true => use "http://yui.yahooapis.com/combo?"
				// yui_CDN == false => use snaphappi hosted yui libs: "/combo/js?"
		    },
	    	PROD : {	// use for unix/server testing
	    		snappi_comboBase: 'app/webroot&',
	    		snappi_useCombo: 1,
	    		pagemaker_useCombo: true,
	    		alloy_useCombo: true,
	    		yahoo_CDN: 0,
	    		YUI_VERSION: '3.3.0',
	    	}
	    }
		/*
		 * bootstrap YUI, Alloy, and snaphappi javascript
		 * will automatically set to CFG.DEBUG for /git/.test(hostname)
		 */
		var hostCfg = Config.getHostConfig(	{} );
	    var Y;
		var yuiConfig = { // GLOBAL
	    	// AUI will set base for yui load	
	     	// yui3 base for alloy_useCombo=false
	    	// base: "/svc/lib/yui3/",		 
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
	    // update yuiConfig for yahoo CDN config
	    if (hostCfg.alloy_useCombo && hostCfg.yahoo_CDN == false) {
	    	// use hosted combo services
	    	yuiConfig.comboBase = 'http://' + hostCfg.host + '/combo/js?baseurl=svc/lib/yui_'+hostCfg.YUI_VERSION+'/yui/build&';
	    	yuiConfig.root = '/';
	    }
	    
	    SNAPPI.yuiConfig = yuiConfig;		// make global
	    
	    /*
	     * YUI3 init, use External Module Loading
	     * - base.js dependencies = 'node', 'event-custom', 'node-menunav', "yui2-container", 'snappi-util', 'snappi-io',  'snappi-dragdrop', 'snappi-domJsBinder'
	     *
	     */
		AUI(SNAPPI.yuiConfig).use(    
			// load first to minimize CSS flash, required for menu-aui/dialog-aui CSS
			'aui-skin-classic-all', 	
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
		
		    // 'snappi-debug',
		    
	    /*
	     * callback function
	     */
	    function(Y, result){
		    if (!result.success) {
				
				Y.log('Load failure: ' + result.msg, 'warn', 'Example');
				
			}    	
		    Y.one('body').addClass('wait');
		    Y.on("domready", function() {
	console.log('domready fired');	    	
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
console.log('SNAPPI.Y is defined');	        
	        SNAPPI.setPageLoading = function (value) {
	        	if (value == undefined) return Y.one('body').hasClass('wait');
	        	if (value) Y.one('body').addClass('wait');
	        	else {
	        		Y.one('body').removeClass('wait');
	        		Y.one('#related-content').removeClass('hide');
	        	}
	        	return value ? true : false;
	        }
	        YAHOO = SNAPPI.Y.YUI2; // YUI2 deprecate when possible	
	        /*
	         * ADD modules to existing Y instance
	         */	
	        
	        Y.ready(
	        		/*
	        		 * aui modules
	        		 */
	        	    'aui-delayed-task', 'aui-io', 'aui-loading-mask', 	        	
	        		/*
	        		 * primary scripts
	        		 */
	        		'snappi-event-hover',
	        		'snappi-dragdrop', 
	        		'snappi-sortedhash',
	        		'snappi-group',
	        		'snappi-thumbnail-helpers', 
	        		'snappi-imageloader',
	        		'gallery-util',
	        		'snappi-thumbnail',
	        		'snappi-io',
	        		'snappi-dialog-aui',
	        		'snappi-menu-aui',
	        		'snappi-paginator',
	        		'snappi-gallery-helpers',
	        		'snappi-io-helpers',
	        		'snappi-ui-helpers',
	        		'snappi-util',
	        		'snappi-gallery', 
	        		'snappi-lightbox',
	        		'snappi-filter', 'snappi-tabs',
	        		/*
	        		 * pagemaker
	        		 */
	        		'pagemaker-base', 
	        		/*
	        		 * util scripts
	        		 */
	        		
	        		// deprecated
	        		// 'snappi-property', 'snappi-menucfg', 'snappi-toolbutton', 'snappi-menu', 'snappi-menuitem', 'snappi-dialogboxCfg','snappi-dialogbox', 'snappi-zoom',
	        		// UNUSED
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
						_main();		// domready event already called
					} else {
	console.warning('Y.ready() BEFORE domready');					
						var detach = Y.on("domready", function() {
							detach.detach();
					    	_main();
					    });
					}
				}
	        });
	    });
	}
	
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
    // define global namespaces
    namespace('SNAPPI');
    namespace('PAGE');
    
   
    /*
     * init *GLOBAL* SNAPPI as root for namespace
     */
    if (!SNAPPI.id) {
        SNAPPI.id = 'SNAPPI';
		SNAPPI.name = 'Snaphappi';
		SNAPPI.namespace = namespace
    }
    PAGE.init = PAGE.init || [];
    
	/*
	 * protected
	 */
    
    /***********************************************************************************
     * Config - bootstrap config methods
     */
	var Config = function(){};    
	SNAPPI.Config = Config;	// make global

	// static methods
	/**
	 * getHost 
	 * - infers correct host config depending on startup mode [localhost|server|AIR]
	 * - runs BEFORE Y.merge is available	
	 */
	Config.getHostConfig = function(cfg) {
		cfg = cfg || {};
	    var defaultCfg, o = {};		
	    try {
	        // get host from AIR bootstrap
	        var host = SNAPPI.isAIR ? SNAPPI.AIR.host : window.location.host;
	    } catch (e) {
	        host = window.location.host;	// hostname:port number
	    }
        //                console.log("host=" + host);
	    o.host = host;
	    o.isLocalhost = /git/.test(host); // live vs dev site	
	    	
	    if (o.isLocalhost) defaultCfg = CFG.DEBUG;
	    else defaultCfg = CFG.PROD;
	    
	    // merge defaultCfg + overrides
	    for (var prop in defaultCfg) {
	    	o[prop] = defaultCfg[prop];
	    }
	    for (prop in cfg) {
	    	o[prop] = cfg[prop];
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
                'snappi-group': {
                    path: 'groups3.js',
                    requires: ['node', 'snappi-sortedhash', 'snappi-dragdrop']
                },
                // 'snappi-datasource': {
                    // path: 'datasource3.js',
                    // requires: ['node', 'async-queue', 'io', 'datatype-xml', 'gallery-util']
                // },
                'snappi-auditions': {
                    path: 'auditions.js',
                    requires: ['node', 'gallery-util', 'snappi-group', 'snappi-sortedhash']
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
        		'XXXsnappi-dialogbox': {
        			path: 'dialogbox.js',
        			requires:['node']
        		},        		
        		'XXXsnappi-dialogboxCfg': {
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
        		'XXXsnappi-menucfg': {
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
                    requires: ['node', 'snappi-sort']
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
                    requires: ['node', 'substitute', 'stylesheet', 'event', 'overlay', 'gallery-util', 'snappi-rating', 'snappi-group', 'snappi-dragdrop', 'snappi-thumbnail-helpers', 'snappi-imageloader']
                    //'gallery-util' SNAPPI.util.hash(bindTo) may be deprecated 
                },
                'snappi-thumbnail-helpers': {
                    path: 'thumbnail-helpers.js',
                    requires: []
                },
                'snappi-gallery': {
                    path: 'gallery.js',
                    requires: ['node', 'event', 'event-key', 'snappi-event-hover', 
                    'snappi-util', // uses SNAPPI.ShotController
                    'snappi-auditions',
                    'snappi-rating', 'snappi-dialog-aui', 'snappi-menu-aui', 'snappi-paginator', 'snappi-gallery-helpers', 'snappi-thumbnail-helpers'
                    ] // snappi-util -> SNAPPI.shotController(move)
                },                                       
                'snappi-gallery-helpers': {
                    path: 'gallery-helpers.js',
                    requires: []
                },
                'XXXsnappi-domJsBinder': {
                    path: 'domJsBinder.js',
                    requires: ['node', 'event-custom', 'io', 'snappi-auditions', 'snappi-sort', 'snappi-gallery']
                },
                'snappi-lightbox': {
                    path: 'lightbox.js',
                    requires: ['node', 'substitute', 'event', 'io', 'dd', 'dd-plugin', 'snappi-util', 'snappi-sortedhash', 'snappi-gallery', 'snappi-dragdrop',  'snappi-rating', 
                    // 'snappi-domJsBinder',
                               /*
                                * experimental
                                */
//	                               'yui2-resize'
                               // 'resize' // yui3 resize
                               ]
                    // snappi-util -> SNAPPI.shotController, SNAPPI.ratingManager, SNAPPI.io (move)
                },
                'snappi-util': {
                    path: 'util.js',
                    requires: ['node', 'event-custom', 'io', 'substitute',
                               'snappi-rating', 
                               'snappi-lightbox'
                               ]
                },
                'snappi-io': {
                    path: 'io.js',
                    requires: ['node', 'io', 'json', 'aui-io', 'aui-loading-mask']
                },  
                'snappi-io-helpers': {
                    path: 'io_helpers.js',
                    requires: ['async-queue', 'node', 'substitute', 'snappi-io']
                },           
                'snappi-ui-helpers': {
                    path: 'ui-helpers.js',
                    requires: [],
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
	                    requires: ['node']
	                },
	                // 'gallery-data-element': {
	                    // path: 'dataelement.js',
	                    // requires: ['node']
	                // },
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
            combine: 0, // hostCfg.snappi_useCombo,
            base: 'http://' + hostCfg.host + '/js/lib/',
            comboBase: 'http://' + hostCfg.host + '/combo/js?baseurl='+hostCfg.snappi_comboBase,
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
	
	
	
	

    
    
    
    
    
    
    
    
    
    /*************************************************************************************
     * 	MODULE Startup/Init
     */

   	/*
	 * merge session/state data
	 * 	use PAGE.jsonData.STATE (global) to pass default session/state data via HTTP GET/XHR
	 *  use SNAPPI.STATE (global) to store local session/state across XHR gets
	 */    
    SNAPPI.mergeSessionData = function(castingCall){
    	var Y = SNAPPI.Y;
        try {
            SNAPPI.STATE = SNAPPI.STATE  || {}; // stores session data across XHR calls
        	SNAPPI.STATE = Y.merge(PAGE.jsonData.STATE, SNAPPI.STATE);
        	PAGE.jsonData.STATE = {};
    		// merge PAGE into SNAPPI.STATE
    		try {
    			castingCall = castingCall || PAGE.jsonData.castingCall;
	    		var ccPage = castingCall.CastingCall.Auditions;
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
    
    
   
    
    
    _afterLoad();
})();
