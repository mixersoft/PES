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
 * Bind extended attrs to DOM elements via javascript
 *
 *
 */
(function(){
    /*
     * protected
     */
    var defaultCfg = {
        lookAhead: 1 // don't lookahead for now
    };
    // find only visible elements
    // deprecate, moved to utils
    var isVisible = function(n){
        return !(n.hasClass('hidden') || n.hasClass('hide'));
    };
    
    var Y = SNAPPI.Y;
    
    var DomJsBinder = function(cfg){};
    
    DomJsBinder.prototype = {
        _cfg: null,
        init: function(cfg){
            this._cfg = Y.merge(defaultCfg, cfg);
        },
        
        bindSelected2Page : function(gallery, selected, oldUuid) {
    		selected = selected || gallery.getFocus();
    		if (!selected.id) selected = SNAPPI.Auditions.get(selected);
    		var newUuid = selected.id;
    		// this instanceof Gallery
        	/*
        	 * update div#hiddenshots, from filmstrip
        	 */
        	SNAPPI.Factory.Thumbnail.PhotoPreview.bindSelected(selected);
        	// SNAPPI.domJsBinder.bindSelected2Preview.call(gallery, selected);
        	
        	var shotGallery = SNAPPI.Gallery.find['shot-'];
        	if (shotGallery) {
        		if (selected.Audition.Shot.id) {
        			shotGallery.showShotGallery(selected);
	        	} else {
	        		shotGallery.container.all('.FigureBox').addClass('hide');
	        	}
        	}
        	
        	
        	
    		return;


// SNAPPI.domJsBinder.bindSelectedToSubstitutes.call(this, selected);
        	
    		
    		// update all page xhr xhr-gets by uuid substitution in ajaxSrc
    		var fragments = Y.all('.xhr-get');
    		if (fragments) {
    			fragments.each(function(n,i,l) {
        			var xhrSrc = n.getAttribute('ajaxsrc');
        			// var newSrc = xhrSrc.replace(/\/([0-9a-f\-]{36})(?=\W)/i, '/'+cfg.uuid);
	    			n.setAttribute('ajaxsrc', xhrSrc.replace(oldUuid, newUuid));
	    			SNAPPI.xhrFetch.fetchXhr(n);
    			});
    		}
    		
    		// update all <A>, <INPUT>, <IMG> elements
    		Y.all('#content a').each(function(n,i,l) {
    			var oldHref = n.getAttribute('href');
    			if (oldHref.match(oldUuid)) {
        			n.setAttribute('href', oldHref.replace(oldUuid, newUuid));
    			}
    		});
    		var newSrc = gallery.castingCall.schemaParser.getImgSrcBySize(selected.urlbase + selected.src, 'sq');
    		try {
        		Y.one('#content div div img').setAttribute('src', newSrc);
        		Y.one('#TagForeignKey').setAttribute('value', newUuid);
        		Y.one('#photos-home-rating').setAttribute('uuid', newUuid);
    		} catch (e) {}    		
    	},
        
        
        
        fetchCastingCall: function(cfg, callback){
            var ioCfg = {
	           	headers: {
	                'Content-Type': 'application/json'
	            },            		
                on: callback,
                context: this,
                arguments: {
                    ul: cfg.ul
                }
            };
			if (cfg.arguments) ioCfg.arguments = Y.merge(ioCfg.arguments, cfg.arguments);
            /*
             * fetch rows for current page
             */
			var page, perpage;
            if (!cfg.skipPaging) {
	            if (cfg.page) SNAPPI.STATE.displayPage.page = cfg.page;
	            if (cfg.perpage) SNAPPI.STATE.displayPage.perpage = cfg.perpage;
            } else {
            	var page = cfg.page || SNAPPI.STATE.displayPage.page;
                var perpage = cfg.perpage || SNAPPI.STATE.displayPage.perpage;
            }
            
            //            var last = perpage * (page - 1);
            //            last += cfg.lookAhead * perpage; // fetch 3 extra pages

            // add paging named params
            var paging = { page: page, perpage: perpage };
            var jsonSrc = window.location.href;
            jsonSrc = SNAPPI.IO.setNamedParams(jsonSrc, paging);
            jsonSrc = jsonSrc.replace('home', 'photos')+'/.json';
            
            /*
             * get jsonSrc to match page request
             */
            SNAPPI.io.get.call(this, jsonSrc, callback);
        },
        // TODO: do NOT parse into global
        parseAuditions: function(providerName, castingCall, parent){
        	parent = parent || SNAPPI;	// SNAPPI is legacy global. deprecate
        	parent.DATASOURCE = castingCall;
        	parent.DATASOURCE.host = providerName;        	
            /*
             * configure DATASOURCE, and schemaParser
             */
            switch (providerName) {
                case 'snappi':
                	// from gallery/js/datasource3.js, in SNAPPI
                	parent.DATASOURCE.schemaParser = SNAPPI.AuditionParser_Snappi;
                    var parsed = parent.DATASOURCE.schemaParser.parse(parent.DATASOURCE);
                    parent.DATASOURCE.parsedResults = parsed.results;
                    // SNAPPI.Auditions._auditionSH is a global/merged sortedhash for all auditions on page.                    
                    
                    if (SNAPPI.STATE.displayPage) {
                    	SNAPPI.STATE.displayPage.total = parent.DATASOURCE.CastingCall.Auditions.Total;
//                    	SNAPPI.STATE.displayPage.count = parent.DATASOURCE.CastingCall.Auditions.Total;
                    }
                    break;
                case 'flickr':
                    break;
                case 'picasaweb':
                    break;
            }
            return SNAPPI.Auditions._auditionSH;
        },
        
        /**
         * TODO: not sure this method is actually used, I think I use new Gallery() instead
         * bind auditionSH to rendered DOM LI thumbnail on page
         * @param node - should be section.gallery.photo
         * @param castingCall - contains output from SNAPPI.Auditions.parseCastingCall()
         * @param cfg - 
         * 
         */ 
        bind: function(node, castingCall, cfg){
        	var _cfg = {
        			ID_PREFIX: null,
        			size: 'sq',
        			start: null,
        			end: null
        	};
        	_cfg = Y.merge(_cfg, SNAPPI.STATE.displayPage, cfg);
        	
        	
            if (false) {
            	//TODO: assume CastingCall is sorted to make page settings??? 
            	castingCall.auditionSH.sort(SNAPPI.sortConfig.byTime);        	
            }
            
            var photoRoll = new SNAPPI.Gallery({
            	type: 'Photo',
                sh: castingCall.auditionSH,
                shots:  castingCall.shots,
                node: node
            });
            photoRoll.render(_cfg);
            if (!node.hasClass('filmstrip')) {
            	photoRoll.restoreState();	// photoRolls have state, but filmstrips do not?
            }
            
            Y.fire('snappi:afterPhotoRollInit', photoRoll.auditionSH);
            return photoRoll;
        }
    };
    
    /*
     * make global
     */
    SNAPPI.domJsBinder = new DomJsBinder();
})();
