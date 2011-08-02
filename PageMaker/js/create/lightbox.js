
/**
 *
 * Copyright (c) 2009, Snaphappi.com. All rights reserved.
 *
 *
 *
 * adapted from http://thecodecentral.com/2008/01/01/yui-based-lightbox-final#latest_source
 *
 *******************  THIS CLASS IS DEPRECATED.   *********************
 
 Lightbox (photo zooom) script is served in iFrame from cakephp project
 
 backend:/app/webroot/js/pageGalley.js
 
 
 */
/*
 * shorthand
 */
var PM = SNAPPI.namespace('SNAPPI.PM');
var Y = PM.Y;

if (!SNAPPI.PM.Lightbox) {
    var Dom = YAHOO.util.Dom, Event = YAHOO.util.Event;
    
    Lightbox = function(){
        this.loaded = false;
        this.datasource = {};
        this.lightbox = null;
    };
    
    
    /*
     * prototype functions
     */
    Lightbox.prototype = {
        default_cfg: {
            tooltip: false,
            hasThumbnails: false,
            maskOpacity: 0.8,
            maskBgColor: '#000',
            effect: false
        },
        load: function(ds, listener){
            this.datasource = ds || this.datasource;
            if (this.listener && this.listener != listener) { // detach previous 
                Event.removeListener(this.listener, this.handleClick);
            }
            this.listener = Dom.get(listener);
            Event.on(this.listener, "click", this.handleClick, this, true);
            if (!this.loaded) {
                var fnContinue = function(){
                    SNAPPI.PM.Lightbox.loaded = true;
                    SNAPPI.PM.Lightbox.lightbox = new YAHOO.com.thecodecentral.Lightbox(SNAPPI.PM.Lightbox.datasource, SNAPPI.PM.Lightbox.default_cfg);
                };
                SNAPPI.PM.main.loadLightboxScript(fnContinue);
                //				SNAPPI.PM.util.loadSlimboxScript(fnContinue);
            }
            else {
                SNAPPI.PM.Lightbox.lightbox = new YAHOO.com.thecodecentral.Lightbox(SNAPPI.PM.Lightbox.datasource, SNAPPI.PM.Lightbox.default_cfg);
            }
            var check;
        },
        handleClick: function(ev){
            Event.stopEvent(ev);
            var tar = Event.getTarget(ev);
            //            if (SNAPPI.util.getClass(tar) == "HTMLImageElement") {  // ie8 returns just 'Object'
            if (tar && tar.nodeName == 'IMG') {
                var id = SNAPPI.PM.util.getBasename(tar.src);
                if (SNAPPI.PM.Lightbox.loaded) {
                    SNAPPI.PM.Lightbox.show(id);
                }
            }
        },
        add: function(cfg){
            var test = SNAPPI.util.getClass(cfg);
            //            if (SNAPPI.util.getClass(cfg) == "HTMLImageElement") {
            if (cfg && cfg.nodeName == 'IMG') {
                var basename = SNAPPI.PM.util.getBasename(cfg.src);
                var id = cfg.id || basename;
                this.datasource[id] = {
                    url: SNAPPI.PM.util.removeCropSpec(cfg.src, 'bp'),
                    title: basename,
                    text: cfg.title
                };
            }
            else {
                this.datasource[cfg.id] = cfg.url;
                if (cfg.title) 
                    this.datasource[cfg.id].title = decodeURI(cfg.title);
                if (cfg.text) 
                    this.datasource[cfg.id].text = decodeURI(cfg.text);
            }
        },
        clear: function(){
            this.datasource = {};
        },
        show: function(id){
            if (SNAPPI.PM.Lightbox.lightbox) {
                SNAPPI.PM.Lightbox.lightbox.show(id);
            }
        },
        hide: function(){
            SNAPPI.PM.Lightbox.lightbox.hide();
        }
    };
    
    SNAPPI.PM.Lightbox = new Lightbox();
}







