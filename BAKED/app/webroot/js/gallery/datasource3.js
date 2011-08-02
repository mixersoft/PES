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

    var Y = SNAPPI.Y;
    
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
            if (false == Y.Lang.isArray(o)) 
                return [o];
            else 
                return o;
            
        } 
        catch (e) {
            return null;
        }
    };
    /*
     * enforce JS array structures by schema
     */
    var _xml2JsTidy = function(root, schema){
        _schema = {
            toArray: ['CastingCall.Auditions.Audition', 'CastingCall.Clusters.Cluster', 'CastingCall.Substitutions.Substitution', 'CastingCall.Tags.Tag', 'CastingCall.Sets.Set'],
            findProperty2Array: ['AuditionREF']
        };
        _schema = Y.merge(_schema, schema);
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
                    if (Y.Lang.isObject(o[i])) 
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
     * queue for managing asynch io calls
     */
    var _queue = new Y.AsyncQueue();
    //		_queue.pause();
    
    SNAPPI.queue = _queue;
    
    /*
     * parse objects for different XML Datasources
     */
    /*
     * Flickr Audition Parser
     */
    var AuditionParser_Flickr = {
        uri: '../../flickr/castingCall.xml?',
        xmlns: 'sn',
        rootNode: 'CastingCall',
        qsOverride: { //                perpage: '100',
}        ,
        parse: function(rootNode){
            //            _xml2JsTidy(rootNode);
            var p, audition, arrAuditions, baseurl, proxyCacheBaseurl, node, results = [];
            if (rootNode.CastingCall && rootNode.CastingCall.Auditions && rootNode.CastingCall.Auditions.Audition) {
                arrAuditions = rootNode.CastingCall.Auditions.Audition;
                baseurl = rootNode.CastingCall.Auditions.Baseurl;
                proxyCacheBaseurl = rootNode.CastingCall.Auditions.ProxyCacheBaseurl;
                // organize catalog by number of photos
                for (p in arrAuditions) {
                    node = {};
                    audition = arrAuditions[p];
                    // extract additional properties from array
                    node.hashcode = SNAPPI.DataElement.prototype.hashcode;
                    node.id = audition.id;
                    node.pid = audition.Photo.id;
                    node.imageWidth = parseInt(audition.Photo.Img.Src.W);
                    node.imageHeight = parseInt(audition.Photo.Img.Src.H);
                    node.exif_DateTimeOriginal = audition.Photo.DateTaken.replace(/T/, ' ');
                    node.ts = parseInt(audition.Photo.TS);
                    node.exif_ExifImageWidth = parseInt(audition.Photo.W);
                    node.exif_ExifImageLength = parseInt(audition.Photo.H);
                    node.exif_Orientation = parseInt(audition.Photo.ExifOrientation) || null;
                    node.exif_Flash = audition.Photo.ExifFlash;
                    node.src = audition.Photo.Img.Src.Src; // deprecate
                    try {
                    	node.src = audition.Photo.Img.Src.previewSrc; // should be flickr base url, size='m'
                    } catch(e) {
                    	alert('change flickr component to output audition.Photo.Img.Src.previewSrc');
                    }
                    node.base64Src = proxyCacheBaseurl + audition.Photo.Img.Src.base64Src; // for manipulating external imgs
                    node.rootSrc = audition.Photo.Img.Src.rootSrc || node.src;
                    node.base64RootSrc = proxyCacheBaseurl + (audition.Photo.Img.Src.base64RootSrc || audition.Photo.Img.Src.base64Src);
                    node.rating = parseInt(audition.Photo.Fix.Rating || 0);
                    node.tags = audition.Tags && audition.Tags.value || null;
                    node.urlbase = baseurl || audition.Photo.Img.Src.Baseurl || '';
                    node['Audition'] = audition;
                    node['Fix'] = audition.Photo.Fix;
                    node['LayoutHint'] = audition.LayoutHint;
                    //                        node['Tags'] = audition.Tags && audition.Tags.Tag || [];
                    node.albumName = this.getAlbumName(audition.Photo.Photoset);
                    results.push(node);
                }
            }
            return {
                results: results
            };
        },
        getAlbumName: function getAlbumName(photoset){
            var account = SNAPPI.util.getFromQs('account');
            var tags = SNAPPI.util.getFromQs('tags');
            if (!account && !tags) 
                tags = 'recent photos';
            var arr = ['flickr'];
            if (account) 
                arr.push(account);
            if (tags) 
                arr.push(tags);
            return arr.join(': ');
        },
        getImgSrcBySize: function(src, size, dataElement){
            // should change suffixes if present
            switch (size) {
                case 's':
                case 'sq':
                    src = src.replace('.jpg', '_s.jpg');
                    break;
                case 't':
                case 'tn':
                    src = src.replace('.jpg', '_t.jpg');
                    break;
                case 'm':
                case 'bs':
                    src = src.replace('.jpg', '_m.jpg');
                    break;
                case 'o':
                case 'b':
                case 'br':
                    if (dataelement) {
                        src = (dataElement.rootSrc) ? dataElement.rootSrc : dataElement.src;
                    }
                    else {// just guess 'large' photo
                        src = src.replace('.jpg', '_b.jpg');
                    }
                    break;
                case 'bp':
                default:
                    // size m
                    break;
            };
            return src;
        }
    };
    
    /*
     * Facebook Audition Parser
     */
    var AuditionParser_Facebook = {};
    
    
    
    /*
     * Snappi Audition Parser
     */
    var AuditionParser_Snappi = {
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
                    node.hashcode = SNAPPI.DataElement.prototype.hashcode;
                    node.id = audition.id;
                    node.src = this.getImgSrcBySize(audition.Photo.Img.Src.previewSrc, 'tn');
                    node.urlbase = baseurl || audition.Photo.Img.Src.Baseurl || '';
                    node['Audition'] = audition;
                    node.tags = audition.Tags && audition.Tags.value || null;
                    node.label = audition.Photo.Caption;
					try {
						var src = audition.Photo.origSrc;
						node.albumName = this.getAlbumName(node, src);
//						if (node.albumName) {
//							src = src.match(/\/(\w*)\.jpg/i);
//							if (src[1]) node.label = src[1];
//						} else {
//							node.label = src;
//						}
					} catch(e) {
						node.albumName = this.getAlbumName(node);
					}
                    results.push(node);
                }
            }
            return {
                results: results
            };
        },
        getAlbumName: function getAlbumName(o, src){
            var parts, name;
			src = src || o.src;
            parts = src.split('/');
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
        getImgSrcBySize: SNAPPI.util.getImgSrcBySize
    };
    
    var AuditionParser_AIR = {
        datasource: null,
        parse: function(rootNode){
            /*
             * this == AuditionParser_AIR
             * rootNode == e.response
             */
 console.log(" ************* AuditionParser_AIR ************");
            var p, audition, arrAuditions, baseurl, node, results = [];
            if (rootNode.CastingCall && rootNode.CastingCall.Auditions && rootNode.CastingCall.Auditions.Audition) {
                arrAuditions = rootNode.CastingCall.Auditions.Audition;
                baseurl = rootNode.CastingCall.Auditions.Baseurl;
                // organize catalog by number of photos
                for (p in arrAuditions) {
                    node = {};
                    audition = arrAuditions[p];
                    // extract additional properties from array
                    node.id = audition.id;
                    node.hashcode = SNAPPI.DataElement.prototype.hashcode;
                    node.urlbase = baseurl || audition.Photo.Img.Src.Baseurl || '';
                    node.src = audition.Photo.Img.Src.Src;
                    try {
                    	node.src = audition.Photo.Img.Src.previewSrc; // should be flickr base url, size='m'
                    } catch(e) {
                    	alert('change AIR db call to output audition.Photo.Img.Src.previewSrc');
                    }                    
                    node['Audition'] = audition;
                    node.tags = audition.Tags && audition.Tags.value || null;
                    node.albumName = this.getAlbumName(node);
                    //                        console.log(" ************* albumName=" + node.albumName);
                    results.push(node);
                }
                console.log(" ************* count=" + results.length);
            }
            return {
                results: results
            };
        },
        /**
         * getImgSrcBySize() called by Thumbnail3.js to set IMG.src
         * NOTE: this takes the unmangled/original audition.src as input for now,
         * 			but it should be changedaudition.id
         * @param {Object} or String node
         * @param String size
         */
        getImgSrcBySize: function(node, size, callback){
            var id = (node && node.id) ? node.id : node;
			var options = {create:true, autorotate:true, replace:false};
			if (callback) options.callback = callback;
//console.log(" ***** datasource.getImgSrcBySize()  id="+id+"  size="+size+"  src=" + this.datasource.getImgSrcBySize(id, size, options));		
            return this.datasource.getImgSrcBySize(id, size, options);
        },
        getAlbumName: function(node){
            var parts, name;
//console.log(" ***** datasource.getAlbumName()  src="+node.src);		
            parts = (node.urlbase+'/'+node.src).replace(/\\/g, "/").split('/');
            parts.pop(); // discard filename
            if ((name = parts[parts.length - 1]) == '.thumbs') 
                parts.pop();
            if (node.urlbase) {
                return parts.join('/');
            }
            else {
                return parts.pop();
            }
        }
    };
    
    
    /***************************************************************************************
     * New datasource class definition for AIRs
     */
    //    try {
    //        var testForAIR = SNAPPI.AIR.CastingCallDataSource;
    //        
    //        
    //        Y.augment(SNAPPI.AIR.CastingCallDataSource, AuditionParser_AIR);
    //        SNAPPI.AIR.AuditionParser_AIR = AuditionParser_AIR;
    //        
    //        
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
        var log = Y.one('#log');
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
        this._cfg = Y.Lang.isObject(cfg) ? Y.merge(this._cfg, cfg) : this._cfg;
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
            //                    // webkit browsers do not return responseXML so we must parse manually with Y.DataType.XML
            //                    var responseText = o.responseText;
            //                    e.response2.responseXML = Y.DataType.XML.parse(responseText);
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
            qs = Y.Lang.isObject(qs) ? Y.merge(this._cfg, qs) : this._cfg;
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
                    if (Y.Lang.isFunction(callback.success)) 
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
            _queue.add(Y.io(uri, ioCfg));
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
                if (!Y.Lang.isArray(Substitutions[gr].AuditionREF)) 
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
                if (Y.Lang.isArray(Clusters[gr].AuditionREF)) {
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
    
    
    Y.augment(SnappiDatasource, Y.EventTarget);
    SNAPPI.SnappiDatasource = SnappiDatasource;
    
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
                    if (Y.Lang.isFunction(callback.success)) 
                        callback.success(e);
                }
                this.detach('SnappiDatasource:responseJSONSuccess');
            });
            qs = (Y.Lang.isObject(this.schemaParser.qsOverride)) ? Y.merge(qs, this.schemaParser.qsOverride) : qs;
            var req = (qs && Y.Lang.isString(qs)) ? qs : this.schemaParser.uri + this._getQsAsStr(qs);
            this.queueRequest(req);
        };
        
    };
    Y.extend(JsonDatasource, SnappiDatasource);
    SNAPPI.JsonDatasource = JsonDatasource;
    
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
                // webkit browsers do not return responseXML so we must parse manually with Y.DataType.XML
                var responseText = o.responseText;
                e.response2.responseXML = Y.DataType.XML.parse(responseText);
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
                    if (Y.Lang.isFunction(callback.success)) 
                        callback.success(e);
                }
                this.detach('SnappiDatasource:responseXMLSuccess');
            });
            qs = (Y.Lang.isObject(this.schemaParser.qsOverride)) ? Y.merge(qs, this.schemaParser.qsOverride) : qs;
            var req = (qs && Y.Lang.isString(qs)) ? qs : this.schemaParser.uri + this._getQsAsStr(qs);
            this.queueRequest(req);
        };
    };
    Y.extend(XmlDatasource, SnappiDatasource);
    SNAPPI.XmlDatasource = XmlDatasource;
    
    
    /*
     * Snappi DataSource for CastingCalls
     */
    var SnappiCastingCall = function(cfg){
        this.HOST = 'snappi';
        this.schemaParser = AuditionParser_Snappi;
        SnappiCastingCall.superclass.constructor.call(this, cfg);
    };
    if (AuditionParser_Snappi.uri.indexOf('.json?') != -1) {
        Y.extend(SnappiCastingCall, JsonDatasource);
    }
    else {
        Y.extend(SnappiCastingCall, XmlDatasource);
    }
    SNAPPI.SnappiCastingCall = SnappiCastingCall;
    
    
    /*
     * AIR DataSource for CastingCalls
     */
    try {
        // check if class exists
        var check = Y.Lang.isObject(SNAPPI.AIR.CastingCallDataSource);
        SNAPPI.AIRCastingCall = SNAPPI.AIR.CastingCallDataSource;
        SNAPPI.AIRCastingCall.prototype.schemaParser = AuditionParser_AIR;
    } 
    catch (ex) {
        //		SNAPPI.isAIR == false;
    }
    
    
    
    /*
     * Flickr DataSource for CastingCalls
     */
    var FlickrXmlCastingCall = function(cfg){
        this.HOST = 'flickr'; // see SNAPPI.cfg.DS_HOST 
        this.schemaParser = AuditionParser_Flickr;
        FlickrXmlCastingCall.superclass.constructor.call(this, cfg);
    };
    Y.extend(FlickrXmlCastingCall, SNAPPI.XmlDatasource);
    SNAPPI.FlickrXmlCastingCall = FlickrXmlCastingCall;
    
	// TEMP: publish parser for baked render
	SNAPPI.AuditionParser_Snappi = AuditionParser_Snappi;
	
    /*
     * Facebook DataSource for CastingCalls
     */
    //        var FacebookXmlCastingCall = function(cfg){
    //            this.schemaParser = AuditionParser_Facebook;
    //            FacebookXmlCastingCall.superclass.constructor.call(this, cfg);
    //        }
    //        Y.extend(FacebookXmlCastingCall, SNAPPI.XmlDatasource);
    //        SNAPPI.FacebookXmlCastingCall = FacebookXmlCastingCall;
})();
