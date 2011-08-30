(function(){

	var Y = SNAPPI.Y;
	
	var HEIGHT_COEFFICIENT = 2.7,
		DEFAULT_CONTEXT = SNAPPI.lightbox;
	
	var markup = {
			
			// need to calculate the height and top, so i need to use hidden, not hide.
			menuFrame: "<ul class='snappi-submenu hidden'></ul>"
				
		};

	/*
	 * private helper methods
	 */
	// *****************************
	var _setFlipPosition = function(node, sibling, parent){
		var parentNode = parent || false,
			sibling = sibling || false;
		if(!parentNode){
			parentNode = node.get('parentNode');
		}

		var h_px = node.get('offsetHeight'),
			oldTop_px = node.getStyle('top'),
			oldTop = parseInt((oldTop_px.split('px'))[0]),
			newTop, menuItemHeight;
			if(node.get('firstChild')){
				menuItemHeight = node.get('firstChild').get('offsetHeight');
			}
			
		var margin_offset = (node.get('parentNode') == parentNode) ? menuItemHeight * HEIGHT_COEFFICIENT : 0;
		var p_h_px = (node.get('parentNode') == parentNode) ? 0 : parentNode.get('offsetHeight');
		
		newTop = oldTop - h_px - p_h_px + margin_offset;
		return newTop + 'px';
	};
	
	var _generateMenu = function(menuCfg){
		var menuFrame = Y.Node.create(markup.menuFrame);

		if(menuCfg.id){
			menuFrame.setAttribute('id', menuCfg.id);
		}
		if(menuCfg.className){
			menuFrame.addClass(menuCfg.className);
		}
		return menuFrame;
	};
	
	var _setXY = function(node){
		var l = node.get('offsetLeft'),
			t = node.get('offsetHeight') + node.get('offsetTop');
		
		this.container.setStyle('left', l);
		this.container.setStyle('top', t);
	};
	
	var _attachOpenEvent = function(openBy){
		if(Y.Lang.isString(openBy)){
			if(openBy == 'click'){
				this.parent.onclick = {
					fn : this.parent.child.show,
					context : this.parent.child
				};
				
				this.parent.listenToClick();
				
			}else if(openBy == 'hover'){
				this.parent.container.addClass('snappi-submenu-triangle');
			}
		}
		else { // open by user's custom event handler
			
			// attach user's custom event handler
			// TODO: only allow one arg temporarily, will finger out how to pass args to delegate listening system.
			var parent = openBy.parent || this.parent.container;
			
			if(openBy.selector){
				parent.delegate(openBy.event, openBy.handler(), openBy.selector, openBy.context || this, openBy.args || [this.getNode()]);
			}else {
				parent.on(openBy.event, openBy.handler(), openBy.context || this, openBy.args || [this.getNode()]);
			}
			
		}
	};
	
	var _appendChild = function(sibling){
		var newMenuNode;
		if(sibling){
			
			newMenuNode = this.parent.container.get('parentNode').appendChild(this.container);
			newMenuNode.dom().Menu = this;

			_setXY.call(this, this.parent.container);
		}else {
			
			newMenuNode = this.parent.container.appendChild(this.container);
			newMenuNode.dom().Menu = this;
		}
		return newMenuNode;
	};
	
	var _addPositions = function(sibling){
		if(!sibling){
			this.newTop = _setFlipPosition(this.container, false, this.parent.container);
		}else {
			this.newTop = _setFlipPosition(this.container, true, this.parent.container);
		}
		this.normalTop = this.container.getStyle('top');
	};

	
	/*
	 * class constructor
	 */
	SNAPPI.Menu = function(menuCfg) {
		this.init.apply(this, arguments);
	};

	/*
	 * Class prototype
	 */
	SNAPPI.Menu.prototype = {
		attach : function(parent, sibling, openBy){
			var sibling = sibling || false,
				openBy = openBy || this.openBy || 'hover',
				parentMenuItemDOM;
			
			if(parent.dom().MenuItem == undefined){
				
				parent.dom().MenuItem = new SNAPPI.MenuItem({});
				this.parent = parent.dom().MenuItem;
				
				this.parent.child = this;
				this.parent.container = parent;
				
			}else {
				this.parent = parent.dom().MenuItem;
				this.parent.child = this;
			}
			
			_attachOpenEvent.call(this, openBy);
			
			if (this.parent instanceof SNAPPI.Menu ) sibling == false;	
			newMenuNode = _appendChild.call(this, sibling);
			_addPositions.call(this, sibling);
			
			// not sure if we need to replace the 'hidden' by 'hide' for later show/hides
			this.container.replaceClass('hidden', 'hide');

			if(this.afterAttach){
				this.afterAttach.fn.apply(this.afterAttach.context || this, this.afterAttach.args || [this.getNode()]);
			}
			
			this.delegateEventsOnMenuItems();

			return newMenuNode;
		},	
		init : function(menuCfg, menuItemSet){

			var i,
				menuNode = _generateMenu(menuCfg),
				menuItems;
						
			this.container = menuNode;
			menuNode.dom().Menu = this;
			
			// just need menuItemsSet || menuItems
			menuItems = menuItemSet || menuCfg.menuItems;

			for (i in menuItems){
				var menuItem = new SNAPPI.MenuItem(menuItems[i]);
				menuItem.attach(menuNode);
				if(menuItem.afterAttach){
					menuItem.afterAttach.fn.apply(menuItem.afterAttach.context || menuItem, menuItem.afterAttach.args || [menuItem.getNode()]);
				}
			}

			if(menuCfg.openBy){
				this.openBy = menuCfg.openBy;
			}
			
			if(menuCfg.afterAttach) {
				this.afterAttach = menuCfg.afterAttach;
			}
			if(menuCfg.beforeShow) {
				this.beforeShow = menuCfg.beforeShow;
			}
			if(menuCfg.afterShow){
				this.afterShow = menuCfg.afterShow;
			}
			if(menuCfg.delegateEvents){
				this.delegateEvents = menuCfg.delegateEvents;
			}
		},
		delegateEventsOnMenuItems : function(){
			if(this.delegateEvents){
				var delegateEvent = this.delegateEvents;
				this.getNode().delegate(delegateEvent.type, 
										delegateEvent.handler(), delegateEvent.selector || 'li', 
										delegateEvent.context || this);
			}
		},
		fireAllMenuItemBeforeShow: function (){
			
			var i;
			for(i in this.child){
				if(this.child[i].beforeShow !== undefined){
					this.child[i].beforeShow.fn.apply(this.child[i].beforeShow.context || this.child[i], this.child[i].beforeShow.args || [this.child[i].getNode()]);
				}
			}
		},
		fireAllMenuItemAfterShow: function (){
			
			var i;
			for(i in this.child){
				if(this.child[i].afterShow !== undefined){
					this.child[i].afterShow.fn.apply(this.child[i].afterShow.context || this.child[i], this.child[i].afterShow.args || [this.child[i].getNode()]);
				}
			}
		},
		fireMenuAfterShow : function(){
			this.afterShow.fn.apply(this.afterShow.context || this, this.afterShow.args || [this.getNode()]);
		},
		getItem : function(label){
			return this.child[label];
		},
		show     : function(args){
			if(this.beforeShow){
				this.beforeShow.fn.apply(this.beforeShow.context ||this, (this.beforeShow.args || [this.getNode()]));
			}
			this.fireAllMenuItemBeforeShow();
			
			this._show();
			
			if(this.afterShow){
				this.afterShow.fn.apply(this.afterShow.context ||this, (this.afterShow.args || [this.getNode()]));
			}
			this.fireAllMenuItemAfterShow();

		},
		_show    : function(){
			
			this.container.removeClass('hide');
			this.checkRegion();
		},
		hide     : function(){
			this.container.addClass('hide');
		},
		getNode  : function(){
			return this.container;
		},
		checkRegion : function(){
			
			if(!Y.DOM.inViewportRegion(this.container.dom(), true)){
				this.container.setStyle('top', this.newTop);
				if (!Y.DOM.inViewportRegion(this.container.dom(), true)){
					this.container.setStyle('top', this.normalTop);
				}else {
				}
			}else {
				
				this.container.setStyle('top', this.normalTop);
				if (!Y.DOM.inViewportRegion(this.container.dom(), true)){
					this.container.setStyle('top', this.newTop);
				}else {
				}
			}
		}
    };
	
	/*
	 * Static methods
	 */
	SNAPPI.Menu.listener = {};
	SNAPPI.Menu._activate_blurListener =  function(e, parentNode, target){
		var detach = Y.one('#container').on(
				'blurSubmenu', 
				function(e, p, t) {
					var blurred = SNAPPI.Menu.blurHandler(e, p, t);
					if (blurred && detach && detach.detach){
						detach.detach(); // detach listener when parentNode is blurred
					}
				}, 
				this, parentNode, target);
	};
	SNAPPI.Menu.plugInBlur = function(parentNode, target, delegateSelector){
		delegateSelector = delegateSelector || null;
		if (delegateSelector) {
			var delegateName = parentNode.get('id')+'-'+delegateSelector.replace(/\W*/, '-')+'-blur';
			if (!SNAPPI.Menu.listener[delegateName]) {
				SNAPPI.Menu.listener[delegateName] = parentNode.delegate(
						'mousedown', 
						function(e, target) {
							var delegatedParentNode = e.currentTarget;
							SNAPPI.Menu._activate_blurListener(e, delegatedParentNode, target);
						}, 
						delegateSelector, null, target );
			}
		} else {
			parentNode.addClass('delegate-blur');
			if (!SNAPPI.Menu.listener['delegate-blur']) {
				SNAPPI.Menu.listener['delegate-blur'] = Y.one('#container').delegate(
						'mousedown', 
						function(e, target) {
							var delegatedParentNode = e.currentTarget;
							SNAPPI.Menu._activate_blurListener(e, delegatedParentNode, target);
						}, 
						'.delegate-blur', null, target);
			}
		}
	},
	
	
	SNAPPI.Menu.blurHandler = function(e, p, t) {
		var target = e.target,
			pid = p.get('id'),
			check1,
			check2,
			check3;
		
    	var subMenuNode = t || p.dom().MenuItem.child.getNode();
		// check 1: check if user click the node that is in the submenu
		check1 = subMenuNode.contains(target);
		// check 2: check if user click the parent node which would trigger the submenu
		check2 = target.get('id') == pid;
		// check 3: check if user click on dialog box
		check3 = false;
		try {
			if(SNAPPI.dialog.node.contains(target)){
				check3 = true;
			}
		}catch(e){
		}
		
		var check = check1 || check2 || check3;
		if(check){
			return false;	// did not blur subMenuNode
		}
		else {
			if(subMenuNode.hasClass('contextmenu') || subMenuNode.ancestor('ul.contextmenu')){
				SNAPPI.Menu.blockLeftClick(subMenuNode);
				SNAPPI.Menu.unbindOldAudition(subMenuNode);
			}
			subMenuNode.addClass('hide');
			return true;
		};
    };
    
    SNAPPI.Menu.blockLeftClick = function(menuNode){
		// if the menu-photoRollContext is on, which means we are about to blur this menu.
		// so we need to restart the listener on photoRoll to listen left clicks.
		// TODO: leave the restarting method here temporarily. need to reorganize this code.
		if(!menuNode.hasClass('hide')){
			try {
				// restart normal Gallery click listener
				menuNode.ancestor('section.gallery.photo').Gallery.listenClick(true); 
			} catch(e) {}
			// TODO: what if this is a GroupRoll or MemberRoll?
		}
	};
	
	SNAPPI.Menu.unbindOldAudition = function(menuNode){
		// unbind this.container from "old" audition  
		var old = menuNode.dom().audition;
		if (old && old.bindTo && Y.Lang.isArray(old.bindTo)) { 
		    old.bindTo.splice(old.bindTo.indexOf(menuNode), 1);
		}
	};
	
	Y.Event.define("blurSubmenu", {
        on: function (node, sub, notifier) {
            sub._evtGuid = Y.guid() + '|';
            node.on( sub._evtGuid + "click", function (e) {
                notifier.fire(e);
                var check;
            });
        }
    } );
	
})();