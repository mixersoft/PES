(function(){
    /*********************************************************************************
     * Class definitions
     */
    /*
     * TimelineFeed - singleton object
     * 		get json from remote server
     * 		group results by period/timeslot
     */
    var TimelineFeed = function(cfg){
        this.baseurl = "http://{jsonHost}/snappi/castingCall.json?page=1&woid=15";
        this.qs = "&perpage=100&tags=venice";
        
        this.baseurl = "http://git:88/my/photos/.json";
		this.baseurl = "castingCall.json";
        this.qs = '';
        this.auditionsSH = null; // parsed json response
        this.init = function(cfg){
            var Y = SNAPPI.Y;
            var _cfg = {
                jsonHost: SNAPPI.host.active.json
            };
            _cfg = Y.merge(_cfg, cfg);
            this.baseurl = Y.substitute(this.baseurl, _cfg);
        };
        
        this.getJsonData = function(qs, fnContinue){
            var Y = SNAPPI.Y;
            if (qs) 
                this.qs = qs;
            
            
            //Configure the cross-domain protocol:
//            var xdrConfig = {
//                id: 'flash', //We'll reference this id in the xdr configuration of our transaction.
//                yid: Y.id, //The yid provides a link from the Flash-based XDR engine
//                //and the YUI instance.
//                src: 'js/io.swf' //Relative path to the .swf file from the current page.
//            };
//            Y.io.transport(xdrConfig);
            
            //Event handler called when the transaction begins:
            var handleStart = function(id, a){
                var Y = SNAPPI.Y;
                Y.log("io:start firing.", "info", "example");
//                output.set("innerHTML", "<li>Loading auditions via json feed</li>");
            };
            
            //Event handler for the success event -- use this handler to write the fetched
            //RSS items to the page.
            var handleSuccess = function(id, o, a){
                var Y = SNAPPI.Y;
                //We use JSON.parse to sanitize the JSON (as opposed to simply eval'ing
                //it into the page):
                var responseText = Y.JSON.parse(o.responseText);
                
                if (responseText) {
                    SNAPPI.timelineFeed.getAuditions(responseText.castingCall);
                    if (Y.Lang.isFunction(fnContinue)) 
                        fnContinue();
                }
                else {
                    var s = "<li>The JSON feed did not return any items.</li>";
                }
            };
            
            //In the event that the HTTP status returned is > 399, a
            //failure is reported and this function is called:
            var handleFailure = function(id, o, a){
                var Y = SNAPPI.Y;
                Y.log("ERROR " + id + " " + a, "info", "example");
            };
            
            //With all the aparatus in place, we can now configure our
            //io call.
            var xdfCfg = {
                method: "GET",
//                xdr: {
//                    use: 'flash'
//                },
                on: {
                    start: handleStart,
                    success: handleSuccess,
                    failure: handleFailure
                },
				context: this
            };
            
            //Wire the buttton to a click handler to fire our request each
            //time the button is clicked:
            var getFeed = function(url){
                var Y = SNAPPI.Y;
                var obj = Y.io(url, xdfCfg);
            };
            
            //add the clickHandler as soon as the xdr Flash module has
            //loaded:
//            Y.on('io:xdrReady', function(){
//                var Y = SNAPPI.Y;
//                getFeed(this.baseurl + this.qs);
//                
//            }, this);
			var obj = Y.io(this.baseurl + this.qs, xdfCfg);
            
        };
        
        
        this.getAuditions = function(response){
            var parsedResponse = SNAPPI.AuditionParser_Snappi.parse(response);
            SNAPPI.auditions = response.CastingCall.Auditions.Audition;
            var sh = new SNAPPI.SortedHash();
            for (var i in parsedResponse.results) {
                sh.add(parsedResponse.results[i]);
            }
            this.auditionsSH = sh;
            return sh;
        };
        
        this.getTimelineData = function(cfg){
            var Y = SNAPPI.Y;
            var _cfg = {
                minRating: 1,
                period: 15 // timespan duration, in minutes
            };
            _cfg = Y.merge(_cfg, cfg);
            var tsdata = SNAPPI.timeline.mapToTimeslot(this.auditionsSH, _cfg);
            return tsdata;
        };
        
        this.init(cfg);
    };
	
	
    SNAPPI.timelineFeed = new TimelineFeed();
        
})();