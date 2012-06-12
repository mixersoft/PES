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
        var _Y = null;
        SNAPPI.namespace('SNAPPI.onYready');
        SNAPPI.onYready.Util = function(Y){
			if (_Y === null) _Y = Y;
			// create global singleton
    		SNAPPI.util = new Util();
		}          
        /*
         * override String
         */
        String.prototype.trim = function(){
            //            return this.replace(/^\s*/, "").replace(/\s*$/, "");
            return this.replace(/^\s+|\s+$/g, '');
        };
        
        var Util = function(){
        	if (Util.instance) return Util.instance;	// singleton
        	Util.instance = this;
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
            useStaticHost: function(src) {
            	try {
            		var host = SNAPPI.Config.getStaticHost(src);
	            	if (src.indexOf('/') !== 0) host += '/'; 
	            	return 'http://'+host+src;
            	} catch(e){}
            	return src;
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
            // deprecate, use _Y.merge
            mergeObjCopy: function(cfg, defaultCfg){
                return SNAPPI.util.mergeObj(SNAPPI.util.copyObj(cfg), defaultCfg);
            },
            // deprecate, use _Y.merge
            mergeObj: function(cfg, defaultCfg){
                if (cfg === undefined || cfg === null) 
                    cfg = {};
                for (var p in defaultCfg) {
                    if (cfg[p] === undefined) 
                        cfg[p] = defaultCfg[p];
                }
                return cfg;
            },
            // deprecate, use _Y.merge
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
            // output JS date to string in local time zone
            formatUnixtimeAsDate: function(unixtime){
            	var date = new Date(unixtime*1000)
                var lpad = function(n, padding){
                	if (padding == undefined) padding = '0';
                    return n.toString().length == 1 ? padding + n : n;
                };
                var h = date.getHours();
                var dd = (h>12) ? 'pm' : 'am';
                h = (h>12) ? h-12 : ((h==0) ? 12 : h);
                return date.getFullYear() + '-' + lpad(date.getMonth() + 1) + '-' + lpad(date.getDate()) + '&nbsp;' + h + ':' + lpad(date.getMinutes()) + dd;
            },
            formatUnixtimeAsTimeAgo: function(unixtime){
            	// var date = new Date(unixtime*1000);
            	var difference = new Date().getTime() - (unixtime * 1000);
            	var daysAgo =  Math.floor(difference/1000/60/60/24);
    			difference -= daysAgo*1000*60*60*24;
    			if (daysAgo >= 7) return this.formatUnixtimeAsDate(unixtime);
    			
            	var hoursAgo = Math.floor(difference/1000/60/60);
    			difference -= hoursAgo*1000*60*60;
    			var minsAgo = Math.floor(difference/1000/60);
    			var label = daysAgo ? daysAgo+'d ':'';
    			label += hoursAgo ? hoursAgo+'h ':'';
    			label += minsAgo ? minsAgo+'m ':'';
    			return label ? label +' ago' : '0m ago';
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
            },
            /**
			 * Function that could be used to round a number to a given decimal points. Returns the answer
			 * Arguments :  number - The number that must be rounded
			 *				decimal_points - The number of decimal points that should appear in the result
			 */
			roundNumber: function (number,decimal_points) {
				if(!decimal_points) return Math.round(number);
				if(number == 0) {
					var decimals = "";
					for(var i=0;i<decimal_points;i++) decimals += "0";
					return "0."+decimals;
				}
			
				var exponent = Math.pow(10,decimal_points);
				var num = Math.round((number * exponent)).toString();
				return num.slice(0,-1*decimal_points) + "." + num.slice(-1*decimal_points)
			},
			/**
			 * apply changes for macintosh platorms
			 * 	- Ctrl-Click to Cmd-Click
			 */
			setForMacintosh: function (node) {
				try { var shortcut;
					if (_Y.UA.os === "macintosh") {
						node.all('span.Cmd-Click').each(function(n,i,l){
							shortcut = n.get('innerHTML');
							n.setContent(shortcut.replace('Ctrl-', 'Cmd-'));	
						});
					}
				} catch(e) {}
			},
        };
        
})();










