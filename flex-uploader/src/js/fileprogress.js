/**
 *
 * Copyright (c) 2009-2011, Snaphappi.com. All rights reserved.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the Affero GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * Affero GNU General Public License for more details.
 *
 * You should have received a copy of the Affero GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * @author Michael Lin, info@snaphappi.com
 *
 *
 */
(function() {
/*
 A simple class for displaying file information and progress
 Note: This is a demonstration only and not part of SWFUpload.
 Note: Some have had problems adapting this class in IE7. It may not be suitable for your application.
 */
// Constructor
// file is a SWFUpload file object
// pageId is the HTML element id attribute that the FileProgress HTML structure will be added to.
// Instantiating a new FileProgress object with an existing file will reuse/update the existing DOM elements

var Factory = SNAPPI.Factory.Thumbnail;
/**
 * @params cfg object {id: label: uuid:}
 * @params page int or Y.Node - for access to the page node
 */
var FileProgress = function (cfg, page){
	var Y = SNAPPI.Y;
	this._cfg = Y.merge(Factory.PhotoAirUpload.defaultCfg, cfg);
    this.fileProgressID = cfg.rowid;	// uploadQueue.id
    this.uuid = cfg.uuid; // deprecate
    this.label = cfg.label; // deprecate
    
    this.opacity = 100;
    this.height = 0;		// deprecate, used by appear()/disappear()
        // use YUI3 libs
//LOG("******** FileProgress constructor  Y.version = " + Y.version + ", cfg.label=" + cfg.label + ", cfg.uuid=" + cfg.uuid);
        // init upload-queue-container
    var node = Y.one('#progress-' + this._cfg.uuid);
    if (!node) {		// create node
        // use ThumbnailFactory for markup init
        node = Y.Node.create(Factory.PhotoAirUpload.markup);
        node.set('id', this._cfg.ID_PREFIX + cfg.uuid);
        node.addClass(this._cfg.size);
        node.uuid = cfg.uuid;
        node.setAttribute('uuid', cfg.uuid);
        var img = node.one('figure > img');
        img.set('title', cfg.label).set('alt', cfg.label);
        this.node = node;
        
        this.node.FileProgress = this; // backreference
        this.node.dom().FileProgress = this; // for firebug

        // add item to page with loading gif
        if (page instanceof Y.Node) {
            page.append(this.node);
        } else {
        	try {
                Y.one('.gallery .container').append(this.node);
        	} catch (e) {
        		alert("page no found for page="+page);
        	}
        }
        
        // change to real image src
       
        var options = {
            create: true,
            autorotate: true,
            replace: false,
            callback: {
                success: function(src, args){	
//        			LOG(">>>>>>>>>>>>>> SUCCESS src=" + src);
        			args.img.set('src', src);
                },
                failure: function(src){
                    LOG(">>>>>>>>>>>>>> FAILURE src=" + src);
                },
                arguments: {
                	img: img,
//                  ,  Y: Y
                }
            }
        };
        try{
        	// add listener for img.onload
			var detach = img.on('load', function(e) {
				detach.detach();
				img.removeClass('hidden');
			});
			// use callback if img does NOT exist
			var src = SNAPPI.DATASOURCE.getImgSrcBySize(cfg.uuid, 'sq', options).replace(/ /g, '%20');
			// or set directly if img already exists
			if (src) img.set('src', src);
//			LOG(">>>>IMAGE ALREADY EXISTS, src=" + src);
        } catch (e) {}

    }
    else {
    	// reuse
        this.node = node;
        this.reset();
    }
    this.height = this.node.get('offsetHeight');
    this.setTimer(null);
}

/*
 * static vars
 *
 */
/*
 * public properties and methods
 */
FileProgress.prototype = {
    setTimer: function(timer){
        this.node.dom().FP_TIMER = timer;
    },
    getTimer: function(timer){
        return this.node.dom().FP_TIMER || null;
    },
    onCancel: function(e){
        var dom = e.target && e.target.dom() || null;
        if (dom && dom.uploadId && dom.uploadQueue) {
            try {
                dom.uploadQueue.cancel(dom.uploadId);
            } 
            catch (ex) {
            }
            return false;
        }
    },
    reset: function(){
        this.node.one('div.progress').addClass('hide');
        this.node.one('div.bar').setStyle('width', "0%");
        this.appear();
    },
    
    setProgress: function(percentage, msg){
        this.node.replaceClass('status-pending', "status-active");
        this.node.one('div.bar').setStyle('width', percentage + "%");
        if (msg) 
            this.setStatus(msg);
        this.appear();
    },
    setComplete: function(msg){
        this.node.replaceClass('status-active', "status-done");
        this.node.one('div.bar').setStyle('width', "100%");
        if (msg) 
            this.setStatus(msg);
        this.showCancelBtn(false, null);
    },
    setAlert: function(msg){
        this.node.replaceClass('status-active', "status-error");
        this.node.one('div.bar').setStyle('width', "100%");
        if (msg) 
            this.setStatus(msg);
        this.showCancelBtn(false, null);
    },
    setCancelled: function(msg){
    	this.node.replaceClass('status-pending', "status-cancelled");
    	this.node.replaceClass('status-active', "status-cancelled");
    	this.node.replaceClass('status-paused', "status-cancelled");
        if (msg) 
            this.setStatus(msg);
        this.showCancelBtn(false, null);
    },
    setPaused: function(msg){
    	this.node.replaceClass('status-pending', "status-paused");
        if (msg) 
            this.setStatus(msg);
        this.showCancelBtn(true, null);
    },    
    setReady: function(msg){
    	this.node.replaceClass('status-cancelled', "status-pending");
    	this.node.replaceClass('status-error', "status-pending");
    	this.node.replaceClass('status-paused', "status-pending");        
        this.node.one('div.bar').setStyle('width', "0%");
        if (msg) 
            this.setStatus(msg);
        this.showCancelBtn(true, null);
    },
    setStatus: function(status){
        var img = this.node.one('figure > img');
        img.setAttribute('title', this._cfg.label + ': ' + status);
    },
    
    // Show/Hide the cancel button
    showCancelBtn: function(show, uploadQueue){
        var node = this.node.one('div.cancel');
        if (show) {
            node.removeClass('hide');
        }
        else 
            node.addClass('hide');
        
        // this is set in UploadManager.uploadStart_Callback()
//        if (uploadQueue) {
//            node.uploadId = this.fileProgressID;
//            //			node.dom().uploadId = this.uuid;
//            node.uploadQueue = uploadQueue;
//        }
    },
    /*
     * animation effect doesn't really work. convert to YUI3 animation
     */
    appear: function(){
        if (this.getTimer() !== null) {
            clearTimeout(this.getTimer());
            this.setTimer(null);
        }
        
        if (this.node.dom().filters) {
            try {
                this.node.dom().filters.item("DXImageTransform.Microsoft.Alpha").opacity = 100;
            } 
            catch (e) {
                // If it is not set initially, the browser will throw an error.  This will set it if it is not set yet.
                this.node.setStyle('filter', "progid:DXImageTransform.Microsoft.Alpha(opacity=100)");
            }
        }
        else {
            this.node.setStyle('opacity', 1);
        }
        
        this.node.setStyle('height', "");
        this.node.removeClass('hide');
        this.height = this.node.get('offsetHeight');
        this.opacity = 100;
        
    },
    
    // Fades out and clips away the FileProgress box.
    disappear: function(){
        var reduceOpacityBy = 15;
        var reduceHeightBy = 4;
        var rate = 30; // 15 fps
        if (this.opacity > 0) {
            this.opacity -= reduceOpacityBy;
            if (this.opacity < 0) {
                this.opacity = 0;
            }
            
            if (this.node.dom().filters) {
                try {
                    this.node.setStyle('filter', "DXImageTransform.Microsoft.Alpha(opacity=" + this.opacity + ")");
                } 
                catch (e) {
                    // If it is not set initially, the browser will throw an error.  This will set it if it is not set yet.
                    this.node.setStyle('filter', "progid:DXImageTransform.Microsoft.Alpha(opacity=" + this.opacity + ")");
                }
            }
            else {
                this.node.setStyle('opacity', this.opacity / 100);
            }
        }
        
        if (this.height > 0) {
            this.height -= reduceHeightBy;
            if (this.height < 0) {
                this.height = 0;
            }
            this.node.setStyle('height', this.height + "px");
        }
        
        if (this.height > 0 || this.opacity > 0) {
            var oSelf = this;
            this.setTimer(setTimeout(function(){
                oSelf.disappear();
            }, rate));
        }
        else {
            this.node.addClass('hide');
            this.setTimer(null);
        }
    }
};

SNAPPI.AIR.FileProgress = FileProgress;

//LOG(SNAPPI);
LOG("load complete: fileprogress.js");	
})();
