
<!-- Combo-handled YUI CSS files: -->
<link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/combo?2.7.0/build/assets/skins/sam/skin.css">
<!-- Combo-handled YUI JS files: -->
<script type="text/javascript" src="http://yui.yahooapis.com/combo?2.7.0/build/utilities/utilities.js&2.7.0/build/container/container-min.js&2.7.0/build/layout/layout-min.js&2.7.0/build/tabview/tabview-min.js"></script>



<div id="center1" style="float:left"></div>	
<div id="left1">
    <script>
function init(){
	// Initialize the temporary Panel to display while waiting for external content to load
	
	
	var wait = new YAHOO.widget.Panel("wait", {
		width: "240px",
		fixedcenter: true,
		close: false,
		draggable: false,
		zindex: 4,
		modal: true,
		visible: false
	});
	
	wait.setHeader("Loading, please wait...");
	wait.setBody('<img src="http://l.yimg.com/a/i/us/per/gr/gp/rel_interstitial_loading.gif" />');
	wait.render(document.body);
	
	// Define the callback object for Connection Manager that will set the body of our content area when the content has loaded
	
	var content = document.getElementById("center1");
	
	var callback = {
		success: function(o){
			content.innerHTML = o.responseText;
			content.style.visibility = "visible";
			wait.hide();
		},
		failure: function(o){
			content.innerHTML = o.responseText;
			content.style.visibility = "visible";
			content.innerHTML = "CONNECTION FAILED!";
			wait.hide();
		}
	}
	
	// Show the Panel
	wait.show();
	
	// Connect to our data source and load the data
	var conn = YAHOO.util.Connect.asyncRequest("GET", "/gallery/data", callback);
}
YAHOO.util.Event.on("panelbutton", "click", init);
</script>
<button id="panelbutton">Get Photos</button>
</div>



<script>
(function() {
    var Dom = YAHOO.util.Dom,
        Event = YAHOO.util.Event;

    Event.onDOMReady(function() {
        var layout = new YAHOO.widget.Layout({
            units: [
                { position: 'top',  height: 50, body: 'header',  gutter: '5px', collapse: true, resize: true },
                { position: 'bottom', height: 100, resize: true, body: 'footer', gutter: '5px', collapse: true },
                { position: 'left', header: 'Left', width: 200, resize: true, body: 'left1', gutter: '5px', collapse: true, close: true, collapseSize: 50, scroll: true, animate: true },
                { position: 'center', body: 'center1' , scroll: true}
            ]
        });
        layout.on('render', function() {
            layout.getUnitByPosition('left').on('close', function() {
                closeLeft();
            });
        });
        layout.render();
        Event.on('tLeft', 'click', function(ev) {
            Event.stopEvent(ev);
            layout.getUnitByPosition('left').toggle();
        });
        Event.on('tRight', 'click', function(ev) {
            Event.stopEvent(ev);
            layout.getUnitByPosition('right').toggle();
        });
        Event.on('padRight', 'click', function(ev) {
            Event.stopEvent(ev);
            var pad = prompt('CSS gutter to apply: ("2px" or "2px 4px" or any combination of the 4 sides)', layout.getUnitByPosition('right').get('gutter'));
            layout.getUnitByPosition('right').set('gutter', pad);
        });
        var closeLeft = function() {
            var a = document.createElement('a');
            a.href = '#';
            a.innerHTML = 'Add Left Unit';
            Dom.get('closeLeft').parentNode.appendChild(a);

            Dom.setStyle('tLeft', 'display', 'none');
            Dom.setStyle('closeLeft', 'display', 'none');
            Event.on(a, 'click', function(ev) {
                Event.stopEvent(ev);
                Dom.setStyle('tLeft', 'display', 'inline');
                Dom.setStyle('closeLeft', 'display', 'inline');
                a.parentNode.removeChild(a);
                layout.addUnit(layout.get('units')[3]);
                layout.getUnitByPosition('left').on('close', function() {
                    closeLeft();
                });
            });
        };
        Event.on('closeLeft', 'click', function(ev) {
            Event.stopEvent(ev);
            layout.getUnitByPosition('left').close();
        });
    });


})();
</script>




