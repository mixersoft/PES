/*
 * SNAPPI util module
 */
(function(){
    /*********************************************************************************
     * Globals
     */
    /*
     * init *GLOBAL* SNAPPI as root for namespace
     */
    if (typeof SNAPPI == "undefined" || !SNAPPI) {
        SNAPPI = {
            id: "SNAPPI Namespace",
            DEBUG: true
        };
    }
    
    /*
     * define namespace method,
     * 		same as YUI YAHOO.namespace
     * TODO: should we load SNAPPI.js instead?
     */
    SNAPPI.namespace = function(){
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
    
    /*************************************************************************
     * DEBUG ONLY
     */
    SNAPPI.debug_showNodes = function() {
        if (SNAPPI.DEBUG) {
	        SNAPPI.Y.all('#content div, .FigureBox').each(function (n,i,l) {
	        	if (n.Rating || n.audition || n.Gallery || n.Lightbox || n.Thumbnail) {
	        		n.dom().yNode = n.ynode();
	        	}
	        });
        }
    };
    /*************************************************************************/
    
    
    /*
     * LEGACY init handler, DEPRECATE
     * 	- CHANGED TO PAGE.init array
     */
    PAGE.init = PAGE.init || [];
    
	/*
	 * protected
	 */
	var getHost = function(config) {
	    var host;		
	    try {
	        // AIR base module MUST be loaded before base.js
	        host = SNAPPI.isAIR ? SNAPPI.AIR.host : window.location.host;
	        //                console.log("host=" + host);
	    } 
	    catch (e) {
	        host = window.location.host;	// hostname:port number
	    }
		switch(config) {
		case 'gallery': 
			host = host.replace(window.location.hostname, 'gallery'); break;
		case 'pagemaker':
			host = host; break;
			default:
				host = host; break; 
		}
		return host;
	};    
    
	/*
	 * for combo load for snappi scripts here
	 */
	var host = getHost();
	var localhost = !(/snaphappi.com/.test(host)); // live vs dev site	
	var combo_baseurl = (localhost ? 'baked/' : '') + 'app/webroot&';
	
    var useCombo = 1 && !localhost;
	
    var galleryHost = getHost('gallery');
    var pagemakerHost = getHost('pagemaker');
    
    SNAPPI.namespace('SNAPPI.yuiConfig');
    SNAPPI.yuiConfig.yui = { // GLOBAL
        base: "http://yui.yahooapis.com/combo?3.3.0/build/", //  local='/svc/yuilib/yui3/'; // or,
        timeout: 10000,
        loadOptional: false,
        combine: 1,
        filter: "MIN",
        allowRollup: true,
//		filter: "DEBUG",
        insertBefore: 'css-start',
		groups: {}
    };
    SNAPPI.yuiConfig.snappi = {
        combine: useCombo,
        base: 'http://' + host + '/js/snappi/',
        comboBase: 'http://' + host + '/combo/js?baseurl='+combo_baseurl,
        root: 'js/snappi/',						// base for combo loading, combo load uri = comboBase+root+[module-name]
        modules: {
    		'snappi-toolbutton': {
    			path:'toolbuttons.js',
    			requires:['node']
    		},
//    		'snappi-async' : {	// rename snappi-io-helper
//    			path: 'async.js',
//    			requires:['node', 'base', 'async-queue', 'stylesheet']
//    		},
    		'snappi-property' : {
    			path : 'property.js',
    			requires : ['node', 'base']
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
            'snappi-thumbnail': {
                path: 'thumbnail3.js',
                requires: ['node', 'substitute', 'stylesheet', 'event', 'overlay', 'gallery-util', 'gallery-rating', 'gallery-group', 'snappi-dragdrop', 'snappi-imageloader']
                //'gallery-util' SNAPPI.util.hash(bindTo) may be deprecated 
            },
            'snappi-thumbnail-helpers': {
                path: 'thumbnail-helpers.js',
                requires: []
            },                       
            'snappi-photoroll': {
                path: 'photo-roll.js',
                requires: ['node', 'event', 'event-key', 'snappi-utils', 'gallery-rating'] // snappi-util -> SNAPPI.shotController, SNAPPI.ratingManager (move)
            },
            'snappi-domJsBinder': {
                path: 'domJsBinder.js',
                requires: ['node', 'event-custom', 'io', 'gallery-datasource', 'gallery-auditions', 'snappi-sort', 'snappi-photoroll']
            },
            'snappi-lightbox': {
                path: 'lightbox.js',
                requires: ['node', 'substitute', 'event', 'io', 'dd', 'dd-plugin', 'snappi-utils', 'snappi-sortedhash', 'snappi-photoroll', 'snappi-dragdrop', 'snappi-domJsBinder', 'gallery-rating', 'pagemaker-base',
                           /*
                            * experimental
                            */
//                           'yui2-resize'
                           'resize' // yui3 resize
                           ]
                // snappi-util -> SNAPPI.shotController, SNAPPI.ratingManager, SNAPPI.io (move)
            },
            'snappi-utils': {
                path: 'utils.js',
                requires: ['node', 'event-custom', 'io', 'substitute', 'gallery-rating', 'snappi-photoroll', 'snappi-lightbox']
            },
            'snappi-io': {
                path: 'io.js',
                requires: ['node', 'io', 'json']
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
                requires: ['node', 'gallery-rating']
            }
        }
    };
    SNAPPI.yuiConfig.gallery = {
        combine: useCombo,
        base: 'http://' + host + '/js/gallery/',
        comboBase: 'http://' + host + '/combo/js?baseurl='+combo_baseurl,
        root: 'js/gallery/',						// base for combo loading, combo load uri = comboBase+root+[module-name]
        modules: {
            'gallery-util': {
                path: 'util.js',
                requires: ['node', 'yui2-container']
            },
            'gallery-rating': {
                path: 'rating.js',
                requires: ['node', 'event', 'stylesheet', 'snappi-thumbnail']
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
    SNAPPI.yuiConfig.jsLib = {
        combine: false,
        base: 'http://' + host + '/js/lib/',
        comboBase: null,
        root: 'js/lib/',
        modules: {
            fleegix_xml: {
                path: 'xml.js'
            }
        }
    };
    SNAPPI.yuiConfig.pagemaker = {
        combine: useCombo,
        base: 'http://' + pagemakerHost + '/app/pagemaker/js/create/',
        comboBase: 'http://' + pagemakerHost + '/combo/js?baseurl=PAGEMAKER&',
        root: 'js/create/',
        modules: {
            'pagemaker-base': {
                path: 'base.js',
                requires: ['node', 'io', 'fleegix_xml']
            }
        }
    };
    SNAPPI.yuiConfig.yui.groups.snappi = SNAPPI.yuiConfig.snappi;
    SNAPPI.yuiConfig.yui.groups.gallery2 = SNAPPI.yuiConfig.gallery;
    SNAPPI.yuiConfig.yui.groups.jsLib = SNAPPI.yuiConfig.jsLib;
    SNAPPI.yuiConfig.yui.groups.pagemaker = SNAPPI.yuiConfig.pagemaker;
    var Y;
	
	
	
    
    /*
     * YUI3 init, use External Module Loading
     * - base.js dependencies = 'node', 'event-custom', 'node-menunav', "yui2-container", 'snappi-utils', 'snappi-io',  'snappi-dragdrop', 'snappi-domJsBinder'
     *
     */
    YUI(SNAPPI.yuiConfig.yui).use(    
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
	 * 'resize' : yui3-resize, for lightbox
	 */
    'resize',   
	/*
     * 'yui2-container'
     * - required by gallery-data-element static function
     * - gallery-util loading panel (deprecated)
     * - lightbox resize(?) for yui2 resize
     */
    'yui2-container', 
    
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
        
        /*
         * add 'snappi:hover' custom event
         * see: http://developer.yahoo.com/yui/3/examples/event/event-synth-hover.html
         * TODO: replace with yui3 hover in yui 3.4.0
         */
        var check = Y.Event.define("snappi:hover", {
            processArgs: function (args) {
                // Args received here match the Y.on(...) order, so
                // [ 'hover', onHover, "#demo p", endHover, context, etc ]
	        	var endHover = null, selector = null, context = null;
	            if (args.length > 3) {
	            	endHover = args[3];
	            	args.splice(3,1);
	            }
	            if (args.length > 3) {
	            	context = args[3];
	            	args.splice(3,1);
	            } 
	            if (args.length > 3) {
	            	selector = args[3];
	            	args.splice(3,1);
	            }                

                // This will be stored in the subscription's '_extra' property
	            return {endHover: endHover, context: context, selector: selector};
            },
            on: function (node, sub, notifier) {
            	var onHover = sub.fn;
                var endHover = sub._extra && sub._extra.endHover || null;
                var context = sub._extra && sub._extra.context || null;
                sub.context = context;
                // To make detaching the associated DOM events easy, use a
                // detach category, but keep the category unique per subscription
                // by creating the category with Y.guid()
                sub._evtGuid = Y.guid() + '|';
                
                node.on( sub._evtGuid + "mouseenter", 
	                function (e) {
	                    // Firing the notifier event executes the hover subscribers
                		sub.fn = onHover;
	                    notifier.fire(e);
	                }
                );
                
                node.on(sub._evtGuid + "mouseleave", 
    	            function (e) {
	                    // Firing the notifier event executes the hover subscribers
                		sub.fn = endHover;
                		notifier.fire(e);
	                }
                );
            },
            detach: function (node, sub, notifier) {
                // Detach the mouseenter and mouseout subscriptions using the
                // detach category
                node.detach(sub._evtGuid + '*');
            },
            // add delegate support. it will be used in zoom or other places
            delegate: function (node, sub, notifier, filter) {
            	var onHover = sub.fn;
                var selector = sub._extra && sub._extra.selector || null;
                var context = sub._extra && sub._extra.context || null;
                sub._evtGuid = Y.guid() + '|';
                
            	node.delegate(sub._evtGuid + "mouseenter", 
            		sub.fn, selector, context
            	);
            	
            },

            // Delegate uses a separate detach function to facilitate undoing more
            // complex wiring created in the delegate logic above.  Not needed here.
            detachDelegate: function (node, sub, notifier) {
                sub._delegateDetacher.detach();
            }
        } );
        
        

        // make global
        SNAPPI.Y = Y;
        YAHOO = SNAPPI.Y.YUI2; // YUI2 deprecate when possible	
        /*
         * ADD modules to existing Y instance
         */	
        
        Y.use(
        		/*
        		 * primary scripts
        		 */
        		'snappi-dragdrop', 'snappi-tabs', 'snappi-domJsBinder', 
        		'snappi-lightbox', 'snappi-photoroll', 'snappi-thumbnail',  
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
        } catch (e) { 
            /*
             * DEPRECATE
             * get current Paging details for photo-roll
             */
            var page, perpage;
            try {
                page = parseInt(window.location.href.match(/\/page:(\d*)/i).pop());
            } 
            catch (e) {
                page = page == undefined ? 1 : page;
            }
            try {
                perpage = parseInt(window.location.href.match(/\/perpage:(\d*)/i).pop());
            } 
            catch (e) {
                perpage = perpage == undefined ? 48 : perpage;
            }        
            SNAPPI.STATE.displayPage = {
                page: page,
                perpage: perpage
            };        	
        }
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
	        init();
	    }   
	};
    
    /*
     * Snappi Global Init
     */
    var main = function(){
//console.log('main()');    	
        var Y = SNAPPI.Y;
        SNAPPI.mergeSessionData();

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
        
        SNAPPI.dialogbox.init(SNAPPI.dialogbox.cfg);
        SNAPPI.cfg.MenuCfg.renderHeaderMenus();
        
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

