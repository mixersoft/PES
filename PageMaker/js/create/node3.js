/**
 *
 * Copyright (c) 2009, Snaphappi.com. All rights reserved.
 *
 *
 *
 * @author Michael Lin, info@snaphappi.com
 *
 * node - module, singleton  used for creating and manipulating GUI nodes
 * 
 *
 */
(function(){
	/*
     * shorthand
     */
    // var Y = PM.Y;
	var _Y = null;
	var PM = SNAPPI.namespace('SNAPPI.PM');
	SNAPPI.namespace('SNAPPI.PM.onYready');
	// Yready init
	PM.onYready.NodeFactory = function(Y){
		if (_Y === null) _Y = Y;
		/*
	     * publish
	     */
	    // Globals 
    	PM.node = new NodeFactory(); // NodeFactory is in module scope only, singleton 	
	} 
		
    // class constructor, defined in module local scope only
    var NodeFactory = function(cfg){
    	this.listener;
		this.init = function() {
			this.startListeners();
		};
		
		/*
         * private methods
         */
        var _fixedDisplayH = function(){
            return {
                h: 800
            };
        };
		
        this.makeCreateHeader = function(cfg){
            var node, title;
            if (node = _Y.one('#tab_create-header')) {
                title = cfg.title || cfg.stack && cfg.stack.label || null;
                if (title) {
                   var header = _Y.one('#tab_create-header > h1').set('innerHTML', title);
                }
            }
            else {
//                cfg = _Y.merge({
//                    stack: SNAPPI.StackManager.getFocus()
//                }, cfg);
                // run once
                var tokens = {
                    id: 'tab_create-header',
                    title: cfg.title || ''
                };
                var tHeader = "<div class='photoSet-header' id='{id}'><h1>{title}</h1>Choose Photos per Page:<ul></ul></div>";
                var node = _Y.Node.create(_Y.substitute(tHeader, tokens));
                
                var UL = node.one('ul');
                var sButtons = "<li class='stack-productions' title='Create a Page Gallery from a template for {n} photos. <br><b>Note:</b> These templates are for demonstration only. The system can be configured to accomodate a wide variety of page templates.'>{n} Photos</li>";
                var btnList = [5, 6, 7, 9, 10];
                for (var n in btnList) {
                    var li = _Y.Node.create(_Y.substitute(sButtons, {
                        n: btnList[n]
                    }));
                    li.dom().arrangement = {
                        xml: btnList[n] + 'up',
                        label: btnList[n] + ' photos'
                    };
                    if (SNAPPI.util.TitleToolTip) SNAPPI.util.TitleToolTip.push(li.dom());
                    UL.append(li);
                }
                
                // add settings at the top 
                node.prepend(_makeCreateSettingsContainer());
                
                /*
                 * register for StackManager.getFocus on custom change event, update label
                 */
				if (SNAPPI.StackManager) {
	                SNAPPI.StackManager.on('StackManager:changeFocus', function(stack){
	                    // update title in header
	                    _Y.one('#tab_create-header > h1').set('innerHTML', stack.label);
	                });
				}
            };
            return node;
        };
        
		/**
         * MOVE TO /audition/node3.js
         */
        var _makeCreateSettingsContainer = function(){
            var UL = _Y.Node.create("<div class='photoSet-settings'>Settings:<br></div>");
            
            /*
             * use Hints checkbox
             */
            var existingCb = _Y.one('.use-hints .cb'); // get current value
            var initialValue = (existingCb) ? existingCb.get('checked') : true;
            var sUseHintTip = "Choose to use/ignore photo ratings and other layout hints when making page. <br><b>Note:</b> Changing this setting also clears the list of already used photos.";
            var LI = _makeCbElement({
                label: "Use Hints",
                tooltip: sUseHintTip
            });
            LI.one('input.cb').set('checked', initialValue);
            if (SNAPPI.util.TitleToolTip)  SNAPPI.util.TitleToolTip.push(_Y.Node.getDOMNode(LI));
            UL.append(LI);
            
            
            /*
             * hide repeats checkbox
             */
            var existingCb = _Y.one('.hide-repeats .cb'); // get current value
            var initialValue = (existingCb) ? existingCb.get('checked') : true;
            var sToolTip = "hide duplicate photos";
            LI = _makeCbElement({
                label: "Hide Repeats",
                tooltip: sToolTip
            });
            LI.one('input.cb').set('checked', initialValue);
            if (SNAPPI.util.TitleToolTip) SNAPPI.util.TitleToolTip.push(_Y.Node.getDOMNode(LI));
            UL.append(LI);
            
            return UL;
        };
		
		var _makeCbElement = function(cfg){
            var strCb = "<div class='cb-label-rt' {tooltip}><input class='cb' type='checkbox'/>{label}</div>";
            var tokens = {
                id: cfg.id ? "id='" + cfg.id + "'" : '',
                label: cfg.label,
                tooltip: cfg.tooltip ? "title='" + cfg.tooltip + "'" : ''
            };
            var el = _Y.Node.create(_Y.substitute(strCb, tokens));
            if (cfg.label) 
                el.addClass(cfg.label.replace(' ', '-').toLowerCase());
            return el;
        };

        /*
         * these are the default click handlers, private scope.
         * we should fire a custom event to allow override
         */
        var _use_hints_ClickedHandler = function(value){
            return;
        };
        var _hide_repeats_ClickedHandler = function(value){
            _Y.one('#lightbox ul.photo-roll').dom().PhotoRoll.toggleSubstitutes(value);
        };
		
		/**
         * MOVE to /audition/nod3.js
         */
        var _makeSaveToGalleryBtn = function(){
            /*
             * add button to share this page
             */
            var a = _Y.Node.create('<li></li>');
            a.setAttrs({
                id: 'save-page-gallery',
//                href: '#',
                title: "Click here to save a copy of this page for sharing. (<b>Note<b>: please allow popup windows.)",
                innerHTML: 'Save Page'
            });
            if (SNAPPI.util.TitleToolTip)  SNAPPI.util.TitleToolTip.push(a.dom());
            
            a.on('click', function(ev){
                ev.stopPropagation();
                var userid, filename, saved_src;
                try { 
                	userid = PAGE.jsonData.controller.xhrFrom.uuid;
                	filename = userid;
                } catch (e){
                	filename = 'saved';
                }
                saved_src = '/gallery/story/'+filename+'?page=last';
                var content = PM.pageMakerPlugin.stage.body.one('div.pageGallery').unscaled_pageGallery;
                var cfg = {
                	content: content, 	// save pageGallery HTML of parent node
//                  tmpfile: 'tmp',		// save from tmp file
                    filename: filename,
                    success: function(){
                        /*
                         * mark scene as saved
                         */
                        var Pr = PM.pageMakerPlugin.production;
                        Pr.saveScene();
                        window.open(saved_src, 'page gallery');
                    }
                };
                PM.util.saveToPageGallery(cfg);
                return false;
            });
            
            var popup = document.createElement('IMG');
            popup.src = 'img/external.png';
            a.appendChild(popup);
            
            return a;
        };
		
		/*
         * public methods
         */
        this.startListeners = function(cfg){
        	if (this.listener) return;
        	this.listener = {};
			//console.log('js/audition/node3.js startListeners()');
            /*
             * delegate event handlers
             */
			var node;
			if (node = _Y.one('#content-tabview')){
				this.listener['tabview-cb-click'] = node.delegate('click', this.handleCbClick, 'div.cb-label-rt > input');
				this.listener['tabview-li-click'] = node.delegate('click', this._makePageGallery, 'div.photoSet-header ul li');
			}
			if (node = _Y.one('#filmstrip')) {
				this.listener['filmstrip-cb-click'] =  node.delegate('click', this.handleCbClick, 'div.cb-label-rt > input');
				this.listener['filmstrip-li-click'] = node.delegate('click', this._makePageGallery, 'div.photoSet-header ul li');
			}
			/*
			 * lightbox event handlers
			 */
			if (node = _Y.one('#pagemaker')){
				this.listener['pagemaker-cb-click'] = node.delegate('click', this.handleCbClick, 'div.cb-label-rt > input');
				this.listener['pagemaker-li-click'] = node.delegate('click', 
	            	function(e){
	            		var self = this;
	            		var loadingNode = e.target;
						var asyncCfg = {
							fn : self._makePageGallery,
							node : loadingNode,		// node for loading gif
							context : self,
							size : 'small',
							args : [e]
						};
						var async = new SNAPPI.AsyncLoading(asyncCfg);
						async.execute(); 	            	
	            	}, 
	            	'div.photoSet-header ul li', this
	            );
			}			
        };
		
		this._makePageGallery = function(e){
			if (SNAPPI.util.LoadingPanel) SNAPPI.util.LoadingPanel.show();
			// assume Gallery is loaded here.
			var stage = PM.pageMakerPlugin.stage;
			var performance = stage ? stage.performance : null;
			var stack;
			// get the current stack		// DEPRECATE: GALLERY APP
			if (SNAPPI.StackManager) {
				stack = SNAPPI.StackManager.getFocus();
			//			var stack = e.currentTarget.get('parentNode').stack;
			}

			/*
			 * get checkbox settings
			 */
			var useHints = e.container.one('.use-hints .cb').get('checked');
//			var showRatings = e.container.one('.show-ratings .cb').get('checked');
			
			var target = e.currentTarget.dom();
			var btnLabel = target.arrangement.label;
			var sceneCfg = {
				//                label: stack.label,
				roleCount: btnLabel.replace(/^(\d+).*/, "$1"),
				fnDisplaySize: _fixedDisplayH,
				useHints: useHints
			};
			
			//TODO: FIRE onPageGalleryReady event 
			PM.node.onPageGalleryReady(sceneCfg);
			
			try {
				e.target.dom().Async.removeLoading();
			} catch (e) {
				var check;
			}			
		};
		
		this.onPageGalleryReady = function(sceneCfg){
			
			//console.log('onPageGalleryReady');
			// all Production Js scripts now available.
			
			/*
			 * fetch XML Catalog of Arrangements
			 */
//			var catalogCfg = {
//				provider: 'Picasa',
//				success: function(){
//					//TODO: FIRE onCatalogReady event 
//					PM.main.onCatalogReady(sceneCfg, this);
//				}
//			};
			var catalogCfg = {
					provider: 'Snappi',	// 'SnappiMagicLayout
					id: 'CustomFit',
					label: 'CustomFit',
					end: null
				};			
			var resp = PM.Catalog.getCatalog(catalogCfg);
			if (resp && resp != 'loading') {
				PM.main.onCatalogReady(sceneCfg, catalogCfg);
			}
		};
		
		
		this.handleCbClick = function(e){
            var cb = e.target;
            var value = cb.get('checked');
            var classNames = cb.get('parentNode').get('className');
            var name = classNames.replace('cb-label-rt', '').trim();
            
            // synch all setting.cb of the same "name"
            _Y.all('div.' + name + ' > input').each(function(n){
                n.set('checked', value);
            });
            
            // handle click, call SNAPPI.node private method
            var method = '_' + name.replace('-', '_') + "_ClickedHandler";
            eval(method + "(value)");
        };
		
		/**
         * MOVE TO /audition/node3.js
         */
        this.addSaveToGalleryBtn = function(){
			var div = _Y.one('#tab_create-contentEl > div.photoSet-header');
			if (!div) div = _Y.one('#pagemaker > div.photoSet-header > ul');
			if (div && !div.one('#save-page-gallery')) {
                // add save Btn
                var a = _makeSaveToGalleryBtn();
                div.appendChild(a);
                
                /*
                 * move gallery-name text field to current header, if it already exists
                 * ToDo: DON'T REUSE THIS FIELD
                 */
                var t = _Y.one('#gallery-name');
                if (t) 
                    div.appendChild(t);
            };
        };
        
        // class init
        this.init();
    }; // end class def
    
	
    /*
     * static methods
     */
    // none
    
})();



