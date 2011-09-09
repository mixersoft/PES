/**
 * 
 * Copyright (c) 2009, Snaphappi.com. All rights reserved.
 * 
 * This program is free software: you can redistribute it and/or modify it under
 * the terms of the Affero GNU General Public License as published by the Free
 * Software Foundation, either version 3 of the License, or (at your option) any
 * later version.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the Affero GNU General Public License for more
 * details.
 * 
 * You should have received a copy of the Affero GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * 
 * 
 * @author Michael Lin, info@snaphappi.com
 * 
 * Lightbox script
 * 
 * 
 */
(function() {
	/*
	 * protected
	 */
	var defaultCfg = {
		GET_CASTINGCALL_URI : "http://" + window.location.host
				+ "/photos/getCC/.json",
		ID_PREFIX : 'lightbox-'
	};
	var Y = SNAPPI.Y;
	var charCode = {
		deletePatt : /(^46$)/
	// del
	};
	var _LIGHTBOX_LIMIT = 72;
	var _LIGHTBOX_FULL_PAGE_LIMIT = 999;
	var _LIGHTBOX_MIN_HEIGHT = 100;
	var _LIGHTBOX_MIN_WIDTH = 400;

	var Lightbox = function(cfg) {
		// this is a singleton Class
		if (SNAPPI.Lightbox.instance) return SNAPPI.Lightbox.instance;
		
		this._cfg = null;
		this.node = null;
		this.listener = {};

       this.addToolbar = function(parent){
            
        	var markup = "<a class='button' title='{tip}' href='#' action='{action}'><span>{label}</span></a>";
            
            var markupButton = "<a class='button' title='{tip}' href='#' action='{action}'><span>{label}</span>&nbsp;<span>▼</span></a>";
            
            var title = Y.Node.create('<li class="title">Lightbox</li>');
            parent.append(title);
            var buttons = [
            {
                action: 'selectAll',
                tip: 'Select All',
                label: 'Select All'
            }, {
            	id: 'clear',
                action: 'clear',
                tip: 'Clear',
                label: 'Clear'
            }, {
            	id: 'organizeBtn',
                action: 'organize',
                tip: 'Organize your photos',
                label: 'Organize',
                drop: true
            }, 
            {
                id: 'createBtn',
                action: 'create',
                tip: 'Create page gallery',
                label: 'Create',
                drop: true
            },{
            	// swtich to zoom mode
            	// <li id='zoom-mode' class="button" onclick="SNAPPI.photoRoll.toggleZoomMode(this);">zoom mode</li>
            	id:'lightbox_zoom_btn',
            	action:'toggleZoomMode',
            	tip: 'zoom/noraml mode',
            	label: 'Zoom Mode'
				}
            ];
            for (var i in buttons) {
                
            	
            	var button = new ToolButton(buttons[i]);
            	if(buttons[i].drop){
            		button.addDropIcon();
            	}
            	parent.append(button.btnNode);
            	
            }

			Y.fire('snappi:lightbox-afterToolBarCreated');
			
            return;
        };


		// this.init(cfg);
	};
	
	/*
	 * static properties and methods
	 */
	Lightbox.loadonce = function(selector, event) {
		Lightbox.loadonce = function(){
			return true;
		};
		selector = selector || '#lightbox';
		event = event || 'snappi:afterPhotoRollInit';
		var run_once = function(){
    		once.detach();
            /*
             * lightbox
             */
            var lightboxNode = Y.one(selector);
            if (lightboxNode && SNAPPI.lightbox) {
            	SNAPPI.lightbox.init(); // must be before domJsBinder
            	SNAPPI.lightbox.listen(true);
            	SNAPPI.namespace("SNAPPI.PM");
            };
    	};
    	var once = SNAPPI.Y.on( event, run_once );		
	};
	SNAPPI.Lightbox = Lightbox;


	Lightbox.prototype = {
		init : function(cfg) {
			this._cfg = Y.merge(defaultCfg, cfg);
			this.node = Y.one('section.lightbox');
			if (this.node) {
				// rendered from cakePHP: app/views/elements/lightbox.ctp
			} else { 
				// load/create lightbox asynchronously 
				this.getMarkup(cfg);
				return;
			};
			this.node.Lightbox = this;
			// plugin droppables
			SNAPPI.DragDrop.pluginDrop(this.node);
			
			this.restoreLightboxFromJson();	// lightbox.visible, lightbox.regionAsJSON
//			this.yui2_plugResizeAndDrag.call(this, this.node);	// yui2 lightbox resize and drag	
			this.plugResizeAndDrag.call(this, this.node);		// yui3 lightbox resize and drag			
			var createCfg = {
				align: { points:['bl', 'tl'] },
				trigger: 'section#lightbox ul.menu-trigger li.create'
			};
			SNAPPI.MenuAUI.initMenus({
				'menu-select-all-markup':1,
				'menu-lightbox-organize-markup': 1,
				'menu-lightbox-share-markup':1,
				'menu-pagemaker-selected-create-markup': createCfg
			});
			
			SNAPPI.Lightbox.instance = this;	// singleton class
			Y.fire('snappi:lightbox-afterLoad');
		},
	/**
	 * load lightbox markup
	 * @param cfg
	 * @return
	 */
	getMarkup : function(cfg){
		var Y = SNAPPI.Y;
		var CSS_ID = 'lightbox-markup';
		var MARKUP = {
				id: CSS_ID,
				selector: '#'+CSS_ID,
				container: Y.one('#markup'),
				uri: '/combo/markup/lightbox',
				end: null
		};
		
		var callback = function(){
			// move markup from #markup to dest
			var parent = Y.one('.anchor-bottom');
			if (parent) {
				parent.prepend(Y.one('section#lightbox'));
			} else {
				if (console) console.error('Mission .anchor-bottom for lightbox markup');
			}
			this.init.apply(this, MARKUP, TRIGGER, cfg);
		};
		return SNAPPI.MenuAUI.getMarkup(MARKUP , callback);
	},
    		
	    	/*
	    	 * this is used to remove the existing options to click on the submenu.
	    	 * 
	    	 */
	    	removeOptionChilds: function(selectBoxId) {
	    		
	    		var parent = Y.one('#' + selectBoxId);
	    		
	    		var options = parent.all('option');
	    		if (options.size() > 1){
	    			options.each(function(n, k) {
	        			
	        			if(n.hasClass('help')){
	        			}else {
	        				
	        				parent.removeChild(n);
	        			}
	        			
	                });
	    		}else {
	    		}
	    		
	    	},	
	    	
	    	renderSubmenu: function(buttonName){
	    		/*
	    		if(Y.one('#menu-organizeBtn')){
	    			var organizeBtn = Y.one('#organizeBtn');
	    			SNAPPI.cfg.MenuCfg.renderLightboxOrganize(organizeBtn);
	    		}
	    		else if (Y.one('#menu-createBtn')){
	    			var createBtn = Y.one('#createBtn');
	    			SNAPPI.cfg.MenuCfg.renderLightboxCreate(createBtn);
	    		}
	    		
	    		else{}
	    		*/
	    	},
	    	
	        listen: function(status){
	            status = (status == undefined) ? true : status;
	            if (status) {
	                if (this.listener.keypress == undefined) {
//	                    this.listener.keypress = Y.on('keypress', this.handleKeypress, document, this);
	                    //					this.listener.keypress = Y.on('keyup', this.handleKeypress, this.node, '46', this);
	                }
	                if (this.listener.toolbar == undefined) {
	                    // this.listener.toolbar = this.node.one('ul.toolbar').delegate('click', this.handleClick, 'li.button', this);
	                	this.listener.toolbar = this.node.one('nav.toolbar').delegate('click', this.handleClick, 'ul > li', this);
	                	// listen clicking on toolbar button's submenu
	                	this.listener.options = this.node.one('nav.window-options').delegate('click', this.handleClick, 'ul > li', this);
	                }
	            }
	            else {
	                for (var i in this.listener) {
	                    this.listener[i].detach();
	                }
	                this.listener = {};
	            }
	        },
		toggleZoomMode: function() {
			
			if(!this.node.dom().Zoom){
				
				var bn = Y.one('#lightbox_zoom_btn');
				var Zoom = new SNAPPI.Zoom(this.node, bn);
				
			}
			
			this.node.dom().Zoom.toggleZoomMode();
			
        },		
		stripIdPrefix : function(str) {
			if (str.ynode) {
				str = str.get('id');
			}
			;
			return str.replace(this._cfg.ID_PREFIX, '');
		},
		addIdPrefix : function(str) {
			if (str.ynode) {
				str = str.get('id');
			}
			;
			return this._cfg.ID_PREFIX + str;
		},
		processDrop : function(nodeList, onComplete) {
			var Y = SNAPPI.Y;
			var saveToSession = true;
			
			var LIMIT = _LIGHTBOX_LIMIT;
			if (SNAPPI.STATE.selectAllPages) {
				/*
				 * if we SelectAllPages, we need to get the castingCall for the missing auditions by XHR
				 */
				var callback = {
					complete: function(id, o, arguments) {
						var castingCall = o.responseJson.castingCall;
						this.renderLightboxFromCC.call(this, castingCall);
						this.save();
						this.updateCount();	
						onComplete.call(this, nodeList); // clear selected items
						SNAPPI.STATE.selectAllPages = false;
						// reset .gallery-container li.select-all .checkbox
						try {
							var cb = SNAPPI.Y.one('.gallery-container li.select-all .checkbox');
							cb.set('checked', false);
						} catch (e) {}
					}
				};
				SNAPPI.domJsBinder.fetchCastingCall.call(this, {
					perpage : _LIGHTBOX_FULL_PAGE_LIMIT,
					page : 1,
					skipPaging: true
				}, callback);
				return false; // don't clear selected until XHR call
				// complete
			} else {
				/*
				 * process dropped items only
				 */
				// current lightbox photoroll count
				if (!this.Gallery) {
		            /**
		             * NEW codepath to create Gallery from castingCall
		             */
					// use castingCall from drop source
		            var cfg = {
		            	ID_PREFIX: this._cfg.ID_PREFIX,	
		            	node:  this.node.one('section.gallery.photo'), 
		            	size : 'sq',
		            	addClass : 'lbx-tiny',
			            end: null                		
		            };
		            this.Gallery = new SNAPPI.Gallery(cfg);
		            this.Gallery.listen(true, ['MultiSelect']);
				}	
				// nodeList of img from drag-drop
				nodeList.each(function(n, i, l) {
					var audition = n.ancestor('.FigureBox').audition;
					this.Gallery.auditionSH.add(audition);
				}, this);
				
	            var lastLI = this.Gallery.render( {
					page : 1,
					perpage : LIMIT
				});

				// reset .gallery-container li.select-all .checkbox
				try {
					var cb = SNAPPI.Y.one('.gallery-container li.select-all input[type="checkbox"]');
					cb.set('checked', false);
				} catch (e) {}
	            
				this.save();
				this.updateCount();					
				return lastLI;
			}
		},
		updateCount: function() {
			var count = this.Gallery.auditionSH.count();
			var label = count==1 ? '1 Snap' : count+" Snaps"; 
			this.node.one('.header .count').set('innerHTML', label);
		},
		getSelected : function() {
			var auditionSH,			// return sortedHash, allows auditionSH.each() maintains consistency
			batch = this.node.all('.FigureBox.selected');
		
			if (batch.size() == 0){ 
				// get all assetIds in lightbox, this is the most common use case 
				if(this.Gallery == undefined){ // just initialized, no photos have ever been dragged to lightbox
					return false;
				}
				auditionSH = this.Gallery.auditionSH;
				if(auditionSH.size() == 0){ // no photos in lightbox
					return false;
				}
			} else { // this uses visible selected only, probably less common use case
				auditionSH = new SNAPPI.SortedHash();
				batch.each(function(node){
					auditionSH.add(node.audition);
				});
			}
			return auditionSH;
		},
        getFocus: function() {
        	// TODO: is this method supposed to be the same as getSelected, but returning a NodeList?
        	// to see if there's any focus items in the photo-roll in lightbox
            var batch = this.node.all('.FigureBox.focus');
            // if (batch.size() == 0) {
            	// // then we will check if there's any selected items in the photo gallery
            	// batch = Y.all('section.gallery.photo .FigureBox.focus');
            	// if (batch.size() == 0){
            		// // if none, then select everything in lightbox
            		// // batch = this.node.all('.FigureBox');
            		// batch = null;
            	// }
            // };
			return batch;
		},		
		add : function(n, limit) {
			var LIMIT = limit || 999;
			var Y = SNAPPI.Y,
				overflow = false,
				ul = this.Gallery.container,
				_auditionSH = this.Gallery.auditionSH;
			
			if (n instanceof Y.NodeList) {
				n.some(function(node,i,l){
					if (_auditionSH.count() >= LIMIT) {
						overflow = true;
						return true;
					}					
					var assetId = node.dom().audition.id;
					if (_auditionSH.get(assetId)) return false;	// skip duplicates
					
					var copy = this.copyThumbnail(node);
					copy.removeClass('focus').removeClass('selected');
					// remove all but img in li
					ul.append(copy);
					_auditionSH.add(copy.dom().audition);

					return false;  // "continue" for nodeList.some()
				}, this);
			} else {
				// from drag/drop, the original nodeList is IMG elements
				// TODO: using temp lightbox.Gallery.castingCall. deprecate when schemaParser is passed with audition
				if (!Y.Lang.isArray(n)) {
					n = [ n ];
				}
				for (var i in n) {
					if (_auditionSH.count() >= LIMIT) {
						overflow = true;
						break;
					}					
					var node = n[i];
					var assetId = node.dom().audition.id;
					if (_auditionSH.get(assetId)) continue;	// skip duplicates
					
					var copy = this.copyThumbnail(node);
					copy.removeClass('focus').removeClass('selected');
					// remove all but img in li
					ul.append(copy);
					_auditionSH.add(copy.dom().audition);
				}
			}
			return overflow;
		},
		remove : function(node) {
			try{
				node.remove(); // remove DOM
				this.removeNodeFromBindTo(node); // update bindTo
				// remove from lightbox Gallery
				this.Gallery.auditionSH.remove(node.dom().audition);
			} catch (e) {
			}
		},
		setThumbsize : function (size) {
			var oldsize = null;
			this.Gallery.container.all('.FigureBox'). each(
				function(n, i, l) {
					if (oldsize == null) {
						var haystack = n.getAttribute('class');
						haystack = haystack.split(' ');
						for (var i in haystack) {
							// search for a 2 char classname
							if (haystack[i].length == 2) {
								oldsize = haystack[i];
								break;
							}
						}
					}
					// remove old class
					n.replaceClass(oldsize, size);
					var img = n.one('figure > img');
					var _getImgSrcBySize = n.audition.getImgSrcBySize;
					img.set('src', _getImgSrcBySize(img.get('src'), size));
				}
				, this
			);
		},
		/*
		 * SAVE We need to choose how we save changes to lightbox so we can
		 * restore on next pageLoad a) server: save to CAKEPHP session, + this
		 * secure, i.e. session data IS ONLY for authenticated user - requires
		 * overhead of a server request, saves to SESSION file/db b) desktop:
		 * save to local COOKIE + JS only, no extra load on server - need to
		 * coordinate COOKIE data with current logged in user. ? save to key on
		 * USERID? Is this secure?, this is not avail by JS or SESSIONID?
		 */
        save: function(property, where){
            var where = where || 'server';
            if (where == 'server') 
                this.saveLightboxOnServer();
            else 
                this.saveLightboxByCookie();
        },
        saveLightboxOnServer: function(){
			var postAuditionsData,
				callback = {
	        		complete: function(id, o, args){
			            var check;
		            },
					failure : function(id, o, args) {
						var check;
					}
				};			
			if (this.Gallery.auditionSH.size()){
				var auditionIds = [], selectedIds = [];
				this.Gallery.auditionSH.each(
						function(audition) {
							auditionIds.push(audition.id);
						}, this
				);
				
				this.Gallery.container.all('.FigureBox.selected').each(
						function(n,i,l){
								selectedIds.push(this.stripIdPrefix(n.get('id')));
						}, this
				);

				postAuditionsData = { 
						'lightbox.auditions': auditionIds.join(','), 
						'lightbox.selected': selectedIds.join(',')
				};
			} else {
				// empty
				postAuditionsData = { 
						'lightbox.selected': '',
						'lightbox.auditions': ''
				};
				this.assetIds = {};	// reset assetIds
			}
			SNAPPI.io.writeSession(postAuditionsData, callback, '');	
			return;
        },
        // TODO: move getCastingCallFromAssetIds to utils, or an appropriate place
        getCastingCallFromAssetIds : function(assetIds, callback) {
			var uri = defaultCfg.GET_CASTINGCALL_URI;
			var localCallback = {
				complete: 	function(id, o, args){
					try {
						var castingCall = o.responseJson.castingCall;
  						callback.complete.call(this, castingCall);	
					} catch (e) {
					};
				}, 
				failure: function(id, o, args){
					var check;
				}
			};
			var postData = {"data[Asset][ids]": assetIds};
			SNAPPI.io.post.call(this, uri, postData, localCallback);
        },
        /*
         * makes sure the auditions retrieved for lightbox are replaced by matching ones in Gallery
         * This should be automatic with parseCastingCall()
         */
        get_auditionSHfromCastingCall : function(castingCall, providerName) {
        	providerName = providerName || castingCall.providerName || 'snappi';
   			// NO REPLACE - if duplicate found, use existing audition, DEFAULT
   			return SNAPPI.Auditions.parseCastingCall(castingCall, providerName, null);
        },
        restoreLightboxFromJson: function() {
    		try{	// restore lightbox regsion from JSON
    			if(PAGE.jsonData.lightbox.regionAsJSON){
    				var region = eval("("+PAGE.jsonData.lightbox.regionAsJSON+")");
    				if(region){
    					this.node.setStyles({
    						'left': region.left,
    						'top': region.top,
    						'width': region.width,
    						'height': region.height
    						}
    					);
    				}
    			}
    		}catch(e){}
    		
    		try{	// restore lightbox innerHTML from JSON
    			if (this.Gallery) this.Gallery.node.addClass('hide');
    			if (PAGE.jsonData.lightbox.castingCall){
					var castingCall = PAGE.jsonData.lightbox.castingCall;
					// render castingCall in Lightbox
					this.renderLightboxFromCC.call(this, castingCall);
					
	    			if (PAGE.jsonData.lightbox.full_page){
	    				// set to full page mode
	    				this.node.setAttribute('style','');
	    				this.node.addClass('full-page');
	    				this.setThumbsize(PAGE.jsonData.lightbox.thumbsize);
	    			}
    			};
    			if (PAGE.jsonData.lightbox.full_page){
    				// set to full page mode
					this.node.setAttribute('style','');
					this.node.addClass('full-page');
    			}     
    			this.Gallery.node.removeClass('hide');
    		}catch(e){
    			if (this.Gallery) this.Gallery.node.removeClass('hide');
    		}
    		
//    		if (!waitForCallback) this.Gallery.container.removeClass('hide');
    		
    		
    		// restore lightbox visible from JSON
    		var show = true;	// default value
    		var foldButtonNode = Y.one('#lbx-foldBtn');
    		try{	
            	if(PAGE.jsonData.lightbox.visible === 'false'){

            		this.node.addClass('hide');	
            	} else throw("show Lightbox"); 
    		}catch(e){
    			// catch "show lightbox" exception. shows lightbox
        		this.node.removeClass('hide');
    		}

			try {
				var selects = PAGE.jsonData.lightbox.selected;
				var node, array_selected = selects.split(',');
				
				Y.each(array_selected, function(id, k) {
					node = this.node.one('#' + this.addIdPrefix(id));
					node.addClass('selected');
		        }, this);
				
			}catch(e){}
			
        },
        /*  
         * used by processDrop, 
         */
        renderLightboxFromCC: function(castingCall) {
        	var LIMIT; 
        	try {
        		LIMIT = (PAGE.jsonData.lightbox.full_page) ? _LIGHTBOX_FULL_PAGE_LIMIT : _LIGHTBOX_LIMIT;
        	} catch(e) {
        		LIMIT = _LIGHTBOX_LIMIT;
        	}
        	
			// get auditions from castingCall
			var parsedCastingCall_AuditionSH = this.get_auditionSHfromCastingCall(castingCall);
			
			if (!this.Gallery) {
	            /**
	             * NEW codepath to create Gallery from castingCall
	             */
				// use castingCall from drop source
//						var pr = nodeList.item(0).ancestor('section.gallery.photo').Gallery;
	            var cfg = {
	            	ID_PREFIX: this._cfg.ID_PREFIX,	
	            	node:  this.node.one('section.gallery.photo'), 
	            	shots: castingCall.shots,
	            	// castingCall: castingCall,
		            end: null                		
	            };
	            switch (SNAPPI.STATE.controller.action) {
		            case 'lightbox':
		            	cfg.size = 'tn';
		            	break;
		            case 'pagemaker':
		            	cfg.size = 'tn';
		            	break;
	            	default:
	            		cfg.size = 'sq';
		            	cfg.addClass = 'lbx-tiny';	            	
	            		cfg.hideSubstituteCSS = true;
		            	break;
	            };
	            this.Gallery = new SNAPPI.Gallery(cfg);			// does NOT render here
	            this.Gallery.listen(true, ['MultiSelect']);	
			}
			
			// add new auditions to existing Gallery.auditionSH
			parsedCastingCall_AuditionSH.each(function(audition){
				this.Gallery.auditionSH.add(audition);
			}, this);
			
            this.Gallery.render( {
				page : 1,
				perpage : LIMIT
			});        	
         },
        /*
         * render popup menus
         */
        // add a parameter 'e' here. 
        organize: function() {
        	
        	/*
        	if(!Y.one('#submenu-organize')){
        		if(Y.one('#menu-organizeBtn')){
	    			var organizeBtn = Y.one('#organizeBtn');
	    			SNAPPI.cfg.MenuCfg.renderLightboxOrganize(organizeBtn);
	    		}
	    		else if (Y.one('#menu-createBtn')){
	    			var createBtn = Y.one('#createBtn');
	    			SNAPPI.cfg.MenuCfg.renderLightboxCreate(createBtn);
	    		}
        		this.renderSubmenu('organizeBtn');
        	}
        	*/
        	if(!Y.one('#menu-organizeBtn')){
    			
        		SNAPPI.cfg.MenuCfg.renderLightboxOrganize();
    		}
        	
        },
        create: function(e) {
        	if (!Y.one('#menu-createBtn')){
        		SNAPPI.cfg.MenuCfg.renderLightboxCreate();
    		}
        },
                        
		clear : function() {
        	// NOTE: in this method, we do NOT want to use getSelected();
			var set = this.Gallery.container.all('.FigureBox.selected');
			if (set.size() == 0) {
				var ret = confirm('Are you sure you want to clear all?');
				if (!ret) {
					return; // cancel clear All
				}
				this.Gallery.auditionSH.clear();	// remove all audition from lightbox.Gallery
				set = this.Gallery.container.all('.FigureBox');  // remove all visible thumbnails from lightbox
			}

			set.each(function(n, i, l) {
				this.remove(n);
			}, this);
			this.save();
			
            this.Gallery.render();  

			/*
			 * fire custom event
			 */
			this.onClearLightbox();
		},
		removeNodeFromBindTo : function(n) {
			try {
				var thumbs = n.dom().audition.bindTo || [];
				for ( var j in thumbs) {
					var n2 = thumbs[j];
					if (n == n2) {
						thumbs.splice(j, 1);
						j -= 1;
					}
				}
			} catch (e) {
			}
		},
		onClearLightbox : function() {
			// Lightbox Organize > rating is reset in beforeShow
			if (console) console.warn("Lightbox.onClearLightbox is deprecated");
			// // reset rating component to 0
			// try {
				// this.node.one('#lbx-rating').Rating.render(0);
			// } catch (e) {}
		},
		selectAll : function() {
			SNAPPI.multiSelect.selectAll(this.Gallery.container);
		},
		groupAsShot : function() {
			var post_aids = [],
				idPrefix = this._cfg.ID_PREFIX || null;
			var batch = this.getSelected();
           batch.each(function(audition){
            	post_aids.push(audition.id);
            }, this);   				
			var callback = {
				complete : function(shot, resp) {
					try {
						this.Gallery.applyShotCSS(shot);	// do we want to see CSS in lightbox?
						// remove hidden subs from lightbox
						this.node.all('li.hiddenshot-hide').each(function(n,i,l){
							this.remove(n);
						}, this);
						this.save();
						SNAPPI.lightbox.onGroupAsShotComplete(shot, resp);
					} catch (e) {};
					// TODO: close organize menu, show flash msg
				}
			};
            SNAPPI.shotController.xxxpostGroupAsShot.call(this, post_aids, callback);
		},
		onGroupAsShotComplete : function (shot, resp){
			SNAPPI.flash.flash(resp.message);
		},
		/**
		 * remove lightbox selected items from their respective Shots
		 * @return
		 */
		unGroupShot : function() {
			var shotIds = [], 
				shotId,
				batch = this.getSelected();
			
			if(batch.size() == 0){
				SNAPPI.flash.flash("You have no items selected.");
				return false;
			}
			
			batch.each(function(audition) {
				shotId = audition.Audition.Substitutions.id;
				if(shotId){
					shotIds.push(shotId);
				}
            });
			var callback = {
					complete : function(resp) {
						if (resp.success == 'true')  {
							SNAPPI.flash.setFlashOnReload(resp.message);
							window.location.reload();							
						}
					},
					failure : function(id, o, args) {
						var check;
					}
				};
			
			SNAPPI.shotController.postUnGroup.call(this, shotIds, callback);
		},

		/*
		 * ratings
		 */
		renderRating : function(node) {
			// TODO: for now, just replace the button. later, render in subMenu
			var cfg = {
				// el : node.dom(),
				id : "lbx_ratingGrp",
				uuid : false,
				'applyToBatch' : this.applyRatingInBatch
			};
			SNAPPI.Rating.attach(node, cfg);
		},
		applyRatingIndividually : function(v) {
			/*
			 * NOTE: this method does not use callback to complete the
			 * transaction and render rating in component after rating is
			 * applied to DB
			 */
			var self;
			if (this && this.applyRatingIndividually)
				self = this;
			else
				self = SNAPPI.lightbox;
			var batch = self.getSelected();

			/*******************************************************************
			 * add set Rating for thumbnail from lightbox
			 */
			var _applyOneRating = function(audition, value) {
				try {
					audition.rating = value;
					audition.bindTo[0].Rating.onClick(audition.rating, false);	// onscreen, Rating exists
				} catch (e) {
					// SNAPPI.AssetRatingController.postRating.call(audition.bindTo[0].dom(), value, audition.id); 	// offscreen
					var node = audition.bindTo[0];
					SNAPPI.AssetRatingController.postRating( value, audition.id, node.Rating, null); 	// offscreen
				}
			};

			batch.each(function(audition) {
				_applyOneRating(audition, v);
			});
		},
		applyRatingInBatch : function(v, node) {

			var self;
			if (this instanceof SNAPPI.Lightbox) self = this;
			else self = SNAPPI.lightbox;

			var batch = self.getSelected();

			var asset_ids = [];
			batch.each(function(audition) {
				asset_ids.push(audition.id);
			});
			node = node || SNAPPI.Y.one('#lbx-rating .ratingGroup'); // .ratingGroup 
			SNAPPI.AssetRatingController.postRating(v, asset_ids.join(','), node.Rating, null);
		},
		/*
		 * Tags
		 */
		renderTagInput : function(node) {
			// TODO: for now, just add to the button. later, render in subMenu
			var nTagForm = Y.Node
					.create("<span><form id='lbx-tag-form' onsubmit='return false;' /><input id='lbx-tag-field' class='help' type='text' size='20' maxlength='255' value='Enter tags' onclick='this.value=null; this.className=null;'><input type='submit' value='Go' onclick='SNAPPI.lightbox.applyTagInBatch(this);return false;'/></form></span>");
			node.append(nTagForm);
		},
		applyTagInBatch : function(submit) {
			var parent = submit.ynode().ancestor('form');
			var text = parent.one('input#lbx-tag-field');
			var tag = text.get('value');

			// post Tags
			var self;
			if (this instanceof SNAPPI.Lightbox) self = this;
			else self = SNAPPI.lightbox;

			var batch = self.getSelected();

			var asset_ids = [];
			batch.each(function(audition) {
				asset_ids.push(audition.id);
			});
			var uri = "/photos/setprop/.json";
			var data = {
				'data[Asset][id]' : asset_ids,
				'data[Asset][tags]' : tag
			};
			var args = {
				node : parent,
				tag : tag
			};
			// use Plugin to add io request and loadingmask
			var loadingNode = parent;
			if (loadingNode.io == undefined) {
				var ioCfg = SNAPPI.IO.pluginIO_RespondAsJson({
					uri: uri ,
					parseContent:true,
					method: 'POST',
					qs: data,
					dataType: 'json',
					context: self,	
					arguments: args, 
					on: {
						successJson:  function(e, id, o, args) {
							var msg = o.responseJson.message;
							this.onTagSaved(args, msg);
							return false;
							// return args.success.apply(this, arguments);
						}
					}
				});
	            loadingNode.plug(Y.Plugin.IO, ioCfg );
			} else {
				loadingNode.io.set('data', data);
				loadingNode.io.set('context', self);
				loadingNode.io.set('uri', uri);
				loadingNode.io.set('arguments', args);
				loadingNode.io.start();
	        }			
			
			// SNAPPI.io.post(uri, data, callback);
			return false;
		},
		onTagSaved : function(cfg, message) {
			// use 'snappi:photo-propertyChanged'
			var n = cfg.node;
			var tag = cfg.tag;
			if (n.loadingmask) n.loadingmask.hide();
			/*
			 * just reload for now - this will show the Session->flash from the
			 * Tag save - reload tagCloud with updated tags - update auditions
			 * with tags BUT: - this will reset paging
			 */
//			window.location.reload();
		},
		/*
		 * Share/Contribute
		 */
        renderShareInput: function(node){
        	// var nShare = Y.Node.create("<ul id='lbx-share-list'></ul>");
        	var nShare = Y.one('#lbx-share-list-1');
        	
        	if(nShare == null){
        		
        		var markup = "<ul title='share groups' id='lbx-share-list-1'></ul>";
        		var n = Y.Node.create(markup);
        		nShare = node.appendChild(n);
        		
        		nShare.append(Y.Node.create("<li class='loading' style='display: block;'>Loading</li>"));
        		
        	}
        	
            var _renderGroupOptions = function(selectNode, jsonGroups, Y){
                var json = eval('(' + jsonGroups + ')');
                var optionMarkup = "<li class='2dialog'><a>{label}</a></li>";
                var more = false;
                for (var i in json.Groups) {
                    var cfg = {
                        value: json.Groups[i].id,
                        label: json.Groups[i].title
                    };
                    var option = Y.Node.create(Y.substitute(optionMarkup, cfg));
                    selectNode.append(option);
                    option.dom()._value = cfg.value;
                    if(i >= 5){
                    	if (!more){
                    		var more_btn = "<li class='more-btn-todialog'><a>more groups...</a></li>";
                        	selectNode.append(more_btn);
                        	more = true;
                    	}
                    	
                    	option.addClass('hide');
                    }
                }
                // b_line : bottom line for testing inViewPort
                var b_line = "<div class='testInView'></div>";
            	selectNode.append(b_line);

                nShare.all('li.loading').remove();
                nShare.removeClass('hidden');
                
                Y.fire('snappi:checkRegion', nShare);
                
                /*
                 * TODO: refactor delegate approach
                 * I don't know why parent.delegate(event, function(){}, 'li.classname')'s "li.classname" doesn't work well
                 * what i m trying to do is to delegate click events to all LI.todialog, then i can avoid the '.more-btn-todialog'
                 * but now i can only delegate to LI
                 * 
                 */
                Submenu.dialog.attachClickListener(nShare);

                nShare.one('.more-btn-todialog').on('click', function(e){
                	
                	Submenu.dialog.moreToDialog(nShare);
                	
                });
                
                
            };
            var groupRoll = Y.one('#json-Groups');
            if (groupRoll) {
                _renderGroupOptions(select, groupRoll.getAttribute('groups'), Y);
            }
            else {

                // get Groups from /my/groups/.json
                var uri = '/my/groups/.json';
                var callback = {
                    complete: function(id, o, args){
                        _renderGroupOptions(args.select, o.responseText, args.Y);
                        
                    }
                }
                var iocfg = {
                    method: "GET",
                    on: callback,
                    timeout: 2000,
                    context: this,
                    arguments: {
                        select: nShare,
                        Y: Y
                    }
                };
                Y.io(uri, iocfg);
            }

            node.append(nShare);
        },
        applyShareInBatch : function(gid, loading, options) {
        	var self;
			if (this instanceof SNAPPI.Lightbox) self = this;
			else self = SNAPPI.lightbox;
			
			var batch = self.getSelected();
	
			var asset_ids = [];
			batch.each(function(audition) {
				asset_ids.push(audition.id);
			});
						
			var uri = "/groups/contributePhoto/.json";
			var data = {
				'data[Group][id]' : gid,
				'data[Asset][id]' : asset_ids
			};
			
			/*
			 * adjustments for 'remove from group'
			 */
			if (options && options.uri) uri = options.uri;	// for unshare link
			if (options && options.data) data = SNAPPI.Y.merge(data, options.data);
			
			var args = {
				gid: gid
			};
			// use Plugin to add io request and loadingmask
			var loadingNode = loading;
			if (loadingNode.io == undefined) {
				var ioCfg = SNAPPI.IO.pluginIO_RespondAsJson({
					uri: uri ,
					parseContent:true,
					method: 'POST',
					qs: data,
					dataType: 'json',
					context: self,	
					arguments: args, 
					on: {
						successJson:  function(e, id, o, args) {
							SNAPPI.Y.fire('snappi:share-complete', this, loadingNode);
							this.onShareGroupComplete(args.gid, o.responseJson.message);
						}
					}
				});
	            loadingNode.plug(Y.Plugin.IO, ioCfg );
			} else {
				loadingNode.io.set('data', data);
				loadingNode.io.set('context', self);
				loadingNode.io.set('uri', uri);
				loadingNode.io.set('arguments', args);
				loadingNode.io.start();
	        }			
        },
		onShareGroupComplete : function(gid, flash) {
			// go to group
			SNAPPI.flash.setFlashOnReload(flash);
			// window.location.href = '/groups/home/' + gid;
		},
	
		/*
		 * UnShare
		 */
	    renderUnShareInput: function(node){
	        // TODO: for now, just add to the button. later, render in subMenu
	        // var nShare = Y.Node.create("<span><form id='lbx-unShare-form' class='hidden' onsubmit='return false;' /><select id='lightbox-unShare'><option class='help' value='' SELECTED >[my Groups]</option></select><input type='submit' value='Go' onclick='SNAPPI.lightbox.applyUnShareInBatch(this);return false;'/></form></span>");
			// var nShare = Y.Node.create("<ul id='lbx-share-list'></ul>");
			var unShare = Y.one('#lbx-unShare-list-1');
			
			if(unShare == null){	
	    		
	    		var markup = "<ul title='share groups' id='lbx-unShare-list-1'></ul>";
	    		var n = Y.Node.create(markup);
	    		unShare = node.appendChild(n);
	    		
	    		unShare.append(Y.Node.create("<li class='loading' style='display: block;'>Loading</li>"));
	    		
	    	}
			
	        var _renderGroupOptions = function(selectNode, jsonGroups, Y){
	        	var more = false;
	            var json = eval('(' + jsonGroups + ')');
	            var optionMarkup = "<li><a title='{value}'>{label}</a></li>";
	            for (var i in json.Groups) {
	                var cfg = {
	                    value: json.Groups[i].id,
	                    label: json.Groups[i].title
	                    
	                };
	                var option = Y.Node.create(Y.substitute(optionMarkup, cfg));
	                selectNode.append(option);
	                option.dom()._value = cfg.value;
	                if(i >= 5){
	                	if (!more){
	                		var more_btn = "<li class='more-btn-todialog'><a>more groups...</a></li>";
	                    	selectNode.append(more_btn);
	                    	more = true;
	                	}
	                	
	                	option.addClass('hide');
	                }
	            }
	            
	            unShare.all('li.loading').remove();
	            unShare.removeClass('hidden');
	            
	            Y.fire('snappi:checkRegion', unShare);
	            
	            Submenu.dialog.attachClickListener(unShare);
	            
	            unShare.one('.more-btn-todialog').on('mouseover', function(e){
	            	if(e.target.one('a')){
	            		e.target.one('a').focus();
	            	}
	            });
	            
	            unShare.one('.more-btn-todialog').on('click', function(e){
	            	// alert("clicking");
	            	Submenu.dialog.moreToDialog(unShare);
	            	
	            });
	            
	            unShare.delegate('mouseover', function(e){
					if(e.target.one('a')){
						e.target.one('a').focus();
					}
				}, 'li');
	            
	        }
	        
	        var groupRoll = Y.one('#json-Groups');
	        if (groupRoll) {
	            _renderGroupOptions(select, groupRoll.getAttribute('groups'), Y);
	        }
	        else {
	            // get Groups from /my/groups/.json
	            var uri = '/my/groups/.json';
	            var callback = {
	                complete: function(id, o, args){
	                    _renderGroupOptions(args.select, o.responseText, args.Y);
	                }
	            }
	            var iocfg = {
	                method: "GET",
	                on: callback,
	                timeout: 2000,
	                context: this,
	                arguments: {
	                    select: unShare,
	                    Y: Y
	                }
	            };
	            Y.io(uri, iocfg);
	        }
	
	        node.append(unShare);
	    },
		applyUnShareInBatch : function(gid) {
			// post Tags
			var self;
			if (this && this.applyShareInBatch)
				self = this;
			else
				self = SNAPPI.lightbox;
	
			var batch = self.getSelected();
	
			var asset_ids = [];
			batch.each(function(audition) {
				asset_ids.push(audition.id);
			});
			/***********************************************************************
			 * add assets to Group - cakePHP POST
			 */
			var _unShareWithGroup = function(g, assets) {
				var uri = "/photos/setprop/.json";
				var data = {
					'data[Group][id]' : g,
					'data[Asset][id]' : assets,
					'data[Asset][unshare]' : 1
				};
				var callback = {
					complete : function(id, o, args) {
						var check;
						if (o.responseJson && o.responseJson.success == 'true')  {
							SNAPPI.lightbox.onUnShareGroupComplete(args.gid, o.responseJson.message);
						}
					},
					failure : function(id, o, args) {
						var check;
					}
				};
				var arguments = {
					gid : g
				};
				SNAPPI.io.post(uri, data, callback, arguments);
			};
			_unShareWithGroup(gid, asset_ids);
			var check;
		},
		onUnShareGroupComplete : function(gid, flash) {
			// go to group
			SNAPPI.flash.setFlashOnReload(flash);
			window.location.href = '/groups/home/' + gid;
		},
		/*
		 * Set Photo Privacy
		 */
		renderPrivacyInput : function(node) {
			// TODO: for now, just add to the button. later, render in subMenu
			// var nShare = Y.one('#lbx-share-list');
	    	// nShare.one('li').removeClass('working');
			var nPrivacy = Y.one('#lbx-privacy-list-1'); 
			
			if(nPrivacy == null){	
	    		
	    		var markup = "<ul title='share groups' id='lbx-privacy-list-1'></ul>";
	    		var n = Y.Node.create(markup);
	    		nPrivacy = node.appendChild(n);
	    		
	    		nPrivacy.append(Y.Node.create("<li class='loading' style='display: block;'>Loading</li>"));
	    		
	    	}
	
			var _renderOptions = function(selectNode, jsonOrCfg, Y) {
				var more = false;
				var cfg = (Y.Lang.isString(jsonOrCfg)) ? eval('(' + jsonOrCfg + ')')
						: jsonOrCfg;
				var optionMarkup = "<li><a>{label}</a></li>";
				for ( var i in cfg) {
					var tokens = {
						value : cfg[i].id,
						label : cfg[i].title
					};
					var option = Y.Node.create(Y.substitute(optionMarkup, tokens));
					selectNode.append(option);
					option.dom()._value = tokens.value;
					if(i >= 5){
	                	if (!more){
	                		var more_btn = "<li class='more-btn-todialog'><a>more groups...</a></li>";
	                    	selectNode.append(more_btn);
	                    	more = true;
	                	}
	                	
	                	option.setStyle('display', 'none');
	                }
				}
				
				nPrivacy.all('li.loading').remove();
				nPrivacy.removeClass('hidden');
	            
				Y.fire('snappi:checkRegion', nPrivacy);
				
	            Submenu.dialog.attachClickListener(nPrivacy);
	            
	            if(nPrivacy.one('.more-btn-todialog')){
		            nPrivacy.one('.more-btn-todialog').on('mouseover', function(e){
		            	if(e.target.one('a')){
		            		e.target.one('a').focus();
		            	}
		            });
		            
		            nPrivacy.one('.more-btn-todialog').on('click', function(e){
		            	// alert("clicking");
		            	Submenu.dialog.moreToDialog(unShare);
		            	
		            });
	            }
	            
	            nPrivacy.delegate('mouseover', function(e){
					if(e.target.one('a')){
						e.target.one('a').focus();
					}
				}, 'li');
				
			};
	
			// TODO: this privacy setting is hard coded
			var privacySettings = [
					{
						id : 519,
						title : "<b>Public</b> - are publicly listed and visible to anyone."
					},
					{
						id : 71,
						title : "<b>Members only</b> - are NOT publicly listed, and are visible only when shared in Groups or Events, and only by Group members."
					},
					{
						id : 7,
						title : "<b>Private</b> - are NOT publicly listed and visible only to me."
					} ];
			_renderOptions(nPrivacy, privacySettings, Y);
	
			node.append(nPrivacy);
		},
		applyPrivacyInBatch : function(value, loading) {
			// post 
        	var self;
			if (this instanceof SNAPPI.Lightbox) self = this;
			else self = SNAPPI.lightbox;
	
			var batch = self.getSelected();
	
			var asset_ids = [];
			batch.each(function(audition) {
				asset_ids.push(audition.id);
			});
			/***********************************************************************
			 * - cakePHP POST
			 */
			var uri = "/photos/setprop/.json";
			var data = {
				'data[Asset][privacy]' : value,
				'data[Asset][id]' : asset_ids.join(',')
			};
			var args = {
				privacy : value
			};
			// use Plugin to add io request and loadingmask
			var loadingNode = loading;
			if (loadingNode.io == undefined) {
				var ioCfg = SNAPPI.IO.pluginIO_RespondAsJson({
					uri: uri ,
					parseContent:true,
					method: 'POST',
					qs: data,
					dataType: 'json',
					context: self,	
					arguments: args, 
					on: {
						successJson:  function(e, id, o, args) {
							SNAPPI.Y.fire('snappi:privacy-complete', this, loadingNode);
							SNAPPI.lightbox.onPrivacyGroupComplete(args.privacy, o.responseJson);
						}
					}
				});
	            loadingNode.plug(Y.Plugin.IO, ioCfg );
			} else {
				loadingNode.io.set('data', data);
				loadingNode.io.set('context', self);
				loadingNode.io.set('uri', uri);
				loadingNode.io.set('arguments', args);
				loadingNode.io.start();
	        }			
		},
		onPrivacyGroupComplete : function(privacy, resp) {
			// go to group
			SNAPPI.flash.flash(resp.message);
//			window.location.reload();
		},
		launchPageGallery : function() {
			/*
			 * get http request for casting call - supports either GET or POST -
			 * POST can take a larger data payload, i.e. 100s of UUIDs
			 */
			// TODO: add support for http GET
			var uri = defaultCfg.GET_CASTINGCALL_URI;
	
			var aid, assetIds = new Array();
			this.Gallery.container.all('.FigureBox').each(function(n, i, l) {
				aid = n.get('id').replace(this._cfg.ID_PREFIX, '');
				assetIds.push(aid);
			}, this);
			var aidsAsString = assetIds.join(",");
			var postdata = "data[Asset][ids]=" + aidsAsString;
	
			var ioCfg = {
				method : "POST",
				data : postdata,
				uri : uri
			};
			
			// TODO: switch to using SNAPPI.io.post() internally 
		
			if (SNAPPI.PM.main) {
				SNAPPI.PM.main.launch(ioCfg);
			} else {
				
				SNAPPI.PM.pageMakerPlugin = new SNAPPI.PageMakerPlugin();
				SNAPPI.PM.pageMakerPlugin.load( 
					function() {
						SNAPPI.PM.main.go(ioCfg);
					}
				);
				return;
			}
		},
		handleKeypress : function(e) {
			// console.log(this.container.get('parentNode'));
			if (!this.node.ancestor('#content')) {
				// container is no longer visible, detach all listeners
				// probably replaced by new ajax fragment
				this.listen(false);
				return;
			}
			var charStr = e.charCode + '';
			if (!e.ctrlKey && !e.shiftKey
					&& charStr.search(charCode.deletePatt) == 0) {
				e.preventDefault();
				this.clear.call(this);
			}
		},
		handleClick : function(e) {
			var action = e.currentTarget.getAttribute('action');
			if (action) {
				e.currentTarget.get('parentNode').all('li').removeClass('focus');
				this.action[action].call(this, e);
			}
		},
		action: {
			// hide lightbox
			minimize: function(e){
				this.Gallery.container.ancestor('.filmstrip-wrap').addClass('hide');
				e.currentTarget.addClass('focus');
			},
			// HScroll thumbnails, 1 row only
			filmstrip: function(e){
				// set width in pixels for 1 line
				var count = Math.min(this.Gallery.auditionSH.count(), _LIGHTBOX_LIMIT);
				var width = this.Gallery.container.one('.FigureBox').get('offsetWidth');
				this.Gallery.container.setStyles({
					width: (width*count)+'px',
					height: 'auto'	
				});
				this.Gallery.container.ancestor('.filmstrip-wrap').removeClass('hide');
				if (e) e.currentTarget.addClass('focus');
			},
			// VScroll thumbnails
			maximize: function(e) {
				var MAX_HEIGHT = window.innerHeight - 120;
				var count = Math.min(this.Gallery.auditionSH.count(), _LIGHTBOX_LIMIT);
				var width = this.Gallery.container.one('.FigureBox').get('offsetWidth');
				var rows = Math.ceil(count*width/940);
				var height = this.Gallery.container.one('.FigureBox').get('offsetHeight');
				if (rows*height > MAX_HEIGHT) {
					rows = Math.floor(MAX_HEIGHT/height);
					height = (rows*height)+'px';
				} else {
					height = 'auto';
				}
				this.Gallery.container.setStyles({
					width: 'auto',
					height: height	
				});
				this.Gallery.container.ancestor('.filmstrip-wrap').removeClass('hide');
				e.currentTarget.addClass('focus');
			},
			'set-display-size' : function(e) {
				var srcSize, displaySize;
				// displaySize: [lbx-tiny | sq | lm], also "summary"
				try {
					displaySize = e.currentTarget.getAttribute('size');	
				} catch (e) {
					try {
						displaySize = SNAPPI.STATE.lightbox.displaySize;
					} catch (e) {}
				}
				displaySize = displaySize || 'lbx-tiny';
				srcSize = this.Gallery._cfg.size;
				if (displaySize == 'summary') {
					// show cover thumbnail only, plus summary description
				} else { 
					switch (srcSize+'+'+displaySize) {
						case "sq+lbx-tiny": 
						case "sq+sq":
						case "lm+lm":
							// do nothing
							break;
						case "lm+lbx-tiny":
						case "lm+sq":
							// convert thumbnails from lm > sq
							this.Gallery.renderThumbSize('sq');
							break;						
						case "sq+lm":
							// convert thumbnails from sq > lm
							this.Gallery.renderThumbSize('lm');
							break;
					};
				}
				// use class to set displaySize to lbx-tiny
				if (displaySize == 'lbx-tiny') {
					this.Gallery.container.all(".FigureBox").addClass('lbx-tiny');
				} else this.Gallery.container.all(".FigureBox").removeClass('lbx-tiny');
				
				// set container to fit new displaySize, i.e. filmstrip mode
				if ( this.Gallery.container.getStyle('width') == 'auto') {
					this.action.maximize.apply(this, null);
				} else {
					this.action.filmstrip.apply(this, null);
				};
				
				// set focus
				e.currentTarget.get('parentNode').all('li').removeClass('focus');
		        e.currentTarget.addClass('focus');
			}
		},
		plugResizeAndDrag : function(node) {
			var Y = SNAPPI.Y;
			var node = node || this.node;
			node.plug(Y.Plugin.Drag); 
			// we may need to narrow the lightbox, then it's much convenient for user.
			node.dd.plug(Y.Plugin.DDConstrained, {
				constrain2node : '#container'
			});
			
			node.dd.addHandle('.toolbar');
			var callback = {
	    		complete: function(id, o, args){
		            var check;
	            },
				failure : function(id, o, args) {
					var check;
				}
			};
			
			node.dd.on('drag:end', function(){
				var region = node.get('region');
	            var postData = {'lightbox.regionAsJSON': region};
	            SNAPPI.io.writeProfile(postData, callback, '');
			}, this);
			/*
			 * disable AUI-resize
			 */
			// var resize = new Y.Resize({
				// node : '#lightbox'
			// });
			// resize.plug(Y.Plugin.ResizeConstrained, {
				// minHeight : _LIGHTBOX_MIN_HEIGHT,
				// minWidth  : _LIGHTBOX_MIN_WIDTH 
			// });
		    // resize.on('endResize', function(){
		    	// var region = node.get('region');
	            // var postData = {'lightbox.regionAsJSON': region};
	            // SNAPPI.io.writeProfile(postData, callback, '');
		    // });
		},
		yui2_plugResizeAndDrag : function (node) {
			var Y = SNAPPI.Y;
			var node = node || this.node;
			node.plug(Y.Plugin.Drag); 
			node.dd.addHandle('.toolbar');
			var callback = {
	   		complete: function(id, o, args){
		            var check;
	           },
				failure : function(id, o, args) {
					var check;
				}
			};
			
			node.dd.on('drag:end', function(){
				var region = node.get('region');
	           var postData = {'lightbox.regionAsJSON': region};
	           SNAPPI.io.writeProfile(postData, callback, '');
			}, this);
			
			/* 
			 * YUI3 version of resize from 
			 * http://www.yuiblog.com/blog/2010/03/25/gallery-resize/
			 * 
			 * but doesn't work
			 * Y.one( "#lightbox" ).plug( Y.Plugin.Resize );
			 * 
			 */
		    
			var YAHOO = Y.YUI2;
		    var resize = new YAHOO.util.Resize('lightbox');
		    resize.on('endResize', function(){
		    	var region = node.get('region');
	           var postData = {'lightbox.regionAsJSON': region};
	           SNAPPI.io.writeProfile(postData, callback, '');
		    });
		},
        showThumbnailRatings : function(node){
			var pr = node || this.Gallery;
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
            SNAPPI.debug_showNodes();
        },
        hideThumbnailRatings : function(node){
        	var pr = node || this.Gallery;
            var thumbs = pr.container.all('.FigureBox');
            pr.container.all('div.ratingGroup').addClass('hide');
            pr.container.all('div.thumb-label').removeClass('hide');
            SNAPPI.STATE.showRatings = 'hide';	
            SNAPPI.Rating.stopListeners(pr.container);
        }
	
	};  // end Lightbox.prototype
	

	
	/******************************************************************************************************
	 * private methods
	 */
	
	Y.on('snappi:lightbox-afterToolBarCreated', function(){
        // SNAPPI.cfg.MenuCfg.setupLightboxMenus();
	});
	
	Y.on('snappi:lightbox-onload', function(){	
	});	

	/*
	 * make global
	 */
	SNAPPI.lightbox = new Lightbox();
})();
