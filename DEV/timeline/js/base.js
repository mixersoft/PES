/*
 * SNAPPI utils module, imported from other code.
 */
(function(){
    /*********************************************************************************
     * Globals
     */
    /*
     * init *GLOBAL* SNAPPI as root for namespace
     */
    if (typeof SNAPPI == "undefined" || !SNAPPI) {
        SNAPPI = {
            id: "SNAPPI Namespace"
        };
    }
    
    /*
     * define namespace method,
     * 		same as YUI YAHOO.namespace
     */
    SNAPPI.namespace = function(){
        var a = arguments, o = null, i, j, d;
        for (i = 0; i < a.length; i = i + 1) {
            d = a[i].split(".");
            o = window;
            for (j = 0; j < d.length; j = j + 1) {
                o[d[j]] = o[d[j]] || {};
                o = o[d[j]];
            }
        }
        return o;
    };
    
    
    
    SNAPPI.yuiConfig = { // GLOBAL
        base: "http://yui.yahooapis.com/combo?3.1.1/build/",
        timeout: 10000,
        loadOptional: false,
        combine: true,
        filter: "MIN",
        allowRollup: true,
        insertBefore: 'css-start'
        //				filter: "DEBUG",
    };
    
    
    
    /*
     * Sort utils from js/utils.js
     */
    var compare = {
        Alpha: function(a, b, cfg){
            var sA, sB;
            sA = a[cfg.property].toLowerCase();
            sB = b[cfg.property].toLowerCase();
            return (sA > sB ? 1 : sA < sB ? -1 : 0) * cfg.invert;
        },
        Numeric: function(a, b, cfg){
            var nA, nB, result;
            nA = a[cfg.property];
            nB = b[cfg.property];
            result = nA - nB;
            if (isNaN(result)) {
                if (parseInt(nA)) 
                    return 1 * cfg.invert;
                if (parseInt(nB)) 
                    return -1 * cfg.invert;
                return 0;
            }
            else 
                return result * cfg.invert;
        },
        AlphaPrefix: function(a, b, cfg){
            var sA, sB;
            sA = a[cfg.property].toLowerCase();
            sB = b[cfg.property].toLowerCase();
            var rA, rB;
            rA = regex.StringDelimNumber.exec(sA) || [null, sA, 0];
            rB = regex.StringDelimNumber.exec(sB) || [null, sB, 0];
            return (rA[1] > rB[1] ? 1 : rA[1] < rB[1] ? -1 : 0) * cfg.invert;
        },
        NumericSuffix: function(a, b, cfg){
            var rA, rB;
            rA = regex.StringDelimNumber.exec(a[cfg.property]) || [null, null, 0];
            rB = regex.StringDelimNumber.exec(b[cfg.property]) || [null, null, 0];
            return (rA[2] - rB[2]) * cfg.invert;
        },
        Time: function(a, b, cfg){
            // "2008-06-14 10:11:10", use Date.parse(d.replace(/-/g,'/'))
            var dA = 0, dB = 0;
            if (a[cfg.property]) 
                dA = Date.parse(a[cfg.property].replace(/-/g, '/'));
            if (b[cfg.property]) 
                dB = Date.parse(b[cfg.property].replace(/-/g, '/'));
            return (dA > dB ? 1 : dA < dB ? -1 : 0) * cfg.invert;
        },
        HashedAlpha: function(a, b, cfg){
            var sA, sB;
            sA = a.hashcode().toLowerCase();
            sB = b.hashcode().toLowerCase();
            return (sA > sB ? 1 : sA < sB ? -1 : 0) * cfg.invert;
        },
        HashedNumeric: function(a, b, cfg){
            var nA, nB;
            nA = a.hashcode();
            nB = b.hashcode();
            result = nA - nB;
            if (isNaN(result)) {
                if (parseInt(nA)) 
                    return 1 * cfg.invert;
                if (parseInt(nB)) 
                    return -1 * cfg.invert;
                return 0;
            }
            else 
                return result * cfg.invert;
        },
        HashedAlphaPrefix: function(a, b, cfg){
            var sA, sB;
            sA = a.hashcode().toLowerCase();
            sB = b.hashcode().toLowerCase();
            var rA, rB;
            rA = regex.StringDelimNumber.exec(sA) || [null, sA, 0];
            rB = regex.StringDelimNumber.exec(sB) || [null, sB, 0];
            return (rA[1] > rB[1] ? 1 : rA[1] < rB[1] ? -1 : 0) * cfg.invert;
        },
        HashedNumericSuffix: function(a, b, cfg){
            var rA, rB;
            rA = regex.StringDelimNumber.exec(a.hashcode()) || [null, null, 0];
            rB = regex.StringDelimNumber.exec(b.hashcode()) || [null, null, 0];
            return (rA[2] - rB[2]) * cfg.invert;
        },
        makeSortFn: function(cfg){
            if (cfg.constructor != Array) 
                cfg = [cfg];
            function arraySortFn(a, b){
                var retval = 0, thisCfg = {};
                for (var i = 0; i < cfg.length; i++) {
                    // WARNING, THIS IS A SHALLOW COPY
                    thisCfg = SNAPPI.util.mergeObjCopy(cfg[i], thisCfg);
                    thisCfg.invert = ((thisCfg.order === 'desc') ? -1 : 1);
                    retval = thisCfg.fn(a, b, thisCfg);
                    if (retval) 
                        break;
                }
                return retval;
            }
            return arraySortFn;
        }
    };
    
    /*
     * Sort configuration object
     */
    SNAPPI.Stack = {};
    SNAPPI.Stack.sort = {
        RATING: {
            label: 'Rating',
            fn: compare.Numeric,
            property: 'rating',
            order: 'desc'
        },
        TIME: {
            label: 'Date Taken',
            fn: compare.Time,
            property: 'exif_DateTimeOriginal',
            order: 'asc'
        },
        NAME: {
            label: 'Name',
            fn: compare.HashedAlpha,
            order: 'asc'
        }
    };
    
    
    /*
     * Snappi Audition Parser
     * 		parse raw json data to extract key properties
     */
    SNAPPI.AuditionParser_Snappi = {
        //        uri: '../../snappi/castingCall.xml?',
        uri: '../../snappi/castingCall.json?',
        xmlns: 'sn',
        rootNode: 'CastingCall',
        qsOverride: { //                perpage: '100',
}        ,
        parse: function(rootNode){
            //            _xml2JsTidy(rootNode);
            var p, audition, arrAuditions, baseurl, node, results = [];
            if (rootNode.CastingCall && rootNode.CastingCall.Auditions && rootNode.CastingCall.Auditions.Audition) {
                arrAuditions = rootNode.CastingCall.Auditions.Audition;
                baseurl = rootNode.CastingCall.Auditions.Baseurl;
                // organize catalog by number of photos
                for (p in arrAuditions) {
                    node = {};
                    audition = arrAuditions[p];
                    // extract additional properties from array
                    node.hashcode = function(){
                        return this.id;
                    }
                    node.id = audition.id;
                    node.src = this.getImgSrcBySize(audition.Photo.Img.Src.Src, 'tn');
                    node.urlbase = baseurl || audition.Photo.Img.Src.Baseurl || '';
                    node['Audition'] = audition;
                    node.tags = audition.Tags && audition.Tags.value || null;
//                    node.albumName = this.getAlbumName(node);
                    node.label = audition.Photo.Caption;					
                    node.rating = parseInt(audition.Photo.Fix.Rating);
                    node.exif_DateTimeOriginal = audition.Photo.DateTaken;
                    results.push(node);
                }

            }
            return {
                results: results
            };
        },
        getAlbumName: function getAlbumName(o){
            var parts, name;
            parts = o.src.split('/');
            parts.pop(); // discard filename
            if ((name = parts[parts.length - 1]) == '.thumbs') 
                parts.pop();
            if (o.urlbase) {
                return parts.join('/');
            }
            else {
                return parts[parts.length - 1];
            }
        },
        getImgSrcBySize: function(src, size){
            size = size || 'tn';
            var parts = SNAPPI.util.parseSrcString(src);
            if (size && !parts.dirname.match(/.thumbs\/$/)) 
                parts.dirname += '.thumbs/';
            return parts.dirname + (size ? size + '~' : '') + parts.filename + (parts.crop ? '~' + parts.crop : '');
        }
    };
})();





/*
 * Timeline related stuff
 */
(function(){
    /*********************************************************************************
     * Class definitions
     */
    /*********************************************************************
     * 		SNAPPI SCRIPT LOADER
     */
    var snappiLoader = function(fnContinue){
        var Y = SNAPPI.Y;
        /*
         * config hosts
         */
        var host;
        if ((/snaphappi.com/.test(window.location.host))) {
            SNAPPI.host.active = SNAPPI.host.remote;
        }
        else {
            SNAPPI.host.active = SNAPPI.host.local;
        }
        host = SNAPPI.host.active;
        
        /*
         * load SNAPPI scripts
         */
        var useCombo = false;
        // for debugging
        var loadScriptUrls = [];
        
        //        // load scripts from windows.location.host
        //        var aScriptUrls = [];
        //        // for runtime. load main.js LAST
        //        if (useCombo) {
        //            // for production
        //            var comboBase = '/combo?baseurl=';
        //            aScriptUrls.unshift(comboBase);
        //            aScriptUrls = aScriptUrls.join('&');
        //        }
        //        else {
        //            // for debugging
        //            var host = window.location.host;
        //            var baseurl = 'http://' + host + '/js/';
        //            for (var i in aScriptUrls) {
        //                loadScriptUrls.push(baseurl + aScriptUrls[i]);
        //            }
        //        }
        
        // load scripts from remote host, i.e. gallery:88
        var aScriptUrls = ['js/util.js', 'js/timeline.js'];
        // for runtime. load main.js LAST
        if (useCombo) {
            // for production
            var comboBase = '/combo?baseurl=GALLERY';
            aScriptUrls.unshift(comboBase);
            aScriptUrls = aScriptUrls.join('&');
        }
        else {
            for (var i in aScriptUrls) {
                loadScriptUrls.push( aScriptUrls[i]);
            }
        }
        
        
        loadScriptUrls.push('js/timeline-feed.js');
        loadScriptUrls.push('js/timeline-chart.js');
        loadScriptUrls.push('js/sortedhash.js');
        loadScriptUrls.push('js/thumbnail3.js');
        loadScriptUrls.push('js/photo-roll.js');
        
        
        //        console.log(aScriptUrls);
        if (loadScriptUrls.length) {
            Y.Get.script(loadScriptUrls, {
                onSuccess: function(e){
                    /*
                     * TEST FOR DOJO LOAD
                     */
                    if (SNAPPI.dojo.loaded) {
                        if (Y.Lang.isFunction(fnContinue)) 
                            fnContinue();
                    }
                    else {
                        Y.later(500, this, fnContinue, true);
                    }
                },
                onFailure: function(e){
                    alert("ERROR: Snappi Javascript script load failed");
                }
            });
        }
        else 
            fnContinue();
        
    };
    /*
     * YUI3 closure,
     * 		- initialize SNAPPI.Y = Y;
     * 		- call SNAPPI.ini() on domeready
     */
    YUI(SNAPPI.yuiConfig).use('event', 'node', 'io', "substitute", "json-parse", "yui2-container", function(Y){
        /*
         * Helper Functions
         */
        Y.Node.prototype.dom = function(){
            return Y.Node.getDOMNode(this);
        };
        Y.Node.prototype.ynode = function(){
            return this;
        };
        HTMLElement.prototype.dom = function(){
            return this;
        };
        HTMLElement.prototype.ynode = function(){
            return Y.one(this);
        };
        // make global
        SNAPPI.Y = Y;
        YAHOO = Y.YUI2; // YUI2 deprecate when possible
        /*
         * configure remote host for staging
         */
        // public
        SNAPPI.host = {
            remote: {
                gallery: 'aws.snaphappi.com',
                json: 'aws.snaphappi.com',
                baked: 'aws.snaphappi.com'
            },
            local: {
                gallery: 'git:88',
                json: 'git:88',
                baked: 'git:88'
            }
        };
        
        
        // local dev
        //        SNAPPI.jsHost = 'gallery:88';
        //        SNAPPI.jsonHost = 'gallery:88';
        
        /*
         *
         */
        snappiLoader(SNAPPI.init);
    });
    
    
    
    
    /*************************************************************************************
     * 	MODULE Startup/Init
     */
    /*
     * Snappi Global Init
     * 		- after all YUI and Snappi scripts are loaded
     */
    SNAPPI.init = function(){
        var Y = SNAPPI.Y;
        //        var ajax = new SNAPPI.Ajax();
        SNAPPI.util.LoadingPanel.hide(); 	// deprecate once we fix js/util.js
        
        
        var success = function(){
        
            /*
             * duration sets the duration of a timeslot/period (i.e. "zoom") for the timeline data.
             * photo timestamps are grouped by timeslot for charting
             */
            var cfg = {
                period: 15 // timeslot duration in mins.
            };
            var result = SNAPPI.timelineFeed.getTimelineData(cfg);
            
            
            var chart = new SNAPPI.TimelineChart();
            chart.init();
            renderTimeline(chart, SNAPPI.timelineFeed);
            zoom.init({
                chart: chart,
                feed: SNAPPI.timelineFeed
            });
            
            /*******************************************
             * debug stuff
             */
            /*
             * get timeline dataset
             */
            var s = '<li><b>timeslot</b>: [0]=id, [1]=timeslot index, [2]=exif_dateTaken, [3]=rating, [4]=src </li>';
            var tsData = result.rows;
            for (var i = 0; i < 5; i++) {
                s += '<li><b>' + result.legend[tsData[i][1]] + "</b>: " + tsData[i][0] + ',' + tsData[i][1] + ',' + tsData[i][2] + ',' + tsData[i][3] + ',' + tsData[i][4] + '</li>';
            }
            s += '<li>' + tsData.length + ' Total rows</li>';
            RESULT = result;
            Y.Node.get("#json-response ul").set("innerHTML", s);
            
            
            
        };
        
        /*
         * qs sets the json dataset by tag,
         * and sets the number of rows returned (perpagex5)
         */
        var qs = "";
        //		var qs = "&perpage=100&tags=paris; venice";
        
        /*
         * get raw timeline Json data from query
         * note: perpage returns max 5xperpage rows
         */
        SNAPPI.timelineFeed.getJsonData(qs, success);
    };
    
    var renderTimeline = function(chart, feed, cfg){
        var _cfg = {
            period: 15,
			labelFmt: 'MM'
        };
        cfg = cfg || {};
        _cfg = Y.merge(_cfg, cfg);
        var result = feed.getTimelineData(_cfg);
        /*
         * GUESS scale for chart
         */
        var span = result.range.max - result.range.min;
        chart.position.scaleX = Math.max(1, span / 40);
        
        chart.setDataset(result);
		chart.groupByTimeslot();
		
        chart.setYAxis();
        chart.setXAxis(_cfg);
        
        chart.plotSeries(1, 'blue');
        chart.plotSeries(2, 'green');
        chart.plotSeries(3, 'yellow');
        chart.plotSeries(4, 'orange');
        chart.plotSeries(5, 'red');
        chart.plotVolume();
        chart.render();
        chart.renderVolumeMask();
        DOJOCHART = chart;
    };
    
    
    var zoom = new function(){
        var Y = SNAPPI.Y;
        
        this.listeners = [];
        this.node = null;
        this.chart = null;
        this.feed = null;
        
        this.init = function(cfg){
            var Y = SNAPPI.Y;
            this.chart = cfg.chart;
            this.feed = cfg.feed;
            var n = Y.one('#zoom');
            n.removeClass('hide');
            this.node = n;
            this.startListeners();
            
        };
        this.startListeners = function(){
            if (this.listeners.length) 
                return;
            var Y = SNAPPI.Y;
            var handle = this.node.delegate('click', this.handleZoom, 'a', this);
            this.listeners.push(handle);
        };
        this.handleZoom = function(e){
            var target = e.target;
            var period = target.getAttribute('period');
            var period = period.split('=');
            
            if (this.chart && this.feed) {
                renderTimeline(this.chart, this.feed, {
					labelFmt: period[0],
                    period: period[1]
                });
            }
            
            var check;
        };
    }
    
    
    
    
})();

