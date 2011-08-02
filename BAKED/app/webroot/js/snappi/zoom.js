(function(){
	
	var Y = SNAPPI.Y;

	var config = {
		zoomBoxId : 'snappi-zoomBox',
		zoomBoxClass : '',
		zoomInfoId : 'zoom_info',
		zoomInfoClass : 'zoom_info',
		zoomRatingGrpId: 'zoom_ratingGrp'
	};
	
	var markup = {
		zoomBox : "<div id='" + config.zoomBoxId + "' class='hide " + config.zoomBoxClass + "'></div>",
		zoom_info : "<div id='" + config.zoomInfoId + "'' class='hide " + config.zoomInfoClass + "'></div>"
	};
	
	
	/*
	 * private helper methods
	 */
	var _attachImg = function(target){

		var img = this.container.one('img'),
			_getImgSrcBySize;
		
		try {
			_getImgSrcBySize = target.dom().audition.getImgSrcBySize;
		} catch (e) {
		}
		
		if(img){
			var src = target.one('img').get('src');
			
			// unbind this.container from "old" audition 
            var old = this.container.dom().audition;
            if (old && old.bindTo && Y.Lang.isArray(old.bindTo)) { 
                old.bindTo.splice(old.bindTo.indexOf(this.container), 1);
            }        	
            
            // bind this.container to "new" audition
            var audition = target.dom().audition;
            var src = _getImgSrcBySize(audition.urlbase + audition.src, 'bs');
            img.set('src', src);
            
            this.container.audition = audition;
            this.container.dom().audition = this.container.audition;	// deprecate
            Y.Lang.isArray(audition.bindTo) ? audition.bindTo.push(this.container) : audition.bindTo = [this.container];
            
            
		}else {
			var copy = target.one('img').cloneNode(false);
    		copy.replaceClass('sq', 'zoom');

    		// TODO base on this bindTo, need to refactor this component and Thumbnail.js to make it more easy and logical
    		// can check _applyOneRating() in lightbox.js to learn how to use bindTo properly  
    		var audition = target.dom().audition;
            var src = _getImgSrcBySize(audition.urlbase + audition.src, 'bs');
            copy.set('src', src);    	
            
    		Y.Lang.isArray(audition.bindTo) ? audition.bindTo.push(this.container) : audition.bindTo = [this.container];
    		
    		var node = this.container;
    		node.audition = audition
    		node.dom().audition = node.audition;	// DEPRECATE
    		
    		node.plug(Y.Plugin.Drag);
    		node.dd.plug(Y.Plugin.DDConstrained, {
    			constrain2node : '#container'
    		});
    		node.dd.addHandle('img');
            
    		node.appendChild(copy);
            
		}
		this.showZoomBox();
		return img;
		
	};
	
	var _attachRating = function(target){
		var audition = target ? target.dom().audition : this.container.dom().audition;
		var targetId = audition.Audition.id;
		var _cfg = {
				v : audition.rating,
				id: config.zoomRatingGrpId,
				uuid: targetId,
				setDbValueFn: SNAPPI.AssetRatingController.postRating,
				listen: true
			};        	
		SNAPPI.Rating.attach(this.container, _cfg);
		var rating = this.container.Rating;
		rating.node.addClass('hide');
		return this.container.Rating;
	};
	
	var _attachInfo = function(target) {
		var info = this.container.one('.'+config.zoomInfoClass);
		if(!info){
			var zoom_info = Y.Node.create(markup.zoom_info);
			info = this.container.appendChild(zoom_info);
		}
		
		var content = _getImgInfo.call(this, target);
		info.setContent("DateTaken: " + content);
		info.addClass('hide');	
		return info;
	};
	
	var _getImgInfo = function(target){
		var audition = target ? target.dom().audition : this.container.dom().audition;
		var info = this.container.dom().audition.Audition.Photo.DateTaken;

		return info;
	};
	
	/*
	 * private event handlers
	 */
	var overZoomHandler = function(e){
		
		try {
			this.container.one('.ratingGroup').removeClass('hide');
			this.container.one('.'+config.zoomInfoClass).removeClass('hide');
		} catch(e){
			
		}
	};
	
	var outZoomHandler = function(e){

		if (SNAPPI.STATE.showRatings && SNAPPI.STATE.showRatings == 'show') return;
		
		try {
			this.container.one('.ratingGroup').addClass('hide');
			this.container.one('.'+config.zoomInfoClass).addClass('hide');
		}catch(e){
			
		}	
	};
	
	var zoomHandler = function(e){
		
		var Y = SNAPPI.Y;
		var target = e.currentTarget;
		
		this.img = _attachImg.call(this, target);
		
		this.ratingGrp = _attachRating.call(this);
		
		this.info = _attachInfo.call(this);
		
		if (SNAPPI.STATE.showRatings && SNAPPI.STATE.showRatings == 'show') {
			try {
				this.ratingGrp.node.removeClass('hide');
				this.info.removeClass('hide');
			} catch(e){
				
			}
		}
	};
	
	var _buildZoomBox = function(){
		
		var zoomBox = Y.Node.create(markup.zoomBox);
		
		zoomBoxNode = Y.one('body').appendChild(zoomBox);
		
		return zoomBoxNode;
	};
	
	
	/*
	 * class constructor
	 */
	SNAPPI.Zoom = function(rootNode, buttonNode, unitClass) {
		
		var Y = SNAPPI.Y;
		
		this.config = {
			unitClass : (unitClass || 'li')
		};
		
		this.attachZoom(rootNode, buttonNode);
		
	};
	
	
	/*
	 * Static attributes and methods
	 *    ONLY ONE PER CLASS, SHARED BY ALL OBJECTS
	 */
	SNAPPI.Zoom.listeningTo = [];	// array of all attached rootNodes
	SNAPPI.Zoom.zoomBox = null;		// this.container = SNAPPI.Zoom.zoomBox = outer DOM node
	
	
	/*
	 * Class prototype
	 */
	SNAPPI.Zoom.prototype = {
		
		toggleZoomMode : function(){

			var label = this.buttonNode.get('innerHTML');
			
			switch (label) {
	
				case 'Zoom Mode' :	
					this.buttonNode.set('innerHTML', 'Normal Mode');
					this.run();
					break;
				case 'Normal Mode':
					this.buttonNode.set('innerHTML', 'Zoom Mode');
					this.stop();
					break;
			}
			
		},	
		
		run : function(){

			if(!this.container){
				if (!SNAPPI.Zoom.zoomBox) {
					// 	CREATE zoomBox for the first time
					SNAPPI.Zoom.zoomBox = _buildZoomBox();
				}
				this.container = SNAPPI.Zoom.zoomBox;
	
			}else {
				this.showZoomBox();
			}
			
			SNAPPI.Zoom.listeningTo.push(this.rootNode);
			this.startAllListeners();
			
		},
		
		stop : function(){
			this.stopAllListeners();

			var listeners = SNAPPI.Zoom.listeningTo;
			
			var i = listeners.indexOf(this.rootNode);
			if (i > -1) listeners.splice(i, 1);
			if (listeners.length == 0) this.hideZoomBox();
		},
		
		startAllListeners: function(){
			
			var Y = SNAPPI.Y;
			
			this.zoomListener = this.rootNode.delegate('snappi:hover', zoomHandler, function(){}, this, this.config.unitClass);
			this.hoverZoomListener = Y.one('#' + config.zoomBoxId).on('snappi:hover', overZoomHandler, outZoomHandler, this);
		},
		
		stopAllListeners: function(){
			
    		this.zoomListener.detach();
    		this.hoverZoomListener.detach();
		},

		attachZoom : function(root, buttonNode){
			
			this.rootNode = root;
			this.buttonNode = buttonNode;
			this.rootNode.dom().Zoom = this;
		},
		
		showZoomBox : function(){
			SNAPPI.Zoom.zoomBox.removeClass('hide');
		},
		
		hideZoomBox : function(){
			SNAPPI.Zoom.zoomBox.addClass('hide');
		}

	};
	
})();