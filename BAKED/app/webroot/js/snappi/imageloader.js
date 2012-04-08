/**
 *
 * Copyright (c) 2009, Snaphappi.com. All rights reserved.
 *
 * This program is free software: you can redistribute it and/or modify it under
 * the terms of the Affero GNU General Public License as published by the Free
 * Software Foundation, either version 3 of the License, or (at your option) any
 * later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the Affero GNU General Public License for more
 * details.
 *
 * You should have received a copy of the Affero GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * @author Michael Lin, info@snaphappi.com
 *
 * SNAPPI.imageLoader, singleton class/object for monitoring/loading IMG src async 
 */
(function(){
	var _Y = null;
	SNAPPI.namespace('SNAPPI.onYready');
	SNAPPI.onYready.ImageLoader = function(Y){
		if (_Y === null) _Y = Y;
		
		ImageLoader.QUEUE_IMAGES = true;
		SNAPPI.imageLoader = new ImageLoader();	
	}  
    
    /*
     * protected methods and variables
     */
	var _queueCallback = {
        fn: function(chunkSize, queueImgSrc){
            var n = chunkSize;
            while (imageLoader.arrayToQueue.length && n-- > 0) {
                if (queueImgSrc) 
                    this.queueOneImg(imageLoader.arrayToQueue.shift());
                else 
                    this.push(imageLoader.arrayToQueue.shift());
            }
            this.finishIfLoaded();
        },
        context: this,
        args: null,
        until: function(){
            return imageLoader.arrayToQueue.length == 0;
        },
        timeout: 10
    };    
    var _queue;
    
    /*
     * ImageLoader Class
     */
    var ImageLoader = function(cfg){
    	if (ImageLoader.instance) return ImageLoader.instance;	// singleton class
    	
		/*
		 * private attributes
		 */
        
        this.init(cfg);
    };
    SNAPPI.ImageLoader = ImageLoader;
    
    /*
     * class static
     */
    ImageLoader.QUEUE_IMAGES = null;
    
	ImageLoader.prototype = {
		init : function(cfg){
			/*
			 * WARNING: By initializing singleton class in module load
			 * this script REQUIRES 'async-queue' to be loaded BEFORE script load
			 */
			_queue = _queue || new _Y.AsyncQueue();
            /*
             * initialize AsynchQueue
             */
            _queue.pause();
            _queue.defaults.timeout = 10;
            _queue.on('complete', function(chunkSize, queueImgSrc){
                // queue again if there are still imgs to load
                // wait a moment before restarting
                if (imageLoader.arrayToQueue.length) {
                    // _queueCallback.args = [chunkSize, queueImgSrc];
                    // _queue.add(_queueCallback);
                    // _queue.run();
                }
                else 
                    this.finishIfLoaded();
            }, this);
            
            /*
             * cfg.limit default
             */
            this.limit = (cfg && cfg.limit) ? cfg.limit : 1000;
            /*
             * object containing list of items to load
             */
            this.arrayToQueue = [];
            this._watchList = {};
            this._watchListCount = 0;
            this.cleanupTimer = null;
            /*
             * array of onComplete callback functions
             */
            this.onComplete = [];
            ImageLoader.instance = this;
        },
        
        /*
         * use a selector to monitor the watch of all IMG elements
         */
        watchBySelector : function(selector, onComplete, cfg){
            var arrImg = [];
            if (_Y.Lang.isFunction(onComplete)) 
                this.onComplete.push(onComplete);
            
            _Y.all(selector).each(function(node){
                if (node.get('nodeName') == "IMG") {
                    arrImg.push(node);
                }
                else {
                    node.all('img').each(function(img){
                        if (img.get('nodeName') == "IMG") {
                            arrImg.push(img);
                        }
                    });
                }
            });
            /*
             * now that we have an array of all IMG elements, load
             */
            var _cfg = _Y.merge({
                chunkSize: 500, // use larger chunkSize if we are just
                // watching
                queueImgSrc: false
            }, cfg);
            // watch by selector
            this.prepareToAddToAsynchQueue(arrImg, _cfg.chunkSize, _cfg.queueImgSrc);
        },
        
        /*
         * use a selector to monitor the load of all IMG elements
         */
        queueBySelector : function(container, selector, onComplete, chunkSize){
            chunkSize = chunkSize || 40;
            var arrImg = [];
            if (_Y.Lang.isFunction(onComplete)) 
                this.onComplete.push(onComplete);
            
            var root = _Y.one(container);
            if (!root) 
                root = container;
            root.all(selector).each(function(node){
                if (node.get('nodeName') == "IMG") {
                    arrImg.push(node);
                }
                else {
                    // nodes are containers, so search for IMG elements
                    node.all('img').each(function(img){
                        if (img.get('nodeName') == "IMG") {
                            arrImg.push(img.dom());
                        }
                    });
                }
            }),
            
            
            /*
             * now that we have an array of all IMG elements, queue all
             * to execute in chunks
             */
            // queue by Selector
        	this.prepareToAddToAsynchQueue(arrImg, chunkSize, true);
        },
        
        
        
        prepareToAddToAsynchQueue : function(arrImg, chunkSize, queueImgSrc){
            chunkSize = chunkSize || 40;
            this.arrayToQueue = this.arrayToQueue.concat(arrImg);
            if (imageLoader.arrayToQueue.length == 0) {
                this.finishIfLoaded();
                return;
            }
            // queue first iteration, and run
            _queueCallback.args = [chunkSize, queueImgSrc];
            if (_queue.indexOf(_queueCallback) == -1) {
                _queue.add(_queueCallback).promote(_queueCallback);
                if (SNAPPI.imageLoader.cleanupTimer == null) 
                    SNAPPI.imageLoader.cleanupTimer = _Y.later(10000, SNAPPI.imageLoader, SNAPPI.imageLoader.queueCleanup, null, true);
            }
            if (!_queue.isRunning()) 
                _queue.run();
        },
        
        queueOneImg : function(imgEl){
            imgEl = imgEl.dom();
            /*
             * begin img load process if queued src exists, assume that
             * is the new src
             */
            if (imgEl.qSrc) {
                imgEl.src = imgEl.qSrc;
                imgEl.qSrc = null;
            }
            /*
             * register to wait for load
             */
            this.push(imgEl);
        },
        
        onLoadHandler : function(e){
            alert("delegate event working in imageLoader!!!");
            var imgEl = e.currentTarget.dom();
            if (imgEl.imageLoader !== undefined) {
                imgEl.imageLoader.popCompleted(imgEl);
            }
        },
        push : function(imgEl){
            if (!(imgEl instanceof HTMLElement)) {
                imgEl = imgEl.dom();
            }
            /*
             * add this src as a property on the watchList. add unique.
             */
            if (imgEl.nodeName == 'IMG' && !imgEl.naturalHeight) {
                this._watchList[imgEl.src] = 1;
                this._watchListCount++;
                imgEl.imageLoader = this;
                _Y.on('load', this.popCompleted, imgEl, this, imgEl);
            }
        },
        
        popCompleted : function(e, imgEl){
            if (!(imgEl instanceof HTMLElement)) {
                imgEl = imgEl.dom();
            }
            if (imgEl.currentTarget) 
                imgEl = imgEl.currentTarget;
            delete this._watchList[imgEl.src];
            this._watchListCount--;
            delete imgEl.imageLoader;
            _Y.Event.detach('load', this.popCompleted, imgEl);
            this.finishIfLoaded();
        },
        finishIfLoaded : function(){
            if (this.arrayToQueue.length || this._watchListCount) 
                return false;
            
            _queue.stop();
            if (this.cleanupTimer) {
                this.cleanupTimer.cancel();
                this.cleanupTimer = null;
            }
            if (this.onComplete.length) {
                for (var c in this.onComplete) {
                    this.onComplete[c]();
                }
                this.onComplete = [];
            }
            return true;
        },
		/*
         * cleanup methods
         */
        queueCleanup : function(loadingmask){
        	try {
        		var visible = loadingmask.overlaymask.get('visible');	
        	} catch (e) {}
            if (visible) {
                SNAPPI.imageLoader.cleanup();
            }
            else {
                var cleanupTimer = SNAPPI.imageLoader.cleanupTimer;
                if (cleanupTimer && cleanupTimer.cancel) 
                    cleanupTimer.cancel();
                SNAPPI.imageLoader.cleanupTimer = null;
            }
        },
        cleanup : function(){
            // confirm that this._watchList && this._watchListCount are
            // in sync
            var n = 0;
            for (var p in this._watchList) {
                n++;
            }
            this._watchListCount = n;
            if (this.finishIfLoaded() == false && this.arrayToQueue.length == 0) {
                this.watchBySelector('img', null, {
                    chunkSize: 5000,
                    queueImgSrc: false
                });
            }
        }		
	}    
})();
