(function(){

	var Y = SNAPPI.Y;
	
	var FLIP_OFFSET_UNPARALELL = 22;
	var CLASS_TRIANGLE = 'snappi-submenu-triangle';

	var markup = {
		
		// TODO: should refactor here, to change to pure <a></a>. menuItem : "<a>{label}</a>",
		menuItem : "<li>{label}</li>",
		menuHBar : "<div class='snappi-submenu-hbar'><div></div></div>",
		menuItemTagA : "<li><a>{label}</a></li>"
			
	};

	/*
	 * private helper methods
	 */
	var _wrapTextWithTag = function(node, tag){
		
		var text = node.get('innerHTML');
		text = "<" + tag + ">" + text + "</" + tag +">";
		node.setContent(text);
		return node;
		
	};
	
	var _generateMenuItem = function(menuItemCfg){

		if(menuItemCfg.label == 'hbar'){
			menuItem = Y.Node.create(Y.substitute(markup.menuHBar, menuItemCfg));
			
			return menuItem;
		}
		
		menuItem = Y.Node.create(Y.substitute(markup.menuItem, menuItemCfg));
		
		if(menuItemCfg.href){
			var tag = 'a';
			menuItem = _wrapTextWithTag(menuItem, tag);
			menuItem.one(tag).setAttribute('href', menuItemCfg.href);
		}
		if(menuItemCfg.id){
			menuItem.setAttribute('id', menuItemCfg.id);
		}
		if(menuItemCfg.title){
			menuItem.setAttribute('title', menuItemCfg.title);
		}
		if(menuItemCfg.className){
			menuItem.addClass(menuItemCfg.className);
		}

		return menuItem;	
	}
	
	/*
	 * class constructor
	 */
	SNAPPI.MenuItem = function(menuItemCfg) {

		this.init.apply(this, arguments);

	};
	
	/*
	 * Class prototype
	 */
	SNAPPI.MenuItem.prototype = {
		
		attach : function(parent){
	
			if(parent.dom().Menu.child == undefined){
				parent.dom().Menu.child = {};
			}
			this.parent = parent.dom().Menu;
			
			this.parent.child[this.label] = this;
			
			parent.append(this.container);
			
			return this.container;
		},	

		init : function(cfg){

			var menuItemNode = _generateMenuItem(cfg);
			
			this.label = cfg.label;
			this.container = menuItemNode;
			
			menuItemNode.dom().MenuItem = this;

			if(cfg.afterAttach && SNAPPI.Y.Lang.isFunction(cfg.afterAttach.fn)){
				this.afterAttach = cfg.afterAttach;
			}
			
			if(cfg.firstMouseover && SNAPPI.Y.Lang.isFunction(cfg.firstMouseover.fn)){
				this.firstMouseover = cfg.firstMouseover;
			}
			
			if(cfg.beforeShow && SNAPPI.Y.Lang.isFunction(cfg.beforeShow.fn)){
				this.beforeShow = cfg.beforeShow;
			}
			
			if(cfg.afterShow && SNAPPI.Y.Lang.isFunction(cfg.afterShow.fn)){
				this.afterShow = cfg.afterShow;
			}
			
			if (cfg.hasChild) {
				this.container.addClass(CLASS_TRIANGLE);
			}

			this.startListeners(cfg);
		},
		
		getMenu   : function(label) {
			return this.parent;
		},
		
		getNode   : function(){
			return this.container;
		},
		
		attachOnFirstMouseoverListener : function(){
			
			if(this.onFirstMouseover){

				var callbackCfg = this.onFirstMouseover;
				
				var onFirstMouseoverListener = function(){
					
					this.firstHoverListener.detach();
					
					if(callbackCfg.async){
						// TODO : need to think if need to remove the following
						var defaultAsyncCfg = {
							context : {},
							args  : []
						};
						
						var _asyncCfg = Y.merge(defaultAsyncCfg, callbackCfg.async);
						var func = function(){
			        		
			        		var asyncCfg = {
			                		fn     : callbackCfg.fn,
			    					node   : _asyncCfg.node,	// this is the same as this.container
			    					context: _asyncCfg.context  || this,
			    					args   : callbackCfg.args
			                };
			        		
			        		var async = new SNAPPI.AsyncLoading(asyncCfg);
			        		async.execute();
			        	};
			        	
						func();
					}else {
						callbackCfg.fn.apply(callbackCfg.context || this, callbackCfg.args || [this.getNode()]);
					}
				};
				this.onFirstMouseoverListener = onFirstMouseoverListener;
			}
		},
		
		attachOnHoverListener : function(){
			
			if(this.onHover){

				var callbackCfg = this.onHover;
				
				var onHoverListener = function(){
					
					if(callbackCfg.once){
						this.hoverListener.detach();
					}
					
					if(callbackCfg.async){
						// TODO : need to think if need to remove the following
						var defaultAsyncCfg = {
							context : {},
							args  : []
						};
						
						var _asyncCfg = Y.merge(defaultAsyncCfg, callbackCfg.async);
						var func = function(){
			        		
			        		var asyncCfg = {
			                		fn     : callbackCfg.fn,
			    					node   : _asyncCfg.node,	// this is the same as this.container
			    					context: _asyncCfg.context  || this,
			    					args   : callbackCfg.args
			                };
			        		
			        		var async = new SNAPPI.AsyncLoading(asyncCfg);
			        		async.execute();
			        	};
			        	
						func();
					}else {
						callbackCfg.fn.apply(callbackCfg.context || this, callbackCfg.args || [this.getNode()]);
					}
				};
				this.onHoverListener = onHoverListener;
			}
		},
		
		attachEndHoverListener : function(){
			
			if(this.endHover){

				var callbackCfg = this.endHover;
				
				var endHoverListener = function(){
					
					if(callbackCfg.once){
						this.hoverListener.detach();
					}
					
					if(callbackCfg.async){
						
						var defaultAsyncCfg = {
							context : {},
							args  : []
						};
						
						var _asyncCfg = Y.merge(defaultAsyncCfg, callbackCfg.async);
						var func = function(){
			        		
			        		var asyncCfg = {
			                		fn     : callbackCfg.fn,
			    					node   : _asyncCfg.node,
			    					context: _asyncCfg.context,
			    					args   : callbackCfg.args
			                };
			        		
			        		var async = new SNAPPI.AsyncLoading(asyncCfg);
			        		async.execute();
			        	};
						func();
					}else {
						callbackCfg.fn.apply(callbackCfg.context, callbackCfg.args || [this.getNode()]);
					}
				};
				this.endHoverListener = endHoverListener;
			}
		},

		listenToClick : function(){

			if(this.onclick){

				var callbackCfg = this.onclick;
				
				var clickListener = this.container.on('click', function(){
					
					if(callbackCfg.once){
						this.clickListener.detach();
					}
					
					if(callbackCfg.async){
						
						var defaultAsyncCfg = {
							context : {},
							args  : []
						};
						
						var _asyncCfg = Y.merge(defaultAsyncCfg, callbackCfg.async);
						var func = function(args){
			        		
			        		var asyncCfg = {
			                		fn     : callbackCfg.fn,
			    					node   : _asyncCfg.node,
			    					context: _asyncCfg.context,
			    					args   : callbackCfg.args
			                };
			        		
			        		var async = new SNAPPI.AsyncLoading(asyncCfg);
			        		async.execute();
			        	};
			        	
						func();
					}else {
						callbackCfg.fn.apply(callbackCfg.context || this.dom().MenuItem, callbackCfg.args || [this]);
					}
					this.clickListener = clickListener;
				});
			}
		},
		
		stopListeningClick : function(){
			this.clickListener.detach();
		},

		startListeners : function(cfg){
			if(cfg.onclick && SNAPPI.Y.Lang.isFunction(cfg.onclick.fn)){
				this.onclick = cfg.onclick;
				this.listenToClick();
			}
			
			if(cfg.hover){
				var hover = cfg.hover;
				if(hover.on && SNAPPI.Y.Lang.isFunction(hover.on.fn)){
					this.onHover = hover.on;
					this.attachOnHoverListener();
				}
				if(hover.end && SNAPPI.Y.Lang.isFunction(hover.end.fn)){
					this.endHover = hover.end;
					this.attachEndHoverListener(); 
				}
				this.hoverListener = this.container.on('snappi:hover', this.onHoverListener || function(){}, 
															this.endHoverListener || function(){}, this);

			}
			
			if(cfg.firstMouseover){
				var firstMouseover = cfg.firstMouseover;
				this.onFirstMouseover = firstMouseover;
				this.attachOnFirstMouseoverListener();
				this.firstHoverListener = this.container.on('mouseover', 
							this.onFirstMouseoverListener, firstMouseover.context || this, firstMouseover.args || [this.getNode()]);
			}
			
		},

		stopListners: function(){
			
			if(this.hoverListener){
				this.hoverListener.detach();
			}
			if(this.clickListener){
				this.clickListener.detach();
			}
		},
		
		autoShowChild : function(){
			this.onHover = {
					fn : this.showChild,
					context : this
				};
			this.attachOnHoverListener();
			this.hoverListener = this.container.on('snappi:hover', this.onHoverListener || function(){}, 
					this.endHoverListener || function(){}, this);

		},
		
		showChild : function(){
			this.child.show();
		},
		
		show    : function(){
			this.container.removeClass('hide');
		},
		
		hide     : function(){
			this.container.addClass('hide');
		}

	};

})();
