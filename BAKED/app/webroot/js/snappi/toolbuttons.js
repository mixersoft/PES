(function(){

	var Y = SNAPPI.Y;
	
	/**
	 * necessary parameters
	 * id: toolButton id
	 * title: toolButton title
	 * action: onclick event
	 * label: the label on the button
	 * 
	 * optional parameters
	 * className: if you need addtional class name
	 * 
	 */
	
	ToolButton = function (buttonCfg) {

		/*
		 * variables in constructor
		 * If you want to modfiy this class, just need to modify:
		 * toolButtonMarkup and buttonCfg
		 * 
		 * If you want to add more functions to toolbutton
		 * add functions in ToolButton.prototype.prototype
		 * 
		 */
		
		this.toolButtonMarkup = "<a id='{id}' class='button' action='{action}' title='{tip}'>{label}</a>";
		
		this.buttonCfg = Y.merge(
			{
				id: "",
				title: "",
				action: "",
				label: "",
				// href: "#",
				className: ""
				
			}, buttonCfg
		
		);

		/*
		 * constructor method
		 */
		this.btnNode = Y.Node.create(Y.substitute(this.toolButtonMarkup, this.buttonCfg));
		// alert(this.buttonCfg.href);
		if(this.buttonCfg.href != undefined){
			this.btnNode.set('href', this.buttonCfg.href);
		}
		
	};
	
	/*
	 * private functions
	 */
	ToolButton.prototype = {	
		
		/*
		 * newFunction : function(args){
		 * 	   // codes
		 * }
		 * 
		 */
			
		addDropIcon: function(){
			dropMarkup = "&nbsp;▼";
			this.btnNode.append(dropMarkup);
		},
	
		disabled: function() {
			this.btnNode.setAttribute("disabled", "disabled");
			this.btnNode.addClass('disabled');
		},
		
		enabled: function() {
			if(this.btnNode.hasClass('disabled')){
				this.btnNode.removeAttribute("disabled");
				this.btnNode.removeClass('disabled');
			}
		},
		
		onMouseEvent : function(event, func){
			
			this.btnNode.on(event, function(e){
				func.call();
			});
			
		},
		
		detachMouseEvent : function(){
			
		}
		
		
		
		
		
		
		
		
		
		
		
	
	};
})();