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

    /*
     * dependencies
     */
    var defaultCfg = {
		ID_PREFIX: 'uuid-',
		node: 'div.gallery-container > section.gallery.photo',
		hideSubstituteCSS: false,
		size: 'lm',
		start: null,
		end: null
    };
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
        /*
         * protected methods
         */
        this.container = null;
        this.auditionSH = null;
        this.shots = null; 	
        this.node = this.init(cfg);
    };
    
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
    		return dom.ynode().ancestor('div.filmstrip').one('ul.filmstrip').dom().Gallery;
    	} catch(e) {
    		return null;
    	}
    };    
    Gallery.getFromChild = function(target){
		var hasPhotoroll = false, 
			found = target.ancestor(
				function(n){
					hasPhotoroll = n.Photoroll || n.dom().Gallery || null; 
					return hasPhotoroll;
				}, true );
		return hasPhotoroll;
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
    

    // private helper function (closure)
	/*
	 * render all shots, including hidden, into a "preview" photoRoll on /photos/home page
	 * - depending on how castingCall was created, we either 
	 * 	1) render directly from castingCall, or 
	 * 	2) if shot.stale==true, use XHR to get substitutionGroup castingCall, then render (async)
	 * 
	 */ 
	var _showHiddenShots = function(node, shot, selected) {
		var HIDDENSHOT_PREVIEW_LIMIT = 40;	// substitute roll preview LIMIT
		if (shot) {
            if (!node && /photos\/home/.test(window.location.href)) {
            	// filmstrip not found,  build filmstrip
            	// TODO: move to a better place. we cannot assume we are on the /photos/home page here
            	var parent = SNAPPI.Y.one('div#hiddenshots');
            	// node = parent.create('<section.gallery.photo></section>'); // class='filmstrip' or 'photo-roll'??
                // parent.append(node);
            }                

            // TODO: should add a more... link to see paging view of shot
            
            var total = shot.count();
            
			var oneShotCfg = {};
			oneShotCfg[shot.id] = shot;		// format for Gallery constructor
			var showHiddenShotsCfg = {
				ID_PREFIX : 'hiddenshot-',
				size : 'lm',
				selected : 1,
				start : 0,
				end : Math.min(HIDDENSHOT_PREVIEW_LIMIT, total),
				total : total,
				uri : null,
            	node: node,
            	sh: shot._sh,
            	castingCall: {
					providerName: this.castingCall.providerName,
					schemaParser: this.castingCall.schemaParser
				},
            	shots: oneShotCfg					
			};            
            
            /**
             * NEW codepath to create Gallery from castingCall
             */
           var shotPhotoRoll = new SNAPPI.Gallery(showHiddenShotsCfg);	
           shotPhotoRoll.node.addClass('hiddenshots').addClass('filmstrip').removeClass('container_16');
           shotPhotoRoll.container.removeClass('grid_16');
           shotPhotoRoll.renderContextMenu = SNAPPI.cfg.MenuCfg.renderSubstituteContextMenu;
           shotPhotoRoll.listen(true, ['Keypress', 'Mouseover', 'Click', 'MultiSelect', 'RightClick']);
			
           shotPhotoRoll.setFocus(shot.indexOfBest());  // show best pic
           shotPhotoRoll.selected = selected;
           // add shot to selected
           selected.Audition.SubstitutionREF = shot.id;
           selected.Audition.Substitutions = shot;
           selected.substitutes = shot; // DEPRECATE
           
           
           // manually show Ratings for Duplicates/shot
           shotPhotoRoll.showThumbnailRatings();

        } else {	// no shots returned
        	// hide/unbind everything
	        try {
	        	// no shots, just hide div#hiddenshots and unbind nodes
	        	if (node.ancestor('div#hiddenshots')) { 
	        		// hide substitute group, if we are on the /photos/home page
	        		node.all('li').addClass('hide').each(function(n,i,l){
		        		SNAPPI.Auditions.unbind(n);
	        		});
	        	} else if (node.ancestor('div#snappi-dialog')) { 
	        		// hide/unbind shots on selected blur?
	        		// detach contextMenu listener
	        	} 
	        } catch (e) {}	        	
        }
		SNAPPI.Y.fire('snappi:hideLoadingMask', node);
		return shotPhotoRoll;
	};	// end _showHiddenShots()    
	
	
	
    Gallery.prototype = {
    	init: function(cfg) {
    		var Y = SNAPPI.Y;
	    	var _cfg = {
	    			PROVIDER_NAME: 'snappi'
	    	};
	    	_cfg = Y.merge(defaultCfg, _cfg, cfg);

			// container should be section.gallery.photo
			var node;
	        if (_cfg.node) {
	        	try {
	        		node = _cfg.node instanceof Y.Node ? _cfg.node : Y.one(_cfg.node);	
	        		if (node.Gallery) {
	        			this.container = node.Gallery.container;
		        		var oldPhotoRoll = parent.Gallery;
			            // TODO: what do we do here???
		        		// reuse existing photoRoll??? or do we need to destroy?	        			
	        		} else {
	        			if (node.hasClass('gallery') && !node.one('div.container')) {
	        				node.prepend('<div class="container grid_16" />');
	        			}
	        			this.container = node.one('div.container');
	        		}
	        	} catch (e) {}
	        }    
	        if (!this.container) {
	        	var MARKUP = '<section class="gallery photo container_16">'+
	        					'<div class="container grid_16" />'+
	        				'</section>';
	        	node  = Y.Node.create(MARKUP);
	        	if (cfg.isWide) node.addClass('wide');
	        	this.container = node.one('div');
	        }
	        node.Gallery = this;
	        node.Gallery.container.Gallery = this;	// is this necessary?
	        delete _cfg.node;	// use this.container from this point forward
	        
	        this.providerName = _cfg.PROVIDER_NAME;
	        if (_cfg.sh) {
	        	// resuse SH, if provided
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
	        
	        if (_cfg.selected) {
	        	// set focus to selected
	        	var selected = this.auditionSH.setFocus(_cfg.selected);
	        }

	        if (cfg.shots) {
	            this.shots = cfg.shots;
	            delete cfg.shots;
	        } else if (this.castingCall && this.castingCall.shots) {
	        	this.shots =  this.castingCall.shots;
	        }

	        this._cfg = _cfg;
	        
	        /**
	         * sort auditions, or are they pre-sorted?
	         */
	        if (false) {
	        	//TODO: assume CastingCall is sorted to make page settings??? 
	            this.auditionSH.sort(SNAPPI.sortConfig.byTime);        	
	        }
	        
	        

	        try {
		        // add reference to global
//	        	Gallery[parent.get('id')] = this;
		        // search for photo-roll header	        	
	        	var parent = this.container.ancestor('.gallery-container');
				this.header = parent.one(".gallery-header");
	        } catch (e) {
	        	this.header = null;
	        }; 
	        
	        switch (_cfg.ID_PREFIX) {
	        case 'filmstrip-':
	        	if (selected)  this.filmstrip_SetFocus(selected);
	        	else this.render(_cfg);
	        	break;
	        case 'lightbox-':
	        	// render manually to enforce LIMIT
	        	this._cfg.showLabel = false;
	        	if (this._cfg.addClass == 'lbx-tiny' ) {
	        		this._cfg.showExtras = false;	        		
	        	}
	        	break;
	        case 'uuid-':
	        	this.render(_cfg); 
	        	var paging = SNAPPI.Paginator.paginate_Photoroll(this);
	        	if (paging === false) {
	        		// add view All
	        		this.add_ViewAll();
	        	}
	        	this.restoreState();	// photoRolls have state, but filmstrips do not.
//	        	this.container.all('img.drag').each(function(n, i, l){
//	                SNAPPI.DragDrop.pluginDrag(n);
//	            }, this);
	            SNAPPI.DragDrop.pluginDelegatedDrag(this.container, 'img.drag');
	        	break;
	        case 'hiddenshot-':
	        	this.render(_cfg);
//	        	this.container.all('img.drag').each(function(n, i, l){
//	                SNAPPI.DragDrop.pluginDrag(n);
//	            }, this);
	        	SNAPPI.DragDrop.pluginDelegatedDrag(this.container, 'img.drag');        	
	        }
	        SNAPPI.Rating.startListeners(this.container);
	        SNAPPI.DragDrop.startListeners();
	        Y.fire('snappi:afterPhotoRollInit', this.auditionSH); 
	        return node;
    	},
        setAudition: function(sh){
            this.auditionSH = sh;
        },
        render: function(cfg, shotsSH){
        	cfg = cfg || {};
        	if (cfg.ID_PREFIX !== undefined && !cfg.ID_PREFIX) {
        		delete (cfg.ID_PREFIX);
        	}
        	var focusUuid = null;
        	if (cfg.uuid) {
        		focusUuid = cfg.uuid;
//        		delete cfg.uuid;	// TODO: filmstrip doesn't work unless we update this._cfg.uuid
        	}
        	
        	if (cfg) this._cfg = Y.merge(this._cfg, cfg);
//        	cfg = this._cfg;
            
            var offset = 0;
            if (!this.auditionSH) 
                return;
            
            if (!this.container) 
                return;
            var offset, page, perpage, 
            	nlist = this.container.all('.FigureBox');
            switch(this._cfg.ID_PREFIX) {
	            case 'filmstrip-':
	            case 'hiddenshot-':
	            	offset = this._cfg.start;
	            	perpage = this._cfg.end - this._cfg.start;            	
	            	break;
	            case 'lightbox-':
            		// use the existing number of .FigureBoxs
	                perpage =  this._cfg.perpage || this.auditionSH.size();
	                page = page ||  1;
	                offset = (page - 1) * perpage;
	            	break;
	            case 'uuid-':
            	default:
                	// calculate offset of requested page from Gallery.castingCall.Auditions.Page
                	// cfg.page = SNAPPI.STATE.displayPage.page, or $this->passedArgs['page']
                	// ccOffset = (ccPage-1)*ccPerpage            		
                	var ccPage = this.castingCall.CastingCall.Auditions.Page;
	            	var ccOffset = (ccPage-1)*this.castingCall.CastingCall.Auditions.Perpage;
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
            if (nlist.size()) {
                // if UL>LIs exist, reuse
            	nlist.each(function(li, i, l){
            		if (i >= perpage  ) {
            			// hide extras
            			li.addClass('hide');
            		} else if (Y.Lang.isNumber(this._cfg.start) && this._cfg.end) {
						var audition = this.auditionSH.get(offset+i);
						if (audition && offset+i < this._cfg.end) { 
						    this.reuseLI(li, audition);
						    li.removeClass('focus');
						    if (audition.id == focusUuid) li.addClass('focus');
						} else {
							li.addClass('hide');
						}
            		} else {
            			// on first bind with cakephp render, 
            			// we match li.id with audition.id and add li.audition
            			// TODO: deprecate when we move to JSON/JS .FigureBox rendering
//	                	var id = li.get('id');	
//	                	if (this._cfg.ID_PREFIX) id = id.replace(this._cfg.ID_PREFIX, '');
//	                    var audition = this.auditionSH.get(id);
//	                    if (audition) 
//	                        this.bindLI(li, audition);
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
                li = this.createLI(this.container, audition, this._cfg);
                if (audition.id == focusUuid) li.addClass('focus');

                lastLI = li;
            }
            var check;
            if (!this._cfg.hideSubstituteCSS) {
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
        reuseLI: function(article, audition){
        	article.Thumbnail.reuse(audition);
        	return;
        },
        createLI: function(ul, audition, cfg){
        	cfg = cfg || this._cfg;
        	cfg.photoroll = cfg.photoroll || this;
        	var t = new SNAPPI.Thumbnail(audition, cfg);
        	ul.append(t.node);
        	return t.node;
        },
        listen: function(status, cfg){
        	if (this.node.listen == undefined) this.node.listen = {};
            status = (status == undefined) ? true : status;
            var k,v;
            if (status) {
            	cfg = cfg || ['Keypress', 'Mouseover', 'Click', 'MultiSelect', 'RightClick', 'FsClick'];
            	for ( k in cfg){
            		v = 'this.listen'+cfg[k]+'();';
            		eval(v);
            	}
            }
            else {
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
        /**
         * listen for click on arrow and .FigureBoxs
         * 	- NOTE: must be started manually, as this method requires a closure
         * 	- not compatible with 'listenClick'
         * @param closure
         * @return
         */
        listenFsClick: function(closure) {
            if (this.node.listen['FsClick'] == undefined) {
            	this.node.listen['FsPrevNextClick'] = this.container.delegate('click', 
            			/*
            			 * 
            			 */
            			function(e) {
            				var pr = e.container.Gallery;
            				var selected, target = e.currentTarget;
            				switch(e.currentTarget.get('innerHTML')) {
            				case '◄':
            					selected = pr.auditionSH.prev(); 
            					break;
            				case '►':
            					selected = pr.auditionSH.next();
            					var extendCC = (selected === null);
            					break;
            				}
            				if (selected) {
	            				var oldUuid = pr._cfg.uuid;
	            				pr.filmstrip_SetFocus(selected);
	                        	if (selected.id != oldUuid) {
	                        		SNAPPI.domJsBinder.bindSelectedToPage.call(pr, selected, oldUuid);
	                        		pr._cfg.uuid = selected.id;
	        	                }
            				}
            				/*
            				 * load a bigger castingCall into session, 
            				 * then reload the page using new castingCall
            				 * 
            				 */
            				// TODO: extendCC will fail near snappi_controller::__getExtendedCCRequest() boundary limit, currently 2000
            				if (extendCC === true) {
            					selected = pr.auditionSH.getFocus();
            					// show "get more" link
            					// update cc using .json fetch
            					ccid = PAGE.jsonData.castingCall.CastingCall.ID;
            					var updateCCUrl = '/snappi/extendcc/'+ccid+'/.json';
            					SNAPPI.io.get(updateCCUrl, {complete: function(i, o, args) {
            						var resp = o.responseJson;
            						var extendReq = resp.request;
                					SNAPPI.io.get(extendReq, {complete: function(i, o, args) {
                						var resp = o.responseJson;
                						// load page with new CastingCalls  
                						var next = '/photos/home/'+selected.id+'?ccid='+resp.castingCall.CastingCall.ID;
                						window.location.href = next;
                					}} );            						
            						
            					}} );
            					// redirect to updated page
            				}  
            			}, 'li.prev-next'
            	);	
                this.node.listen['FsClick'] = this.container.delegate('click', 
	                /*
	                 * update all components on /photos/home page to match 'selected'
	                 */		
	                function(e){
                		var pr = e.container.Gallery;
	                	e.target.ancestor('li').removeClass('focus');
	                	var selected = e.target.ancestor('li').audition;
	                	var oldUuid = pr.auditionSH.get(pr._cfg.selected).id;
	                	pr.filmstrip_SetFocus(selected);
	                	
	                	if (selected.id != oldUuid) {
	                		SNAPPI.domJsBinder.bindSelectedToPage.call(pr, selected, oldUuid);
		                }

	                }, 'img'); 
	            } 
        },
        /*
         * what is this? not a click on the hiddenshot-icon.
         * @deprecated???
         */
        listenHiddenShotClick: function(){
        	if (this.node.listen['HiddenShotClick'] == undefined) {
        		this.node.listen['HiddenShotClick'] = this.container.delegate('click', 
	                /*
	                 * update all components on /photos/home page to match 'selected'
	                 */		
	                function(e){
        				e.container.all('.FigureBox').removeClass('focus');
	                	var selected = e.target.ancestor('.FigureBox').audition;
	                	var pr = e.container.Gallery;
	                	var oldUuid = pr.auditionSH.getFocus().id;
	                	pr.filmstrip_SetFocus(selected);
	                	if (selected.id != oldUuid) {
	                		SNAPPI.domJsBinder.bindSelected2Preview.call(pr, selected);
		                }
	                }, 'img'); 
	          }
        	var detach = this.node.listen['Click'];
        	if (detach) detach.detach();
	    },
        listenClick: function(forceStart) {
            if (this.node.listen['Click'] == undefined || forceStart ) {
            	// section.gallery.photo or div.filmstrip.photo
                this.node.listen['Click'] = this.node.delegate('click', function(e){
                    var next = e.target.getAttribute('linkTo');
                    if (this.castingCall.CastingCall) {
                    	next += '?ccid=' + this.castingCall.CastingCall.ID;
						try {
							var shotType = e.currentTarget.ancestor('.FigureBox').audition.Audition.Substitutions.shotType;
							if (shotType == 'Groupshot'){
								next += '&shotType=Groupshot';
							}
						} catch (e) {}
                    }
                    window.location.href = next;
                }, '.FigureBox > figure > img', this);
                try {
	                // listen thumbnail size
	                this.node.listen['thumbSize_Click'] = this.node.get('parentNode').one('section.gallery-header ul.thumb-size').delegate('click', function(e){
	                	var thumbSize = e.currentTarget.getAttribute('thumb-size');
	                	window.location.href = SNAPPI.IO.setNamedParams(window.location.href, {'thumbSize':thumbSize});
	                }, 'li', this);
				} catch (e) {}	                
				try {
	                // listen hiddenshot-icon
	                this.node.listen['hiddenShot_Click'] = this.node.delegate('click', function(e){
	                	var thumbnail = e.currentTarget.ancestor('.FigureBox');
						try {
							var audition = thumbnail.audition;
							var photoRoll = this;
							var shotType = audition.Audition.Substitutions.shotType;
							if (!shotType) shotType = /^Groups/.test(SNAPPI.STATE.controller.name) ? 'Groupshot' : 'Usershot';
							photoRoll.showHiddenShotsInDialog(audition, shotType);
						} catch (e) {
						}                	
	                }, 'div.hidden-shot', this);
	          	} catch (e) {}
			}
        },
        listenMultiSelect : function () {
        	SNAPPI.multiSelect.listen(this.container, true);
        	return;
        },
        listenMouseover : function(){
        	
        	if(this.node.listen['Mouseover'] == undefined){
        		this.node.listen['Mouseover'] = this.container.delegate('mouseover', function(e){
        			
        			var target = e.currentTarget;
            		
        			// may need to encapsulate the following code into a function. will refactor later.
            		if(this.contextMenu && SNAPPI.util.isDOMVisible(this.contextMenu.container)) {
            			this.contextMenu.parent.container = target;
            			// context menu is visible
            			if(!this.contextMenu.getNode().hasClass('hide')){
                			this.contextMenu.show();
                			this.stopClickListener();
            			}
            		}
            		
            		// set focus
            		this.setFocus(target);

            		
                }, ' > li', this);
        	}
        	
        },
        stopMouseoverListener : function(){
        	if(this.node.listen.mouseover != undefined){
        		this.node.listen.mouseover.detach();
        		delete this.node.listen.mouseover;
        	}
        },
		
        listenRightClick : function (){
        	if (this.node.listen['RightClick'] == undefined){
        		this.node.listen['RightClick'] = this.container.delegate('contextmenu', function(e){
					this.toggle_ContextMenu(e);
        		}, '.FigureBox', this);
        		
        		// .FigureBox li.context-menu.icon
     		this.node.listen['ContextMenu'] = this.container.delegate('click', function(e){
					this.toggle_ContextMenu(e);
        		}, '.FigureBox > ul > li.context-menu', this);        		
			}        	
        	return;
        },
        toggle_ContextMenu : function(e) {
	        e.preventDefault();
        	
        	var CSS_ID;
        	if (this.node.hasClass('hiddenshots') || this.node.hasClass('hidden-shot')) {
        		CSS_ID = 'contextmenu-hiddenshot-markup';
        	} else if (this.node.hasClass('filmstrip')) {
        		// TODO: not yet done
        		console.warn("Gallery.listenRightClick: not done for filmstrip");
        	} else {
        		CSS_ID = 'contextmenu-photoroll-markup';
        	}  
        	
        	// load/toggle contextmenu
        	if (!SNAPPI.MenuAUI.find[CSS_ID]) {
        		SNAPPI.MenuAUI.CFG[CSS_ID].load({currentTarget:e.currentTarget});
        		this.stopClickListener();
        	} else {
        		var menu = SNAPPI.MenuAUI.toggleEnabled(CSS_ID, e);
        		if (menu.get('disabled')) {
        			this.listenClick();
        		} else {
        			this.stopClickListener();
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
         * @param i, index or .FigureBox
         * @return
         */
        setFocus: function(i){
        	var next, i = parseInt(i);
        	if(!Y.Lang.isNumber(i)){
        		next = i;
        	}else {
        		next = this.container.all('.FigureBox').item(i);
        	}
            
            if (next) {
	            var prev = this.container.one('li.focus');
	            if (prev) {
	                prev.removeClass('focus');
	            };
            
                next.addClass('focus');
                var id = this.getUuid(next);
                this.auditionSH.setFocus(id);
            }
            return this;
        },
        // DEPRCATE
        renderContextMenu : function(next){
        	// SEE photoroll.listenRightClick()
            // TODO: put this initializing context is temp code. will refator later
        	this.contextMenu.parent.container = next;
			// context menu is visible
			if(!this.contextMenu.getNode().hasClass('hide')){
    			this.contextMenu.show();
    			this.stopClickListener();
			}
    		
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
        filmstrip_SetFocus: function(audition) {
        	var selected = audition || this.auditionSH.getFocus();
        	this.auditionSH.setFocus(selected);
        	var selectedIndex = parseInt(this.auditionSH.indexOfKey(selected));
        	var cfg = {
				// ID_PREFIX: 'filmstrip-',
				// size: 'tn',                			
        		selected: selectedIndex,
        		uuid : selected.id,
        		start: selectedIndex - this._cfg.flimstripHalfsize,
        		end: selectedIndex + this._cfg.flimstripHalfsize +1,
        		hideSubstituteCSS: false
        	};
        	var count = this.auditionSH.count();	// count of auditions retrieved in castingCall
        	if (0 <= cfg.start && cfg.end <= count) {
        		cfg.focus = this._cfg.flimstripHalfsize;
        	}
        	if (cfg.start<0) {
        		cfg.end -= cfg.start;
        		cfg.start = 0;
        	}
        	if (cfg.end > count) {
        		if (count == cfg.total) {
            		cfg.start -= (cfg.end-count);
            		cfg.end = count;
        		} else {
        			// TODO: Get next page of CastingCall
            		cfg.start -= (cfg.end-count);
            		cfg.end = count;
        		}
        	}
        	// render #neighbors filmstrip
        	this.render(cfg);
    		this.setFocus(cfg.selected-cfg.start);
        	
//        	// if no dialogbox or dialogbox is invisible, then not render dialog box
//        	if(SNAPPI.dialogbox.node && !SNAPPI.dialogbox.node.hasClass('hide')){
//        		SNAPPI.propertyManager.renderDialogInPhotoRoll(selected);
//        	}
        	
//        	this.renderAsNode(selected);
        	
        },
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
			if (SNAPPI.STATE.selectAllPages){ 
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
				batch = this.container.all('.FigureBox.selected');
				auditionSH = new SNAPPI.SortedHash();
				batch.each(function(node){
					auditionSH.add(node.dom().audition);
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
		/**
		 * @deprecated using Paginator.paginate_Photoroll instead???
		 * add aui Paginator to photoroll
		 * @return
		 */
		add_Paginator: function(){
			if (console) console.warn("WARNING: photo-roll.add_Paginator() is deprecated? using Paginator.paginate_Photoroll");
			return;
			
			// var self = this;	// photoRoll
			// var target = self.container;
			// var paginateContainer = target.ancestor('div#paging-photos-inner').one('div.paging-numbers');
			// if (!paginateContainer) {
				// if (target.ancestor('#paging-photos')) {
					// // auto-create paging DIV
					// paginateContainer = target.create("<div class='paging-control paging-numbers' />");
					// target.get('parentNode').insert(paginateContainer,'after');
				// } else return false;	// this is a preview, do NOT auto-create paging DIV
			// }
			// if (paginateContainer.Paginator) {
				// // already created, just reuse
				// return paginateContainer.Paginator;
			// }
			// var controller = SNAPPI.STATE.controller;
			// var displayPage = SNAPPI.STATE.displayPage;
// 			
			// /**
			 // * private/closure method
			 // * @param pageNumber
			 // * @return
			 // */
			// var _getPage = function(pageNumber){
				// if (pageNumber == SNAPPI.STATE.displayPage.page) return;
				// var uri = controller.here + "/.json";				
				// var nameData = {page: pageNumber};
				// if (target.io) {
					// // already plugged, just reuse
					// uri = SNAPPI.IO.setNamedParams(uri, nameData);
					// target.io.set('uri', uri).start();
					// return;
				// }
				// // uses SNAPPI.IO.pluginIO_RespondAsJson() with Plugin.IO
				// target.plug(Y.Plugin.IO, SNAPPI.IO.pluginIO_RespondAsJson({
					// uri: uri ,
					// parseContent:true,
					// nameData: nameData,
					// dataType: 'json',					
					// context: self,					
					// on: {
						// success: function(e, id, o, args) {
								// if (o.responseJson) {
									// PAGE.jsonData = o.responseJson;
									// SNAPPI.mergeSessionData();
									// SNAPPI.domJsBinder.bindAuditions2Photoroll();
									// // TODO: update paginateContainer.Paginator.set('total'), etc									
									// return false;	// plugin.IO already rendered
								// }
							// }
					// }
				// }));
				// return;
			// };
// 			
// 			
			// var pageCfg = {
					// page: displayPage.page,
					// total: displayPage.total,  
					// maxPageLinks: 10,
					// rowsPerPage: displayPage.perpage,
					// rowsPerPageOptions: [displayPage.perpage, 12,24,48,96],
					// alwaysVisible: false,
					// containers: paginateContainer,
					// on: {
						// changeRequest: function(e) {
							// var self = this;
							// var newState = e.state;
							// var userClicked = newState.before !== undefined;
							// if (userClicked) {
								// console.warn('Page.changeRequest: page='+newState.page);
								// _getPage(newState.page);
							// } 
							// self.setState(newState);
						// }
					// }
			// };
			// paginateContainer.Paginator = new Y.Paginator(pageCfg).render();
			// return paginateContainer.Paginator;
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
            SNAPPI.debug.showNodes();
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
					context: this,	// photoroll
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
			var shot = SNAPPI.ShotController.markSubstitutes_afterPostSuccess(this, shotCfg, args.aids);
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
		_ungroupShot_success: function(e, id, o, args) {
        	var response = o.responseJson.response;
        	var hiddenShots = response['unGroupShot']['hiddenShots'],
        		shotIds = response['unGroupShot']['shotIds'];
			var hiddenShots_pr, photoroll = this;
			/*
			 * for hiddenShots
			 */
			if (photoroll.container.hasClass('hiddenshots')) {
				// TODO: CHANGE TO ul.hidden-shots
				hiddenShots_pr = photoroll;
				photoroll = null;
				// search hiddenShots for the node which is ALSO visible in photoroll
				hiddenShots_pr.auditionSH.each(function(audition){
					var unbindNodes = [];
					for (var i in audition.bindTo) {
						var node = audition.bindTo[i];
						if (!photoroll && /^uuid-/.test(node.get('id'))){
							// find parent photoroll
							photoroll = Gallery.getFromChild(node);
						};
						if (node.ancestor('ul') == hiddenShots_pr.container){
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
					SNAPPI.Dialog.find['photo-roll-hidden-shots'].hide();
				} catch (e) {}
				// cancel multiSelect
				SNAPPI.multiSelect.clearAll(this.container);
			}
			/*
			 *  add hiddenShots back to Photoroll 
			 */
			photoroll.addFromCastingCall(hiddenShots, true, args.sort);
			photoroll.render();
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
		_removeFromShot_success: function(e, id, o, args) {
			var response = o.responseJson.response;
			var photoroll, hiddenShots_pr = this;
			/*
			 * for hiddenShots, usually we remove from hiddenShots
			 */
			// TODO: CHANGE TO ul.hidden-shots
			if (hiddenShots_pr.container.hasClass('hiddenshots')) {
				var audition, 
					removed = response['removeFromShot']['assetIds'];
//				var bestShotSystem_changed = response['updateBestShotSystem']['changed'],
//					bestShotSystem_assetId = response['updateBestShotSystem']['asset_id'];
				
				var moveToParent = [];
				for (var i in removed) {
					audition = SNAPPI.Auditions._auditionSH.get(removed[i]);
					/*
					 *  unbind and hide removed node from hiddenShots dialog
					 */
					var unbindNodes = [];
					for (var j in audition.bindTo) {
						var node = audition.bindTo[j];
						if (!photoroll && /^uuid-/.test(node.get('id'))){
							// find (parent) photoroll
							photoroll = Gallery.getFromChild(node);
						};
						if (node.ancestor('ul') == hiddenShots_pr.container){
							unbindNodes.push(node);
						}
					}	
					// wait until after for loop to unbind
					while (unbindNodes.length) {
						var node = unbindNodes.shift();
						SNAPPI.Auditions.unbind(node);
						node.addClass('hide');
					}
					// remove audition from Shot, HiddenShot photoroll 
					audition.Audition.Substitutions.remove(audition);
					hiddenShots_pr.auditionSH.remove(audition);
					moveToParent.push(audition);
				}
				if (!photoroll) {
					// none of the removed photos were visible, search all remaining hiddenShots
					hiddenShots_pr.auditionSH.some(function(audition){
						for (var k in audition.bindTo) {
							var node = audition.bindTo[k];
							if (/^uuid-/.test(node.get('id'))){
								photoroll = Gallery.getFromChild(node);
								return true;
							};
						}
					});
				}
//				if (!photoroll) {
//					photoroll = Y.one('section.gallery.photo');
//					if (photoroll.Gallery) photoroll = photoroll.Gallery; 
//				}
				
				
				/*
				 *  add removed Shots back to Photoroll 
				 */
				while (moveToParent.length) {
					photoroll.auditionSH.add(moveToParent.shift());
				}
				
				// confirm showHidden bestShot is in main photoroll
				var bestShot = hiddenShots_pr.auditionSH.first().Audition.Substitutions.best;
				if (!photoroll.auditionSH.get(bestShot)) {
					photoroll.auditionSH.add(bestShot);
				}
				// update Shot.count for div.hiddenshot
				bestShot.Audition.Shot.count = bestShot.Audition.Substitutions.count();
				
				// render photoroll
				photoroll.auditionSH.sort(args.sort);
				photoroll.render();
				
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
				shotPhotoRoll = args.thumbnail.ancestor('ul.hiddenshots').Gallery;
				var bestShot = selected.Audition.Substitutions.best;
				// confirm showHidden bestShot is in main photoroll
				if (bestShot !== selected) {
					var photoroll = SNAPPI.Y.one('section.gallery.photo').Gallery;
					// splice into original location
					var result = photoroll.auditionSH.replace(bestShot, selected);
					if (result) {
						shotPhotoRoll.selected = selected;
						selected.Audition.Substitutions.setBest(selected);
						photoroll.render();	
						bestShot_Substitution = selected.Audition.Substitutions;
						photoroll.shots[bestShot_Substitution.id]=bestShot_Substitution;
						for (var i in photoroll.shots) {
							var shot = photoroll.shots[i]; 
							photoroll.applyShotCSS(shot);
						}
						
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
         * Show HiddenShots as Preview block
         * @param selected audition - selected audition containing SubstitutionGroup
         * @param shotType string, [Usershot|Groupshot|null], default Usershot
         * @param ul - container for displaying SubstitutionGroup
         * @return
         */    
        showHiddenShotsAsPreview : function(selected, ul) {    	
        	selected = selected || this.auditionSH.getFocus();
        	var ul, parent, shots, Y = SNAPPI.Y;
        	try {
        		ul = ul || Y.one('div#hiddenshots > ul');
        		// ERROR:  selected.Audition.Shot.id != selected.Audition.Substitutions.id
        		shots = selected.Audition.Substitutions;
        		shotType = shots.shotType;
        	} catch (e) {
        		// no hidden shots
        		// /photos/home page: no shots, just hide div#hiddenshots on and unbind nodes
            	if (ul && ul.ancestor('div#hiddenshots')) { 
            		// hide substitute group, if we are on the /photos/home page
            		ul.all('li').addClass('hide').each(function(n,i,l){
    	        		SNAPPI.Auditions.unbind(n);
            		});
            	}
        		return;
        	}
        	var closure = {
        		photoroll : this,
        		selected : selected
        	};
    		
    		/*
    		 * plugin Y.Plugin.IO
    		 */
    		if (!ul.io) {
    			var cfg = {
//    					uri: subUri,
    					parseContent: false,
    					autoLoad: false,
    					context: this,
    					arguments: closure, 
    					on: {
    						successJson: function(e, i,o,args) {
    							if (o.responseJson) {
    								// get auditions from raw json castingCall
    								var shotCC = o.responseJson.castingCall;
    								var onDuplicate = function(a,b) {
    			                    	return a; 	// return original, do not replace
    								};
    								var shotAuditionSH =  SNAPPI.Auditions.parseCastingCall(shotCC, this.castingCall.providerName, null, onDuplicate);
    			                    
    			                    var audition = shotAuditionSH.first();
    			                    var shot = audition.Audition.Substitutions;
    			                    shot.stale = shot._sh.count() != audition.Audition.Shot.count ;
    			                    var shotPr = _showHiddenShots.call(this, ul, shot, args.selected);
    			                    // start listener
    			                    shotPr.listenHiddenShotClick();
    			                    return false;
    							};
    						}					
    					}
    			};
    			ul.plug(Y.Plugin.IO, SNAPPI.IO.pluginIO_RespondAsJson(cfg));
    			// set loadingmask to parent
    			ul.plug(Y.LoadingMask, {
    				target: ul.get('parentNode')
    			});
    			ul.loadingmask._conf.data.value['target'] = ul.get('parentNode');
    			ul.loadingmask.overlayMask._conf.data.value['target'] = ul.loadingmask._conf.data.value['target'];

    		}
    		
    		
    		if (shots.stale == false) {
    			var pr = _showHiddenShots.call(this, ul, shots, selected);
    			pr.listenHiddenShotClick();
    		} else {
    			// shots are NOT included. get shots via XHR and render
    			var subUri = '/photos/hiddenShots/'+shots.id+'/'+shotType+'/.json';
    			ul.io.set('uri', subUri );
    			var ioCfg = ul.io.get('cfg');
    			ioCfg.arguments = closure;
    			ul.io.set('cfg', ioCfg);
    			ul.io.start();			
    		}
        },
        /**
         * show hidden shots in Dialog box
         * @param selected audition- selected audition containing SubstitutionGroup
         * @param shotType string, [Usershot|Groupshot|null], default Usershot
         * @param ul - container for displaying SubstitutionGroup
         * @return
         */    
        showHiddenShotsInDialog : function(selected, shotType, ul) {    	
        	selected = selected || this.auditionSH.getFocus();
        	shotType = shotType || 'Usershot';
    		var shots, 
    			Y = SNAPPI.Y;
    		
            try {
            	shots = selected.Audition.Substitutions;
        		if (!shots ) return; 
            } catch (e) { return; }
        	var closure = {
    			photoroll : this,
        		selected : selected
            };

    		
    		/*
    		 * create or reuse Dialog
    		 */
    		var dialog_ID = 'dialog-photo-roll-hidden-shots';
    		var dialog = SNAPPI.Dialog.find[dialog_ID];
    		if (!dialog) {
            	dialog = SNAPPI.Dialog.CFG[dialog_ID].load();
            	closure.dialog = dialog;
    			var cfg = {
//    					uri: subUri,
    					parseContent: false,
    					autoLoad: false,
    					context: this,
    					arguments: closure,    					
    					on: {
    						successJson: function(e, i,o,args) {
    							if (o.responseJson) {
    								// get auditions from raw json castingCall
    								var shotCC = o.responseJson.castingCall;
    								var onDuplicate = function(a,b) {
    			                    	return a; 	// return original, do not replace
    								};
    								var subAuditionSH =  SNAPPI.Auditions.parseCastingCall(shotCC, this.castingCall.providerName, null, onDuplicate);
    			                    
    			                    var audition = subAuditionSH.first();
    			                    var shot = audition.Audition.Substitutions;
    			                    shot.stale = shot._sh.count() != audition.Audition.Shot.count ;
    			                    // adjust dialog size to fit hiddenShots
    			                    var cells = shot._sh.count() > 6 ? {w:4,h:3} : {w:3,h:2}; 
    			                    var offsets = args.dialog.cellOffsets;
    			                    if (cells.w > 3) offsets.boundingBoxOffset.w += 19; // scrollbar
    			                    args.dialog.get('boundingBox').setStyles({
    			                    	width: cells.w * offsets.cellSize.w + offsets.boundingBoxOffset.w,
    			                    	height: cells.h * offsets.cellSize.h + offsets.boundingBoxOffset.h
    			                    })
    			                    var shotPr = _showHiddenShots.call(this, null, shot, args.selected);
    			                    return shotPr.node;
    							}
    						}					
    					}
    			};
    			dialog.plug(Y.Plugin.IO, SNAPPI.IO.pluginIO_RespondAsJson(cfg));
    			// dialog_ID == dialog.get('boundingBox').get('id')
    			SNAPPI.Dialog.find[dialog_ID] = dialog;
    		} else {
    			if (!dialog.get('visible')) {
    				dialog.setStdModContent('body','<ul />');
    				dialog.show();
    			}
    			dialog.io.set('title', 'Hidden Shots');
    		}
    		
    		
    		if (shots.stale == false) {
    			var ul = dialog.get('bodyContent');
    			if (ul instanceof Y.Node === false) {
    				ul = Y.Node.create('<ul />');
    			};
    			_showHiddenShots.call(this, ul, shots, selected);
    			dialog.setStdModContent('body',ul);
    		} else {
    			// shots are NOT included. get shots via XHR and render
    			var subUri = '/photos/hiddenShots/'+shots.id+'/'+shotType+'/.json';
    			dialog.io.set('uri', subUri );
    			var ioCfg = dialog.io.get('cfg');
    			ioCfg.arguments = closure;
    			dialog.io.set('cfg', ioCfg);    			
    			dialog.io.start();			
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
