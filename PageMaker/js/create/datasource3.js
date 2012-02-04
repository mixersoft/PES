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
	var _Y = null;
	var Plugin = null;
	var PM = SNAPPI.namespace('SNAPPI.PM');	// Yready init
	PM.onYready.Datasource = function(Y){
		if (_Y === null) _Y = Y;
		_Y.extend(SnappiXmlCatalog, SNAPPI.XmlDatasource);
    	PM.SnappiXmlCatalog = SnappiXmlCatalog;
    
    	PM.xmlArrangementParser_Snappi = xmlArrangementParser_Snappi;
	} 
	
	/*
	 * protected
	 */
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
    
})();
