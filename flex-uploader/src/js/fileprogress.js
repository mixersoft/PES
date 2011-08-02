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


/**
 * @params cfg object {id: label: uuid:}
 * @params page int or Y.Node - for access to the page node
 */
var FileProgress = function (cfg, page){
	var Y = SNAPPI.Y;
    this.fileProgressID = cfg.id;
    this.uuid = cfg.uuid;
    this.label = cfg.label;
    this.row_deprecate = cfg.row_deprecate;
    
    this.opacity = 100;
    this.height = 0;
        // use YUI3 libs
//LOG("******** FileProgress constructor  Y.version = " + Y.version + ", cfg.label=" + cfg.label + ", cfg.uuid=" + cfg.uuid);
        // init upload-queue-container
    var progressContainer = Y.one('#progress-' + this.uuid);
    if (!progressContainer) {
    	var progressContainerTemplate = FileProgress.prototype.markupTemplate;
        progressContainerTemplate = progressContainerTemplate.replace(/:label/g, cfg.label);
        progressContainerTemplate = progressContainerTemplate.replace(/:uuid/g, cfg.uuid);
        this.progressContainer = Y.Node.create(progressContainerTemplate);
        this.progressContainer.setAttribute('uuid', cfg.uuid);
        this.progressContainer.FileProgress = this; // backreference
        this.progressContainer.dom().FileProgress = this; // DEPRECATE

        // add item to page with loading gif
        if (page instanceof Y.Node) {
            page.append(this.progressContainer);
        } else {
        	try {
            	var pageId = "#uq-page-"+page;
                Y.one(pageId).append(this.progressContainer);
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
                success: function(src, arguments){	
//        			LOG(">>>>>>>>>>>>>> SUCCESS src=" + src);
        			var img = Y.one('#img-' + arguments.uuid).set('src', src);
                },
                failure: function(src){
                    LOG(">>>>>>>>>>>>>> FAILURE src=" + src);
                },
                arguments: {
                    uuid: cfg.uuid
//                  ,  Y: Y
                }
            }
        };
        try{
        	// add listener for img.onload
			var img = this.progressContainer.one('#img-' + cfg.uuid);
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
        this.progressContainer = progressContainer;
        this.reset();
    }
    this.height = this.progressContainer.get('offsetHeight');
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
	markupTemplate: "<li id='progress-:uuid' class='progress-container'>" +
			"<img class='thumbnail hidden' id='img-:uuid' title=':label' alt=':label'>" +
	 		"<div class='cancel'></div>" +
	 		"<div class='icon'>" +
	 		"<div class='glass'></div>" +
	 		"<div class='progress'>" +
	 		"<div class='border'><div class='bar'></div></div>" +
	 		"</div></div></li>",
    setTimer: function(timer){
        this.progressContainer.dom().FP_TIMER = timer;
    },
    getTimer: function(timer){
        return this.progressContainer.dom().FP_TIMER || null;
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
        this.progressContainer.one('div.progress').addClass('hide');
        this.progressContainer.one('div.bar').setStyle('width', "0%");
        this.appear();
    },
    
    setProgress: function(percentage, msg){
        this.progressContainer.replaceClass('status-pending', "status-active");
        this.progressContainer.one('div.progress').removeClass('hide');
        this.progressContainer.one('div.bar').setStyle('width', percentage + "%");
        if (msg) 
            this.setStatus(msg);
        this.appear();
    },
    setComplete: function(msg){
        this.progressContainer.replaceClass('status-active', "status-done");
//        this.progressContainer.one('div.progress').addClass('hide');
        this.progressContainer.one('div.bar').setStyle('width', "100%");
        if (msg) 
            this.setStatus(msg);
        this.showCancelBtn(false, null);
    },
    setAlert: function(msg){
        this.progressContainer.replaceClass('status-active', "status-error");
        this.progressContainer.one('div.progress').addClass('hide');
        this.progressContainer.one('div.icon').addClass('hide');
        //        this.progressContainer.one('div.bar').setStyle('width', "0%");
        if (msg) 
            this.setStatus(msg);
        this.showCancelBtn(false, null);
    },
    setCancelled: function(msg){
    	this.progressContainer.replaceClass('status-pending', "status-cancelled");
    	this.progressContainer.replaceClass('status-active', "status-cancelled");
        this.progressContainer.one('div.icon').addClass('hide');
//        this.progressContainer.one('div.bar').setStyle('width', "0%");
        if (msg) 
            this.setStatus(msg);
        this.showCancelBtn(false, null);
    },
    setPaused: function(msg){
    	this.progressContainer.replaceClass('status-pending', "status-paused");
        if (msg) 
            this.setStatus(msg);
        this.showCancelBtn(true, null);
    },    
    setReady: function(msg){
        this.progressContainer.addClass('status-pending');
//        this.progressContainer.one('div.progress').addClass('hide');
        this.progressContainer.one('div.bar').setStyle('width', "0%");
        if (msg) 
            this.setStatus(msg);
        this.showCancelBtn(true, null);
    },
    setStatus: function(status){
        var img = this.progressContainer.one('img.thumbnail');
        img.setAttribute('title', this.progressContainer.FileProgress.label + ': ' + status);
    },
    
    // Show/Hide the cancel button
    showCancelBtn: function(show, uploadQueue){
        var node = this.progressContainer.one('div.cancel');
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
        
        if (this.progressContainer.dom().filters) {
            try {
                this.progressContainer.dom().filters.item("DXImageTransform.Microsoft.Alpha").opacity = 100;
            } 
            catch (e) {
                // If it is not set initially, the browser will throw an error.  This will set it if it is not set yet.
                this.progressContainer.setStyle('filter', "progid:DXImageTransform.Microsoft.Alpha(opacity=100)");
            }
        }
        else {
            this.progressContainer.setStyle('opacity', 1);
        }
        
        this.progressContainer.setStyle('height', "");
        this.progressContainer.removeClass('hide');
        this.height = this.progressContainer.get('offsetHeight');
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
            
            if (this.progressContainer.dom().filters) {
                try {
                    this.progressContainer.setStyle('filter', "DXImageTransform.Microsoft.Alpha(opacity=" + this.opacity + ")");
                } 
                catch (e) {
                    // If it is not set initially, the browser will throw an error.  This will set it if it is not set yet.
                    this.progressContainer.setStyle('filter', "progid:DXImageTransform.Microsoft.Alpha(opacity=" + this.opacity + ")");
                }
            }
            else {
                this.progressContainer.setStyle('opacity', this.opacity / 100);
            }
        }
        
        if (this.height > 0) {
            this.height -= reduceHeightBy;
            if (this.height < 0) {
                this.height = 0;
            }
            this.progressContainer.setStyle('height', this.height + "px");
        }
        
        if (this.height > 0 || this.opacity > 0) {
            var oSelf = this;
            this.setTimer(setTimeout(function(){
                oSelf.disappear();
            }, rate));
        }
        else {
            this.progressContainer.addClass('hide');
            this.setTimer(null);
        }
    }
};

SNAPPI.AIR.FileProgress = FileProgress;

//LOG(SNAPPI);
LOG("load complete: fileprogress.js");	
})();
