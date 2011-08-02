/**
 *
 * Copyright (c) 2009, Snaphappi.com. All rights reserved.
 *
 *
 *
 * @author Michael Lin, info@snaphappi.com
 *
 * pagegallery.js
 * - this is the initial script object for loading the SNAPPI.PageMaker plugin.
 * - the only dependency should be SNAPPI.js to define SNAPPI.namespace
 *
 */
(function(){
    /*
     * shorthand
     */
    var PM = SNAPPI.namespace('SNAPPI.PM');
	var _defaultCfg = {
			fn_DISPLAY_SIZE: function(){
				return {
					h: 600
				};
			}		
	};
	
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
//			host = host.replace('baked', 'gallery'); break;
			host = host.replace('git', 'gallery'); break;
		case 'pagemaker':
			host = host; break;
			default:
				host = host; break; 
		}
		return host;
	};
	
	
	/*
	 * for dev config, do NOT use combo load for snappi scripts here
	 */
    var localhost = !(/snaphappi.com/.test(getHost())); // live vs dev site
    var useCombo = 0 || !localhost;
    
    
    /*
     * get basic yuiConfig
     */
    if (0 && SNAPPI.yuiConfig) {
        PM.yuiConfig = SNAPPI.yuiConfig;
    }
    else {
        // DEFAULT yuiConfig for PageMaker module
    	PM.yuiConfig = {};
        PM.yuiConfig.yui = { // GLOBAL
            base: "http://yui.yahooapis.com/combo?3.3.0/build/", 
            timeout: 10000,
            loadOptional: false,
            combine: true,
            filter: "MIN",
            allowRollup: true,
            groups: {},
            insertBefore: 'css-start'
            //				filter: "DEBUG",
        };
    };
    
    
    /*
     * YUI external Module loading configuration for PageGallery components
     */
    var pagemakerHost = getHost('pagemaker');
    PM.yuiConfig.pagemaker = {
        combine: useCombo,
        base: 'http://' + pagemakerHost + '/app/pagemaker/js/',
        comboBase: 'http://' + pagemakerHost + '/combo/js?baseurl=PAGEMAKER&',
        root: 'js/',
        modules: {
//    		'pagemaker-base': {
//		        path: 'base.js',
//		        requires: ['node', 'io', 'fleegix_xml']
//		    },    
		    'snappi-pm-main': {
		        path: 'create/main3.js',
		        requires: ['snappi-pm-snappi-css','node','snappi-pm-catalog3','snappi-pm-performance3']
		    },
		    'snappi-pm-node3': {
		        path: 'create/node3.js',
		        requires: ['snappi-pm-util','node', 'event', 'substitute', 'stylesheet']
		    },
		    'snappi-pm-util': {
		        path: 'create/util.js',
		        requires: ['io-base','node']
		    },
		    'snappi-pm-datasource3': {
		        path: 'create/datasource3.js',
		        requires: ['node', 'io-base', 'io-queue', "io-xdr", 'substitute', 'datatype-xml', 'async-queue']
		    },
		    'snappi-pm-arrangement': {
		        path: 'create/arrangement.js',
		        requires: ['oop']
		    },
		    'snappi-pm-role': {
		        path: 'create/role.js',
		        requires: ['oop','snappi-sort']
		    },
		    'snappi-pm-production': {
		        path: 'create/production.js',
		        requires: ['node','snappi-pm-catalog3','snappi-pm-util']
		    },
		    'snappi-pm-audition': {
		        path: 'create/audition.js',
		        requires: ['snappi-sort','snappi-pm-util']
		    },
		    'snappi-pm-tryout': {
		        path: 'create/tryout.js',
		        requires: ['snappi-sortedhash','snappi-pm-audition']
		    },
		    'snappi-pm-catalog3': {
		        path: 'create/catalog3.js',
		        requires: ['snappi-sortedhash','snappi-pm-audition', 'snappi-pm-role' ]
		    },
		    'snappi-pm-casting': {
		        path: 'create/casting.js',
		        requires: ['node', 'snappi-pm-catalog3']
		    },
		    'snappi-pm-performance3': {
		        path: 'create/performance3.js',
		        requires: ['node','snappi-pm-tryout','snappi-pm-node3']
		    },
		    'snappi-pm-play': {
		        path: 'play/pageGallery.js',
		        requires: ["event-delegate", "node", "anim"]
		    }		    

        }
    };

    PM.yuiConfig.pagemakerCss = {
        combine: false,
//            base: 'http://' + host + '/css/pagemaker/',
//            comboBase: 'http://' + host + '/combo/js?baseurl=baked/app/webroot&',
//            root: 'css/pagemaker/',						// base for combo loading, combo load uri = comboBase+root+[module-name]
        base: 'http://' + pagemakerHost + '/app/pagemaker/css/',
        comboBase: 'http://' + pagemakerHost + '/combo/js?baseurl=PAGEMAKER&',
        root: 'css/',            
        modules: {
            'snappi-pm-snappi-css': {
                path: 'snappi.css',
                type: 'css'
            }
        }
    };
    
	// required modules imported from other applications
	// NOTE: if we are loading PageGallery from Snappi, these components may already be loaded.
    var snappiHost = getHost();
	var localhost = !(/snaphappi.com/.test(snappiHost)); // live vs dev site	
	var combo_baseurl = (localhost ? 'baked/' : '') + 'app/webroot&';    
    PM.yuiConfig.snappi = {
        combine: useCombo,
        base: 'http://' + snappiHost + '/js/snappi/',
        comboBase: 'http://' + snappiHost + '/combo/js?baseurl='+combo_baseurl,
        root: 'js/snappi/',						// base for combo loading, combo load uri = comboBase+root+[module-name]
        modules: {
    		'snappi-sort': {
		    	path: 'sort.js',
		        requires: ['node']
            },
            'snappi-sortedhash': {
            	path: 'sortedhash.js',
                requires: ['node', 'snappi-sort', 'gallery-data-element']
            },
    		'snappi-menu': {
    			path: 'menu.js',
    			requires:['node']
    		},
    		'snappi-menuitem': {
    			path: 'menuitem.js',
    			requires:['node']
    		},
    		'snappi-async' : {
    			path: 'async.js',
    			requires:['node', 'base', 'async-queue', 'stylesheet']
    		},    		
            'snappi-io': {
                path: 'io.js',
                requires: ['node', 'io', 'json']
            }      		
        }
    };
    
    PM.yuiConfig.gallery = {
        combine: useCombo,
        base: 'http://' + snappiHost + '/js/gallery/',
        comboBase: 'http://' + snappiHost + '/combo/js?baseurl='+combo_baseurl,
        root: 'js/gallery/',						// base for combo loading, combo load uri = comboBase+root+[module-name]
        modules: {
            'gallery-data-element': {
                path: 'dataelement.js',
                requires: ['node', 'yui2-container']
            },
            'gallery-util': {
                path: 'util.js',
                requires: ['node', 'yui2-container']
            },
            'gallery-group': {
                path: 'groups3.js',
                requires: ['node', 'snappi-sortedhash', 'snappi-dragdrop']
            },            
            'gallery-auditions': {
                path: 'auditions.js',
                requires: ['node', 'gallery-util', 'gallery-group', 'snappi-sortedhash']
            }            
        }
    };  
    
    
    PM.yuiConfig.yui.groups.pagemaker = PM.yuiConfig.pagemaker;
    PM.yuiConfig.yui.groups.pagemakerCss = PM.yuiConfig.pagemakerCss;
	// check if these yui.group configs were inherited before adding
	if (!PM.yuiConfig.yui.groups.snappi) {
		PM.yuiConfig.yui.groups.snappi = PM.yuiConfig.snappi;
	}
	if (!SNAPPI.PM.yuiConfig.yui.groups.gallery) {
		PM.yuiConfig.yui.groups.gallery = PM.yuiConfig.gallery;
	}    
    
            
    /*****************************
     * NOTE: gallery project uses static methods to initialize plugin
     * - BUT BAKED project uses new PageMakerPlugin() object methods.
     */
    var PageMakerPlugin = function(){
    	/*
    	 * protected/closures
    	 */
    	var _self = this;
        var Y = PM.Y;
        
        
        /*
         * call yui_external_module_loader to load modules
         */
        this.load = function(fnContinue) {
            
            /**
             * NOTE: PageMaker module should have its own Y sandbox instance.
             * - do not reuse from parent scope.
             */
        	/*
        	 * load minimum set of yui modules
        	 */
            YUI(PM.yuiConfig.yui).use('node', 'event', 'event-custom', 
            		/*
            		 * for datasource3.js.
            		 * TODO: refactor to delay init of _queue until class init
            		 */
            		'async-queue', 
            	
        		/*
        		 * callback function
        		 */
            	function(Y2){
                /*
                 * Helper Functions
                 */
                Y2.Node.prototype.dom = function(){
                    return Y2.Node.getDOMNode(this);
                };
                Y2.Node.prototype.ynode = function(){
                    return this;
                };
                HTMLElement.prototype.dom = function(){
                    return this;
                };
                HTMLElement.prototype.ynode = function(){
                    return Y2.one(this);
                };
                // make global
                Y2.sandboxName = "PageMaker"; // just to check that we are using correct sandbox
                PM.Y = Y2;
                
                var check = PM.Y.Event.define("snappi-pm:hover", {
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
                
                
                /*
                 * continue loading snappi modules
                 */
            	PM.Y.use(
        			/*
        			 * snappi-pm/yui load modules
        			 */
        			'snappi-pm-main','snappi-pm-util','snappi-pm-catalog3','snappi-pm-node3',
        			'snappi-pm-datasource3','snappi-pm-casting','snappi-pm-audition',
            		'snappi-pm-arrangement','snappi-pm-role','snappi-pm-production',
            		'snappi-pm-tryout','snappi-pm-performance3',
            		'snappi-pm-play',
            		/*
            		 * from snappi
            		 */
            		'snappi-io', 'gallery-auditions', 'snappi-async',
    	        	/*
    	        	 * YUI callback function
    	        	 */	
    				function(Y3, result){
    				    if (!result.success) {
    						Y3.log('Load failure: ' + result.msg, 'warn', 'Example');
    					}
    					else {
    						PM.Y = Y3;
    						// load once, then just a passthru
                			_self.load = function() {
                				fnContinue();
                			};
                			
    						fnContinue();
    						PM.Y.fire('snappi-pm:afterPagemakerPluginLoad');
    					}
    		        }
    	    	);
            });        	
        };
    };  
    
    /*
     * static method
     */
    PageMakerPlugin.getHost = getHost;


    
    // Globals
    SNAPPI.PageMakerPlugin = PageMakerPlugin;
    
        
    
    
    
    
    
    
    
    
    
    
    /****************************************************************************
     * NOTE: the methods which follow is legacy code for use with the gallery app. 
     * they are here only for backward compatibility, and should eventually be 
     * deprecated
     ******************************************************************************/
    function clone(obj){
        if(obj == null || typeof(obj) != 'object')
            return obj;

        var temp = new obj.constructor(); // changed (twice)

        for(var key in obj)
            temp[key] = clone(obj[key]);

        return temp;

    };
    
    PageGalleryPlugin = clone(PageMakerPlugin);	// legacy
    /*
     * Static Methods cannot use 'this' inside method
     *
     */
    
    
    PageGalleryPlugin.baseurl = 'http://' + window.location.host + '/app/pagemaker/';
    
    PageGalleryPlugin.launchPageGallery = function(e){
        var Y = PM.Y;
        // test for gallery init or Lightbox init
        SNAPPI.util.LoadingPanel.show();
        var stack = e.currentTarget.ancestor('div.stack-content').dom().stack;
        var target = e.currentTarget.dom();
        
        var sceneCfg = {
            label: stack.label,
//            stage: SNAPPI.PageGalleryPlugin.XXXinitCreateTabs(),
            fnDisplaySize: SNAPPI.Layouts.getLayoutBodyMinusTabsDim,
            // fnDisplaySize: _fixedDisplayH, // scale pageGallery with in
            // production w/ NATIVE_PAGE_GALLERY_H
            stack: stack
        };
        
        /*
         * set datasource for pageGallery
         * - we need to get SH to Tryout.getAuditionsFromSortedHash()
         */
        var datasource = e.currentTarget.ancestor('div.stack-content');
        if (datasource) {
            sceneCfg.stack = datasource.dom().stack;
        }
        else {
            datasource = e.currentTarget.ancestor('#lightbox');
            if (datasource) {
                sceneCfg.sortedhash = datasource.Lightbox.sh;
            }
        }
        
        var o = {
            success: function(e){
                SNAPPI.PM.node.onPageGalleryReady(sceneCfg);
            },
            failure: function(e){
                alert("ERROR: Javascript script load failed");
            }
        };
        
        if (SNAPPI.productionJsLoaded == undefined ||
        SNAPPI.productionJsLoaded == false) {
            Y.Get.script(SNAPPI.PageMakerPlugin.baseurl +
            "audition/main3.js", {
                onSuccess: function(e){
                    SNAPPI.PM.main.go(o.success);
                },
                onFailure: function(e){
                    o.failure();
                    alert("ERROR: Javascript script load failed");
                }
            });
        }
        else {
            o.success();
        }
    };
    
    PageGalleryPlugin.XXXinitCreateTabs = function(cfg){	// DEPRECATE
        var Y = PM.Y;
        cfg = Y.merge({
            contentEl: 'tab_create-contentEl',
            label: "Create"
        }, cfg);
        var contentNode = Y.one('#' + cfg.contentEl);
        if (!contentNode) {
            /*
             * make new Create Tab, returns yui2 DOM element
             */
            SNAPPI.TabView.addTab(cfg); // DOM node
            contentNode = Y.one('#' + cfg.contentEl);
        }
        
        /*
         * also make Preview tab,
         */
        var tabCfg = {
            contentEl: 'tab_preview-contentEl',
            label: 'Preview'
        };
        var previewContentNode = Y.one('#' + tabCfg.contentEl);
        if (!previewContentNode) {
            var previewContentEl = SNAPPI.TabView.addTab(tabCfg); // DOM
            // node
            /*
             * MOVE. this should all happen in /audition/node3.js
             */
            previewContentNode = Y.one('#' + tabCfg.contentEl);
            var h = previewContentEl.parentNode.clientHeight;
            previewContentNode.setStyles({
                height: h + 'px',
                overflow: 'hidden'
            });
            var iFrame = Y.one('#preview-iframe');
            if (!iFrame) {
                // create preview-iFrame
                var sIFrame = "<iframe id='preview-iframe' width='100%' height='100%'><p>Your browser does not support iframes.</p></iframe>";
                var iFrame = Y.Node.create(sIFrame);
                iFrame.set('src', '../../../gallery/page_gallery/saved?page=last');
            }
            previewContentNode.appendChild(iFrame.dom());
        }
        
        // return Create ContentEl
        SNAPPI.PageGalleryPlugin.stage = contentNode;
        return SNAPPI.PageGalleryPlugin.stage;
    };
    
    PageGalleryPlugin.startPlayer = function() {
    	if (!PageGalleryPlugin.player) {
    		PageGalleryPlugin.player = new SNAPPI.PM.Player({
    			container: SNAPPI.PageGalleryPlugin.stage.body,
    			isPreview: true,
    			FOOTER_H: 20
    		});
    	}
    	try {
    		PageGalleryPlugin.player.init();
    	} catch(e) {}
    };
    
    SNAPPI.PageGalleryPlugin = PageGalleryPlugin;

    SNAPPI_Plugin = function(node){
        // console.log('SNAPPI_Plugin', id);
        SNAPPI.node.addButton({
            name: 'Create Page Galleries',
            desc: 'Create Page Galleries for your friends and family',
            onclick: SNAPPI.PageGalleryPlugin.launchPageGallery,
            // elementId: id
            elementNode: node
        });
    };
    
    SNAPPI.Y.fire('snappi-pm:afterModuleLoad');	
    
})();
