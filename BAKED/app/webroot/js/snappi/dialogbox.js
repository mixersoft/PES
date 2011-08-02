(function(){
	
	var Y = SNAPPI.Y;
	
	/*
	 * one built-in buttons OK_CANCEL
	 * if you doesn't need buttons, just leave them blank
	 */
	var defaultCfg = {
			BUTTONS_OK_CANCEL: [
			            		{
			            			text : 'OK',
			            			fn   : function() {
			            				alert("You clicked yes!");
			            			}
			            		},{
			            			text : 'Cancel',
			            			fn   : function() {
			            				SNAPPI.dialogbox.hide();
			            			}
			            		}	
			            	],
			DEFAULT_TITLE: 'title'
			
	    };
	
	defaultCfg.DEFAULT_CFG = {
		title : defaultCfg.DEFAULT_TITLE,
		buttons : defaultCfg.BUTTONS_OK_CANCEL
	};

	var className = {
		
		closeBtn : 'close',
		header : 'header',
		body : 'body',
		submitBtn: 'button',
		dialog : 'snappi-dialog',
		selected : 'selected'
		
	};
	
	var dialogId = 'snappi-dialog';
	
	var markup = {	
			
		headerMarkup : "<div class='" + className.header + "'><h3>{title}</h3>" +
						"<div class='" + className.closeBtn + "'><b>×</b></div></div>",
		contentMarkup: "<div class='" + className.body + "'><ul>{body}</ul></div>",
  		buttonMarkup : "<a class='" + className.submitBtn + "'>{text}</a>",
  		frameMarkup  : "<div id='" + dialogId + "' class='snappi-dialog-container hide'>" +
  						"<div class='" + className.dialog + "' " +
		  				"'></div></div>",
		item     : "<li>{body}</li>",
		ul       : "<ul class='snappi-dialog-ul'></ul>"
	};

	var _buildDialogbox = function(cfg){
		
		var nodes = new Array();

		var dialogHeaderNode = Y.Node.create(Y.substitute(markup.headerMarkup, cfg));
		var dialogContentNode = Y.Node.create(Y.substitute(markup.contentMarkup, cfg));
		var dialogButtonNode = Y.Node.create(Y.substitute(markup.buttonMarkup, cfg));
	    var dialogNode = Y.Node.create(markup.frameMarkup);
	    
	    dialogContentNode.append(dialogButtonNode);
	    dialogNode.one('.' + className.dialog).append(dialogHeaderNode);
	    dialogNode.one('.' + className.dialog).append(dialogContentNode);
	    
	    var dialog = Y.one('body').appendChild(dialogNode);

	    this.ulParent = dialog.one('.' + className.body);
		this.submitBtn = dialog.one('.' + className.submitBtn);
		this.headerText = dialog.one('.' + className.header + ' h3');
		
		var node = dialog.one('.' + className.dialog);
		node.plug(Y.Plugin.Drag);
		node.dd.plug(Y.Plugin.DDConstrained, {
			constrain2node : '#container'
		}); 
		node.dd.addHandle('.' + className.header); 
		
		return dialog;
		
	};
		
	var Dialogbox = function() {
		this.ulParent = null;
		this.node = null;
		this.submitBtn = null;
		this.cfg = defaultCfg;
		
		this.init = function(cfg) {

			if(!Y.one('#' + dialogId)){
				this.node = _buildDialogbox.call(this, cfg);
			}

			this.node.dom().Dialogbox = this; 
			this.startListeners();

			if(cfg.onload){
				cfg.onload.fn.apply((cfg.onload.context || this), cfg.onload.args);
			}
		};
	};
	
	Dialogbox.prototype = {
			
		render : function(cfg){
			var check = cfg;
			if(cfg.body || cfg.body == null){
				this.replaceBody(cfg.body);
			}
			if(cfg.title){
				this.replaceTitle(cfg.title);
			}
			if(cfg.buttons){
				this.replaceButton(cfg.buttons);
			}else {
				this.ulParent.all('.' + className.submitBtn).addClass('hide');
			}
			
			this.listenToLI();
			this.show();
			
		},
		
		/*
		 * cfg.title
		 */
		replaceTitle : function(title){
			
			this.headerText.set('innerHTML', title);
			
		},
		
		/*
		 * cfg.button
		 */
		replaceButton : function(buttons){
			
			var list = this.ulParent.all('.' + className.submitBtn),
				button;
			
			this.stopListeningSubmit();
			// remove all buttons
			if(list !== undefined){
				list.each(function(n){
					n.remove();
				});
			}
			
			if(Y.Lang.isArray(buttons)){
				var buttonList = [];
				for (i in buttons){
					button = Y.Node.create(Y.substitute(markup.buttonMarkup, buttons[i]));
					this.ulParent.append(button);
					button.dom().dialogBtnAction = {fn : buttons[i].fn};
				}
			}else {
				button = Y.Node.create(Y.substitute(markup.buttonMarkup, buttons));
				this.ulParent.append(button);
			}
			this.listenToSubmitBtn();
			
		},

		/*
		 * cfg.body
		 */
		replaceBody : function(node){
			
			if(node == null){
				this.ulParent.one('ul').remove();
				this.ulParent.append(markup.ul);
			}else {
				this.stopListeningLI();
				this.ulParent.one('ul').remove();
				
				var nodes = this.createBody(node);
				this.ulParent.append(markup.ul);
				for (i in nodes) {
					this.ulParent.one('ul').append(nodes[i]);
				};
			}
		},
		
		createBody : function(node){
			var check = node;
			var i,
				n,
				nodes = [];
			
			if(Y.Lang.isArray(node)){
				for (i in node){
					nodes.push(node[i]);
				}
			}else {
				node.addClass('selected');
				nodes.push(node);
			}

			return nodes;
		},
		
		show : function(){
			this.node.removeClass('hide');
		},
		
		hide : function(){
			this.node.addClass('hide');
		},
		
		listenToLI : function(){

			this.ulParent.one('ul').delegate('LI|click', function(e){
				this.ulParent.all('li').removeClass(className.selected);
				e.target.addClass(className.selected);
			}, 'li', this);
		},
		
		stopListeningLI : function(){
			Y.detach('LI|*');
		},
		
		listenToCloseBtn : function(){
			var detach = this.node.one('.' + className.closeBtn).on('click', function(e){
				this.hide();
			}, this);
			return detach;
		},
		
		listenToSubmitBtn : function(){
			if (!this.listeners.submit) {
				var detach = this.ulParent.delegate('click', function(e){
					var target = e.target.dom().dialogBtnAction,
						fn = target.fn,
						context = target.context;
					fn.call((context || {}), fn);
	
				}, 'a.' + className.submitBtn);
				this.listeners.submit = detach;
			}
			return detach;
		},

		startListeners : function(){
			this.listeners = this.listeners || {};
			if (!this.listeners.close) this.listeners.close = this.listenToCloseBtn();
			this.listenToSubmitBtn();
		},
		
		stopListeningSubmit : function(){
			try {
				this.listeners.submit.detach();
				this.listeners.submit = null;
			} catch (e) {}
		},

		callback : {}
		
	};
	
	/*
	 * make global
	 */
	SNAPPI.dialogbox = new Dialogbox();
	
})();




