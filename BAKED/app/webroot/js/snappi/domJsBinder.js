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
    
    var DomJsBinder = function(cfg){
    	/*
    	 * /photos/home preview image
    	 */
    	this.bindSelected2Preview = function(selected) {
    		var Y = SNAPPI.Y;
    		var filmstrip;
    		if (this instanceof SNAPPI.Gallery) filmstrip = this;
    		else filmstrip = Y.one('section#nav-filmstrip .gallery.photo.filmstrip').Gallery;
//    		var castingCall = PAGE.jsonData.castingCall;
    		var castingCall = filmstrip.castingCall;
    		var parent = Y.one('#preview');
    		if (!parent) return;
    		var uuid, size, auditionSH;
    		size = parent.getAttribute('size');
    		if (selected)  {
    			parent.setAttribute('uuid', selected.id);
    			uuid = selected.id;
    		} else {
	    		uuid  = parent.getAttribute('uuid');
    		}
            // get auditions from global or raw json castingCall
    		if (filmstrip.auditionSH) {
    			selected = filmstrip.auditionSH.get(uuid);
    		} else {
    			// this should already have been parsed in bindAuditions2Filmstrip()
	            var providerName = filmstrip._cfg.PROVIDER_NAME;
	            filmstrip.auditionSH = SNAPPI.Auditions.parseCastingCall(castingCall, providerName);
	            selected = filmstrip.auditionSH.get(uuid);
    		}
            
            // set rating in ul.photo-header
            var rating = Y.one('div#photos-home-rating');
            if (rating) {
            	// update rating component
            	SNAPPI.Rating.attach(rating, {
            		id:'photos-home-ratingGroup', 
            		uuid: uuid, 
            		v: selected.rating
            		});
            	// update selected.bindTo
            	if (rating.audition) {
            		// unbind old audition
            		rating.audition.bindTo.splice(rating.audition.bindTo.indexOf(rating), 1);
            	}
            	rating.audition = selected;
            	selected.bindTo.push(rating);
            } else {
            	// init Rating in div.photo-header
            	var n = Y.one('#set-rating');
            	try {
            		var cfg = {
            				id : "photos-home-rating",
            				v : selected.rating,
            				uuid: selected.id,
            				'setDbValueFn': SNAPPI.AssetRatingController.postRating,
            				listen: true
            		};
            		SNAPPI.Rating.attach(n, cfg);
                	n.Rating.node.audition = selected;
                	selected.bindTo.push(n.Rating.node);            		
            	} catch(e) {
            	}
            	n.removeClass('hide');            	
            }
            
            // set size buttons
            try {
            	var ccid = (filmstrip.auditionSH.count() > 1) ? castingCall.CastingCall.ID : null;
            } catch (e) {}
    		var href, j=0, sizes = [{ key: 'sq', label: 'Square' }, 
    		                        { key: 'tn', label: 'Thumbnail' }, 
    		                        { key: 'bs', label: 'Small' }, 
    		                        { key: 'bm', label: 'Medium' }, 
    		                        { key: 'bp', label: 'Preview' }];
    		parent.all('ul.sizes > li.button').each(function(n,i,l){
    			// reuse existing buttons
    			href = '/photos/home/'+uuid+'/size:'+sizes[i].key;
    			if (ccid) href += '?ccid='+ccid;
    			n.one('a').set('href', href);
    			j=i+1;
    		});
    		var ul = parent.one('ul.sizes ');
    		for (j; j<sizes.length; j++) {
    			// create buttons if missing
    			href = '/photos/home/'+uuid+'/size:'+sizes[j].key;
    			if (ccid) href += '?ccid='+ccid;
    			ul.append(ul.create('<li class="button"><a href="'+href+'" onclick="SNAPPI.helper.Session.savePreviewSize(\'' + sizes[j].key + '\')">'+sizes[j].label+'</a></li>'));
    		}
            
            
            // set preview image
    		try{
    			var previewSize = PAGE.jsonData.profile.previewSize;
        		if(previewSize){
        			size = previewSize;
        		}
    		}catch(e){}
    		
    		var src = selected.getImgSrcBySize(selected.urlbase + selected.src, size);
    		var preview = parent.one('div.preview');
    		var previewImg = preview.one('img');
    		if (!previewImg) {
    			// create preview img
    			var previewImg = parent.create('<img src="" title="'+selected.label+'">'); 
    			preview.append(previewImg);
    			// plugin loadingmask
    			if (!preview.loadingmask) {
    				preview.plug(Y.LoadingMask, {
    					strings: {loading:''}, 	// BUG: A.LoadingMask
    					target: previewImg,
    					end: null
    				});
    				// BUG: A.LoadingMask does not set target properly
    				preview.loadingmask._conf.data.value['target'] = previewImg;
    				preview.loadingmask.overlayMask._conf.data.value['target'] = preview.loadingmask._conf.data.value['target'];
    			}    			
    			if (!preview.listen) preview.listen = {};
    			preview.listen['imgOnLoad'] = previewImg.on('load', function(e,i,o){
    				preview.loadingmask.hide();
    			});
    		}
    		preview.loadingmask.show();  
    		previewImg.set('src', src).set('title', selected.label);
    		// set Details

    	};
    	
    };
    
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
        	SNAPPI.domJsBinder.bindSelected2Preview.call(gallery, selected);
        	
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
        	
    		
    		// update all page xhr fragments by uuid substitution in ajaxSrc
    		var fragments = Y.all('div.fragment');
    		if (fragments) {
    			fragments.each(function(n,i,l) {
        			var xhrSrc = n.getAttribute('ajaxsrc');
        			// var newSrc = xhrSrc.replace(/\/([0-9a-f\-]{36})(?=\W)/i, '/'+cfg.uuid);
	    			n.setAttribute('ajaxsrc', xhrSrc.replace(oldUuid, newUuid));
	    			SNAPPI.ajax.fetchXhr(n);
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
