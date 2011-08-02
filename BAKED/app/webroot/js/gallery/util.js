/**
 *
 * Copyright (c) 2009, Snaphappi.com. All rights reserved.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the Affero GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * Affero GNU General Public License for more details.
 *
 * You should have received a copy of the Affero GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * @author Michael Lin, info@snaphappi.com
 *
 * configure SNAPPI.util namespace as
 * use singleton pattern
 */
(function(){
    /*
     * CONSTANTS, config, etc.
     */
    SNAPPI.namespace("SNAPPI.cfg");
    var cfg = {};
    cfg.uri = {
        snappi: {
            update: '/snappi/update_asset'
        },
        flickr: {
            update: '/flickr/update_asset'
        }
    };
    cfg.perpage = 50;
    SNAPPI.cfg = cfg;
    
})();



/*
 * util loads before all YAHOO yui libs have been loaded
 */
(function(){
    if (!SNAPPI.util) {
    
        var Y = SNAPPI.Y;
        
        /*
         * override String
         */
        String.prototype.trim = function(){
            //            return this.replace(/^\s*/, "").replace(/\s*$/, "");
            return this.replace(/^\s+|\s+$/g, '');
        };
        
        var Util = function(){
        };
        Util.prototype = {
            /*
             * SNAPPI util functions
             */
            /*
             * outputs a label with count in parens "cars (45)"
             */
            labelWithCount: function(sLabel, n, t){
                var regexp = /\([\d+\/]\)$/;
                if (t) 
                    n = n + '/' + t;
                if (sLabel.match(regexp)) {
                    sLabel = sLabel.replace(regexp, "(" + n + ")");
                }
                else 
                    sLabel += " (" + n + ")";
                return sLabel;
            },
            /*
             * resize changes the size prefix for an image src
             * using Snaphappi format
             */
            getImgSrcBySize: function(src, size){
                size = size || 'tn';
                var parts = SNAPPI.util.parseSrcString(src);
                if (size && !parts.dirname.match(/.thumbs\/$/)) 
                    parts.dirname += '.thumbs/';
                return parts.dirname + (size ? size + '~' : '') + parts.filename + (parts.crop ? '~' + parts.crop : '');
            },
            parseSrcString: function(src){
                var i = src.lastIndexOf('/');
                var name = {
                    dirname: '',
                    size: '',
                    filename: '',
                    crop: ''
                };
                name.dirname = src.substring(0, i + 1);
                var parts = src.substring(i + 1).split('~');
                switch (parts.length) {
                    case 3:
                        name.size = parts[0];
                        name.filename = parts[1];
                        name.crop = parts[2];
                        break;
                    case 2:
                        if (parts[0].length == 2) {
                            name.size = parts[0];
                            name.filename = parts[1];
                        }
                        else {
                            name.filename = parts[0];
                            name.crop = parts[1];
                        }
                        break;
                    case 1:
                        name.filename = parts[0];
                        break;
                    default:
                        name.filename = src.substring(i + 1);
                        break;
                }
                return name;
            },
            getClass: function(object){
                //                var test = Object.prototype.toString.call(object);
                return Object.prototype.toString.call(object).slice(8, -1);
            },
            hash: function(key){
                if (key && key.hashcode && this.getClass(key.hashcode) == 'Function') {
                    return key.hashcode();
                }
                else 
                    return key ? key.toString() : null;
            },
            // deprecate, use Y.merge
            mergeObjCopy: function(cfg, defaultCfg){
                return SNAPPI.util.mergeObj(SNAPPI.util.copyObj(cfg), defaultCfg);
            },
            // deprecate, use Y.merge
            mergeObj: function(cfg, defaultCfg){
                if (cfg === undefined || cfg === null) 
                    cfg = {};
                for (var p in defaultCfg) {
                    if (cfg[p] === undefined) 
                        cfg[p] = defaultCfg[p];
                }
                return cfg;
            },
            // deprecate, use Y.merge
            copyObj: function(obj){
                if (obj == null || typeof(obj) != 'object') 
                    return obj;
                if (obj.constructor == Array) {
                    var temp = [];
                    for (var i = 0; i < obj.length; i++) {
                        // special code for dataElement.boundTo =[]
                        if (typeof(obj[i]) == 'object' && !(obj[i] instanceof HTMLElement)) 
                            temp.push(SNAPPI.util.copyObj(obj[i]));
                        else 
                            temp.push(obj[i]);
                    }
                    return temp;
                }
                else 
                    if (obj.constructor == RegExp) {
                        return obj;
                    }
                var temp = {};
                for (var key in obj) 
                    temp[key] = SNAPPI.util.copyObj(obj[key]);
                return temp;
            },
            deactivateField: function(o, str){
                o = o.dom();
                if (str) {
                    o.default_text = str;
                }
                else 
                    if (o.default_text === undefined) {
                        o.default_text = o.value;
                    }
                o.value = o.default_text;
                o.original_color = o.style.color;
                o.style.color = 'gray';
                o.blur();
            },
            activateField: function(o){
                o = o.dom();
                if (o.default_text && o.value == o.default_text) 
                    o.value = '';
                o.style.color = o.original_color;
            },
            createTag: function(o){
                var str = o.value.replace(/;/g, '-');
                SNAPPI.util.deactivateField(o, 'add a tag');
                if (!str) {
                    return false;
                }
                
                var defaultCfg = {
                    name: str,
                    type: 'tags',
                    droppable: true,
                    refresh: true,
                    focus: false
                };
                SNAPPI.Tags.add(null, defaultCfg);
                return true;
            },
            hideNextSibling: function(o){
                var target = o.ynode().next();
                target.style.display = target.style.display === 'none' ? 'block' : 'none';
                var span = o.ynode().one('span');
                if (span) {
                    span.innerHTML = span.innerHTML == 'hide' ? 'show' : 'hide';
                };
                            },
            getFromQs: function(name){
                /*
                 * get a query param value by name from the current URL
                 */
                name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
                var regexS = "[\\?&]" + name + "=([^&#]*)";
                var regex = new RegExp(regexS);
                var results = regex.exec(window.location.href);
                if (results == null) 
                    return "";
                else 
                    return results[1];
            },
            parseExifDateTime: function(sDateTime){
                // valid formats = ["yyyy-MM-dd HH:mm:ss", "yyyy-MM-ddTHH:mm:ss"];
                var date, time, parts = sDateTime.split(' ');
                if (parts.length == 1) 
                    parts = sDateTime.split('T'); // iso1860 format
                var date = parts[0].split('-');
                var time = parts.length == 2 ? parts[1].split(":") : ['00', '00', '00'];
                var d = new Date(date[0], date[1], date[2], time[0], time[1], time[2], 0);
                return d;
            },
            formatUTCDateAsStringNoTZ: function(date){
                //                var lpad = function(n){
                //                    return n.toString().length == 1 ? '0' + n : n;
                //                }				
                //				return date.toDateString() + ' ' + date.getUTCHours() + ':' + lpad(date.getUTCMinutes());
                return date.toUTCString().replace('GMT', '');
                
            },
            // output JS date to string in local time zone
            formatDateAsISO: function(date){
                var lpad = function(n){
                    return n.toString().length == 1 ? '0' + n : n;
                };
                return date.getFullYear() + '-' + lpad(date.getMonth() + 1) + '-' + lpad(date.getDate()) + 'T' + lpad(date.getHours()) + ':' + lpad(date.getMinutes()) + ':' + lpad(date.getSeconds());
            },
            // output JS date to string in GMT time zone
            formatDateAsISO_UTC: function(date){
                var lpad = function(n){
                    return n.toString().length == 1 ? '0' + n : n;
                };
                return date.getUTCFullYear() + '-' + lpad(date.getUTCMonth() + 1) + '-' + lpad(date.getUTCDate()) + 'T' + lpad(date.getUTCHours()) + ':' + lpad(date.getUTCMinutes()) + ':' + lpad(date.getUTCSeconds());
            },
            /**
             * Parse datetime string into a Javascript Date, assumed to be UTC time by default
             *
             * NOTE: Exif dateOriginalTaken timestamps DO NOT HAVE TIMEZONE data attached.
             * assumed to be local time at time photo was captured.
             * for processing, assume local time zone is always UTC.
             *
             * @param string sDateTime e.g. "2009-07-31 11:45:00" or  "2009-07-31T11:45:00"
             * @param boolean utcTime DEFAULT = TRUE
             */
            parseExifDateTimeString: function(sDateTime, utcTime){
                utcTime = utcTime === undefined ? true : utcTime;
                // valid formats = ["yyyy-MM-dd HH:mm:ss", "yyyy-MM-ddTHH:mm:ss"];
                var date, time, parts = sDateTime.split(' ');
                if (parts.length == 1) 
                    parts = sDateTime.split('T'); // iso1860 format
                var date = parts[0].split('-');
                var time = parts.length == 2 ? parts[1].split(":") : ['00', '00', '00'];
                // javascript dates are created in local time
                var d = new Date(date[0], date[1] - 1, date[2], time[0], time[1], time[2], 0);
                // convert from local TZ to UTC
                if (utcTime) {
                    var utcOffset = d.getTimezoneOffset() * 60 * 1000;
                    d = new Date(d.getTime() - utcOffset);
                }
                return d;
            },
            getObjKeys: function(o){
                if (this.getClass(o) == 'Object') {
                    var a = [];
                    for (var p in o) {
                        if (o.hasOwnProperty(p)) {
                            a.push(p);
                        }
                    }
                    return a;
                }
                else 
                    return o;
            },
            joinObjKeys: function(o, separator){
                separator = separator || ',';
                if (this.getClass(o) == 'Array') {
                    return o.join(separator);
                }
                else {
                    var a = this.getObjKeys(o);
                    if (this.getClass(a) == 'Array') 
                        return a.join(separator);
                    else 
                        return o;
                }
            }, 
            isDOMVisible: function(n){
                return n.getComputedStyle('display') != 'none' && n.getComputedStyle('visibility') != 'hidden';
            }
        };
    }
    SNAPPI.util = new Util();
})();


/*
 * deprecate, replaced by util3.ImageLoader
 */
//
//(function(){
//    SNAPPI.namespace("SNAPPI.util");
//    
//    var Y = SNAPPI.Y;
//    
//    /*
//     * initialize delagated event listener ONE TIME ONLY
//     */
//    if (!SNAPPI.util.ImageLoader) {
//    
//        
//        
//        
//        SNAPPI.util.ImageLoader = new function(cfg){
//            /*
//             */
//            this.limit = (cfg && cfg.limit) ? cfg.limit : 500;
//            this._watchList = {};
//            this.onComplete = [];
//            this.load = function(arr, onComplete){
//                // set callback fcn
//                if (onComplete) 
//                    this.onComplete.push(onComplete);
//                
//                // load new imgs
//                if (arr.constructor == Array) {
//                    var img, i = 0, stop = (this.limit < arr.length ? this.limit : arr.length);
//                    for (var i = 0; i < stop; i++) {
//                        img = arr[i];
//                        if (!img || img.nodeName !== 'IMG' || img.naturalHeight) {
//                            // skip if img is already available, remove from array
//                            continue;
//                        }
//                        else {
//                            this.push(img);
//                        }
//                    }
//                }
//                this.finishIfLoaded();
//            };
//            this.push = function(imgEl){
//                this._watchList[imgEl.src] = 1;
//                if (!imgEl.ImageLoader) {
//                    imgEl.ImageLoader = this;
//                }
//                YAHOO.util.Event.addListener(imgEl, 'load', this.popCompleted, imgEl, this);
//            };
//            this.popCompleted = function(imgEl){
//                if (imgEl.currentTarget) 
//                    imgEl = imgEl.currentTarget;
//                delete this._watchList[imgEl.src];
//                delete imgEl.ImageLoader;
//                this.finishIfLoaded();
//            };
//            this.onLoad = function(e){
//                var imgEl = e.currentTarget;
//                this.popCompleted(imgEl);
//            };
//            this.finishIfLoaded = function(){
//                for (var p in this._watchList) {
//                    if (this._watchList[p]) 
//                        return; // more properties, so we are not done.
//                }
//                // no more properties means we are all done.
//                if (this.onComplete.length) {
//                    for (var c in this.onComplete) {
//                        this.onComplete[c]();
//                    }
//                    this.onComplete = [];
//                }
//                else 
//                    SNAPPI.util.LoadingPanel.hide();
//            };
//        };
//    }
//})();




///*
// * 
// * Sample Config for Sort
// * 
// *     var sampleCfg = [{
// *        fn: compare.Time,
// *        property: 'exif_DateTimeOriginal',
// *        order: 'desc',
// *    }, {
// *        fn: compare.Numeric,
// *        property: 'rating', 
// *        order: 'desc',
// *    }, {
// *        fn: compare.HashedAlpha,
// *        order: 'asc',
// *    }];
// * 
// */



(function(){
	if (SNAPPI.Sort) return;
    SNAPPI.namespace("SNAPPI.Sort");

    
    var regex = {
        /*
         * StringDelimNumber.exec(string) = [string, string prefix, last occurance of number]
         * with number delimited by ['(' ,'-', '_']
         */
        StringDelimNumber: /(.*)[\(_-]([\d]*).*$/,
        LastNumber: /([\d]*)$/
    };
    SNAPPI.Sort.regex = regex;
    
    
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
		// e.g. abc-11, efg-2, hij (33) etc., (see above)
        AlphaPrefix: function(a, b, cfg){
            var sA, sB;
            sA = a[cfg.property].toLowerCase();
            sB = b[cfg.property].toLowerCase();
            var rA, rB;
            rA = regex.StringDelimNumber.exec(sA) || [null, sA, 0];
            rB = regex.StringDelimNumber.exec(sB) || [null, sB, 0];
            return (rA[1] > rB[1] ? 1 : rA[1] < rB[1] ? -1 : 0) * cfg.invert;
        },
		// e.g. hig-2, abc-11, efg (33) etc., (see above)
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
			try {
	            sA = a.hashcode().toLowerCase();
	            sB = b.hashcode().toLowerCase();				
			} catch (e) {
				return 0;
			}
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
					try {
						retval = thisCfg.fn(a, b, thisCfg);	
					} catch (e) {
						retval = 0;
					}
                    if (retval) 
                        break;
                }
                return retval;
            }
            return arraySortFn;
        }
    };
    SNAPPI.Sort.compare = compare;
    
    SNAPPI.Sort.resequenceSortOrderPreserveSettings = function(aOldSortCfg, aNewSortSequence){
        /*
         * reset defaultSortCfg sort order to sequence specified by aNewSortSequence,
         * match on cfg.label of aNewSortSequence,
         * preserving cfg settings
         */
        var found, reorderedSortCfg = [];
        for (var j = 0; j < aNewSortSequence.length; j++) {
            found = false;
            for (var k = 0; k < aOldSortCfg.length; k++) {
                if (aOldSortCfg[k].label == aNewSortSequence[j].label) {
                    reorderedSortCfg.push(aOldSortCfg[k]);
                    found = true;
                    break;
                }
                
            }
            if (!found) 
                reorderedSortCfg.push(aNewSortSequence[j]);
        }
        return reorderedSortCfg;
    };
    
})();

(function(){

    var Y = SNAPPI.Y;
    
    /*
     * ToolTip module
     */
    if (!SNAPPI.util.TitleToolTip) {
//        TitleToolTip = new YAHOO.widget.Tooltip("title-tooltip", {
//            width: "300px",
//            effect: {
//                effect: YAHOO.widget.ContainerEffect.FADE,
//                duration: 0.20
//            }
//        });
//        
//        Y.one("#title-tooltip").setStyle("text-align", "left");
//        
//        /*
//         * add to namespace
//         */
//        SNAPPI.util.TitleToolTip = TitleToolTip;
//        
//        /*
//         * TitleToolTip Static Methods
//         */
//        TitleToolTip.push = function(el){
//            el = el.dom();
//            var ttt = SNAPPI.util.TitleToolTip;
//            var arr = ttt.cfg.getProperty('context') || [];
//            arr.push(el);
//            ttt.cfg.setProperty('context', arr);
//        };
//        
//        TitleToolTip.remove = function(el){
//            el = el.dom();
//            var ttt = SNAPPI.util.TitleToolTip;
//            var arr = ttt.cfg.getProperty('context') || [];
//            for (var i = 0; i < arr.length; i++) {
//                if (arr[i] == el) 
//                    arr.splice(i, 1);
//            }
//            ttt.cfg.setProperty('context', arr);
//        };
//        TitleToolTip.hide = function(){
//            Y.one("#title-tooltip").setStyle('visibility', 'hidden');
//        };
        
    }
})();

/*
 * from http://yuiblog.com/blog/2008/06/24/buildingwidgets
 */
(function(){
    if (!SNAPPI.util.LoadingPanel) {
    
        var Y = SNAPPI.Y;
        
        var LoadingPanel = function(id){
        
            // in this case, all we have to do is call the constructor of the class we inherit from
            // giving it all the necessary configuration options.
            LoadingPanel.superclass.constructor.call(this, // If not id was given, create one
 id || YUI.guid(), {
                width: "240px",
                fixedcenter: true,
                constraintoviewport: true,
                underlay: "shadow",
                close: false,
                zindex: 99,
                visible: false,
                draggable: true,
                modal: true
            });
            this.getInstance = function(){
                return this;
            };
            this.init = function(){
                // since our custom panel inherits from Panel, all its methods and properties are accessible through 'this'
                this.setHeader("Loading ...");
                this.setBody('<img src="css/rel_interstitial_loading.gif" />');
                this.body.ynode().setStyle('textAlign', 'center');
                
                var cancelLink = Y.Node.create('<a style="cursor:pointer" >Continue</a>');
                cancelLink.on('click', function(e){
                    this.hide();
                }, this);
                this.appendToBody(document.createElement('BR'));
                this.appendToBody(cancelLink.dom());
                this.render(document.body);
            };
            
            this.init();
        };
        
        // We declare the above constructor to inherit from Panel
        Y.extend(LoadingPanel, YAHOO.widget.Panel);
        
		if (/^gallery/.test(window.location.host)) {
			SNAPPI.util.LoadingPanel = new LoadingPanel();
		}
        
    }
})();

/*
 * SHOW LOADING PANEL AS SOON AS IT IS READY
 * for HOSTING FROM gallery.snaphappi.com, etc.
 */
if (/^gallery/.test(window.location.host)) SNAPPI.util.LoadingPanel.show();
