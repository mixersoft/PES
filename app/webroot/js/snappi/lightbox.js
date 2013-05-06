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
	var _Y = null;
    SNAPPI.namespace('SNAPPI.onYready');
    SNAPPI.onYready.Lightbox = function(Y){
		if (_Y === null) _Y = Y;
		
		SNAPPI.Lightbox = Lightbox;
	}	
	var defaultCfg = {
		GET_CASTINGCALL_URI : "http://" + window.location.host
				+ "/photos/getCC/.json",
		ID_PREFIX : 'lightbox-'
	};
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
		if (Lightbox.instance) return Lightbox.instance;
		this._cfg = null;
		this.node = null;
		try {
			this.init(cfg);
			Lightbox.instance = this;	// singleton Class
		} catch (ex){
			console.warn('Lightbox.init() failed.');
		}
	};
	
	

	Lightbox.prototype = {
		init : function(cfg) {
			this._cfg = _Y.merge(defaultCfg, cfg);
			this.node = _Y.one('section.lightbox');
			if (this.node) {
				// rendered from cakePHP: app/views/elements/lightbox.ctp
			} else if (cfg) { 
				// load/create lightbox asynchronously 
				this.getMarkup(cfg);
				return;
			} else return false; 
			this.node.listen = this.node.listen  || {};
			this.node.removeClass('hide');
			_Y.one('div.anchor-bottom').append(this.node);	// move to the right location
			this.node.Lightbox = this;
			this.node.dom().Lightbox = this;	// for firebug
			// plugin droppables
			SNAPPI.DragDrop.pluginDrop(this.node);
			// init Lightbox.Gallery, do not render
			try {
				var view = SNAPPI.STATE.profile.view['Lightbox'];
			} catch (e) {
				// if not set, use smart default
				try {			
	    			if (PAGE.jsonData.lightbox.full_page){
						// TODO: deprecate fullpage lightbox, use 'maximize' instead
	    				view = 'maximize';
	    			}					
	    			if (!view && PAGE.jsonData.lightbox.castingCall.CastingCall.Auditions.Total){
	    				view = 'one-row';
	    			}
				} catch (e) {
				}
			}
			view = view || 'minimize';	
			
			var options = {
				type: 'Lightbox',
				render: false,
				view: view,
			}
			this.Gallery = new SNAPPI.Gallery(options);
			SNAPPI.Factory.Gallery.actions.setView(this.Gallery, view);	
			// add .lightbox-tab listener
			var action = 'LightboxTabClick';
			if (this.node.listen[action] == undefined) {
				this.node.listen[action] = this.node.delegate('click', 
	                function(e){
	                	SNAPPI.Factory.Gallery.actions.setToolbarOption.call(this, e);
	                }, '.lightbox-tab', this);
			}	
						
			
			this.restoreLightboxFromJson();	// lightbox.visible, lightbox.regionAsJSON
			
			
//			this.yui2_plugResizeAndDrag.call(this, this.node);	// yui2 lightbox resize and drag	
// 			this.plugResizeAndDrag.call(this, this.node);		// yui3 lightbox resize and drag			
			
			SNAPPI.namespace("SNAPPI.PM");  // for menu-pagemaker-selected-create-markup
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
			this.listen(true);
			this.updateCount();
			
			_Y.fire('snappi:afterLightboxInit', this); 
		},
		/**
		 * load lightbox markup
		 * @param cfg
		 * @return
		 */
		getMarkup : function(cfg){
			var CSS_ID = 'lightbox-markup';
			var MARKUP = {
					id: CSS_ID,
					selector: '#'+CSS_ID,
					container: _Y.one('#markup'),
					uri: '/combo/markup/lightbox',
					end: null
			};
			
			var callback = function(){
				// move markup from #markup to dest
				var parent = _Y.one('.anchor-bottom');
				if (parent) {
					parent.prepend(_Y.one('section#lightbox'));
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
    		
    		var parent = _Y.one('#' + selectBoxId);
    		
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
    		if(_Y.one('#menu-organizeBtn')){
    			var organizeBtn = _Y.one('#organizeBtn');
    			SNAPPI.cfg.MenuCfg.renderLightboxOrganize(organizeBtn);
    		}
    		else if (_Y.one('#menu-createBtn')){
    			var createBtn = _Y.one('#createBtn');
    			SNAPPI.cfg.MenuCfg.renderLightboxCreate(createBtn);
    		}
    		
    		else{}
    		*/
    	},
    	
        listen: function(status){
            status = (status == undefined) ? true : status;
            var node = this.node;
            if (node.listen == undefined) node.listen = {};
            if (status) {
                if (node.listen['Keydown'] == undefined) {
//	                this.listen['Keydown'] = _Y.on('keydown', this.handleKeydown, document, this);
//					this.listen['Keydown'] = _Y.on('keyup', this.handleKeydown, node, '46', this);
                }
            }
            else {
                for (var i in node.listen) {
                    node.listen[i].detach();
                }
                node.listen = {};
            }
        },
		toggleZoomMode: function() {
			
			if(!this.node.dom().Zoom){
				
				var bn = _Y.one('#lightbox_zoom_btn');
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
							var cb = _Y.one('.gallery-container li.select-all .checkbox');
							cb.set('checked', false);
						} catch (e) {}
					}
				};
				// TODO: DEPRECATE: alert("deprecated domJsBinder.fetchCastingCall: USE this.loadCastingCall()");
				alert("deprecated domJsBinder.fetchCastingCall: USE this.Gallery.loadCastingCall()");
				// SNAPPI.domJsBinder.fetchCastingCall.call(this, {
					// perpage : _LIGHTBOX_FULL_PAGE_LIMIT,
					// page : 1,
					// skipPaging: true
				// }, callback);
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
		            	type: 'Lightbox',
		            	node:  this.node.one('section.gallery.lightbox'),
		            	view: 'one-row',  
		            };
		            this.Gallery = new SNAPPI.Gallery(cfg);
		            // this.Gallery.listen(true, ['MultiSelect']);
				} 
				
				// nodeList of img from drag-drop
				nodeList.each(function(n, i, l) {
					var audition = SNAPPI.Auditions.find(n.ancestor('.FigureBox').uuid);
					this.Gallery.auditionSH.add(audition);
				}, this);					
	            var lastLI = this.Gallery.render( {
					page : 1,
					perpage : LIMIT
				});					


				// reset .gallery-container li.select-all .checkbox
				// reset #lightbox .checkbox
				var galleryHeaders = {
					'.gallery-container gallery-header':1,
					'#lightbox':1,
				}
				for (var header in galleryHeaders) {
					try {
						var cb = _Y.one(header + ' li.select-all input[type="checkbox"]');
						cb.set('checked', false);
					} catch (e) {}
				}
	            // set View if necessary
	            if (this.Gallery.view == 'minimize') {
	            	SNAPPI.Factory.Gallery.actions.setView(this.Gallery, 'one-row');	
	            }
				this.save();
				this.updateCount();					
				return lastLI;
			}
		},
		updateCount: function() {
			try {
				var count = this.Gallery.auditionSH.count();
				var label = count==1 ? '1 Snap' : count+" Snaps"; 
				this.node.one('.gallery-header .count').set('innerHTML', label);
			} catch (e) {}
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
				// should return an empty sortedHash
			} else { // this uses visible selected only, probably less common use case
				auditionSH = new SNAPPI.SortedHash();
				batch.each(function(node){
					var audition = SNAPPI.Auditions.find(node.uuid);
					auditionSH.add(audition);
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
            	// batch = _Y.all('section.gallery.photo .FigureBox.focus');
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
			var overflow = false,
				ul = this.Gallery.container,
				_auditionSH = this.Gallery.auditionSH;
			
			if (n instanceof _Y.NodeList) {
				n.some(function(node,i,l){
					if (_auditionSH.count() >= LIMIT) {
						overflow = true;
						return true;
					}					
					var audition = SNAPPI.Auditions.find(node.uuid);
					if (_auditionSH.get(node.uuid)) return false;	// skip duplicates
					
					var copy = this.copyThumbnail(node);
					copy.removeClass('focus').removeClass('selected');
					// remove all but img in li
					ul.append(copy);
					_auditionSH.add(audition);

					return false;  // "continue" for nodeList.some()
				}, this);
			} else {
				// from drag/drop, the original nodeList is IMG elements
				// TODO: using temp lightbox.Gallery.castingCall. deprecate when schemaParser is passed with audition
				if (!_Y.Lang.isArray(n)) {
					n = [ n ];
				}
				for (var i in n) {
					if (_auditionSH.count() >= LIMIT) {
						overflow = true;
						break;
					}					
					var node = n[i];
					var audition = SNAPPI.Auditions.find(node.uuid);
					if (_auditionSH.get(node.uuid)) continue;	// skip duplicates
					
					var copy = this.copyThumbnail(node);
					copy.removeClass('focus').removeClass('selected');
					// remove all but img in li
					ul.append(copy);
					_auditionSH.add(audition);
				}
			}
			return overflow;
		},
		remove : function(node) {
			try{
				node.remove(); // remove DOM
				this.removeNodeFromBindTo(node); // update bindTo
				// remove from lightbox Gallery
				var audition = SNAPPI.Auditions.find(node.uuid);
				this.Gallery.auditionSH.remove(audition);
			} catch (e) {
			}
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
        	_Y.fire('snappi:lightbox-content-changed', this);
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
							auditionIds.push(audition.Audition.id);
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
        restoreLightboxFromJson: function(view) {
    		try {	// restore lightbox innerHTML from JSON
    			if (this.Gallery) this.Gallery.node.addClass('hide');
    			
    			if (PAGE.jsonData.lightbox.castingCall){
					var castingCall = PAGE.jsonData.lightbox.castingCall;
					// render castingCall in Lightbox
					this.renderLightboxFromCC.call(this, castingCall);
					this.node.get('parentNode').removeClass('minimize');
					// set view from state if available
					try {
						var view = SNAPPI.STATE.profile.view['-lightbox'];	
					} catch (e) {
						view = 'one-row'
					}
					SNAPPI.Factory.Gallery.actions.setView(this.Gallery, view);	
    			};
    			this.Gallery.node.removeClass('hide');
    		} catch(e) {
    			if (this.Gallery) this.Gallery.node.removeClass('hide');
    		}
    		
    		// restore lightbox selected
			try {
				var selects = PAGE.jsonData.lightbox.selected;
				var node, array_selected = selects.split(',');
				
				_Y.each(array_selected, function(id, k) {
					node = this.node.one('#' + this.addIdPrefix(id));
					node.addClass('selected');
		        }, this);
			} catch(e) {}
        },
        /*  
         * used by processDrop, 
         */
        renderLightboxFromCC: function(castingCall) {
        	if (!castingCall) return;
        	try {
        		LIMIT = (PAGE.jsonData.lightbox.full_page) ? _LIGHTBOX_FULL_PAGE_LIMIT : _LIGHTBOX_LIMIT;
        	} catch(e) {
        		LIMIT = _LIGHTBOX_LIMIT;
        	}
        	
        	this.Gallery.render({
        		perpage: LIMIT,
        		castingCall: castingCall,
        	});
        	if ( 0 || castingCall.auditionSH.count() > LIMIT) {
        		this.node.addClass('is-preview');
        	} else {
        		this.node.removeClass('is-preview');
        	} 
        	return;
         },
        /*
         * render popup menus
         */
        // add a parameter 'e' here. 
        organize: function() {
        	
        	/*
        	if(!_Y.one('#submenu-organize')){
        		if(_Y.one('#menu-organizeBtn')){
	    			var organizeBtn = _Y.one('#organizeBtn');
	    			SNAPPI.cfg.MenuCfg.renderLightboxOrganize(organizeBtn);
	    		}
	    		else if (_Y.one('#menu-createBtn')){
	    			var createBtn = _Y.one('#createBtn');
	    			SNAPPI.cfg.MenuCfg.renderLightboxCreate(createBtn);
	    		}
        		this.renderSubmenu('organizeBtn');
        	}
        	*/
        	if(!_Y.one('#menu-organizeBtn')){
    			
        		SNAPPI.cfg.MenuCfg.renderLightboxOrganize();
    		}
        	
        },
        create: function(e) {
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
				var audition = SNAPPI.Auditions.find(n.uuid);
				var thumbs = audition.bindTo || [];
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
            	post_aids.push(audition.Audition.id);
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
            SNAPPI.shotController.postGroupAsShot.call(this, post_aids, callback);
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
					SNAPPI.AssetRatingController.postRating( value, audition.Audition.id, node.Rating, null); 	// offscreen
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
				asset_ids.push(audition.Audition.id);
			});
			node = node || _Y.one('#lbx-rating .ratingGroup'); // .ratingGroup 
			SNAPPI.AssetRatingController.postRating(v, asset_ids.join(','), node.Rating, null);
		},
		/*
		 * Tags
		 */
		// deprecate: moved to #menu-lightbox-organize-markup
		renderTagInput : function(node) {
			// TODO: for now, just add to the button. later, render in subMenu
			var nTagForm = _Y.Node
					.create("<span><form id='lbx-tag-form' onsubmit='return false;' /><input id='lbx-tag-field' class='field-help' type='text' size='20' maxlength='255' value='Enter tags' onclick='this.value=\"\"; this.className=\"\";'><input type='submit' value='Go' onclick='SNAPPI.lightbox.applyTagInBatch(this);return false;'/></form></span>");
			node.append(nTagForm);
		},
		applyTagInBatch : function(submit) {
			var loadingNode = submit.get('parentNode');
			var text = submit.previous('input#lbx-tag-field');
			var tag = text.get('value');

			// post Tags
			var self;
			if (this instanceof SNAPPI.Lightbox) self = this;
			else self = SNAPPI.lightbox;

			var batch = self.getSelected();

			var asset_ids = [];
			batch.each(function(audition) {
				asset_ids.push(audition.Audition.id);
			});
			var uri = "/photos/setprop/.json";
			var data = {
				'data[Asset][id]' : asset_ids,
				'data[Asset][tags]' : tag
			};
			var args = {
				node : text,
				tag : tag
			};
			// use Plugin to add io request and loadingmask
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
	            loadingNode.plug(_Y.Plugin.IO, ioCfg );
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
        	// var nShare = _Y.Node.create("<ul id='lbx-share-list'></ul>");
        	var nShare = _Y.one('#lbx-share-list-1');
        	
        	if(nShare == null){
        		
        		var markup = "<ul title='share groups' id='lbx-share-list-1'></ul>";
        		var n = _Y.Node.create(markup);
        		nShare = node.appendChild(n);
        		
        		nShare.append(_Y.Node.create("<li class='loading' style='display: block;'>Loading</li>"));
        		
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
                    var option = _Y.Node.create(_Y.Lang.sub(optionMarkup, cfg));
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
                
                _Y.fire('snappi:checkRegion', nShare);
                
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
            var groupRoll = _Y.one('#json-Groups');
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
                _Y.io(uri, iocfg);
            }

            node.append(nShare);
        },
        applyShareInBatch : function(gid, loading, options) {
        	var self, options = options || {};
			if (this instanceof SNAPPI.Lightbox) self = this;
			else self = SNAPPI.lightbox;
			
			var batch = options.batch || self.getSelected();
	
			var asset_ids = [];
			batch.each(function(audition) {
				asset_ids.push(audition.Audition.id);
			});
						
			var uri = options.uri || "/groups/contributePhoto/.json";
			var data = {
				'data[Group][id]' : gid,
				'data[Asset][id]' : asset_ids
			};
			
			/*
			 * adjustments for 'remove from group'
			 */
			if (options.data) data = _Y.merge(data, options.data);
			data = SNAPPI.IO.object2querystring(data);
			
			var args = {
				gid: gid
			};
			// use Plugin to add io request and loadingmask
			var loadingNode = loading;
			if (!loadingNode.io) {
				var ioCfg = SNAPPI.IO.pluginIO_RespondAsJson({
					uri: uri ,
					parseContent:true,
					method: 'POST',
					autoLoad: false,
					// qs: data,
					dataType: 'json',
					context: self,	
					arguments: args, 
					on: {
						successJson:  function(e, id, o, args) {
							_Y.fire('snappi:share-complete', this, loadingNode, o.responseJson);
							this.onShareGroupComplete(args.gid, o.responseJson.message);
							return false;
						}
					}
				});
	            loadingNode.plug(_Y.Plugin.IO, ioCfg );
			} 
			loadingNode.io.set('data', data);
			loadingNode.io.set('context', self);
			loadingNode.io.set('uri', uri);
			loadingNode.io.set('arguments', args);
			loadingNode.io.start();
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
	        // var nShare = _Y.Node.create("<span><form id='lbx-unShare-form' class='hidden' onsubmit='return false;' /><select id='lightbox-unShare'><option class='help' value='' SELECTED >[my Groups]</option></select><input type='submit' value='Go' onclick='SNAPPI.lightbox.applyUnShareInBatch(this);return false;'/></form></span>");
			// var nShare = _Y.Node.create("<ul id='lbx-share-list'></ul>");
			var unShare = _Y.one('#lbx-unShare-list-1');
			
			if(unShare == null){	
	    		
	    		var markup = "<ul title='share groups' id='lbx-unShare-list-1'></ul>";
	    		var n = _Y.Node.create(markup);
	    		unShare = node.appendChild(n);
	    		
	    		unShare.append(_Y.Node.create("<li class='loading' style='display: block;'>Loading</li>"));
	    		
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
	                var option = _Y.Node.create(_Y.Lang.sub(optionMarkup, cfg));
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
	            
	            _Y.fire('snappi:checkRegion', unShare);
	            
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
	        
	        var groupRoll = _Y.one('#json-Groups');
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
	            _Y.io(uri, iocfg);
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
				asset_ids.push(audition.Audition.id);
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
			// var nShare = _Y.one('#lbx-share-list');
	    	// nShare.one('li').removeClass('working');
			var nPrivacy = _Y.one('#lbx-privacy-list-1'); 
			
			if(nPrivacy == null){	
	    		
	    		var markup = "<ul title='share groups' id='lbx-privacy-list-1'></ul>";
	    		var n = _Y.Node.create(markup);
	    		nPrivacy = node.appendChild(n);
	    		
	    		nPrivacy.append(_Y.Node.create("<li class='loading' style='display: block;'>Loading</li>"));
	    		
	    	}
	
			var _renderOptions = function(selectNode, jsonOrCfg, Y) {
				var more = false;
				var cfg = (_Y.Lang.isString(jsonOrCfg)) ? eval('(' + jsonOrCfg + ')')
						: jsonOrCfg;
				var optionMarkup = "<li><a>{label}</a></li>";
				for ( var i in cfg) {
					var tokens = {
						value : cfg[i].id,
						label : cfg[i].title
					};
					var option = _Y.Node.create(_Y.Lang.sub(optionMarkup, tokens));
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
	            
				_Y.fire('snappi:checkRegion', nPrivacy);
				
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
				asset_ids.push(audition.Audition.id);
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
							_Y.fire('snappi:privacy-complete', this, loadingNode);
							SNAPPI.lightbox.onPrivacyGroupComplete(args.privacy, o.responseJson);
						}
					}
				});
	            loadingNode.plug(_Y.Plugin.IO, ioCfg );
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
		get_StoryPage : function() {
			SNAPPI.UIHelper.create.get_StoryPage({gallery:this.Gallery});	
		},
		handleKeydown : function(e) {
			// console.log(this.container.get('parentNode'));
			if (!this.node.ancestor('#content')) {
				// container is no longer visible, detach all listeners
				// probably replaced by new ajax xhr-get
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
		// handleClick : function(e) {
			// var action = e.currentTarget.getAttribute('action');
			// if (action) {
				// e.currentTarget.get('parentNode').all('li').removeClass('focus');
				// this.action[action].call(this, e);
			// }
		// },
		plugResizeAndDrag : function(node) {
			var node = node || this.node;
			node.plug(_Y.Plugin.Drag); 
			// we may need to narrow the lightbox, then it's much convenient for user.
			node.dd.plug(_Y.Plugin.DDConstrained, {
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
			// var resize = new _Y.Resize({
				// node : '#lightbox'
			// });
			// resize.plug(_Y.Plugin.ResizeConstrained, {
				// minHeight : _LIGHTBOX_MIN_HEIGHT,
				// minWidth  : _LIGHTBOX_MIN_WIDTH 
			// });
		    // resize.on('endResize', function(){
		    	// var region = node.get('region');
	            // var postData = {'lightbox.regionAsJSON': region};
	            // SNAPPI.io.writeProfile(postData, callback, '');
		    // });
		},
        showThumbnailRatings : function(node){
			var pr = node || this.Gallery;
            var audition, thumbs = pr.container.all('.FigureBox');
            thumbs.each(function(n){
            	if (n.hasClass('hide')) return;
                if (n.Rating == undefined) {
                	audition = SNAPPI.Auditions.find(n.Thumbnail.uuid);
                	SNAPPI.Rating.pluginRating(this, n.Thumbnail, audition.rating);
                } else {
                	// rating already exists
                	n.one('div.ratingGroup').removeClass('hide');
                }
            }, pr);
            
            SNAPPI.STATE.showRatings = 'show';  
            SNAPPI.Rating.startListeners(pr.container);
            // SNAPPI.debug_showNodes();
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
	
	// _Y.on('snappi:lightbox-afterToolBarCreated', function(){
        // // SNAPPI.cfg.MenuCfg.setupLightboxMenus();
	// });
// 	
	// _Y.on('snappi:lightbox-onload', function(){	
	// });	

})();
