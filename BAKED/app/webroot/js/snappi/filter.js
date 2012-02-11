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
 * Filter: filter component for filtering Gallery results
 *
 */
(function(){
    /*
     * local vars
     */
    var _Y = null;
    SNAPPI.namespace('SNAPPI.onYready');
    SNAPPI.onYready.Filter = function(Y){
		if (_Y === null) _Y = Y;
		SNAPPI.filter = new Filter();	// singleton
	}
    
    
    /*
     * class def
     */
    var Filter = function(cfg) {
    	if (Filter.instance) return Filter.instance;
    	Filter.instance = this;
    };

    /*
     * static methods
     */
    
    
    /*
     * prototype
     */
    Filter.prototype = {
    	container: null,
    	active : {},
    	renderRating : function(value) {
    		var parent = _Y.Node.create('<li></li>');
			this.initRating(parent, value);
			return parent;
    	},
    	/*
    	 * deprecate. use /photo/display-options.ctp
    	 */
    	renderBar : function(filters){
    		if (!this.container) {
    			// this.container = _Y.one('#page-filters');	
    			this.container = _Y.one('#display-option-sub');
    			if (!this.container) return;
    		}    		
    		if (!filters) {
    			// disable for manoj HTML
    			// this.container.one('ul').set('innerHTML','');	
    			return;
    		}
    		
    		var markup = '<li><span class="context"><span class="filter-bar"><span class="remove"> <a href="{removeHref}" title="click here to REMOVE this filter.">x</a></span> {labelClass} {labelLabel}</span></span></li>';
    		var markup_A = '<a href="{labelHref}">{labelLabel}</a>';
    		var markup_B = '<b>{labelLabel}</b>';
    		var output = [];
    		// if (filters.length) {
    			// this.container.one('ul').set('innerHTML','').append(this.container.create('<li>Filters: </li>'));
    		// }
    		for (var i in filters) {
    			var filter = filters[i];
    			if (!filter.label && filter.label !==0 ) continue;
    			var tokens = {
					labelClass: filter['classLabel'] || filter['class'],
					labelLabel: filter.label,
					labelHref: filter.labelHref,
					removeHref: filter.removeHref || '#'
    			};
    			if (filter['class'] == 'Rating') {
    				continue;
    				// var wrapper = this.renderRating(filter.value);
    				// tokens.labelLabel = wrapper.get('innerHTML');
    				// SNAPPI.filter.active.Rating = filter.value;
    			} else {
	    			if (filter.labelHref) tokens.labelLabel = _Y.substitute(markup_A, tokens);
	    			else tokens.labelLabel = _Y.substitute(markup_B, tokens);
	    			if (console) console.warn("deprecate SNAPPI.filter, use SNAPPI.STATE.filter instead");
	    			SNAPPI.filter.active[filter['class']] = filter;
    			}
    			var filterNode = this.container.create(_Y.substitute(markup, tokens));
    			this.container.one('ul').append(filterNode);
    		}
    	},
    	initRating: function(parent, value) {
    		try {
//     			value = value || SNAPPI.filter.active.Rating || 0;
    			if (!value) {
    				value = 0;
    				var filters = SNAPPI.STATE.filters;
    				for (var i in filters) {
    					if (filters[i]['class'] == 'Rating') {
    						value = parseInt(filters[i].value) || 0;
    					}
    				}
    			}
    		} catch (e) {
    			value = 0;
    		}
    		if (!parent) {
    			parent = _Y.one('#filter-rating-parent');
    		}
    		var cfg = {
				// el : parent.dom(),
				id : "filter-ratingGroup",
				v : value,
				'setDbValueFn': SNAPPI.filter.byRating
    		}
    		return SNAPPI.Rating.attach(parent, cfg);    			
    	},
    	byRating: function(v, target, e) {
    		if (e.ctrlKey) v = 0;	// ctrl-click of ratingGroup == 0
    		SNAPPI.filter.active.Rating = v;
    		/*
    		 * BUG: have to move ?q=79 into named params 
    		 */
    		
    		var next = SNAPPI.IO.setNamedParams(window.location.href, {
    			rating: v,
    			page: null,
    			perpage: null,
    		});
    		window.location.href = next;
    	},    	
    	
    	/*
    	 * XHR load into #paging-photos
    	 * @deprecated
    	 */    	
    	byRatingXhr: function(v, target) {
    		if (console) console.warn("SNAPPI.filter.byRatingXhr should be deprecated");
    		return;
    		
    		// SNAPPI.filter.active.Rating = v;
    		// var parent = _Y.one('#'+target).ancestor('div#paging-photos-inner').get('parentNode');
    		// SNAPPI.filter.container = parent;
    		// SNAPPI.filter.fetchFilteredXhr.call(SNAPPI.filter);	// set this context 
//     		
    		// // set state to ShowRatings
    		// var pr = parent.one('section.gallery.photo').Gallery;
    		// pr.toggleRatings(parent.one('li#show-ratings'), 'show');
    	},
    	fetchFilteredXhr: function(cfg, target){
    		cfg = cfg || this.active;
    		var uri = PAGE.jsonData.castingCall.CastingCall.Request;
//    		if (!/my\/photos/.test(uri)) return;
    		var callback = {
    			complete: function(id, o ,args) {
    				// expect return in XHR or json format?
    				if (o.status == '200') {
    		            var data = o.responseText; // Response data.
    		            
    		            /*
    		             * this code needs to be generalized
    		             */
    		            var target = args.self.container;
    		            var pagingControls = target.one('div.paging-sort');
    		            // note: we lose the paging-controls if we replace innerHTML, 
    		            // so keep a copy
    		            var node = target.set('innerHTML', data);
    		            target.prepend(pagingControls);
    		            
    		            SNAPPI.xhrFetch.xhrInit(node); // execute js in ajax markup
    		            _Y.fire('snappi:ajaxLoad'); // execute js in script files
    		            /*
    		             * end XHR page update
    		             */
    				}
    			}
    		};
    		SNAPPI.io.get.call(this, uri, callback, null, cfg, {self: this});
    	}
    };
})();