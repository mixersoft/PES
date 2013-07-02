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
	if (typeof SNAPPI.Gallery !== 'undefined') return; 	// firefox/firebug 1.9.1 bug
	
	var _Y = null;
    SNAPPI.namespace('SNAPPI.onYready');
    SNAPPI.onYready.Gallery = function(Y){
		if (_Y === null) _Y = Y;
		Factory = SNAPPI.Factory.Gallery;
		/*
	     * make global
	     */
	    SNAPPI.Gallery = Gallery;
	}
	
	var Factory = null;	// closure, init in onYready()
	
	
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
    
	
    Gallery.prototype = {
    	destroy: function(){
    		// remove all references to contained nodes
    		this.node.get('parentNode').remove();	// remove from dom tree
    		for (var i in this.node.listen) {
    			this.node.listen[i].detach();
    			delete(this.node.listen[i]);
    		}
    		for (var i in this.container.listen) {
    			this.container.listen[i].detach();
    			if (i == 'RatingClick') {	// look 2 places for RatingClick
    				var l = this.container.listen[i];
    				for( var j in SNAPPI.Rating.listen) {
    					if (SNAPPI.Rating.listen[j] == l) {
    						delete(SNAPPI.Rating.listen[j]);
    						break;
    					}
    				}
    			}
    			delete(this.container.listen[i]);
    		}
    		for (var i in this.node._plugins) {
    			this.node.unplug(i);
    		}
    	},
    	init: function(cfg) {
	    	var _cfg = _Y.merge(cfg);		// copy cfg
	    	
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
	        		if (typeof _cfg.replace == 'boolean') _cfg.replace = _cfg.replace ? SNAPPI.Auditions.onDuplicate_REPLACE : SNAPPI.Auditions.onDuplicate_ORIGINAL;
	        		else if (typeof _cfg.replace != 'function') _cfg.replace = SNAPPI.Auditions.onDuplicate_REPLACE;
	        		this.auditionSH = SNAPPI.Auditions.parseCastingCall(this.castingCall, this.providerName, this.auditionSH, _cfg.replace );
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
            if (0 && this.header) { // header should already be initialized
            	// update thumbnailSize
            	var thumbSize = this._cfg.size;
            	this.header.all('.window-options .thumb-size > li.btn').each(
            		function(n,i,l){
            			var action = n.getAttribute('action');
            			if (action.match(thumbSize+'$')) n.addClass('focus');
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
	        
	        // TODO: switch to _cfg.type
	        switch (_cfg.ID_PREFIX) {
	        case 'uuid-':
	        	if (SNAPPI.util.getFromQs('focus')) {
	        		_cfg.uuid = SNAPPI.util.getFromQs('focus');
	        	}
	        	if (renderOnInit) {
	        		this.render(_cfg);
	        		// this.scrollFocus();   // adapt for gallery view, not filmstrip
	        	} 
	        	var paging;
	        	if (_cfg.isPreview !== true) {
	        		paging = SNAPPI.Paginator.paginate_PhotoGallery(this);
	        	}
	        	if (!paging) {
	        		// add view All
	        		this.add_ViewAll();
	        	}
	        	this.restoreState();	// photoRolls have state, but filmstrips do not.
	        	var selector = '.FigureBox > figure > img';
	        	break;	        	
	        case 'lightbox-':
	        	this.container.addClass('one-row'); // these are all filmstrips, init in filmstrip mode
	        	if (renderOnInit) {
	        		this.render(_cfg);
        		} else {
                	var emptyMsg = _Y.one('#markup .empty-lightbox-gallery-message');
                	SNAPPI.util.setForMacintosh(emptyMsg);
                	if (emptyMsg) this.container.append(emptyMsg.removeClass('hide'));
                }	        	
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
	        		if (n.uuid == selected.id) n.addClass('focus');
	        	});
        	}
        	_Y.fire('snappi:gallery-render-complete', this);
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
	        		if (typeof cfg.replace == 'function') this._cfg.replace = cfg.replace;
	        		else if (cfg.replace && typeof cfg.replace == 'boolean') this._cfg.replace = cfg.replace ? SNAPPI.Auditions.onDuplicate_REPLACE : SNAPPI.Auditions.onDuplicate_ORIGINAL;
					var sh = SNAPPI.Auditions.parseCastingCall(this.castingCall, this._cfg.PROVIDER_NAME, null, this._cfg.replace);
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
        	} else if (this.castingCall && this.castingCall.shots) {
	        	this.shots =  this.castingCall.shots;
	        }
        	
        	var focusUuid = null;
        	if (cfg.uuid || cfg.selected) {
        		focusUuid = cfg.uuid || cfg.selected;
        		this.auditionSH.setFocus(focusUuid);
//        		delete cfg.uuid;	// TODO: filmstrip doesn't work unless we update this._cfg.uuid
        	} else focusUuid = this.auditionSH.getFocus();
        	if (cfg) this._cfg = _Y.merge(this._cfg, cfg);
            
            var offset = 0;
            if (!this.auditionSH) 
                return;
            
            if (!this.container) 
                return;
            var offset, page, perpage, ccAuditions, nlist;
            try {
            	ccAuditions = this.castingCall.CastingCall.Auditions;
            } catch (e) {
            	// move to shot/hiddenshot
            	ccAuditions = {
            		Page: 1,
            		Perpage: this.auditionSH.count(),
            		Total: this.auditionSH.count(),
            	}
            }
            
            nlist = this.container.all('> .FigureBox');
			// check for strange TextNodes, only allow .FigureBox childNodes
            if (nlist.size() < this.container.get('childNodes').size()) {
            	this.container.setContent(nlist);
            }
            
            var lastLI, thumbCfg = cfg.thumbCfg;
            if (!thumbCfg) thumbCfg = {size: this._cfg.size, type:'Photo'};
            switch(this._cfg.ID_PREFIX) {
	            case 'lightbox-': 	// type=Lightbox
            		// use the existing number of .FigureBoxs
	                perpage =  this._cfg.perpage || this.auditionSH.size();
	                page = page ||  1;
	                offset = (page - 1) * perpage;
	            	break;
	            case 'hiddenshot-': 	// type=DialogHiddenShot
	            case 'shot-': 			// type=ShotGallery
					ccAuditions = {
	            		Page: 1,
	            		Perpage: this.auditionSH.count(),
	            		Total: this.auditionSH.count(),
	            	}	            
	            	if (!shot) {
	            		var audition = this.auditionSH.first();
	            		shot = audition.Audition.Substitutions;
	            		shot.stale = shot._sh.count() != audition.Audition.Shot.count;
	            	}
	            	this.Shot = shot;
	            	// continue below	
	            case 'nav-': 	// type=NavFilmstrip
	            	this.node.get('parentNode').removeClass('hide');
	            	this._cfg.page = ccAuditions.Page;
	            	this._cfg.perpage = ccAuditions.Perpage || ccAuditions.Total;
	            	perpage = this._cfg.perpage;
	            case 'uuid-': 	// type=Photo
            	default:
                	// calculate offset of requested page from Gallery.castingCall.Auditions.Page
                	// cfg.page = SNAPPI.STATE.displayPage.page, or $this->passedArgs['page']
                	// ccOffset = (ccPage-1)*ccPerpage            		
                	var ccPage = ccAuditions.Page;
	            	var ccOffset = (ccPage-1) * ccAuditions.Perpage;
	            	var displayPage = this._cfg.page;
	            	var displayOffset = (displayPage-1)*this._cfg.perpage;
	            	offset = displayOffset - ccOffset;
	            	if (!perpage){		// could be set by case 'nav-':
						try {
		            		this._cfg.perpage = Math.min(SNAPPI.STATE.displayPage.perpage, ccAuditions.Perpage);	// should we get from cfg or paginator???
	            		} catch (e) {
	            			this._cfg.perpage = ccAuditions.Perpage;
	            		} 
	            		perpage = perpage || this._cfg.perpage; 
	            	}
	                this._cfg.start = 0;
	                this._cfg.end = perpage;
	                if (this.auditionSH.size() == 0) {
	                	var emptyMsg = _Y.one('#markup .empty-photo-gallery-message');
	                	if (emptyMsg) this.container.append(emptyMsg.removeClass('hide'));
	                }	                
            		break;
            };

            /*
             * reuse or create LIs
             */
            // if (this.node.hasClass('hiddenshots')) thumbCfg['showHiddenShot'] =  false;
            if (nlist.size()) {
                // if .FigureBox exist, reuse
                nlist.removeClass('focus');
            	nlist.each(function(n, i, l){
            		if (i >= perpage  ) {
            			// hide extras, unbind and remove extras
            			n.Thumbnail.remove();
            			// n.addClass('hide');
            		} else if (_Y.Lang.isNumber(this._cfg.start) && this._cfg.end) {
						var audition = this.auditionSH.get(offset+i);
						if (audition && offset+i < this._cfg.end) { 
						    lastLI = this.reuseThumbnail(audition, n, thumbCfg);
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
            
            while (i < limit) {
                audition = this.auditionSH.get(i++);
                if (audition == null) 
                    break;
                lastLI = this.createThumbnail(audition, thumbCfg);
                if (audition.id == focusUuid) lastLI.addClass('focus');
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
            if (this.container.hasClass('one-row')) {
            	this.setFilmstripWidth();
            }
            _Y.fire('snappi:gallery-render-complete', this);
            return lastLI;
        },
        /**
         * calls g.loadCastingCall() > g.render() and waits for IMG load before hiding loadingmask
         */
        refresh: function(cfg, force){
        	cfg = cfg || {};
        	var page = cfg.page || cfg.nameData && cfg.nameData.page || SNAPPI.STATE.displayPage.page;  
        	var perpage = cfg.perpage || cfg.nameData && cfg.nameData.perpage || SNAPPI.STATE.displayPage.perpage;
			if (!force && page == SNAPPI.STATE.displayPage.page) return;
        	
        	var uri = cfg.uri || this.castingCall.CastingCall.Request;
        	cfg.nameData = _Y.merge(cfg.nameData, {page:page, perpage:perpage});
        	
        	// cfg.qs -> cfg.data as querystring
	 		if (force) cfg.qs = _Y.merge(cfg.qs, {
				refresh: 1,
				ccid: this.castingCall.CastingCall.ID,
			});       	
	        cfg.ioCfg = {
				parseContent: true,
				dataType: 'json',
			}
			cfg.args = _Y.merge(cfg.args,{page: page});
			cfg.successJson = function(e, i,o,args) {
					var response = o.responseJson.response;
					// PAGE.jsonData.castingCall = response.castingCall;
					PAGE.jsonData = response;
					SNAPPI.mergeSessionData(response.castingCall);	// need this for paginate
					var options = {
						page: args.page,
	                	castingCall: response.castingCall,
	                	uuid: args.uuid,
	                }
	                var lastThumb = this.render(options);
	                /*
	                 * CHECK Thumbnail.isReady() before loadingmask.hide()
	                 * TODO: Thumbnail.isReady() is still a hack, need to tally onload for all thumbs
	                 */
	                var after = _Y.after(
	                	function(){
	                		after.detach();
	                		var REFRESH_TIMEOUT = 5000;
	                		var timeout = _Y.later(REFRESH_TIMEOUT, this, function(){
		                		this.hide();
		                		SNAPPI.setPageLoading(false);
		                	});	// set timeout for loadingmask 
	                		if (!lastThumb.Thumbnail.isReady()) {
	                			this.show();
			                	var detach = _Y.on('snappi:img-ready', function(imgNode, lastThumb){
			                		if (!lastThumb.contains(imgNode)) return;
									detach.detach();
									this.hide();
									timeout.cancel();
									SNAPPI.setPageLoading(false);
								}, 
								this, // context == loadingmask
								lastThumb 	//arg 2
			                )}
						}, this.node.loadingmask, 'hide', 
						this.node.loadingmask		// context
					);
					_Y.fire('snappi:gallery-refresh-complete', this);
	                return false;								
			};
	        this.loadCastingCall(uri, cfg);
	        return;
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
        	if (node.ShotGallery)  {
				node.ShotGallery.destroy();
        	}
        	node.Thumbnail.reuse.call(node.Thumbnail, audition, cfg);
        	return node;
        },
        createThumbnail: function(audition, cfg){
        	// cfg.size = ??? 
        	cfg.gallery = this;
        	cfg.type = this._cfg.tnType || this._cfg.type;
        	var t = new SNAPPI.Thumbnail(audition, cfg);
        	this.container.append(t.node);
        	return t.node;
        },
        listen: function(status, cfg){
        	if (this.node.listen == undefined) this.node.listen = {};
            status = (status == undefined) ? true : status;
            var k,v,handler, fn;
            if (status) {
            	cfg = cfg || ['Keydown', 'Mouseover', 'LinkToClick', 'WindowOptionClick', 'MultiSelect', 'Contextmenu', 'FsClick'];
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
                		if (!this.node.listen[v].detach) continue;
	                    this.node.listen[v].detach();
	                    delete (this.node.listen[v]);
                	}
                }
            }
        },

		// deprecate: use GalleryFactory.nav.toggle_ContextMenu(this, e); instead
        toggle_ContextMenu : function(e) {
        	Factory.nav.toggle_ContextMenu(this, e);
        	return;
        },
        // restore state from SNAPPI.STATE
        // TODO: should get state from cakephp Session / user profile
        restoreState : function(){
            try {
                if (SNAPPI.STATE.showRatings && _Y.one('li#show-ratings')) {
                	var v = SNAPPI.STATE.showRatings || 'hide';
                    this.toggleRatings(_Y.one('li#show-ratings'), v);
                }
                if (SNAPPI.STATE.selectAllPages && _Y.one('li#select-all-pages')) {
                    this.applySelectAllPages();
                }
                if (SNAPPI.STATE.showSubstitutes && _Y.one('li#show-substitutes')) {
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
        	var uuid = n.uuid || this._cfg.ID_PREFIX ? id.replace(this._cfg.ID_PREFIX, '') : id;
        	return uuid;
        },
        /**
         * set focus in DOM object
         * - calls auditionSH.setFocus()
         * @param m mixed, .FigureBox Node, audition, UUID, or index
         * @return
         */
        setFocus: function(m){
        	var focusNode, o;
        	if (m instanceof _Y.Node && m.hasClass('FigureBox')) {
				o = SNAPPI.Auditions.find(m.uuid);  
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
        	if (!focusNode) { // check bindTo for correct node
        		for (var i in o.bindTo) {
        			if (this.container.contains(o.bindTo[i])) {
        				focusNode = o.bindTo[i]; 
        				break;
        			}
        		}
        		// focusNode = this.container.one('#'+this._cfg.ID_PREFIX+o.id);
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
        		var wrapper, thumb, count, width, oldWidth, newWidth;
	        	wrapper = this.container.ancestor('.filmstrip');
	        	thumb = this.container.one('.FigureBox');
	        	count = this.auditionSH.count();
	        	count -= this.container.all('.hiddenshot-hide').size(); 
	        	var lookupWidth = {
	        		'sq':81,
	        		'tn':151,
	        		'lm':151,
	        	}
	        	width = lookupWidth[thumb.Thumbnail._cfg.size];
	        	newWidth = width*count;
	        	var pageControls = this.container.all('li.btn');
	        	if (pageControls.size()) newWidth += (pageControls.size()*pageControls.item(0).get('offsetWidth'));
	        	newWidth = Math.max(newWidth, wrapper.get('clientWidth'));
	        	
	        	oldWidth = this.container.get('clientWidth');
	        	var setWidth = function(w, g) {
	        		g.container.setStyles({
						width: (w)+'px',	
						height: 'auto',
					}); 
	        		g.scrollFocus();	
	        	}
	        	if (0 && oldWidth > newWidth) {  
	        		// TODO: bug. must cancel delay if we setPagingControls, deprecate???
	        		// use delay to avoid flash when narrowing
					var delay = new _Y.DelayedTask( 
						function() {
							setWidth(newWidth, this);  
						}, this);
					delay.delay(100);	        		
	        	} else {
	        		setWidth(newWidth, this);
	        	}
	        	
			} catch (e) {}
			return newWidth;	
        },
        /*
         * scroll ".gallery .filmstrip" to show focus in center
         * @params m mixed, _Y.Node (.FigureBox), uuid, or index
         * context == SNAPPI.Gallery
         */
        scrollFocus: function(m) {
        	var i, thumbs, selected, parent = this.container.ancestor('.filmstrip');
        	try {
        		if (m instanceof _Y.Node && m.hasClass('FigureBox')) {
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
	        		if (n.uuid == focus.id) return true;
	        		else if (n.hasClass('hiddenshot-hide')) {
	        			offset--;
	        		}
	        		return false;
	        	});
	        	var lookupWidth = {
	        		'sq':81,
	        		'tn':151,
	        		'lm':151,
	        		'll':222, 
	        	}
	        	var width = lookupWidth[this._cfg.size];	        	
	        	var center = parent.get('clientWidth')/2 ;
				var scrollLeft = (i+offset + 0.5) * width - center; 
				parent.set('scrollLeft', scrollLeft);
				thumbs = thumbs || parent.all('.FigureBox');
				thumbs.removeClass('focus').item(i).addClass('focus');
				return thumbs.item(i);
        	} catch (e) {}
        },
        /**
         * for shot gallery 
         */
		processDrop : function(nodeList, onComplete) {
			var g = this;
			/*
			 * process dropped items only
			 */
			// current lightbox photoroll count
			var LIMIT = 100;
			
			// nodeList of img from drag-drop
			nodeList.each(function(n, i, l) {
				var audition = SNAPPI.Auditions.find(n.ancestor('.FigureBox').uuid);
				this.auditionSH.add(audition);
			}, g);
			
            var lastLI = g.render( {
            	// uuid: null, 	// focus 
				page : 1,
				perpage : LIMIT
			});

			// reset .gallery-container li.select-all .checkbox
			try {
				var cb = _Y.one('.gallery-container li.select-all input[type="checkbox"]');
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
        		prev = this.container.one('.FigureBox.focus');
	    	if(prev) {
	    		prev.removeClass('focus');
	    	}	
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
    		
			this.setFocus(next);
			next.scrollIntoView();
            
            if(this.contextMenu) {
            	this.renderContextMenu(next);
            }
            return next; 
    	},
        down: function(){
        	var next,
        		lineCount = 1,
        		prev = this.container.one('.FigureBox.focus');
        	if(prev) {
        		prev.removeClass('focus');
        	}	
    		// get the amount of each line
    		var first = this.container.one(' > .FigureBox');
    		while(first.get('offsetTop') == first.next(SNAPPI.util.isDOMVisible).get('offsetTop')){
    			first = first.next(SNAPPI.util.isDOMVisible);
    			lineCount++; 
    		}
    		
    		// get the number of THE photo
    		var now = this.auditionSH._focus;
    		
    		// to see if it has a photo under it
    		var num_down = parseInt(now) + lineCount;
    		next = this.container.get('childNodes').item(num_down);
    		
    		// if it reaches the end of bottom, back to the top
    		if(!next){
    			next = this.container.get('childNodes').item(now % lineCount);
    		}

    		// if so, nav to the photo
			this.setFocus(next);
			next.scrollIntoView();
            
            if(this.contextMenu) {
            	this.renderContextMenu(next);
            }
            return next; 
        },
        next: function(){
            var next, prev = this.container.one('.FigureBox.focus');
            if (prev) {
                prev.removeClass('focus');
                next = prev.next(SNAPPI.util.isDOMVisible);
            }
            else {
                next = this.container.one('.FigureBox');
            }
            
            if(next == null || next.hasClass('contextmenu')){
            	next = this.container.get('childNodes').item(0);
            }
            
            this.setFocus(next);
            next.scrollIntoView();
            
            if(this.contextMenu) {
            	this.renderContextMenu(next);
            }
            return next; 
        },
        prev: function(){
            var next, prev = this.container.one('.FigureBox.focus');
            if (prev) {
                prev.removeClass('focus');
                next = prev.previous(SNAPPI.util.isDOMVisible);
            }
            else {
                next = this.container.one('.FigureBox');
            }
            
            if(next == null){
            	next = this.container.get('childNodes').item(this.container.get('childNodes').size() - 1);
            	if(next.hasClass('contextmenu')){
            		next = this.container.get('childNodes').item(this.container.get('childNodes').size() - 2);
            	}
            	
            }
            this.setFocus(next);
            next.scrollIntoView();
            
            if(this.contextMenu) {
            	this.renderContextMenu(next);
            }
            return next; 
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
        		
        		var detailNodeParent = _Y.one('.assets blockquote');
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

            	_Y.fire('snappi:completeAsync', detailNodeParent);
            	
            	// add 'more info' label and attach listener to it.
            	if(!detailNodeParent.one('h5')){
            		
            		var moreInfoNode = _Y.Node.create('<h5>more info...</h5>');
            		detailNodeParent.one('div').append(moreInfoNode);
            		
            		detailNodeParent.one('h5').on('click', function(e){
            			
            			var photo_audition = SNAPPI.Auditions.find(_Y.one('#neighbors ul li.focus').uuid);
            			
            			SNAPPI.propertyManager.renderDialogInPhotoRoll(photo_audition);
                	}, this);
            	}
        	};

        	var asyncCfg = {
					fn : _renderAsNode,
					node : _Y.one('.assets blockquote'),
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
		getSelected : function(allPages) {
			var auditionSH; 	// return sortedHash, allows auditionSH.each() maintains consistency
			if (0 && (SNAPPI.STATE.selectAllPages || allPages)){ 
				// TODO: get all assetIds for ALL pages in CastingCall
				// from lightbox.js ProcessDrop
				var callback = {
					complete: function(id, o, arguments) {
						var castingCall = o.responseJson.castingCall;
						this.renderLightboxFromCC.call(this, castingCall);
						this.save();
					onComplete.call(this, nodeList); // clear selected items
						SNAPPI.STATE.selectAllPages = false;
					}
				};
				alert("deprecated domJsBinder.fetchCastingCall: USE this.loadCastingCall()");
				// SNAPPI.domJsBinder.fetchCastingCall.call(this, {
					// perpage : _LIGHTBOX_FULL_PAGE_LIMIT,
					// page : 1,
					// skipPaging: true
				// }, callback);
				return false; // don't clear selected until XHR call
				// complete				
				var check;
			} else if (allPages) {
				// TODO: WARNING this only selects all on current page.
				var audition, batch = this.container.all('.FigureBox');
				auditionSH = new SNAPPI.SortedHash();
				batch.each(function(node){
					audition = SNAPPI.Auditions.find(node.uuid);
					auditionSH.add(audition);
				});
			} else { // this uses visible selected only
				var audition, batch = this.container.all('.FigureBox.selected');
				auditionSH = new SNAPPI.SortedHash();
				batch.each(function(node){
					audition = SNAPPI.Auditions.find(node.uuid);
					auditionSH.add(audition);
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
				
				var bn = _Y.one('#element-roll_zoom_btn');
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
            var audition, thumbs = pr.container.all('.FigureBox');
            thumbs.each(function(n){
            	if (n.hasClass('hide')) return;
                if (n.Rating == undefined) {
                	audition = SNAPPI.Auditions.find(n.uuid);
                	SNAPPI.Rating.pluginRating(this, n.Thumbnail, audition.rating);
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
			this.toggleSubstitutes(_Y.one('#show-substitutes'), SNAPPI.STATE.showSubstitutes);
        },		
        /*
         * for workorder processing by role=EDITOR/MANAGER
         */
        addWorkorderData: function (data){
        	var role = SNAPPI.STATE.controller.ROLE;
        	var auth = /(EDITOR|MANAGER)/.test(role);
			if (!auth) return data;
			if (/Workorder|TasksWorkorder/.test(SNAPPI.STATE.controller['class'])) {
				var woid = SNAPPI.STATE.controller.xhrFrom.uuid;
				data['data[Workorder][type]'] = SNAPPI.STATE.controller['class']; 
				data['data[Workorder][woid]'] = woid;
			}
			return data;
        },
        /*
         * for groupAsShot processing, 
         * 	find/add hiddenshots if we are adding a bestshot
         */
        addIsBestshot: function(data, options){
        	if (options.isBestshot) {
        		data['data[isBestshot]']=1;
        	}
        	return data;
        },
		/**
		 * TODO: move to ShotController.groupAsShot(), or postGroupAsShot()
		 * deprecate: check if lightbox.postGroupAsShot is used or lightbox.Gallery.groupAsShot
		 * group all selected items into ONE shot. 
		 * @params batch auditionSH (optional)
		 */        
        groupAsShot: function(batch, cfg){
            var auditionREFs = [], 
            	aids = [],            
            	idPrefix = this._cfg.ID_PREFIX || null;
            
			batch = batch || this.getSelected();
			batch.each(function(o) {
				aids.push(o.Audition.id);
            });    
			var data = {
				'data[Asset][group]' : 1, // groupAsShot
				'data[Asset][id]' : aids.join(','),
				'data[ccid]' : SNAPPI.ShotController.getCCid(this),
				'data[shotType]' : cfg.shotType,
			};
			if (cfg.group_id) data['data[group_id]'] = cfg.group_id; 
			data = this.addWorkorderData(data);
			data = this.addIsBestshot(data, cfg);
			
			var uri = '/photos/shot/.json';
			var args = {
					aids: aids,
					auditions: batch,
					shotType: cfg.shotType,
					lightbox: cfg.lightbox,
					success: cfg.success || this._groupAsShot_success,
					menu: cfg.menu,	
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
							args.menu.hide();
							return args.success.apply(this, arguments);
						}
					}
				});
	            loadingNode.plug(_Y.Plugin.IO, ioCfg );
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
			batch.each(function(o) {
				post_aids.push(o.Audition.id);
				try {
					shotId = o.Audition.Substitutions.id;
					if (shotIds.indexOf(shotId) == -1) shotIds.push(shotId);
				} catch (e) {}
	        }); 
	        
			var data = {
					'data[Asset][id]' : post_aids.join(','),	// deprecate. not required for Usershot, check Groupshot
					'data[Shot][id]' : shotIds.join(','),
					'data[Asset][ungroup]' : '1', // if '', then generate UUID on server
					'data[ccid]' : SNAPPI.ShotController.getCCid(this),
					'data[shotType]' : cfg.shotType,
				};
			if (cfg.group_id) data['data[group_id]'] = cfg.group_id; 
							
			data = this.addWorkorderData(data);	
			var uri = '/photos/shot/.json';	
			var sort = SNAPPI.sortConfig.byTime;
			if (/\/sort:.*\.rating/.test(SNAPPI.STATE.controller.here)) {
				sort = SNAPPI.sortConfig.byRating;
			}
			var args = {
				sort: sort,
				aids: post_aids,
				success: cfg.success || this._ungroupShot_success,
				menu: cfg.menu,					
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
							if (args.menu) args.menu.hide();
							return args.success.apply(this, arguments);
						}
					}
				});
	            loadingNode.plug(_Y.Plugin.IO, ioCfg );
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
				post_aids.push(audition.Audition.id);
				try {
					shotId = shotId || audition.Audition.Substitutions.id;
				} catch (e) {}
	        }); 
	        
			var data = {
					'data[Asset][id]' : post_aids.join(','),	// deprecate
					'data[Shot][id]' : shotId,
					'data[Asset][remove]' : '1', 
					'data[ccid]' : SNAPPI.ShotController.getCCid(this),
					'data[shotType]' : cfg.shotType,
				};
			if (cfg.group_id) data['data[group_id]'] = cfg.group_id; 
				
			data = this.addWorkorderData(data);	
			var uri = '/photos/shot/.json';	
			var sort = SNAPPI.sortConfig.byTime;
			if (/\/sort:.*\.rating/.test(SNAPPI.STATE.controller.here)) {
				sort = SNAPPI.sortConfig.byRating;
			}
			var args = {
				sort: sort,
				success: this._removeFromShot_success,
				menu: cfg.menu,	
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
							args.menu.hide();
							return args.success.apply(this, arguments);
						}
					}
				});
	            loadingNode.plug(_Y.Plugin.IO, ioCfg );
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
					photoGallery = _Y.one('section#nav-filmstrip .gallery.photo');
					if (!photoGallery) photoGallery = _Y.one('.gallery-container .gallery.photo');				
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
				SNAPPI.Factory.Thumbnail.PhotoPreview.bindSelected(bestShot, previewBody, {gallery:shotGallery});
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
					// render bestShot again
					var i, t;
					for (i in bestShot.bindTo) {
						try {
							t = bestShot.bindTo[i].Thumbnail;
							if (t.node.one('.hidden-shot')) {
								SNAPPI.Factory.Thumbnail.setHiddenShotNode(t, bestShot);
							}
							// also update '#dialog-photo-roll-hidden-shot .FigureBox.PhotoPreview'
							t = _Y.one('.preview-body .FigureBox.PhotoPreview').Thumbnail;
							SNAPPI.Factory.Thumbnail.setHiddenShotNode(t, bestShot);
						} catch(e) {}
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
		/**
		 * 
		 * @param selected  .FigureBox of selected shot
		 * @param cfg
		 * @return
		 */
		setBestshot: function(selected, cfg){
            var shotId, 
				idPrefix = this._cfg.ID_PREFIX || null,
				audition = SNAPPI.Auditions.find(selected.uuid);  
			shotId = audition.Audition.Shot.id || audition.Audition.Substitutions.id;

			var data = {
					'data[Asset][id]' : audition.Audition.id,
					'data[Shot][id]' : shotId,
					'data[shotType]' : cfg.shotType,
					'data[setBestshot]': 1
			};
			data = this.addWorkorderData(data);
			var uri = '/photos/setprop/.json';	
			var args = {
				thumbnail: selected, 
				audition: audition,
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
	            loadingNode.plug(_Y.Plugin.IO, ioCfg );
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
				selected = args.audition;
				shotPhotoRoll = args.thumbnail.ancestor('.gallery.filmstrip').Gallery;
				var bestShot = selected.Audition.Substitutions.best;
				// confirm showHidden bestShot is in main photoroll
				if (1 || bestShot !== selected) {
					// var photoroll = _Y.one('section.gallery.photo').Gallery;
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
		/**
		 * delete Thumbnail, .FigureBox, and refresh gallery page
		 * @params nodeOrselected Thumbnail node or SortedHash of thumbnails
		 * @params loadingmask _Y.Node, node for loading mask. NOTE: uses node for now.
		 */
		deleteThumbnail: function(nodeOrselected, loadingmask) {
			var sh, aids = [], gids=[];
			if (nodeOrselected && nodeOrselected.uuid) {
				aids = [nodeOrselected.uuid];
			} else {
				nodeOrselected.each(function(o){
					aids.push(o.id);
				});
			}
			// get gids, if any
			SNAPPI.AssetPropertiesController.deleteByUuid(loadingmask || nodeOrselected, {
				ids: aids.join(','), 
				actions: {'delete':1},
				context: this,
				callbacks: {
					successJson: function(e, args){
						var aud,  
							auditions = SNAPPI.Auditions._auditionSH,
							shots = SNAPPI.Auditions._shotsSH;
						for (var i in aids) {
							aud = SNAPPI.Auditions.find(aids[i]);
							SNAPPI.Auditions.unbind(aud);
							auditions.remove(aud);
							try {
								shots.remove(aud.Audition.Shot.id);	
							} catch(e) {}
						}
						this.refresh(null, true);
						return false;
					}
				}
			});
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
         * NOTE: calls g.render() by default, NOT g.refresh(); 
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
                    }
                    this.render(options);
                    PAGE.jsonData.castingCall = response.castingCall;
                    return false;
				}
        	}
			// SNAPPI.io GET JSON  
			// force json request in cakephp
			var isJson = /(\/\.json$|\/\.json\?\b)/i;
			if (!uri.match(isJson)) {
				uri = uri.replace(/(\?)/i, '/.json$1');
				if (!uri.match(isJson)) uri = uri.replace(/(\b$|\W$|\/$)/i, '/.json');
			}
			if (cfg.nameData) uri = SNAPPI.IO.setNamedParams(uri, cfg.nameData);
	    	// add querystring params, ok for both GET and POST
	        if (cfg.qs) {
	            var qs = [];
	            // stringify qs params
	            for (var i in cfg.qs) {
	                qs.push(i + '=' + cfg.qs[i]);
	            }            
	            uri += '?'+qs.join('&');
	            delete cfg.qs;
	        }  
	          
            var args = {
        		uuid : cfg.uuid,
        		successJson: cfg.successJson,
        		uri: uri,
        	};
        	// if (cfg.replace == undefined) args.replace = true;	// replace audition with value in response, default true
        	if (cfg.args) args = _Y.merge(args, cfg.args);
        	
        	
        	var pluginNode = this.node;	// plugin to node seems to work better
        	/*
    		 * plugin _Y.Plugin.IO
    		 */
    		if (0 || !pluginNode.io) {
    			if (pluginNode.io) pluginNode.unplug(_Y.Plugin.IO);
    			var ioCfg = {
//    					uri: subUri,
    					parseContent: false,
    					autoLoad: false,
    					context: cfg.context || this,
    					arguments: args, 
    					on: {
    						successJson: function(e, i,o,args){
    							return args.successJson.call(this, e, i,o,args);
    						}					
    					}
    			};
    			if (cfg.ioCfg) ioCfg = _Y.merge(ioCfg, cfg.ioCfg);
    			if (cfg.complete) ioCfg.on.complete = cfg.complete;
    			pluginNode.plug(_Y.Plugin.IO, SNAPPI.IO.pluginIO_RespondAsJson(ioCfg));
    		}
         	
         	
    		if (0 || !pluginNode.loadingmask) {
    			// loadingmask doesn't seem to be reusable, even with delay
    			if (!pluginNode.loadingmask) pluginNode.unplug(_Y.LoadingMask);
    			
    			var loadingmaskTarget = this.node.hasClass('filmstrip') ? this.container.ancestor('.filmstrip') : this.container;
    			pluginNode.plug(_Y.LoadingMask, {
    				target: loadingmaskTarget
    			});    			
    			pluginNode.loadingmask._conf.data.value['target'] = loadingmaskTarget;
    			pluginNode.loadingmask.overlayMask._conf.data.value['target'] = pluginNode.loadingmask._conf.data.value['target'];
    			// pluginNode.loadingmask.set('target', target);
    			// pluginNode.loadingmask.overlayMask.set('target', target);
    			pluginNode.loadingmask.set('zIndex', 10);
    			pluginNode.loadingmask.overlayMask.set('zIndex', 10);   			
    		}   
    		   			
			// get CC via XHR and render
			pluginNode.io.set('uri', args.uri);
			pluginNode.io.set('arguments', args);
			pluginNode.loadingmask.show();		
			pluginNode.io.start();
        },
        // called by SNAPPI.Factory.Thumbnail.PhotoPreview.handle_HiddenShotClick(), and 
        // DialogHelper.bindSelected2DialogHiddenShot() 
        showShotGallery : function(selected, cfg) {
        	selected = selected || this.auditionSH.getFocus();
        	var container, parent, shots;
        	var shot = selected.Audition.Substitutions;
        	if (!shot) return;
        	
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
        		_Y.fire('snappi:shot-gallery-render-complete', this, shot);
        	} else {
        		var uri = '/photos/hiddenShots/'+shot.id+'/'+shot.shotType+'/.json';
        		var ioCfg = {
        			uuid: selected.id,
		    		successJson : function(e, i,o,args) {
						var response = o.responseJson.response;
						// get auditions from raw json castingCall
						var shotCC = response.castingCall;
	                    var options = {
	                    	uuid: args.uuid,
	                    	castingCall: shotCC,
	                    	thumbCfg: {
			        			type: this._cfg.tnType,
			        			size: this._cfg.size,	// size set in GalleryFactory[Lightbox].build()
			        		}
	                    }
	                    this.render( options);		// render shot directly
	                    _Y.fire('snappi:shot-gallery-render-complete', this, shotCC.shots[shot.id]);
	                    return false;
					},
				};
				ioCfg = _Y.merge(ioCfg, cfg);
				this.loadCastingCall(uri, ioCfg);
        	}
        },
    };
    
})();
