/**
 *
 * Copyright (c) 2009, Snaphappi.com. All rights reserved.
 *
 *
 *
 * @author Michael Lin, info@snaphappi.com
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
     * Static Methods
     */
	
	
    /*
     * Class Methods
     */
    var Performance = function(cfg){
        this.init(cfg);
    };	
    Performance.prototype = {
        init: function(cfg){
            this.stack = cfg.stack; // deprecate, use Tryout instead
            if (cfg.stack) 
                this.setAuditions(cfg.stack); // add Tryout
            if (cfg.tryout) 
                this.setTryout(cfg.tryout);
            this.sceneCfg = cfg.sceneCfg || null;
            this.label = cfg.sceneCfg.label;
            //                this.fnDisplayH = cfg.sceneCfg.fnDisplayH;
            if (!Y.Lang.isFunction(cfg.sceneCfg.fnDisplaySize)) {
            	this.fnDisplaySize = function(){return cfg.sceneCfg.fnDisplaySize;};
            } 
            else this.fnDisplaySize = cfg.sceneCfg.fnDisplaySize;
            this.roleCount = cfg.sceneCfg.roleCount;
            this.stage = cfg.sceneCfg.stage || null;
            if (this.stage) 
                this.stage.performance = this; // back reference
            this.useHints = (cfg.sceneCfg.useHints === false) ? false : true;
            this.catalog = cfg.catalog;
            
        },
        setAuditions: function(cfg){
			if (cfg && cfg.stack !== undefined && !cfg.stack) cfg.stack = this.stack;
            this.tryout = new SNAPPI.PM.Tryout(cfg);
        },
        setTryout: function(cfg){
            if (cfg && cfg.tryout) {
                this.tryout = cfg.tryout;
            }
            else {
                this.tryout = cfg; // TODO: should use instanceof here
            }
        },
        setStaging: function(stage, noHeader){
            stage = stage || this.stage;
            // hack: sometimes we are passing a cfg ojbect
            var displaySize = this.fnDisplaySize();
            stage.setStyles({
                'overflowY': 'auto',
                'height': displaySize.h + 'px',
//                'width': '100%', // displaySize.w + 'px',
                'backgroundColor': 'black'
            });
            
            /*
             * add Header to PageGallery
             */
            if (!noHeader) {
	            stage.header = this.getStageHeader();
	            stage.prepend(stage.header);
            }
            /*
             * add Body
             */
            stage.body = this.getStageBody();
            stage.body.setContent(''); // if body has existing Performance, reset before re-render
            stage.append(stage.body);
            stage.performance = this;
			return stage;
        },
        getStageHeader: function(cfg){
            var header;
            if (header = this.stage.header) {
                // just update label 
                header.one('h1').dom().innerHTML = cfg && cfg.title || this.label;
            }
            else {
                var headerCfg = {
                    title: cfg && cfg.title || this.label,
                    stack: cfg && cfg.stack || this.tryout && this.tryout.stack || this.stack
                    //                        width: this.fnDisplaySize().w,
                };
                header = SNAPPI.PM.node.makeCreateHeader(headerCfg);
            };
            return header;
        },
        getStageBody: function(id){
            /*
             * config Stage Body
             */
            var displaySize = this.fnDisplaySize();
            var displayPixelsH = displaySize.h - this.stage.header.get('clientHeight');
            id = id || 'tab_create-bodyEl';
            var node = Y.one('#' + id);
            if (!node) {
                node = Y.Node.create('<div></div>');
                node.set('id', id);
                node.setStyles({
                    height: displayPixelsH + 'px',
                    width: '100%', //displaySize.w + 'px',
                    backgroundColor: 'black',
                    overflow: 'hidden' // avoids scrollbars around iFrame
                });
            }
            else {
                // just update height
                node.setStyle('height', displayPixelsH + 'px');
            }
            return node;
        },
        clearBody: function(o){
            o = o || this.getStageBody();
            o.dom().innerHTML = ''; // if body has existing Performance, reset before re-render
        },
        getScene: function(cfg){
			var PM = SNAPPI.namespace('SNAPPI.PM'); 
            if (SNAPPI.util.LoadingPanel) SNAPPI.util.LoadingPanel.show();
//            var _cfg = Y.merge({}, cfg);
            
            /*
             * config Production
             */
            this.useHints = cfg && cfg.useHints !== undefined ? cfg.useHints : this.useHints;
            this.clearBody(this.getStageBody()); // update body height in case of resize and clear
            
            var Pr = this.stage.production;
            Pr.catalog = this.catalog;
            
            /*
             * get Tryout/Auditions, a formatted subset of rawCastingCall
             */
            Pr.tryout = cfg && cfg.tryout || this.tryout ||
	            new PM.Tryout({
	                oStack: cfg && cfg.stack || this.stack
	            });
            cfg = PM.Y.merge(cfg, {
                label: this.label,
                useHints: this.useHints,
                isRehearsal: true,				// TODO: for now, use preview when casting
                roleCount: parseInt(this.roleCount)
            });
            var scene = Pr.renderScene(cfg);
            if (scene) {
                SNAPPI.PM.node.addSaveToGalleryBtn();
            }
            
            // TODO: deprecate. legacy code
            if (SNAPPI.TabView) SNAPPI.TabView.gotoTab('Create');
            //				SNAPPI.util.LoadingPanel.hide();
            var check;
        }
    };
    PM.Performance = Performance;
})();
