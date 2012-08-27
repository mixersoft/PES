(function(){
	
	var Y = SNAPPI.Y;
	
	var isObject = Y.Lang.isObject, 
		isUndefined = Y.Lang.isUndefined, 
		isArray  = Y.Lang.isArray,
		isFunction = Y.Lang.isFunction,
		isNull = Y.Lang.isNull;
	
	var BACKGROUND_POSITION = 'center',
		CUSTOMIZATION_LOADING_ICON_TEXT = 'customizedLoadingIcon',
		DIR = '/img/css/',
		TIMEOUT = 500;
	
	var imgs = {
		small : DIR + 'ajax-loading-img-small.gif',
		medium : DIR + 'ajax-loading-img-med.gif',
		big : DIR + 'ajax-loading-img-big.gif',
		huge : DIR + 'ajax-loading-img-hg.gif'
	};
	
	var q = new Y.AsyncQueue();
	
	var className = {
		small  : 'loading-sm',
		medium : 'loading-med',
		big : 'loading-big',
		huge  : 'loading-hg'	
	};
	
	function AsyncLoading(o){
		AsyncLoading.superclass.constructor.apply(this, arguments);
	};
	
	Y.mix(AsyncLoading, {
		NAME : 'asyncLoading',
		
		ATTRS : {
			fn : {
				validator : function(func){
					return isFunction(func);
				},
				getter : function(func){
					if(!isFunction(func)){
						alert('invalid function, please check your async.fn');
					}else {
						return func;
					}
				}
			},
			node : {
				validator : function(node){
					return !isNull(node);
				},
				getter : function(node){
					if(isUndefined(node)){
						alert('invalid node, please check your async.node');
					}else {
						return node;
					}
				}
			},
			context : {
				value : {}
			},
			size : {
				value : 'small',
				validator : function(size){
					return !isUndefined(className[size]);
				},
				getter : function(size){
					if(isUndefined(className[size])){
						alert('invalid size, please check your async.size');
					}else {
						return size;
					}
				}
			},
			args : {
				validator : function(args){
					return isArray(args) ? args : new Array(args);
				}
			},
			deleteIt : {
				value : false
			},
			className : {
				
			},
			customizedLoadingIcon : {
				value : false
			},
			done : {
				value : 0
			}
		}
	});
	
	Y.extend(AsyncLoading, Y.Base, {
		initializer : function(){
			var Y = SNAPPI.Y;
			// first way to remove loading icon, by publishing/subscribing.
			Y.on('snappi:completeAsync', function(node){
		        
				if(node.dom().Async == undefined){
					return false;
				}
				
		    	if(node.dom().Async.deleteIt){
		    		node.remove();
		    	}else {
		        	node.dom().Async.removeLoading();
		    	}
		        
		    });
			
		},
		
		// detaching after timeout.
		_detach  : function(){
			var node = this.get('node');
			
			if(node.dom().Async == undefined){
				return false;
			}
			
	    	if(node.dom().Async.deleteIt){
	    		node.remove();
	    	}else {
	        	node.dom().Async.removeLoading();
	    	}
	        
		},
		
		_render  : function(){
			var _className, node = this.get('node');
			
			node.setStyle('backgroundPosition', BACKGROUND_POSITION);
			_className = this.get('className');
			node.addClass(_className);
		},
		
		_addClass : function(){
			
			if(this.get('customizedLoadingIcon')){
				this.set('className', CUSTOMIZATION_LOADING_ICON_TEXT);
				return false;
			}
			
			var _className, 
				size = this.get('size');

			_className = className[size];
			
			this.set('className', _className);
		},
		
		_run : function(){
//			Y.later(1500, this, function(){
				this.get('fn').apply(this.get('context'), this.get('args'));
//			});
			
		},
		
		_attach : function(){
			
			this.get('node').dom().Async = {
        		deleteIt  : this.get('deleteIt'),
        		className : this.get('className'),
        		container : this.get('node'),
        		removeLoading    : function(){
    	    		this.container.removeClass(this.className);
    	    	}
        	};
		},
		
		execute : function(){
			
			q.add(
				{
					fn : this._addClass,
					context : this
				},
				{
					fn : this._render,
					context : this
				},
				{
					fn : this._attach, 
					context : this
				},
				{
					fn : this._run,
					context : this
				}
				/*
				 * second way to remove loading icon, by setting a default timeout, after specific time, 
				 * we will remove the loading icon.
				, {
					fn : this._detach,
					context : this
					, timeout: TIMEOUT
				}
				*/
				
			);
			q.run();
		},
		
		setLoadingIcon : function(backgroudImg){
			var cssObj = {
				'background' : 'url("/img/css/' + backgroudImg + '") no-repeat scroll center center #FFFFFF',
				'z-index' : 1
			};
			
			loadingIconCSS = new Y.StyleSheet();
			loadingIconCSS.set('ul.' + CUSTOMIZATION_LOADING_ICON_TEXT, cssObj);
			loadingIconCSS.set('li.' + CUSTOMIZATION_LOADING_ICON_TEXT, cssObj);
			loadingIconCSS.set('div.' + CUSTOMIZATION_LOADING_ICON_TEXT, cssObj);
			
			this.set(CUSTOMIZATION_LOADING_ICON_TEXT, true);
		}
		
	});
	
	SNAPPI.AsyncLoading = AsyncLoading;
	
})();


















