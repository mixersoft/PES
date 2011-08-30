/*
 * SNAPPI util module for javascript debugging
 */
(function(){
	SNAPPI.namespace('SNAPPI.DEBUG');
	var Debug = function(){
		SNAPPI.DEBUG_MODE = 1;
	};
	Debug.prototype = {
	    /*************************************************************************
	     * showNodes - allows insepction of Y.Node properties from DOM object in Firebug
	     */			
		showNodes: function(selectors) {
			var Y = SNAPPI.Y;
			var selectors = selectors || ['#content div, .FigureBox'];
			if (Y.Lang.isString(selectors)) selectors = [selectors];
	        
			if (SNAPPI.DEBUG_MODE == 0) return;
	        
	        for (var selector in selectors) {
		        Y.all(selector).each(function (n,i,l) {
		        	if (n.Rating || n.audition || n.Gallery || n.Lightbox || n.Thumbnail) {
		        		n.dom().yNode = n.ynode();
		        	}
		        });
	        }
		}
	};
	

    SNAPPI.debug = new Debug();
})();