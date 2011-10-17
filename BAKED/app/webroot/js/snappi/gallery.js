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
 * Gallery - render/reuse thumbnails from JSON
 *
 */
(function(){
	var Factory = SNAPPI.Factory.Gallery;
    /*
     * dependencies
     */
    var charCode = {
        nextPatt: /(^110$)|(^39$)|(^32$)|(^54$)/, // n,right,space,
        // keypad right
        prevPatt: /(^112$)|(^37$)|(^8$)|(^52$)/, // p,left,backspace,
        // keypad left
        closePatt: /(^27$)/,
        // escape
        selectAllPatt: /(^65$)|(^97$)/,
        // ctrl-a		
        groupPatt: /(^103$)|(^71$)/,
        // ctrl-g/G		
        	
        downPatt : /(^40)/,
        // keypad down
        upPatt : /(^38)/
        // kepad up
    };
    // find only visible elements
//    var isVisible = function(n){
//    	var not_display = (n.getComputedStyle('display') == "none") ? true : false;
//    	var invisible = (n.getComputedStyle('visibility') == "hidden") ? true : false;
//    	var eleInvisible = (not_display || invisible);
//    	
//        return !(n.hasClass('hidden') || n.hasClass('hide')  || eleInvisible);
//    };
    var Y = SNAPPI.Y;
    
    var Gallery = function(cfg){
    	cfg = cfg || {type: 'Photo'};
    	Factory[cfg.type].build(this, cfg);
    	return this;
    };
    
    Gallery.find = {}; 			// gallery lookup
    
    Gallery.getFromDom = function(dom) {
    	try {
    		return dom.ynode().ancestor('section.gallery.photo').Gallery;
    	} catch(e) {
    	}
    	try {
    		return dom.Gallery;
    	} catch(e) {
    	}    	
    };
    
    Gallery.filmstrip_getFromDom = function(dom) {
    	try {
    		return dom.ynode().ancestor('div.filmstrip .container').Gallery;
    	} catch(e) {
    		return null;
    	}
    };    
    Gallery.getFromChild = function(target){
		var g = false; 
		var n = target.ancestor(
				function(n){
					g = n.Gallery || n.Gallery ||  (n.Lightbox && n.Lightbox.Gallery) || null; 
					return g;
				}, true );
		return g;
	};	
    /**
     * DEPRECATE???
     * @param selected audition- selected audition containing SubstitutionGroup
     * @param ul - container for displaying SubstitutionGroup
     * @param shotType - [Usershot | Groupshot]
     * @return
     */
    Gallery.renderSubstituteAsPreview = function(selected, shotType, ul) {
    	this.showHiddenShotsAsPreview(selected, shotType, ul);
    };
    

    // // private helper function (closure)
	// /*
	 // * render all shots, including hidden, into a "preview" photoRoll on /photos/home page
	 // * - depending on how castingCall was created, we either 
	 // * 	1) render directly from castingCall, or 
	 // * 	2) if shot.stale==true, use XHR to get substitutionGroup castingCall, then render (async)
	 // * 
	 // */ 
	// var _showHiddenShots = function(node, shot, selected) {
		// var HIDDENSHOT_PREVIEW_LIMIT = 40;	// substitute roll preview LIMIT
		// if (shot) {
            // if (!node && /photos\/home/.test(window.location.href)) {
            	// // filmstrip not found,  build filmstrip
            	// // TODO: move to a better place. we cannot assume we are on the /photos/home page here
            	// var parent = SNAPPI.Y.one('div#hiddenshots');
            	// // node = parent.create('<section.gallery.photo></section>'); // class='filmstrip' or 'photo-roll'??
                // // parent.append(node);
            // }                
// 
            // // TODO: should add a more... link to see paging view of shot
//             
            // var total = shot.count();
//             
			// var oneShotCfg = {};
			// oneShotCfg[shot.id] = shot;		// format for Gallery constructor
			// var showHiddenShotsCfg = {
				// type : 'Photo',
				// ID_PREFIX : 'hiddenshot-',
				// size : 'lm',
				// selected : 1,
				// start : 0,
				// end : Math.min(HIDDENSHOT_PREVIEW_LIMIT, total),
				// total : total,
				// uri : null,
            	// node: node,
            	// sh: shot._sh,
            	// castingCall: {
					// providerName: this.castingCall.providerName,
					// schemaParser: this.castingCall.schemaParser
				// },
            	// shots: oneShotCfg					
			// };            
//             
            // /**
             // * NEW codepath to create Gallery from castingCall
             // */
           // var shotPhotoRoll = new SNAPPI.Gallery(showHiddenShotsCfg);	
           // shotPhotoRoll.node.addClass('hiddenshots').removeClass('container_16');
           // shotPhotoRoll.container.removeClass('grid_16');
           // shotPhotoRoll.listen(true, ['Keypress', 'Mouseover', 'Click', 'MultiSelect', 'Contextmenu']);
// 			
           // shotPhotoRoll.setFocus(shot.indexOfBest());  // show best pic
           // shotPhotoRoll.selected = selected;
           // // add shot to selected
           // selected.Audition.SubstitutionREF = shot.id;
           // selected.Audition.Substitutions = shot;
           // selected.substitutes = shot; // DEPRECATE
           // // skip this line
//            
           // // manually show Ratings for Duplicates/shot
           // shotPhotoRoll.showThumbnailRatings();
// 
        // } else {	// no shots returned
        	// // hide/unbind everything
	        // try {
	        	// // no shots, just hide div#hiddenshots and unbind nodes
	        	// if (node.ancestor('div#hiddenshots')) { 
	        		// // hide substitute group, if we are on the /photos/home page
	        		// node.all('li').addClass('hide').each(function(n,i,l){
		        		// SNAPPI.Auditions.unbind(n);
	        		// });
	        	// } else if (node.ancestor('div#snappi-dialog')) { 
	        		// // hide/unbind shots on selected blur?
	        		// // detach contextMenu listener
	        	// } 
	        // } catch (e) {}	        	
        // }
        // // skip this line
		// SNAPPI.Y.fire('snappi:hideLoadingMask', node);
		// return shotPhotoRoll;
	// };	// end _showHiddenShots()    
	
	
	
    Gallery.prototype = {
    	init: function(cfg) {
    		var Y = SNAPPI.Y;
	    	var _cfg = Y.merge(cfg);		// copy cfg
	    	
	    	// skip this line
	        if (_cfg.sh) {	// resuse SH, if provided
	            this.auditionSH = _cfg.sh;
	        }	        
	        if (_cfg.castingCall) {
	            this.castingCall = _cfg.castingCall;
	            delete _cfg.castingCall;
		        // reuses sh if available, or creates a new one from parsed castingCall
	        	if (_cfg.sh) {	// reusing provided sh
	        		delete _cfg.sh;
	        	} else { 		// build auditionSH from castingCall
	        		this.auditionSH = SNAPPI.Auditions.parseCastingCall(this.castingCall, this.providerName, this.auditionSH);
	        	}
	        }
	        if (!this.auditionSH) this.auditionSH = new SNAPPI.SortedHash();
	        
	        if (_cfg.shots) {
	            this.shots = _cfg.shots;
	            delete _cfg.shots;
	        } else if (this.castingCall && this.castingCall.shots) {
	        	this.shots =  this.castingCall.shots;
	        }
			
			var renderOnInit = _cfg.render === false ? false : true;
			delete _cfg.render;
			
	        this._cfg = _cfg;
            if (this.header) {
            	// update thumbnailSize
            	this.header.all('.window-options .thumb-size > li.btn').each(
            		function(n,i,l){
            			var action = n.getAttribute('action');
            			if (action.indexOf(this._cfg.size)>-1) n.addClass('focus');
            			else n.removeClass('focus'); 
            		}, this
            	);
            }	        
	        /**
	         * sort auditions, or are they pre-sorted?
	         */
	        if (false) {
	        	//TODO: assume CastingCall is sorted to make page settings??? 
	            this.auditionSH.sort(SNAPPI.sortConfig.byTime);        	
	        }
	        
	        switch (_cfg.ID_PREFIX) {
	        case 'uuid-':
	        	if (renderOnInit) this.render(_cfg); 
	        	var paging = SNAPPI.Paginator.paginate_PhotoGallery(this);
	        	if (paging === false) {
	        		// add view All
	        		this.add_ViewAll();
	        	}
	        	this.restoreState();	// photoRolls have state, but filmstrips do not.
	        	var selector = '.FigureBox > figure > img';
	        	break;	        	
	        case 'lightbox-':
	        	this.container.addClass('one-row'); // these are all filmstrips, init in filmstrip mode
	        	if (renderOnInit) this.render(_cfg);
	        	// this.scrollFocus( cfg.uuid );
	        	this.container.ancestor('.filmstrip-wrap').removeClass('hidden');
	        	break;
	        case 'nav-': 		
	        case 'shot-':
	        case 'hiddenshot-':
	        	// TODO: set one-row from session, profile
	        	this.container.addClass('one-row'); // these are all filmstrips, init in filmstrip mode
	        	if (renderOnInit) this.render(_cfg);
				this.scrollFocus( cfg.uuid );
				this.container.ancestor('.filmstrip-wrap').removeClass('hidden');
	        	break;
	        default:
	        	break;
	        }
	        if (this._cfg.listeners) this.listen(true, this._cfg.listeners);
	        if (_cfg.draggable) SNAPPI.DragDrop.pluginDelegatedDrag(this.container, 'img.drag');
	        if (_cfg.droppable) SNAPPI.DragDrop.pluginDrop(this.node);
    	},
        setAudition: function(sh){
            this.auditionSH = sh;
        },
        /*
         *  re-render existing gallery to a new thumbSize;
         */ 
        renderThumbSize: function(thumbSize) {
        	var selected = this.auditionSH.getFocus();
        	if (this._cfg.size != thumbSize) {
	        	this._cfg.size = thumbSize;	// Gallery._cfg.size
			
				var isFilmstrip = this.container.ancestor('.filmstrip-wrap');
				var isHidden = (isFilmstrip && isFilmstrip.hasClass('hidden'));
	    		// rehide .FigureBoxes in hidden filmstrips
	        	this.container.all('.FigureBox').each(function(n,i,l){
	        		n.Thumbnail.resize(thumbSize);
	        		if (isHidden) n.addClass('hide');
	        		if (n.audition == selected) n.addClass('focus');
	        	});
        	}
        }, 
        render: function(cfg, shot){
        	cfg = cfg || {};
        	if (cfg.render === 'false') return;
        	
        	if (cfg.ID_PREFIX !== undefined && !cfg.ID_PREFIX) {
        		this._cfg.ID_PREFIX = cfg.ID_PREFIX;
        		delete (cfg.ID_PREFIX);
        	}
        	if (cfg.castingCall) {
        		this.castingCall = cfg.castingCall;
        		if (!this.castingCall.auditionSH) {
        			var onDuplicate = cfg.replace ? SNAPPI.Auditions.onDuplicate_REPLACE : SNAPPI.Auditions.onDuplicate_ORIGINAL; 
					var sh = SNAPPI.Auditions.parseCastingCall(this.castingCall, this._cfg.PROVIDER_NAME, null, onDuplicate);
        		}
        		this.auditionSH = this.castingCall.auditionSH;
        		delete cfg.castingCall;
        		delete cfg.sh;	// cfg.castingCall takes priority over cfg.sh
        	}             	
        	if (cfg.sh) {
        		this.auditionSH = cfg.sh;
        		delete (cfg.sh);
        	}
        	if (shot && shot._sh) {
        		this.auditionSH = shot._sh;	// shot override
        		this.Shot = shot;
        	}
        	
        	var focusUuid = null;
        	if (cfg.uuid || cfg.selected) {
        		focusUuid = cfg.uuid || cfg.selected;
        		this.auditionSH.setFocus(focusUuid);
//        		delete cfg.uuid;	// TODO: filmstrip doesn't work unless we update this._cfg.uuid
        	} else focusUuid = this.auditionSH.getFocus();
        	if (cfg) this._cfg = Y.merge(this._cfg, cfg);
            
            var offset = 0;
            if (!this.auditionSH) 
                return;
            
            if (!this.container) 
                return;
            var offset, page, perpage, ccAuditions, nlist;
            try {
            	ccAuditions = this.castingCall.CastingCall.Auditions;
            } catch (e) {
            	ccAuditions = {
            		Page: 1,
            		Perpage: this.auditionSH.count(),
            		Total: this.auditionSH.count(),
            	}
            }
            
            nlist = this.container.all('.FigureBox');
			// check for strange TextNodes, only allow .FigureBox childNodes
            if (nlist.size() < this.container.get('childNodes').size()) {
            	this.container.setContent(nlist);
            }
            
            
            switch(this._cfg.ID_PREFIX) {
	            case 'lightbox-':
            		// use the existing number of .FigureBoxs
	                perpage =  this._cfg.perpage || this.auditionSH.size();
	                page = page ||  1;
	                offset = (page - 1) * perpage;
	            	break;
	            case 'hiddenshot-':
	            case 'shot-':
	            case 'nav-':
	            	this._cfg.perpage = ccAuditions.Perpage || ccAuditions.Total;
	            case 'uuid-':
            	default:
                	// calculate offset of requested page from Gallery.castingCall.Auditions.Page
                	// cfg.page = SNAPPI.STATE.displayPage.page, or $this->passedArgs['page']
                	// ccOffset = (ccPage-1)*ccPerpage            		
                	var ccPage = ccAuditions.Page;
	            	var ccOffset = (ccPage-1) * ccAuditions.Perpage;
	            	var displayPage = this._cfg.page;
	            	var displayOffset = (displayPage-1)*this._cfg.perpage;
	            	offset = displayOffset - ccOffset;
	                perpage = this._cfg.perpage;            
	                this._cfg.start = 0;
	                this._cfg.end = perpage;
            		break;
            };

            /*
             * reuse or create LIs
             */
            var thumbCfg = {};
            // if (this.node.hasClass('hiddenshots')) thumbCfg = {	showHiddenShot : false	}
            if (nlist.size()) {
                // if .FigureBox exist, reuse
                nlist.removeClass('focus');
            	nlist.each(function(n, i, l){
            		if (i >= perpage  ) {
            			// hide extras, unbind and remove extras
            			n.Thumbnail.remove();
            			// n.addClass('hide');
            		} else if (Y.Lang.isNumber(this._cfg.start) && this._cfg.end) {
						var audition = this.auditionSH.get(offset+i);
						if (audition && offset+i < this._cfg.end) { 
						    this.reuseThumbnail(audition, n, thumbCfg);
						    if (audition.id == focusUuid) n.addClass('focus');
						} else {
							// n.addClass('hide');
							n.Thumbnail.remove();
						}
            		}
                }, this);
            }
            // otherwise create new LIs, or if there is not enough
            var li, audition, i = offset + nlist.size(), limit = offset + perpage;
            var lastLI;
            
            while (i < limit) {
                audition = this.auditionSH.get(i++);
                if (audition == null) 
                    break;
                li = this.createThumbnail(audition, thumbCfg);
                if (audition.id == focusUuid) li.addClass('focus');

                lastLI = li;
            }
            
            if (this._cfg.hideHiddenShotByCSS) {
	            try {	// add CSS for shotsSH
	            	var shot, processedShots = {};
		            this.auditionSH.each(function(a){
		            	// check each PR audition for shots, and process if necessary
		            	shot = a.Audition.Substitutions;
		            	if (shot && !processedShots[shot.id]) {
		            		this.applyShotCSS(shot);
		            		processedShots[shot.id] = 1;
		            	}
		            }, this);
	            } catch (e) {}
            }
            this.updateCount();
            if (this.container.hasClass('one-row')) this.setFilmstripWidth();
            return lastLI;
        },
        /**
         * add auditions to photoroll from raw castingCall. usually adding hiddenShots
         * @param castingCall json, raw castingCall from XHR
         * @param replace, boolean, default true
         * @param sort, {} default SNAPPI.sortConfig.byTime
         * @return
         */
        addFromCastingCall: function(castingCall, replace, sort){
			if (replace !== false) replace = function(a,b){return b;};
			sort = sort || SNAPPI.sortConfig.byTime;
			var hiddenShots = SNAPPI.Auditions.parseCastingCall(castingCall, null , null, replace);
			hiddenShots.each(function(o){
				 this.auditionSH.add(o);
			}, this);
			this.auditionSH.sort(sort);        	
        },
        bindLI: function(li, audition){
        	console.warn("photoroll.bindLI has been deprecated");
        	// deprecated
        },       
        trimLabel: function(label, length) {
        	length = length || 15;
        	if (label.length > length) {
        		if (label.length < length-3) {
        			return label.substr(0,length);
        		} else return label.substr(0,length-3)+'...';
        	} else return label;
        }, 
        reuseThumbnail: function(audition, node, cfg){
        	node.Thumbnail.reuse(audition, node, cfg);
        },
        createThumbnail: function(audition, cfg){
        	// TODO: do NOT copy ALL cfg attrs to thumbnail. figure out which ones we need
        	cfg = SNAPPI.Y.merge(this._cfg, cfg);	// copy
        	cfg.gallery = this;
        	cfg.type = cfg.tnType || cfg.type;
        	var t = new SNAPPI.Thumbnail(audition, cfg);
        	this.container.append(t.node);
        	return t.node;
        },
        listen: function(status, cfg){
        	if (this.node.listen == undefined) this.node.listen = {};
            status = (status == undefined) ? true : status;
            var k,v,handler, fn;
            if (status) {
            	cfg = cfg || ['Keypress', 'Mouseover', 'Click', 'WindowOptionClick', 'MultiSelect', 'Contextmenu', 'FsClick'];
            	for ( k in cfg){
            		try {
            			Factory.listeners[cfg[k]].call(this); 
            		} catch(e) {}
            	}
            }
            else {
            	// do we still need status==false to detach?
            	cfg = cfg || this.node.listen;
                for (k in cfg) {
                	v = cfg[k];
                	if (v == 'MultiSelect') {
                		SNAPPI.multiSelect.listen(this.container, false);
                	} else {
	                    this.node.listen[v].detach();
	                    delete (this.node.listen[v]);
                	}
                }
            }
        },


        toggle_ContextMenu : function(e) {
	        e.preventDefault();
        	
        	var CSS_ID, TRIGGER;
        	switch(this._cfg.type){
        		case 'Photo':
        		case 'NavFilmstrip': 
        			CSS_ID = 'contextmenu-photoroll-markup';
        			break;
        		case 'DialogHiddenShot': 
        		case 'ShotGallery': 
        			CSS_ID = 'contextmenu-hiddenshot-markup';	
        			break;
				default:
					return;        			
        	}
        	if (this.node.hasClass('hiddenshots') || this.node.hasClass('hidden-shot')) {
        		CSS_ID = 'contextmenu-hiddenshot-markup';
        	} 
        	
        	// load/toggle contextmenu
        	if (!SNAPPI.MenuAUI.find[CSS_ID]) {
        		SNAPPI.MenuAUI.CFG[CSS_ID].load({
        			currentTarget:e.currentTarget,
        			triggerRoot: this.container
				});
        		this.stopClickListener();
        	} else {
        		var menu = SNAPPI.MenuAUI.toggleEnabled(CSS_ID, e);
        		if (this._cfg.listeners.indexOf('Click')> -1) {
        			// toggle Click listener
	        		if (menu.get('disabled')) {
	        			// TODO: nav to attribute "linkTo"
	        			Factory.listeners.Click();
	        		} else {
	        			// TODO: ignore "linkTo" click
	        			this.stopClickListener();
	        		}
        		}
        	}
        },
        stopClickListener : function(){
        	if(this.node.listen.click != undefined){
        		this.node.listen.click.detach();
        	}
        },
        listenKeypress: function(){
            if (this.node.listen['Keypress'] == undefined) {
            	var startListening = function() {
            		if (!this.node.listen['Keypress']) {
            			this.node.listen['Keypress'] = Y.on('keypress', this.handleKeypress, document, this);
//            			this.node.listen.keypress = Y.on('key', this.handleKeypress, document, this);
            		}
            	};
            	var stopListening = function() {
            		if (this.node.listen['Keypress']) { 
            			this.node.listen['Keypress'].detach();
            			delete this.node.listen['Keypress'];
            			// hide focus
            			this.container.all('li.focus').removeClass('focus');
            		}
            	}; 
            	this.container.on('snappi:hover', startListening, stopListening, this);
            }
        },
        /*
         * Key press functionality of next & previous buttons
         */
        handleKeypress: function(e){
        	var charStr = e.charCode + '';
            if (e.ctrlKey) {
            	// selectAll
                if (charStr.search(charCode.selectAllPatt) == 0) {
                    e.preventDefault();
                    this.selectAll();
                    return;
                }
                // group
                if (charStr.search(charCode.groupPatt) == 0) {
                    e.preventDefault();
                    this.groupAsShot();
                    return;
                }
            }
            
			// key navigation for photoRoll
            // set focus, if not set
        	if (this.container.one('li.focus') == null ) {
				var i = this.auditionSH.indexOf(this.auditionSH.getFocus());
				this.setFocus(i);
				return;
        	}
            if (charStr.search(charCode.nextPatt) == 0) {
                e.preventDefault();
                this.next();
                return;
            }
            if (charStr.search(charCode.prevPatt) == 0) {
                e.preventDefault();
                this.prev();
                return;
            }
            if (charStr.search(charCode.downPatt) == 0) {
            	e.preventDefault();
            	this.down();
            }
            if (charStr.search(charCode.upPatt) == 0) {
            	e.preventDefault();
            	this.up();
            }
        },
        // restore state from SNAPPI.STATE
        // TODO: should get state from cakephp Session / user profile
        restoreState : function(){
            try {
                if (SNAPPI.STATE.showRatings && SNAPPI.Y.one('li#show-ratings')) {
                	var v = SNAPPI.STATE.showRatings || 'hide';
                    this.toggleRatings(SNAPPI.Y.one('li#show-ratings'), v);
                }
                if (SNAPPI.STATE.selectAllPages && SNAPPI.Y.one('li#select-all-pages')) {
                    this.applySelectAllPages();
                }
                if (SNAPPI.STATE.showSubstitutes && SNAPPI.Y.one('li#show-substitutes')) {
                    this.applyShowSubstitutes();
                }
            } 
            catch (e) {
            }
            // check for any active filters, i.e. SNAPPI.filter.active
            // TODO: standardize method for restoring state to page/DOM elements
            /*
             * NOTE: context state is currently rendered in cakephp view
             *  filter.rating state set in 
             *  	a) SNAPPI.filter.active.Rating (?), or 
             *  	b) named param /rating:X  (which is better?)
             *   
             */

        },
        
        
        
        /*
         * focus, keyboard methods
         */
        getUuid: function(n){
        	var id = n.get('id');
        	var uuid = this._cfg.ID_PREFIX ? id.replace(this._cfg.ID_PREFIX, '') : id;
        	return uuid;
        },
        /**
         * set focus in DOM object
         * - NOT the same as auditionSH.getFocus()
         * @param m mixed, .FigureBox Node, audition, UUID, or index
         * @return
         */
        setFocus: function(m){
        	var Y = SNAPPI.Y;
        	var focusNode, o;
        	if (m instanceof Y.Node && m.hasClass('.FigureBox')) {
				o = m.audition;  
        		focusNode = m;
        	} else if (m && m.id) {
        		o = m;
      		} else if (typeof m == 'string') {
        		o = this.auditionSH.get(m);        		
        	} else if (typeof m == 'number') {
        		m = parseInt(m);
        		o = this.auditionSH.get(m);
        	} else {
        		return;
        	};
        	if (o) this.auditionSH.setFocus(o);
        	if (!focusNode) {
        		focusNode = this.container.one('#'+this._cfg.ID_PREFIX+o.id);
        	}
            if (focusNode) {
	            this.container.all('.FigureBox.focus').removeClass('focus');
                focusNode.addClass('focus');
            }
            return this;
        },
        /*
         * set filmstrip to 1 row tall with HScroll
         * 	NOTE: calls Gallery.setFocus() after resizing width
         */
        setFilmstripWidth: function() {
        	try {
	        	var wrapper = this.container.ancestor('.filmstrip');
	        	var thumb = this.container.one('.FigureBox');
	        	var count = this.auditionSH.count();
	        	count -= this.container.all('.hiddenshot-hide').size(); 
	        	var lookupWidth = {
	        		'sq':81,
	        		'tn':151,
	        		'lm':151,
	        	}
	        	var width = lookupWidth[thumb.Thumbnail._cfg.size];
	        	var newWidth = Math.max(width*count, wrapper.get('clientWidth'));
	        	var oldWidth = this.container.get('clientWidth');
	        	var setWidth = function(w, g) {
	        		g.container.setStyles({
						width: (w)+'px',	
						height: 'auto',
					}); 
	        		g.scrollFocus();	
	        	}
	        	if (oldWidth > newWidth) {
	        		// use delay to avoid flash when narrowing 
					var delay = new Y.DelayedTask( 
						function() {
							setWidth(newWidth, this);  
						}, this);
					delay.delay(100);	        		
	        	} else {
	        		setWidth(newWidth, this);
	        	}
	        	return;
			} catch (e) {}	
        },
        /*
         * scroll ".gallery .filmstrip" to show focus in center
         * @params m mixed, Y.Node (.FigureBox), uuid, or index
         */
        scrollFocus: function(m) {
        	var i, thumbs, selected, parent = this.container.ancestor('.filmstrip');
        	try {
        		if (m instanceof SNAPPI.Y.Node && m.hasClass('.FigureBox')) {
        			thumbs = this.container.all('.FigureBox');
        			i = thumbs.indexOf(m);
        		} else if (m && m.id) {
        			i = this.auditionSH.indexOf(m);        			
        		} else if (typeof m == "string") {
        			i = this.auditionSH.indexOfKey(m);
        		} else if (typeof i == 'number') {
					i = m;	        		
	        	}
				if (typeof i == 'number') {
					selected = this.auditionSH.setFocus(i);
	        	}
	        	var offset = 0, focus = this.auditionSH.getFocus();
	        	i = this.auditionSH.indexOf(focus);  // use index of ACTUAL focus
	        	// TODO: adjust for hiddenshot-hide, or remove hidden Thumbnails
	        	// move to end doesn't work, count hiddenshot-hides
	        	thumbs = thumbs || this.container.all('.FigureBox');
	        	thumbs.some(function(n){
	        		if (n.audition == focus) return true;
	        		else if (n.hasClass('hiddenshot-hide')) {
	        			offset--;
	        		}
	        		return false;
	        	});
	        	var lookupWidth = {
	        		'sq':81,
	        		'tn':151,
	        		'lm':151,
	        	}
	        	var width = lookupWidth[this._cfg.size];	        	
	        	var center = parent.get('clientWidth')/2 ;
				var scrollLeft = (i+offset + 0.5) * width - center; 
				parent.set('scrollLeft', scrollLeft);
				thumbs = thumbs || parent.all('.FigureBox');
				// set focus
				thumbs.removeClass('focus').item(i).addClass('focus');
        	} catch (e) {}
        	
        },
        /**
         * for shot gallery 
         */
		processDrop : function(nodeList, onComplete) {
			var g = this;
			var Y = SNAPPI.Y;
			/*
			 * process dropped items only
			 */
			// current lightbox photoroll count
			var LIMIT = 100;
			
			// nodeList of img from drag-drop
			nodeList.each(function(n, i, l) {
				var audition = n.ancestor('.FigureBox').audition;
				this.auditionSH.add(audition);
			}, g);
			
            var lastLI = g.render( {
            	// uuid: null, 	// focus 
				page : 1,
				perpage : LIMIT
			});

			// reset .gallery-container li.select-all .checkbox
			try {
				var cb = SNAPPI.Y.one('.gallery-container li.select-all input[type="checkbox"]');
				cb.set('checked', false);
			} catch (e) {}
            
            /*
             * POST groupAsShot with this.auditionSH
             */
            // TODO: post groupAsShot
            
			g.updateCount();
			if (onComplete) onComplete();					
			return lastLI;
		},
		updateCount: function() {
			var count;
			if (this._cfg.pageCount > 1 && this.castingCall) {
				// use Total if the gallery is paged	
				count = this._cfg.total;
			} else count = this.auditionSH.count();
			var label = count==1 ? '1 Snap' : count+" Snaps"; 
			var count = this.node.get('parentNode').one('.gallery-header .count')
			if (count) 
				count.set('innerHTML', label);
		},        
        up : function() {
        	var next,
        		lineCount = 1,
        		prev = this.container.one('li.focus');
	    	if(prev) {
	    		prev.removeClass('focus');
	    		
	    		// get the amount of each line
	    		var first = this.container.one(' > .FigureBox');
	    		while(first.get('offsetTop') == first.next(SNAPPI.util.isDOMVisible).get('offsetTop')){
	    			first = first.next(SNAPPI.util.isDOMVisible);
	    			lineCount++; 
	    		}
	    		
	    		// get the number of THE photo
	    		var now = this.auditionSH._focus;
	    		
	    		// to see if it has a photo under it
	    		var num_down = now - lineCount;
	    		next = this.container.get('childNodes').item(num_down);
	    		
	    		// if it reaches the end of top, then go search the bottom of this column
	    		if(!next){
	    			var num_lines = this.container.get('childNodes').size() / lineCount;
	    			num_lines = Math.round(num_lines);
	    			while (!this.container.get('childNodes').item(now + num_lines * lineCount)){
	    				num_lines--;
	    			}
	    			next = this.container.get('childNodes').item(now + num_lines * lineCount);
        		}
	    		
    			next.addClass('focus');
                var id = this.getUuid(next);
                this.auditionSH.setFocus(id);
                
                if(this.contextMenu) {
                	this.renderContextMenu(next);
                }
	    	}
        },
        down: function(){
        	var next,
        		lineCount = 1,
        		prev = this.container.one('li.focus');
        	if(prev) {
        		prev.removeClass('focus');
        		
        		// get the amount of each line
        		var first = this.container.one(' > .FigureBox');
        		while(first.get('offsetTop') == first.next(SNAPPI.util.isDOMVisible).get('offsetTop')){
        			first = first.next(SNAPPI.util.isDOMVisible);
        			lineCount++; 
        		}
        		
        		// get the number of THE photo
        		var now = this.auditionSH._focus;
        		
        		// to see if it has a photo under it
        		var num_down = now + lineCount;
        		next = this.container.get('childNodes').item(num_down);
        		
        		// if it reaches the end of bottom, back to the top
        		if(!next){
        			next = this.container.get('childNodes').item(now % lineCount);
        		}

        		// if so, navs to the photo
    			next.addClass('focus');
                var id = this.getUuid(next);
                this.auditionSH.setFocus(id);
                
                if(this.contextMenu) {
                	this.renderContextMenu(next);
                }
        	}
        },
        next: function(){
            var next, prev = this.container.one('li.focus');
            if (prev) {
                prev.removeClass('focus');
                next = prev.next(SNAPPI.util.isDOMVisible);
            }
            else {
                next = this.container.one('li');
            }
            
            if(next == null || next.hasClass('contextmenu')){
            	next = this.container.get('childNodes').item(0);
            }
            
            next.addClass('focus');
            var id = this.getUuid(next);
            this.auditionSH.setFocus(id);
            
            if(this.contextMenu) {
            	this.renderContextMenu(next);
            }
             
        },
        prev: function(){
            var next, prev = this.container.one('li.focus');
            if (prev) {
                prev.removeClass('focus');
                next = prev.previous(SNAPPI.util.isDOMVisible);
            }
            else {
                next = this.container.one('li');
            }
            
            if(next == null){
            	next = this.container.get('childNodes').item(this.container.get('childNodes').size() - 1);
            	if(next.hasClass('contextmenu')){
            		next = this.container.get('childNodes').item(this.container.get('childNodes').size() - 2);
            	}
            	
            }
            var check = next;
            next.addClass('focus');
            var id = this.getUuid(next);
            this.auditionSH.setFocus(id);
            
            if(this.contextMenu) {
            	this.renderContextMenu(next);
            }
        },
        getFocus: function() {
        	var focus = this.auditionSH.getFocus();
        	if (!focus) {
        		focus = this.container.one('.FigureBox.focus');
        		if (focus) this.auditionSH.setFocus(focus);
        	}
        	return focus;
        },
        /**
         * @deprecated uses scotts property object
         */
        renderAsNode: function(selected){
        	
        	var _renderAsNode = function(selected){
        		
        		var detailNodeParent = Y.one('.assets blockquote');
            	var existingNode = detailNodeParent.one('dl');
            	if(existingNode){
            		existingNode.remove();
            	} 
            	
            	var Audition = selected.Audition;
            	var cfg = [
            	// ownder and photostream needs to join table. Chris and I have discussed it, but we are not sure about the process of getting castingCall.
            	// so first leave these two properties in controller
            	{
            		title : 'Owner : ',
            		label : PAGE.jsonData.controller.owner,
            		element : 'a'
            	}, {
            		title : 'Caption : ',
            		label : Audition.Photo.Caption,
            		element : 'a'
            	}, {
            		title : 'Photostream : ',
            		label : PAGE.jsonData.controller.photostream,
            		element : 'a'            			
            	}, {
            		title : 'Avg Rating : ',
            		label : Audition.LayoutHint.Rating,
            		element : 'a'
            	}, {
            		title : 'Date Taken : ',
            		label : Audition.Photo.DateTaken
            	}];
            	
            	var p = new SNAPPI.Property({
            		data : cfg,
            		type : 'dl'
            	});
            	
            	var detailNode = p.render();
            	
            	if(detailNodeParent){
            		detailNodeParent.append(detailNode);
            	}

            	Y.fire('snappi:completeAsync', detailNodeParent);
            	
            	// add 'more info' label and attach listener to it.
            	if(!detailNodeParent.one('h5')){
            		
            		var moreInfoNode = Y.Node.create('<h5>more info...</h5>');
            		detailNodeParent.one('div').append(moreInfoNode);
            		
            		detailNodeParent.one('h5').on('click', function(e){
            			
            			var photo_audition = Y.one('#neighbors ul li.focus').dom().audition;
            			
            			SNAPPI.propertyManager.renderDialogInPhotoRoll(photo_audition);
                	}, this);
            	}
        	};

        	var asyncCfg = {
					fn : _renderAsNode,
					node : Y.one('.assets blockquote'),
					size: 'big',
					context : this,
					args : [selected]
				};
    		
        	SNAPPI.propertyManager.renderAsAsyncLoading(asyncCfg);
        	
        },
        selectAll: function(){
            SNAPPI.multiSelect.selectAll(this.container);
        },
        selectAllPages: function(){
            SNAPPI.multiSelect.selectAll(this.container);
            SNAPPI.STATE.selectAllPages = true;
        },
		getSelected : function() {
			var auditionSH; 	// return sortedHash, allows auditionSH.each() maintains consistency
			if (0 && SNAPPI.STATE.selectAllPages){ 
				// TODO: get all assetIds for ALL pages in CastingCall
				// from lightbox.js ProcessDrop
//				var callback = {
//					complete: function(id, o, arguments) {
//						var castingCall = o.responseJson.castingCall;
//						this.renderLightboxFromCC.call(this, castingCall);
//						this.save();
//						onComplete.call(this, nodeList); // clear selected items
//						SNAPPI.STATE.selectAllPages = false;
//					}
//				};
//				SNAPPI.domJsBinder.fetchCastingCall.call(this, {
//					perpage : _LIGHTBOX_FULL_PAGE_LIMIT,
//					page : 1,
//					skipPaging: true
//				}, callback);
//				return false; // don't clear selected until XHR call
//				// complete				
//				var check;
			}else { // this uses visible selected only
				var batch = this.container.all('.FigureBox.selected');
				auditionSH = new SNAPPI.SortedHash();
				batch.each(function(node){
					auditionSH.add(node.audition);
				});
			}
			return auditionSH;
		},        
        applySelectAllPages: function(){
			if (SNAPPI.STATE.selectAllPages) this.selectAll();
        },
        clearAll: function(){
            SNAPPI.multiSelect.clearAll(this.container);
            SNAPPI.STATE.selectAllPages = false;
        },
        // toggle button state and UI
		toggleRatings: function(n, force){
			var label;
			if (n && n.ynode) {
				n = n.ynode();
			} else {
				n = this.header.one('#show-ratings');
			}
			switch (force) {
				case true:
				case 'show': label = 'show Ratings'; break;
				case false:
				case 'hide': label = 'hide Ratings'; break;
				
				default: label = n.get('innerHTML'); break; // just toggle
			}
			switch (label) {
				case 'show Ratings':
					this.showThumbnailRatings();	
					n.set('innerHTML', 'hide Ratings');	// prepare label for next toggle
					break;
				case 'hide Ratings':
					this.hideThumbnailRatings();
					n.set('innerHTML', 'show Ratings'); // prepare label for next toggle
					break;
			}
		},
		toggleZoomMode: function() {
			
			if(!this.container.dom().Zoom){
				
				var bn = Y.one('#element-roll_zoom_btn');
				var Zoom = new SNAPPI.Zoom(this.container, bn);
				
			}
			
			this.container.dom().Zoom.toggleZoomMode();

		},
		add_ViewAll: function() {
			if (SNAPPI.STATE.displayPage.pageCount > 1) {
				var self = this;	// photoRoll
				var target = self.container;
				if (target.ancestor('#photos-preview-xhr')) {
					// auto-create view All 
					var viewAllContainer = target.create('<p class="center"><a>more...</a></p>');
					var here = SNAPPI.STATE.controller.here.split('/');
					here[2] = 'photos';	// replace action
					viewAllContainer.one('a').set('href', here.join('/'));
					target.insert(viewAllContainer,'after');
					return viewAllContainer;
				} 
			}
			return false;	// this is a preview, do NOT auto-create paging DIV
		},
		// TODO: move these methods to SNAPPI.Thumbnail
        showThumbnailRatings : function(node){
	        // private method
			var pr = node ? SNAPPI.Gallery.getFromDom(node) : this;
            var thumbs = pr.container.all('.FigureBox');
            thumbs.each(function(n){
            	if (n.hasClass('hide')) return;
                if (n.Rating == undefined) {
                	SNAPPI.Rating.pluginRating(this, n.Thumbnail, n.dom().audition.rating);
                } else {
                	// rating already exists
                	n.one('div.ratingGroup').removeClass('hide');
                }
            }, pr);
            
            SNAPPI.STATE.showRatings = 'show';  
            SNAPPI.Rating.startListeners(pr.container);
            // SNAPPI.debug.showNodes();
        },
        hideThumbnailRatings : function(node){
        	var pr = node ? SNAPPI.Gallery.getFromDom(node) : this;
            var thumbs = pr.container.all('.FigureBox');
            pr.container.all('div.ratingGroup').addClass('hide');
            pr.container.all('div.thumb-label').removeClass('hide');
            SNAPPI.STATE.showRatings = 'hide';	
            SNAPPI.Rating.stopListeners(pr.container);
        },
		// toggle button state and UI
		toggleSubstitutes: function(n, force){
			var label;
			if (n && n.ynode) {
				n = n.ynode();
			} 		
			switch (force) {
				case 'show': 
				case true: label = 'show Substitutes'; break;
				case 'hide': 
				case false: label = 'hide Substitutes'; break;
				default: label = n.get('innerHTML'); break; // just toggle
			}
			
			switch (label) {
				case 'show Substitutes':
					SNAPPI.shotController.show(n);
					n.set('innerHTML', 'hide Substitutes');	
					break;
				case 'hide Substitutes':
					SNAPPI.shotController.hide(n);
					n.set('innerHTML', 'show Substitutes');
					break;
			}			
		},
        applyShowSubstitutes: function(){
			this.toggleSubstitutes(SNAPPI.Y.one('#show-substitutes'), SNAPPI.STATE.showSubstitutes);
        },		
		/**
		 * group all selected items into ONE shot. 
		 * @params batch auditionSH (optional)
		 */        
        groupAsShot: function(batch, cfg){
            var Y = SNAPPI.Y,
            	auditionREFs = [], 
            	aids = [],            
            	idPrefix = this._cfg.ID_PREFIX || null;
            
			batch = batch || this.getSelected();
			batch.each(function(audition) {
				aids.push(audition.id);
            });    
			var data = {
				'data[Asset][id]' : aids.join(','),
				'data[Asset][group]' : '', // if '', then generate UUID on server
				'data[ccid]' : SNAPPI.ShotController.getCCid(this),
				'data[shotType]' : cfg.shotType,
				'data[shotUuid]': cfg.uuid
			};
			var uri = '/photos/shot/.json';
			var args = {
					aids: aids,
					auditions: batch,
					shotType: cfg.shotType,
					lightbox: cfg.lightbox,
					success: this._groupAsShot_success	
			};
			var loadingNode = cfg.loadingNode;
			if (loadingNode.io == undefined) {
				var ioCfg = SNAPPI.IO.pluginIO_RespondAsJson({
					uri: uri ,
					parseContent:true,
					method: 'POST',
					qs: data,
					dataType: 'json',
					context: this,	// photoGallery
					arguments: args, 
					on: {
						successJson:  function(e, id, o, args) {
							return args.success.apply(this, arguments);
						}
					}
				});
	            loadingNode.plug(Y.Plugin.IO, ioCfg );
			} else {
				loadingNode.io.set('data', data);
				loadingNode.io.set('context', this);
				loadingNode.io.set('uri', uri);
				loadingNode.io.set('arguments', args);
				loadingNode.io.start();
	        }
            return;
        },
        _groupAsShot_success: function(e, id, o, args) {
        	var response = o.responseJson.response;
			var shotCfg = response['groupAsShot'];
			// remember hiddenShot count
			var newHiddenShot = { id:null, count:0},
				alreadyCounted={};
			args.auditions.each(function(audition){
				if (!audition.Audition.Shot.id) newHiddenShot.count++;
				else if (!alreadyCounted[audition.Audition.Shot.id]) {
					newHiddenShot.count += parseInt(audition.Audition.Shot.count);
					alreadyCounted[audition.Audition.Shot.id] = true;
				}
			});
			var shot = SNAPPI.ShotController.markSubstitutes_afterPostSuccess(this, shotCfg, args);
			if (shot){
				this.applyShotCSS(shot);
			}
			// update hiddenShot count
			newHiddenShot.id = shot.id;
			args.auditions.each(function(audition){
				audition.Audition.Shot = newHiddenShot;
				// update .FigureBox to show hiddenshot-icon
				for (var i in audition.bindTo) {
					var o = audition.bindTo[i];
					if (o.hasClass('FigureBox') && o.hasClass('hiddenshot-show')) {
						o.Thumbnail.reuse(audition, o);
					}
				}
			});			
			
			// if lightbox, remove hiddenshot-hide
			if (args.lightbox) {
				var lightbox = args.lightbox;
				lightbox.Gallery.container.all('.FigureBox.hiddenshot-hide').each(function(n,i,l){
					lightbox.remove(n);	
				})
			}
			
			
			// cancel multiSelect
			SNAPPI.multiSelect.clearAll(this.container);
			return false;
		},
		/**
		 * delete Shot groups for all selected items. 
		 * - deletes ENTIRE shot, does NOT Remove one photo from Shot
		 * @params batch auditionSH (optional)
		 */
		unGroupShot : function(batch, cfg) {
            var post_aids = [],             
            	shotId, shotIds = [],
    			idPrefix = this._cfg.ID_PREFIX || null;

	        batch = batch || this.getSelected();
			batch.each(function(audition) {
				post_aids.push(audition.id);
				try {
					shotId = audition.Audition.Substitutions.id;
					if (shotIds.indexOf(shotId) == -1) shotIds.push(shotId);
				} catch (e) {}
	        }); 
	        
			var data = {
					'data[Asset][id]' : post_aids.join(','),
					'data[Shot][id]' : shotIds.join(','),
					// TODO: ungroup or removefromshot
					'data[Asset][ungroup]' : '1', // if '', then generate UUID on server
					'data[ccid]' : SNAPPI.ShotController.getCCid(this),
					'data[shotType]' : cfg.shotType,
					'data[uuid]': cfg.uuid
				};
			var uri = '/photos/shot/.json';	
			var sort = SNAPPI.sortConfig.byTime;
			if (/\/sort:.*\.rating/.test(SNAPPI.STATE.controller.here)) {
				sort = SNAPPI.sortConfig.byRating;
			}
			var args = {
				sort: sort,
				aids: post_aids,
				success: this._ungroupShot_success				
			};
			var loadingNode = cfg.loadingNode;
			if (loadingNode.io == undefined) {
				var ioCfg = SNAPPI.IO.pluginIO_RespondAsJson({
					uri: uri ,
					parseContent:true,
					method: 'POST',
					qs: data,
					dataType: 'json',
					context: this,	
					arguments: args,
					on: {
						successJson:  function(e, id, o, args) {
							return args.success.apply(this, arguments);
						}
					}
				});
	            loadingNode.plug(Y.Plugin.IO, ioCfg );
			} else {
				loadingNode.io.set('data', data);
				loadingNode.io.set('context', this);
				loadingNode.io.set('uri', uri);
				loadingNode.io.set('arguments', args);
				loadingNode.io.start();
	        }
	        return;
		},
		_ungroupShot_success: function(e, id, o, args) {
        	var response = o.responseJson.response;
        	var hiddenShots = response['unGroupShot']['hiddenShots'],
        		shotIds = response['unGroupShot']['shotIds'];
			var shotGallery, photoGallery = this;
			/*
			 * for hiddenShots
			 */
			if (photoGallery._cfg.type=="DialogHiddenShot" || photoGallery._cfg.type=="ShotGallery") {
				shotGallery = photoGallery;
				photoGallery = null;
				// search hiddenShots for the node which is ALSO visible in photoGallery
				shotGallery.auditionSH.each(function(audition){
					var unbindNodes = [];
					for (var i in audition.bindTo) {
						var node = audition.bindTo[i];
						if (!photoGallery && /^uuid-/.test(node.get('id'))){
							// find parent photoGallery
							photoGallery = Gallery.getFromChild(node);
						};
						if (node.ancestor('ul') == shotGallery.container){
							// unbind hiddenShots
							unbindNodes.push(node);
							// we will replace auditions with clean ones from response.hiddenShots
							continue;
						}
					}
					// wait until after for loop to unbind
					while (unbindNodes.length) {
						var node = unbindNodes.shift();
						SNAPPI.Auditions.unbind(node);
						node.addClass('hide');
					}									
				});
				// close hiddenShot afterwards
				try {
					SNAPPI.MenuAUI.find['contextmenu-hiddenshot-markup'].hide();
					SNAPPI.Dialog.find['dialog-photo-roll-hidden-shots'].hide();
				} catch (e) {}
				// cancel multiSelect
				SNAPPI.multiSelect.clearAll(this.container);
			}

			/*
			 *  add hiddenShots back to Photoroll 
			 */
			photoGallery.addFromCastingCall(hiddenShots, true, args.sort);
			photoGallery.render();

			// ALSO, search lightbox or bindTo[] for node in lightbox
			var aids = args.aids;			
			return false;
		},
		/**
		 * removes photo(s) from Shot. 
		 * - does NOT Delete Shot
		 * @params batch auditionSH (optional)
		 */
		removeFromShot : function(batch, cfg) {
            var post_aids = [],             
        	shotId,
			idPrefix = this._cfg.ID_PREFIX || null;

	        batch = batch || this.getSelected();
			batch.each(function(audition) {
				post_aids.push(audition.id);
				try {
					shotId = shotId || audition.Audition.Substitutions.id;
				} catch (e) {}
	        }); 
	        
			var data = {
					'data[Asset][id]' : post_aids.join(','),
					'data[Shot][id]' : shotId,
					// TODO: ungroup or removefromshot
					'data[Asset][remove]' : '1', // if '', then generate UUID on server
					'data[ccid]' : SNAPPI.ShotController.getCCid(this),
					'data[shotType]' : cfg.shotType,
					'data[uuid]': cfg.uuid
				};
			var uri = '/photos/shot/.json';	
			var sort = SNAPPI.sortConfig.byTime;
			if (/\/sort:.*\.rating/.test(SNAPPI.STATE.controller.here)) {
				sort = SNAPPI.sortConfig.byRating;
			}
			var args = {
				sort: sort,
				success: this._removeFromShot_success
			};
			var loadingNode = cfg.loadingNode;
			if (loadingNode.io == undefined) {
				var ioCfg = SNAPPI.IO.pluginIO_RespondAsJson({
					uri: uri ,
					parseContent:true,
					method: 'POST',
					qs: data,
					dataType: 'json',
					context: this,	
					arguments: args,
					on: {
						successJson:  function(e, id, o, args) {
							return args.success.apply(this, arguments);
						}
					}
				});
	            loadingNode.plug(Y.Plugin.IO, ioCfg );
			} else {
				loadingNode.io.set('data', data);
				loadingNode.io.set('context', this);
				loadingNode.io.set('uri', uri);
				loadingNode.io.set('arguments', args);
				loadingNode.io.start();
	        }
	        return;
		}, 
		_removeFromShot_success: function(e, id, o, args) {
			var response = o.responseJson.response;
			var photoGallery, shotGallery = this;
			/*
			 * for hiddenShots, usually we remove from hiddenShots
			 */
			if (shotGallery._cfg.type=="DialogHiddenShot" || shotGallery._cfg.type=="ShotGallery") {
				var audition, 
					removed = response['removeFromShot']['assetIds'];
//				var bestShotSystem_changed = response['updateBestShotSystem']['changed'],
//					bestShotSystem_assetId = response['updateBestShotSystem']['asset_id'];
				var oldFocus = shotGallery.getFocus();
				var moveToParent = [];
				for (var i in removed) {
					audition = SNAPPI.Auditions.get(removed[i]);
					/*
					 *  unbind and hide removed node from hiddenShots dialog
					 */
					var unbindNodes = [];
					var photoGallery_PREFIX = shotGallery._cfg.type=="ShotGallery" ? 'nav-' : 'uuid-';
					for (var j in audition.bindTo) {
						var node = audition.bindTo[j];
						if (!photoGallery && node.get('id').indexOf(photoGallery_PREFIX) == 0){
							photoGallery = Gallery.getFromChild(node);
						};
						if (node.ancestor('ul') == shotGallery.container){
							unbindNodes.push(node);
						}
					}	
					// wait until after for loop to unbind
					while (unbindNodes.length) {
						var node = unbindNodes.shift();
						SNAPPI.Auditions.unbind(node);
						node.addClass('hide');
					}
					// remove audition from Shot, HiddenShot photoGallery 
					audition.Audition.Substitutions.remove(audition);
					shotGallery.auditionSH.remove(audition);
					moveToParent.push(audition);
				}
				if (!photoGallery) {
					// none of the removed photos were visible, search all remaining hiddenShots
					shotGallery.auditionSH.some(function(audition){
						for (var k in audition.bindTo) {
							var node = audition.bindTo[k];
							if (node.get('id').indexOf(photoGallery_PREFIX) == 0){
								photoGallery = Gallery.getFromChild(node);
								return true;
							};
						}
					});
				}
				if (!photoGallery) {
					// still not found, try to find by CSS
					photoGallery = Y.one('section#nav-filmstrip .gallery.photo');
					if (!photoGallery) photoGallery = Y.one('.gallery-contaienr .gallery.photo');				
					if (photoGallery && photoGallery.Gallery) photoGallery = photoGallery.Gallery;
				}


				/*
				 * update shotGallery
				 */
				var bestShot = shotGallery.auditionSH.first().Audition.Substitutions.best;
				// update Shot.count for div.hiddenshot						
				bestShot.Audition.Shot.count = bestShot.Audition.Substitutions.count();
				shotGallery.setFocus(bestShot);
				// render changes
				// TODO: set {uuid:} to scrollFocus();
				shotGallery.render();	
				var previewBody = shotGallery.node.one('.preview-body');
				SNAPPI.Factory.Thumbnail.PhotoPreview.bindSelected(bestShot, previewBody);
				// shotGallery.updateHiddenShotPreview(shotGallery, oldFocus);
				
				if (photoGallery) {
					/*
					 *  update photoGallery, add removed Shots back to Photoroll 
					 */
					while (moveToParent.length) {
						photoGallery.auditionSH.add(moveToParent.shift());
					}	
					if (!photoGallery.auditionSH.get(bestShot)) {
						// if new bestShot is not in photoGallery, add it
						photoGallery.auditionSH.add(bestShot);
					}				
					photoGallery.auditionSH.sort(args.sort);
					photoGallery.render();								
				}
				
				
				try {
					SNAPPI.MenuAUI.find['contextmenu-hiddenshot-markup'].hide();
				} catch (e) {}
				// cancel multiSelect
				SNAPPI.multiSelect.clearAll(this.container);
			}
			return false;
		},
		// @deprecated use SNAPPI.Factory.Thumbnail.PhotoPreview.bindSelected()
		updateHiddenShotPreview: function(gallery, oldFocus){
			var focus = gallery.getFocus();
			if (focus != oldFocus) {
				gallery.setFocus(focus);
				switch(gallery._cfg.type) {
					case "DialogHiddenShot":
						// Helper.bindPreview(gallery);
						SNAPPI.Factory.Thumbnail.PhotoPreview.bindSelected();
					break;
					case "ShotGallery":
						SNAPPI.domJsBinder.bindSelected2Preview.call(gallery, focus);
					break;
				}
			}			
		},
		/**
		 * 
		 * @param selected  .FigureBox of selected shot
		 * @param cfg
		 * @return
		 */
		setBestshot: function(selected, cfg){
            var shotId,
			idPrefix = this._cfg.ID_PREFIX || null;

			var data = {
					'data[Asset][id]' : selected.audition.id,
					'data[Shot][id]' : selected.audition.Audition.Shot.id,
					'data[shotType]' : cfg.shotType,
					'data[setBestshot]': 1
			};
			var uri = '/photos/setprop/.json';	
			var args = {
				thumbnail: selected, 
				success: this._setBestshot_success
			};
			var loadingNode = cfg.loadingNode;
			if (loadingNode.io == undefined) {
				var ioCfg = SNAPPI.IO.pluginIO_RespondAsJson({
					uri: uri ,
					parseContent:true,
					method: 'POST',
					qs: data,
					dataType: 'json',
					context: this,	
					arguments: args,
					on: {
						success:  function(e, id, o, args) {
							return args.success.apply(this, arguments);
						}
					}
				});
	            loadingNode.plug(Y.Plugin.IO, ioCfg );
			} else {
				loadingNode.io.set('data', data);
				loadingNode.io.set('context', this);
				loadingNode.io.set('uri', uri);
				loadingNode.io.set('arguments', args);
				loadingNode.io.start();
	        }
	        return;			
		},
		_setBestshot_success : function(e, id, o, args) {
			try {
				var selected, shotPhotoRoll;
				selected = args.thumbnail.audition;
				shotPhotoRoll = args.thumbnail.ancestor('.gallery.filmstrip').Gallery;
				var bestShot = selected.Audition.Substitutions.best;
				// confirm showHidden bestShot is in main photoroll
				if (1 || bestShot !== selected) {
					// var photoroll = SNAPPI.Y.one('section.gallery.photo').Gallery;
					g = SNAPPI.Gallery.find['uuid-'] || SNAPPI.Gallery.find['nav-'];
					// splice into original location, nav- or uuid-
					var result = g.auditionSH.replace(bestShot, selected);
					if (result) {
						shotPhotoRoll.selected = selected;
						selected.Audition.Substitutions.setBest(selected);
						g.render();	
						bestShot_Substitution = selected.Audition.Substitutions;
						g.shots[bestShot_Substitution.id]=bestShot_Substitution;
						g.applyShotCSS(bestShot_Substitution);
						// for (var i in g.shots) {
							// var shot = photoroll.shots[i]; 
							// g.applyShotCSS(shot);
						// }
					}
				}
			} catch (e) {}		
			return false;	// reset loadingNode
		},
        applyShotCSS: function(shot){
            shot.each(function(audition){
                if (shot.isBest(audition)) {
//            	if (shot.sameAsBest(audition)) {
                    // render as best of shot 
                    for (var i in audition.bindTo) {
                        var n = audition.bindTo[i];
                        if (!audition.Audition.Substitutions.id){
	                        audition.Audition.Shot = shot;
	                        audition.Audition.Substitutions = shot;			// shot.1 legacy 
	                        audition.Audition.SubstitutionREF = shot.id;	// shot.1 legacy 	
                        }
                        if (n.Thumbnail) n.Thumbnail.setSubGroupHide('show');
                    }
                }
                else {
                    // hide
                	// TODO: move to another div
                    for (var i in audition.bindTo) {
                        var n = audition.bindTo[i];
                        if (n.Thumbnail) n.Thumbnail.setSubGroupHide();
                    }
                }
            });
        },
        /**
         * use PluginIO to render castingCall (JSON response) into Gallery
         * @params uri string, JSON request for castingCall
         * @params cfg object
         * 		cfg.uuid string, UUID of selected audition
         * 		cfg.successJson function, success handler, should return 'false'
         */
        loadCastingCall: function(uri, cfg){
        	cfg = cfg || {};
        	if (!uri) {
        		// use existing CC
        		var sh = PAGE.jsonData.castingCall.auditionSH;
                this.render({
                	sh: sh,
                	uuid: cfg.uuid || sh.first().id
                });
                return;       		
        	}
        	
        	if (!cfg.successJson) {
        		cfg.successJson = function(e, i,o,args) {
					var response = o.responseJson.response;
					// get auditions from raw json castingCall
					var options = {
                    	castingCall: response.castingCall,
                    	uuid: args.uuid || null,
                    	replace: args.replace,
                    }
                    this.render(options);
                    PAGE.jsonData.castingCall = response.castingCall;
                    return false;
				}
        	}
			// SNAPPI.io GET JSON  
			var container = this.container;
			if (!/\.json$/.test(uri)) uri += '/.json'; 
        	var args = {
        		uuid : cfg.uuid,
        		successJson: cfg.successJson,
        		uri: uri,
        		replace: cfg.replace,
        	};
        	/*
    		 * plugin Y.Plugin.IO
    		 */
    		if (!container.io) {
    			var ioCfg = {
//    					uri: subUri,
    					parseContent: false,
    					autoLoad: false,
    					context: this,
    					arguments: args, 
    					on: {
    						successJson: function(e, i,o,args){
    							return args.successJson.call(this, e, i,o,args);
    						}					
    					}
    			};
    			if (cfg.complete) ioCfg.on.complete = cfg.complete;
    			// var loadingmaskTarget = container.get('parentNode');
    			var loadingmaskTarget = this.node.hasClass('filmstrip') ? container.ancestor('.filmstrip') : container;
    			// set loadingmask to parent
    			container.plug(Y.LoadingMask, {
    				target: loadingmaskTarget
    			});    			
    			container.loadingmask._conf.data.value['target'] = loadingmaskTarget;
    			container.loadingmask.overlayMask._conf.data.value['target'] = container.loadingmask._conf.data.value['target'];
    			// container.loadingmask.set('target', target);
    			// container.loadingmask.overlayMask.set('target', target);
    			container.loadingmask.set('zIndex', 10);
    			container.loadingmask.overlayMask.set('zIndex', 10);
    			container.plug(Y.Plugin.IO, SNAPPI.IO.pluginIO_RespondAsJson(ioCfg));
    		}
			// get CC via XHR and render
			container.io.set('uri', args.uri);
			container.io.set('arguments', args);
			// container.loadingmask.refreshMask();
			container.loadingmask.show();		//DEBUG: loadingmask is NOT showing here
			container.io.start();			
        },
        // called by SNAPPI.Factory.Thumbnail.PhotoPreview.handle_HiddenShotClick(), and 
        // DialogHelper.bindSelected2DialogHiddenShot() 
        showShotGallery : function(selected, cfg) {
        	selected = selected || this.auditionSH.getFocus();
        	var container, parent, shots, Y = SNAPPI.Y;
        	var shot = selected.Audition.Substitutions;
        	
        	// unhide ShotGallery if there is a Shot
			if (shot.id) {
				this.container.ancestor('.filmstrip-wrap').removeClass('hidden');
				this.container.all('.FigureBox').removeClass('hide');
			} 
			
			// render ShotGallery
			if (shot.stale == false && this.Shot == shot) {
				// skip
        	} else if (shot.stale == false) {
        		this.render({ uuid: selected.id }, shot);		// render shot directly
        	} else {
        		var uri = '/photos/hiddenShots/'+shot.id+'/'+shot.shotType+'/.json';
        		var ioCfg = {
        			uuid: selected.id,
		    		successJson : function(e, i,o,args) {
						var response = o.responseJson.response;
						// get auditions from raw json castingCall
						var shotCC = response.castingCall;
						var onDuplicate = SNAPPI.Auditions.onDuplicate_ORIGINAL
						var shotAuditionSH =  SNAPPI.Auditions.parseCastingCall(shotCC, this._cfg.PROVIDER_NAME, null, onDuplicate);
	                    var audition = shotAuditionSH.first();
	                    var shot = audition.Audition.Substitutions;
	                    shot.stale = shot._sh.count() != audition.Audition.Shot.count ;
	                    this.render({ uuid: args.uuid }, shot);		// render shot directly
	                    return false;
					},
				};
				ioCfg = Y.merge(ioCfg, cfg);
				this.loadCastingCall(uri, ioCfg);
        	}
        },
        launchPagemaker: function (node){
        	if (SNAPPI.PM && SNAPPI.PM.performance) {
        		this.createPageGallery(this);
        		return;
        	}
        	
        	var detach = SNAPPI.Y.on('snappi-pm:after-launch', function(e) {
        		detach.detach();
        		node.ynode().set('innerHTML', 'Create Page');
        		var photoRoll = this;
        		photoRoll.createPageGallery(photoRoll);
        	}, this);
        	
			if (SNAPPI.PM.main) {
				SNAPPI.PM.main.launch();
			} else {
				SNAPPI.PM.pageMakerPlugin = new SNAPPI.PageMakerPlugin();
				SNAPPI.PM.pageMakerPlugin.load( 
					function() {
						SNAPPI.PM.main.go(this);
					}
				);
				return;
			}        	
        },
        createPageGallery: function(photoRoll) {
    		var batch;	// target
    		var audition = photoRoll.auditionSH.get(0);
    		batch = photoRoll.getSelected();
    		if (batch.count()) {
    			var Y = SNAPPI.PM.Y;
    			var stage = SNAPPI.PageGalleryPlugin.stage;
    			var performance = stage ? stage.performance : null;
    			
//    			var stage2 = photoRoll.container.create("<div id='stage-2' class='grid_16' style='position:absolute;top:200px;'></div>");
//    			Y.one('#content').append(stage2);
    			var sceneCfg = {
    				roleCount: batch.count(),
    				fnDisplaySize: {h:800},
    				stage: stage,
    				auditions: batch,
    				noHeader: true,
    				useHints: true,
    				hideRepeats : false
    			};
    			SNAPPI.PM.node.onPageGalleryReady(sceneCfg);
    		};
        }
    };
    
    
    /*
     * make global
     */
    SNAPPI.Gallery = Gallery;
    
    
})();
