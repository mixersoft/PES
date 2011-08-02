/**
 *
 * Copyright (c) 2009, Snaphappi.com. All rights reserved.
 *
 *
 *
 * @author Michael Lin, info@snaphappi.com
 *
 */
(function(){
    /*
     * shorthand
     */
    var PM = SNAPPI.namespace('SNAPPI.PM');
    var Y = PM.Y;
	
	
	/*
	 * protected
	 */
	
	// add additional modules to PM.Y sandbox
	/*
	 *  WARNING: datasource classes can be defined in this script,
	 *  but they WONT WORK until Y.use() returns from asynch load
	 *  
	 *  we should normally define classes inside Y.use(), but then we cannot
	 *  use outline explorer in the eclipse IDE
	 */ 
    /*
     * queue for managing asynch io calls
     */
    var _queue = new Y.AsyncQueue();
    //		_queue.pause();
	SNAPPI.PM.queue = _queue;	// make global
    
    
    /*
     * one datasourceIO shared by all XmlDatasource objects
     */
    //        var _dsIO = new Y.DataSource.IO({
    //            source: ''
    //        })
    //        _dsIO.on('destroy', function(e){
    //            var destroyed;
    //        });
    //        SNAPPI._dsIO = _dsIO;
    
	
	
    /*
     * Base class for parsing XML DataSource
     */
    var XmlDatasource = function(cfg){
        this.NAME = "XmlDatasource";
        /*
         * internal methods and attributes
         */
        var _cfg = {
        	sync: false,	// async io is default
            page: 1,
            perpage: null
        };
        this._cfg = Y.merge(_cfg, cfg);
        this._getQsAsStr = function(qs){
            qs = Y.Lang.isObject(qs) ? Y.merge(this._cfg, qs) : this._cfg;
            qs = (Y.Lang.isObject(this.xmlSchemaParser.qsOverride)) ? Y.merge(qs, this.xmlSchemaParser.qsOverride) : qs;
            // &pages=all override
            if (SNAPPI.util.getFromQs('perpage')) 
                qs.perpage = SNAPPI.util.getFromQs('perpage');
            var arrQs = [];
            for (var p in qs) {
                if (qs[p] !== null && qs[p] !== '') 
                    arrQs.push(p + '=' + qs[p]);
            }
            return this.xmlSchemaParser.uri + arrQs.join('&');
        };
        
        this._dsIoResponseXmlCallback = {
            success: function(e){
                var responseXML = e.responseXML ? e.responseXML : e.response2.results[0].responseXML;
                e.response2.requestUri = e.target.get('source') + e.request;
                e.response2.responseXML = responseXML;
                e.response2.parsedResponseXMLAsObj = fleegix.xml.parse(responseXML, _cfg.rootNode, _cfg.xmlns);
                var self = e.callback.argument.self; // this
                self.fire('XmlDatasource:responseXMLSuccess', e);
            },
            failure: function(e){
                alert("Could not retrieve data: " + e.error.message + ', request=' + e.request);
            },
            argument: {
                self: this
            }
        };
        
        /*
         * io Transaction Object
         */
        this.log = function(str, args){
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
        
        /* transaction event object */
        var tH = {
            start: function(id, args){
                this.log(id + ": Transaction Event Start.", args.start);
            },
            complete: function(id, o, args){
                this.log(id + ": Transaction Event Complete.  The status code is: " + o.status + ".", args.complete);
            },
            success: function(id, o, args){
                this.onIOSuccess(id, o, args);
            },
            failure: function(id, o, args){
                this.log(id + ": Transaction Event Failure.  The status text is: " + o.statusText + ".", args.failure);
            },
            end: function(id, args){
                this.log(id + ": Transaction Event EnLog.", args.end);
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
            //                headers: {},
            arguments: {},
            sync: this._cfg.sync
        };
        
        
        /**
         * Use Cross-domain transport DOESN'T WORK
         */
        if (0) {
            //Configure the cross-domain protocol:
            var xdrConfig = {
                id: 'flash', //We'll reference this id in the xdr configuration of our transaction.
                yid: Y.id, //The yid provides a link from the Flash-based XDR engine
                //and the YUI instance.
                src: '/js/io.swf' //Relative path to the .swf file from the current page.
            };
            Y.io.transport(xdrConfig);
            ioCfg.xdr = {
                use: 'flash'
            };
        }
        /* end configuration object */
        
        this.onIOSuccess = function(id, o, args){
            var e = o;
            e.arguments = args;
            
            // this._dataSourceIO.on('response'), 
            e.response2 = {};	// webkit e.response is READONLY
            e.response2.responseXML = e.responseXML;
//            if (e.response2.responseXML === null) {
//                // webkit browsers do not return responseXML so we must parse manually with Y.DataType.XML
//                var responseText = o.responseText;
//                e.response2.responseXML = Y.DataType.XML.parse(responseText);
//                if (!e.response2.responseXML instanceof XMLDocument) {
//                    // fire error event                        e.stopPropagation();
//                    e.error = {
//                        message: 'error parsing responseText to XMLDocument'
//                    };
//                    this.fire('error', e);
//                }
//            }
            
//            var responseXML = e.responseXML;
            e.response2.requestUri = args.request;
            e.response2.parsedResponseXMLAsObj = fleegix.xml.parse(e.response2.responseXML, _cfg.rootNode, _cfg.xmlns);
            this.fire('XmlDatasource:responseXMLSuccess', e, this);
        };
        
        
        /*
         * public methods
         */
        // get parsed XML doc
        this.getParsedResponse = function(qs, callback){
            this.on('XmlDatasource:responseXMLSuccess', function(e, self){
                if (e.response2.parsedResponseXMLAsObj) {
                    e.response2.parsedResponse = this.xmlSchemaParser.parse(e.response2.parsedResponseXMLAsObj);
                    e.response2.parsedResponse.requestUri = e.response2.requestUri;
                    // parse XML with XML "schema" to get datasource results
                    this.fire('XmlDatasource:parsedResponseSuccess', e); // { response: e,target: self,});
                    if (Y.Lang.isFunction(callback.success)) 
                        callback.success(e);
                }
                this.detach('XmlDatasource:responseXMLSuccess');
            });
            this.getResponseXML(qs);
        };
        
        // get XML doc
        this.getResponseXML = function(qs){
            var uri = (qs && Y.Lang.isString(qs)) ? qs : this._getQsAsStr(qs);
            //                var check = this._dataSourceIO.get('source');
            //                SNAPPI.PM.queue.add(this._dataSourceIO.sendRequest(uri, this._dsIoResponseXmlCallback));
            ioCfg.arguments.request = uri;
            if (ioCfg.sync == true) {
	            var response = Y.io(uri, ioCfg);
	            return null;	// will still use ioCfg callbacks to return value
            } else SNAPPI.PM.queue.add(Y.io(uri, ioCfg));
        };
    };
    //        Y.augment(XmlDatasource, Y.DataSource.IO);
    Y.augment(XmlDatasource, Y.EventTarget);
    PM.XmlDatasource = XmlDatasource;
    
    
    var xmlArrangementParser_Snappi = {
        uri: '../../pagemaker/catalog.xml?',
        xmlns: 'sn',
        rootNode: 'Catalog',
        qsOverride: {
            perpage: 'all'
        },
        parse: function(rootNode){
            var p, q, arrangement, arrArrangements, role, node;
            var arrangements = {};
            if (rootNode.Catalog && rootNode.Catalog.Arrangements) {
                arrArrangements = rootNode.Catalog.Arrangements;
                for (p in arrArrangements) {
                    arrangement = arrArrangements[p].Arrangement;
                    node = {};
                    node.owner = rootNode.Catalog.owner;
                    node.W = parseFloat(arrangement.W);
                    node.H = parseFloat(arrangement.H);
                    node.format = node.W / node.H;
                    node.title = arrangement.Title;
                    node.spacing = arrangement.Spacing;
                    node.Orientation = arrangement.Orientation; // landscape/portrait count
                    node.roles = [];
                    for (q in arrangement.Role) {
                        var role = arrangement.Role[q];
                        role.format = (node.W * role.W) / (node.H * role.H);
                        node.roles.push(role);
                    }
                    if (arrangements[node.roles.length] === undefined) {
                        arrangements[node.roles.length] = [];
                    }
                    arrangements[node.roles.length].push(node);
                }
            }
            //                
            var catalog = {
                arrangements: arrangements,
                id: rootNode.Catalog.Id,
                provider: rootNode.Catalog.Owner,
                format: rootNode.Catalog.Owner // TODO: add  attribute  "format" to XML
            };
            return {
                results: catalog
            };
        }
    };
    
    
    /*
     * Snappi DataSource for Catalogs
     */
    var SnappiXmlCatalog = function(cfg){
        this.xmlSchemaParser = xmlArrangementParser_Snappi;
        SnappiXmlCatalog.superclass.constructor.call(this, cfg);
    };
    Y.extend(SnappiXmlCatalog, PM.XmlDatasource);
    PM.SnappiXmlCatalog = SnappiXmlCatalog;
    
    PM.xmlArrangementParser_Snappi = xmlArrangementParser_Snappi;
    
})();
