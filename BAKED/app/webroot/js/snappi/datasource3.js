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
 */
(function(){

    var _Y = null;
    SNAPPI.namespace('SNAPPI.onYready');
	SNAPPI.onYready.Datasource = function(Y){
		if (_Y === null) _Y = Y;
		_Y.augment(SnappiDatasource, _Y.EventTarget);
    	SNAPPI.SnappiDatasource = SnappiDatasource;
    	
    	_Y.extend(JsonDatasource, SnappiDatasource);
    	SNAPPI.JsonDatasource = JsonDatasource;
    	
    	_Y.extend(XmlDatasource, SnappiDatasource);
		SNAPPI.XmlDatasource = XmlDatasource;
		
		if (SNAPPI.AuditionParser.snappi.uri.indexOf('.json?') != -1) {
	        _Y.extend(SnappiCastingCall, JsonDatasource);
	    }
	    else {
	        _Y.extend(SnappiCastingCall, XmlDatasource);
	    }
	    SNAPPI.SnappiCastingCall = SnappiCastingCall;
	    
	    /*
	     * AIR DataSource for CastingCalls
	     */
	    try {
	        // check if class exists
	        var check = _Y.Lang.isObject(SNAPPI.AIR.CastingCallDataSource);
	        SNAPPI.AIRCastingCall = SNAPPI.AIR.CastingCallDataSource;
	        SNAPPI.AIRCastingCall.prototype.schemaParser = SNAPPI.AuditionParser.AIR;
	    } 
	    catch (ex) {
	        //		SNAPPI.isAIR == false;
	    }
	    
	    _Y.extend(FlickrXmlCastingCall, SNAPPI.XmlDatasource);
	    SNAPPI.FlickrXmlCastingCall = FlickrXmlCastingCall;
	    
	    //        _Y.extend(FacebookXmlCastingCall, SNAPPI.XmlDatasource);
	    //        SNAPPI.FacebookXmlCastingCall = FacebookXmlCastingCall;
	    
	}  
    
    /*
     * protected methods and variables
     */
    /*
     * adjustment for fleegix.xml in lib/xml.js
     * when it parses XML into JS objects, single element results are created as objects, not arrays.
     * arrays are only created for multi-element results.
     */
    var _forceArray = function(o){
        try {
            if (false == _Y.Lang.isArray(o)) 
                return [o];
            else 
                return o;
            
        } 
        catch (e) {
            return null;
        }
    };
    var _hashcode = function(){
            return this.id;
	}
    /*
     * enforce JS array structures by schema
     */
    var _xml2JsTidy = function(root, schema){
        _schema = {
            toArray: ['CastingCall.Auditions.Audition', 'CastingCall.Clusters.Cluster', 'CastingCall.Substitutions.Substitution', 'CastingCall.Tags.Tag', 'CastingCall.Sets.Set'],
            findProperty2Array: ['AuditionREF']
        };
        _schema = _Y.merge(_schema, schema);
        // handle arrays
        var o;
        for (var p in _schema.toArray) {
            try {
                var parts = _schema.toArray[p].split('.');
                o = root;
                for (var q in parts) {
                    o = o[parts[q]];
                }
            } 
            catch (e) {
                break;
            }
            o = _forceArray(o);
        }
        // recursive wildcard search for property p
        var findProperty = function(o, p, fn){
            for (var i in o) {
                if (i == p) 
                    fn(o[i]);
                else {
                    if (_Y.Lang.isObject(o[i])) 
                        findProperty(o[i], p, fn);
                }
            }
        };
        for (var p in _schema.findProperty2Array) {
            findProperty(root, _schema.findProperty2Array[p], function(o){
                o = _forceArray(o);
            });
        }
    };
    
    
    
    /*
     * private attrs
     */
    // queue for managing asynch io calls, 
    // shared by SnappiDatasource, JsonDatasource, XmlDatasource
    var _queue = null;
    
    
    /***************************************************************************************
     * New datasource class definition for AIRs
     */
    //    try {
    //        var testForAIR = SNAPPI.AIR.CastingCallDataSource;
    //        
    //        
    //        // this is mandatory, why?
    //        var qs = {
    //            page: 1,
    //            perpage: 9
    //        };
    //        SNAPPI.AIR.CastingCall = new SNAPPI.AIR.CastingCallDataSource(qs);
    //        
    //        // deprecate, for thumbnail3.js line 160
    //        SNAPPI.AIR.CastingCall.schemaParser = SNAPPI.AIR.CastingCall.parser;
    //        console.log("main.js: SNAPPI.AIR.CastingCall loaded baseurl=" + SNAPPI.AIR.CastingCall.baseurl());
    //    } 
    //    catch (e) {
    //        // SNAPPI.AIR.CastingCallDataSource not available
    //        SNAPPI.isAIR = false;
    //    }
    
    /*
     * end datasource class definition
     */
    /*
     * protected
     */
    var tH = {
        start: function(id, args){
            log(id + ": Transaction Event Start.", args.start);
        },
        complete: function(id, o, args){
            log(id + ": Transaction Event Complete.  The status code is: " + o.status + ".", args.complete);
        },
        success: function(id, o, args){
            this.onIOSuccess(id, o, args);
        },
        failure: function(id, o, args){
            log(id + ": Transaction Event Failure.  The status text is: " + o.statusText + ".", args.failure);
        },
        end: function(id, args){
            log(id + ": Transaction Event EnLog.", args.end);
        }
    };
    /* end transaction event object */
    /* configuration object for transactions */
    var ioCfg = {
        on: {
            start: tH.start,
            complete: tH.complete,
            success: tH.success,
            failure: tH.failure,
            end: tH.end
        },
        context: this,
        headers: {},
        arguments: {}
    };
    var log = function(str, args){
        var log = _Y.one('#log');
        if (log) {
            var s = log.get('innerHTML');
            s += "ID: " + str;
            if (args) {
                s += " " + "The arguments are: " + args;
            }
            s += "<br>";
            log.set('innerHTML', s);
        }
    };
    
    
    /*
     * Base class for parsing DataSource
     */
    var SnappiDatasource = function(cfg){
        this.NAME = "datasource";
        /*
         * internal methods and attributes
         */
        this._cfg = _Y.Lang.isObject(cfg) ? _Y.merge(this._cfg, cfg) : this._cfg;
        if (_queue === null) _queue = new _Y.AsyncQueue();
    };
    
    SnappiDatasource.prototype = {
        _cfg: {
            page: 1,
            perpage: null
        },
        getHost: function(){
            return this.HOST || null;
        },
        /*
         * io Transaction Object
         */
        onIOSuccess: function(id, o, args){
            /*
             * OVERRIDE FOR JSON AND XML
             */
            //            var e = o;
            //            e.arguments = args;
            //            
            //            // this._dataSourceIO.on('response')
            //            e.response = {};
            //            if (args.request.indexOf('.json?') != -1) {
            //                // json parse
            //                e.response2.dsResponse = eval('(' + o.responseText + ')');
            //                e.response2.requestUri = args.request;
            //            }
            //            else { // xml parse
            //                e.response2.responseXML = o.responseXML;
            //                if (e.response2.responseXML === null) {
            //                    // webkit browsers do not return responseXML so we must parse manually with _Y.DataType.XML
            //                    var responseText = o.responseText;
            //                    e.response2.responseXML = _Y.DataType.XML.parse(responseText);
            //                    if (!e.response2.responseXML instanceof XMLDocument) {
            //                        // fire error event                        e.stopPropagation();
            //                        e.error = {
            //                            message: 'error parsing responseText to XMLDocument'
            //                        };
            //                        this.fire('error', e);
            //                    }
            //                }
            //                
            //                var responseXML = e.response2.responseXML;
            //                e.response2.requestUri = args.request;
            //                e.response2.dsResponse = fleegix.xml.parse(responseXML, _cfg.rootNode, _cfg.xmlns);
            //            }
            //            
            //            
            //            this.fire('XmlDatasource:responseXMLSuccess', e, this);
        },
        _getQsAsStr: function(qs){
            qs = _Y.Lang.isObject(qs) ? _Y.merge(this._cfg, qs) : this._cfg;
            // &pages=all override
            if (SNAPPI.util.getFromQs('perpage')) 
                qs.perpage = SNAPPI.util.getFromQs('perpage');
            var arrQs = [];
            for (var p in qs) {
                if (qs[p] !== null && qs[p] !== '') 
                    arrQs.push(p + '=' + qs[p]);
            }
            return arrQs.join('&');
        },
        /*
         * public methods
         */
        // get parsed response
        getParsedResponse: function(qs, callback){
            this.on('SnappiDatasource:responseSuccess', function(e, self){
                if (e.response2.dsResponse) {
                    e.response2.parsedResponse.requestUri = e.response2.requestUri;
                    e.response2.schemaParser = this.schemaParser;
                    e.response2.parsedResponse = this.schemaParser.parse(e.response2.dsResponse);
                    if (_Y.Lang.isFunction(callback.success)) 
                        callback.success(e);
                }
                this.detach('SnappiDatasource:responseSuccess');
            });
            this.queueRequest(qs);
        },
        
        // get response doc
        queueRequest: function(uri){
            ioCfg.arguments.request = uri;
            ioCfg.context = this;
            _queue.add(_Y.io(uri, ioCfg));
        },
        
        parseMeanShiftGroupings: function(results, rootNode){
            if (rootNode.CastingCall == undefined) 
                return;
            /*
             * now add Groups. maybe we should have a separate Groups parser
             */
            var auditionByKey = {};
            for (var aud in results) {
                auditionByKey[results[aud].id] = results[aud];
            }
            
            var cc = rootNode.CastingCall;
            var Substitutions = cc.Substitutions && cc.Substitutions.Substitution || [];
            var Clusters = cc.Clusters && cc.Clusters.Cluster || [];
            var Tags = cc.Tags && cc.Tags.Tag || [];
            
            for (var gr in Substitutions) {
                if (!_Y.Lang.isArray(Substitutions[gr].AuditionREF)) 
                    continue; // skip if there is only one element in this substitution group
                //                    var best = null, arrGroupIds = [];
                var subGrData = new SNAPPI.SubstitutionGroupData();
                for (var a in Substitutions[gr].AuditionREF) {
                    var audition = auditionByKey[Substitutions[gr].AuditionREF[a].idref];
                    if (!audition) 
                        continue;
                    subGrData.add(audition);
                    audition.substitutes = subGrData;
                    //                        arrGroupIds.push(Substitutions[gr].AuditionREF[a].idref);
                    //                        var audition = auditionByKey[Substitutions[gr].AuditionREF[a].idref];
                    //                        audition.substitutes = arrGroupIds; // reference to array
                    //                        if (best && (best.rating < audition.rating)) {
                    //                            best.bestSubstitute = false; // reset old best
                    //                        }
                    //                        best = (best && (best.rating >= audition.rating)) ? best : audition;
                    //                        audition.bestSubstitute = (audition == best) ? true : false;
                }
            }
            for (var gr in Clusters) {
                var type = Clusters[gr].Type;
                if (_Y.Lang.isArray(Clusters[gr].AuditionREF)) {
                    for (var a in Clusters[gr].AuditionREF) {
                        var audition = auditionByKey[Clusters[gr].AuditionREF[a].idref];
						if (!audition) break;
                        if (audition.cluster == undefined) 
                            audition.cluster = {};
                        if (audition.cluster[type] == undefined) 
                            audition.cluster[type] = [];
                        audition.cluster[type].push(Clusters[gr].id);
                    }
                }
                else {
                    var audition = auditionByKey[Clusters[gr].AuditionREF.idref];
                    if (audition.cluster == undefined) 
                        audition.cluster = {};
                    if (audition.cluster[type] == undefined) 
                        audition.cluster[type] = [];
                    audition.cluster[type].push(Clusters[gr].id);
                }
            }
            
        }
    };
    
    
    /*
     * JSON datasource
     */
    var JsonDatasource = function(cfg){
        this.name = "JsonDatasource";
        JsonDatasource.superclass.constructor.call(this, cfg);
        
        this.onIOSuccess = function(id, o, args){
            var e = o;
            e.arguments = args;
            
            // this._dataSourceIO.on('response')
            e.response = {};
            e.response2.requestUri = args.request;
            // json parse
            e.response2.dsResponse = eval('(' + e.responseText + ')');
            this.fire('SnappiDatasource:responseSuccess', e, this);
        };
        
        // get parsed JSON doc
        this.getParsedResponse = function(qs, callback){
            this.on('SnappiDatasource:responseSuccess', function(e, self){
                if (e.response2.dsResponse) {
                    e.response2.schemaParser = this.schemaParser;
                    e.response2.parsedResponse = this.schemaParser.parse(e.response2.dsResponse);
                    e.response2.parsedResponse.requestUri = e.response2.requestUri;
					this.parseMeanShiftGroupings(e.response2.parsedResponse.results , e.response2.dsResponse);
                }
                this.detach('SnappiDatasource:responseSuccess');
                this.fire('SnappiDatasource:responseJSONSuccess', e, this);
            });
            this.on('SnappiDatasource:responseJSONSuccess', function(e, self){
                if (e.response2.dsResponse) {
                    // parse JSON with JSON "schema" to get datasource results
                    this.fire('JsonDatasource:parsedResponseSuccess', e); // { response: e,target: self,});
                    if (_Y.Lang.isFunction(callback.success)) 
                        callback.success(e);
                }
                this.detach('SnappiDatasource:responseJSONSuccess');
            });
            qs = (_Y.Lang.isObject(this.schemaParser.qsOverride)) ? _Y.merge(qs, this.schemaParser.qsOverride) : qs;
            var req = (qs && _Y.Lang.isString(qs)) ? qs : this.schemaParser.uri + this._getQsAsStr(qs);
            this.queueRequest(req);
        };
        
    };
    
    
    /*
     * XML datasource
     */
    var XmlDatasource = function(cfg){
        this.name = "XmlDatasource";
        XmlDatasource.superclass.constructor.call(this, cfg);
        
        this.onIOSuccess = function(id, o, args){
            var e = o;
            e.arguments = args;
            
            // this._dataSourceIO.on('response')
            e.response = {};
            e.response2.requestUri = args.request;
            e.response2.responseXML = o.responseXML;
            if (e.response2.responseXML === null) {
                // webkit browsers do not return responseXML so we must parse manually with _Y.DataType.XML
                var responseText = o.responseText;
                e.response2.responseXML = _Y.DataType.XML.parse(responseText);
                if (!e.response2.responseXML instanceof XMLDocument) {
                    // fire error event                        e.stopPropagation();
                    e.error = {
                        message: 'error parsing responseText to XMLDocument'
                    };
                    this.fire('error', e);
                }
            }
            
            var responseXML = e.response2.responseXML;
            var xmlParse = fleegix.xml.parse(responseXML, this._cfg.rootNode, this._cfg.xmlns);
            _xml2JsTidy(xmlParse);
            e.response2.dsResponse = xmlParse;
            this.fire('SnappiDatasource:responseSuccess', e, this);
        };
        
        // get parsed XML doc
        this.getParsedResponse = function(qs, callback){
            this.on('SnappiDatasource:responseSuccess', function(e, self){
                if (e.response2.dsResponse) {
                    e.response2.schemaParser = this.schemaParser;
                    e.response2.parsedResponse = this.schemaParser.parse(e.response2.dsResponse);
                    e.response2.parsedResponse.requestUri = e.response2.requestUri;
                }
                this.detach('SnappiDatasource:responseSuccess');
                this.fire('SnappiDatasource:responseXMLSuccess', e, this);
            });
            this.on('SnappiDatasource:responseXMLSuccess', function(e, self){
                if (e.response2.dsResponse) {
                    // parse XML with XML "schema" to get datasource results
                    this.fire('XmlDatasource:parsedResponseSuccess', e); // { response: e,target: self,});
                    if (_Y.Lang.isFunction(callback.success)) 
                        callback.success(e);
                }
                this.detach('SnappiDatasource:responseXMLSuccess');
            });
            qs = (_Y.Lang.isObject(this.schemaParser.qsOverride)) ? _Y.merge(qs, this.schemaParser.qsOverride) : qs;
            var req = (qs && _Y.Lang.isString(qs)) ? qs : this.schemaParser.uri + this._getQsAsStr(qs);
            this.queueRequest(req);
        };
    };
    
    
    
    /*
     * Snappi DataSource for CastingCalls
     */
    var SnappiCastingCall = function(cfg){
        this.HOST = 'snappi';
        this.schemaParser = SNAPPI.AuditionParser.snappi;
        SnappiCastingCall.superclass.constructor.call(this, cfg);
    };

    
    
    
    /*
     * Flickr DataSource for CastingCalls
     */
    var FlickrXmlCastingCall = function(cfg){
        this.HOST = 'flickr'; // see SNAPPI.cfg.DS_HOST 
        this.schemaParser = SNAPPI.AuditionParser.flickr;
        FlickrXmlCastingCall.superclass.constructor.call(this, cfg);
    };
    
    
	
    /*
     * Facebook DataSource for CastingCalls
     */
    //        var FacebookXmlCastingCall = function(cfg){
    //            this.schemaParser = AuditionParser_Facebook;
    //            FacebookXmlCastingCall.superclass.constructor.call(this, cfg);
    //        }

})();
