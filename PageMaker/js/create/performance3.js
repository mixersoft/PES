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
	var _Y = null;
	var Plugin = null;
	var PM = SNAPPI.namespace('SNAPPI.PM');
	PM.onYready.Performance = function(Y){
		if (_Y === null) _Y = Y;
	    /*
	     * publish
	     */
	    PM.Performance = Performance;
	    Plugin = PM.PageMakerPlugin.instance;
	} 
    
    /*
     * Static Methods
     */
    var Performance = function(cfg){
    	this.cfg = cfg;
        this.init(cfg);
    };	
    Performance.prototype = {
        init: function(cfg){
            this.stack = cfg.stack; // deprecate, use Tryout instead
            this.production = cfg.production;
            if (cfg.stack) 
                this.setAuditions(cfg.stack); // add Tryout
            if (cfg.tryout) 
                this.setTryout(cfg.tryout);
            this.sceneCfg = cfg.sceneCfg || null;
            this.label = cfg.sceneCfg.label;
            //                this.fnDisplayH = cfg.sceneCfg.fnDisplayH;
            if (!_Y.Lang.isFunction(cfg.sceneCfg.fnDisplaySize)) {
            	this.fnDisplaySize = function(){return cfg.sceneCfg.fnDisplaySize;};
            } 
            else this.fnDisplaySize = cfg.sceneCfg.fnDisplaySize;
            this.roleCount = cfg.sceneCfg.roleCount;
            if (cfg.stage) cfg.stage.performance = this; // back reference
            this.useHints = (cfg.sceneCfg.useHints === false) ? false : true;
            this.catalog = cfg.catalog;
            
        },
        setAuditions: function(cfg){
			if (cfg && cfg.stack !== undefined && !cfg.stack) cfg.stack = this.stack;
            this.tryout = new PM.Tryout(cfg);
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
            stage = stage || Plugin.stage;
            
            var displaySize = this.fnDisplaySize();
            stage.setStyles({
                'overflowY': 'auto',
                // 'height': displaySize.h + 'px',		// required for playback sizing
                // 'width': '100%', // displaySize.w + 'px',
                'backgroundColor': 'black'
            });
            /*
             * add Header to PageGallery
             */
            if (noHeader || stage.noHeader) {
            	try{
            		stage.one('.stage-header').remove();
            	} catch(e){}
            } else {
	            stage.header = this.getStageHeader();
	            stage.prepend(stage.header);
            }
            /*
             * add Body
             */
            var body = this.getStageBody(stage);
            body.setContent(''); // if body has existing Performance, reset before re-render
            stage.append(body);
            stage.body = body;			// TODO: deprecate, backreference needed?
            stage.performance = this;	// TODO: deprecate, backreference needed?
			return stage;
        },
        getStageHeader: function(cfg){
        	// TODO: use '.stage-header'
            var header = Plugin.stage.one('.stage-header');
            if (header) {
                // just update label 
                header.one('h1').dom().innerHTML = cfg && cfg.title || this.label;
            }
            else {
                var headerCfg = {
                    title: cfg && cfg.title || this.label,
                    stack: cfg && cfg.stack || this.tryout && this.tryout.stack || this.stack
                    //                        width: this.fnDisplaySize().w,
                };
                header = PM.node.makeCreateHeader(headerCfg);
            };
            header.addClass('stage-header');
            return header;
        },
        getStageBody: function(parent, id){
            /*
             * config Stage Body
             */
            var displayPixelsH, 
            	displaySize = this.fnDisplaySize(),
            	backgroundColor = '#000';
            var header = parent.one('.stage-header');
            if (header) {
            	displayPixelsH = displaySize.h - header.get('clientHeight');
            } else displayPixelsH = displaySize.h;
            id = id || 'tab_create-bodyEl';
            var body = parent.one('.stage-body') || parent.one('#' + id);
            if (!body) {
                body = parent.create('<div></div>');
                body.set('id', id).addClass('stage-body');
                body.setStyles({
                    // height: displayPixelsH + 'px',
                    width: '100%', //displaySize.w + 'px',
                    backgroundColor: backgroundColor,
                    overflow: 'hidden' // avoids scrollbars around iFrame
                });
            }
            else {
                // just update height
                // body.setStyle('height', displayPixelsH + 'px');
            }
            return body;
        },
        clearBody: function(n){
        	n = n.hasClass('stage-body') ? n : n.one('stage-body');
            n.setContent('');
        },
        getScene: function(cfg){
            if (SNAPPI.setPageLoading) SNAPPI.setPageLoading(true);
 
            
            /*
             * config Production
             */
            this.useHints = cfg && cfg.useHints !== undefined ? cfg.useHints : this.useHints;
            this.clearBody(Plugin.stage.body); // update body height in case of resize and clear
            
            var Pr = PM.pageMakerPlugin.production || Plugin.stage.production;
            // Pr.catalog = this.catalog;	// set in parent
            
            /*
             * get Tryout/Auditions, a formatted subset of rawCastingCall
             * tryout <- sceneCfg.auditions
             */
            Pr.tryout = cfg && cfg.tryout || this.tryout ||
	            new PM.Tryout({
	                oStack: cfg && cfg.stack || this.stack
	            });
            cfg = _Y.merge(cfg, {
                label: this.label,
                useHints: this.useHints,
                isRehearsal: true,				// TODO: for now, use preview when casting
                // roleCount: parseInt(this.roleCount)
            });
            var scene = Pr.renderScene(cfg);
            if (scene) {
                PM.node.addSaveToGalleryBtn();
            }
            var node = scene.performance;
            /*
             * fire event: 'snappi-pm:render'
             */
            PM.Y.fire('snappi-pm:render', this, node);
            PM.pageMakerPlugin.external_Y.fire('snappi-pm:render', this, node);
            var check;
        }
    };
    
})();
