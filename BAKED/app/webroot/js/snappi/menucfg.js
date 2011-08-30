(function(){
	
	var Y = SNAPPI.Y;
	
	var OFFSET_CONTEXT_MENU = {group : {left : 20, top : 5}};
		OFFSET_CONTEXT_MENU.header = {left : 0, top : 5};
	var MenuCfg = function(){};
	
	MenuCfg.prototype = {
		listenToGroupRoll : function(){
			var rootNode = Y.one('div.element-roll.group > ul.group-roll');
			if(rootNode){
				rootNode.delegate('contextmenu', function(e, rootNode){

	           	e.preventDefault();
	       		var target = e.currentTarget,
	       			rootNodeDOM = rootNode.dom();
	       		
		       		if(!rootNodeDOM.contextMenu){
		       			// need to add stop listener.
		       			SNAPPI.cfg.MenuCfg.renderGroupContextMenu(target);
		       		}else {
		       			rootNodeDOM.contextMenu.parent.container = target;
		       			rootNodeDOM.contextMenu.show();
		       		}
				}, 'li', {}, rootNode);
				
			}else {
			}
		}, 
		listenToSubstituteGroupRoll : function(){
			var rootNode = Y.one('#substitutes ul.filmstrip');
			if(rootNode){
				rootNode.delegate('contextmenu', function(e, rootNode){

	           	e.preventDefault();
	       		var target = e.currentTarget,
	       			rootNodeDOM = rootNode.dom();
	       		
		       		if(!rootNodeDOM.contextMenu){
		       			// need to add stop listener.
		       			SNAPPI.cfg.MenuCfg.renderSubstituteGroupContextMenu(target);
		       		}else {
		       			rootNodeDOM.contextMenu.parent.container = target;
		       			rootNodeDOM.contextMenu.show();
		       		}
				}, 'li', {}, rootNode);
				
			}else {
			}
		},
		renderSubstituteGroupContextMenu : function(target){
			var substituteContextMenuItemCfg = [{
				label: 'Un-substitute',
				onclick : {
        	   		fn : function(){
						var assetId = this.getMenu().parent.container.dom().audition.id;
						var uri = "/photos/setprop/.json";
						var data = {
							'data[Asset][id]' : assetId,
							'data[Asset][unsubstitute]' : 1
						};
						var callback = {
							complete : function(id, o, args) {
								var check;
								window.location.reload();
								//TODO need to use o.responseJson
//								if (o.responseJson && o.responseJson.success == 'true')  {
//									
//								}
							},
							failure : function(id, o, args) {
								var check;
							}
						};
						SNAPPI.io.post(uri, data, callback, '');
           			}
               }
			}];
			                  
			var substituteContextMenuCfg = {
				id : 'menu-substituteGroup',
				className: 'contextmenu',
				menuItems : substituteContextMenuItemCfg
				, afterAttach : {
					fn : function(node){
						var parentNode = Y.one('#substitutes ul.filmstrip');
						parentNode.dom().contextMenu = this;
						SNAPPI.Menu.plugInBlur(parentNode, this.getNode(), "> li");
						target.simulate('mousedown');
					}
				}
				
				, afterShow : {
					fn : function(){
						var contextMenu_target =  this.parent.getNode();  // this = Menu object
						var l = contextMenu_target.get('offsetLeft'),
							t = contextMenu_target.get('offsetTop');
							l += OFFSET_CONTEXT_MENU.group.left;
							t += OFFSET_CONTEXT_MENU.group.top;
						this.getNode().setStyle('left', l + 'px');
						this.getNode().setStyle('top', t + 'px');
					}
				}
			};
		
			var substituteContextMenu = new SNAPPI.Menu(substituteContextMenuCfg);
			substituteContextMenu.attach(target, true, 'click');
			substituteContextMenu.show();
		},
		renderGroupContextMenu : function(target){
			var groupContextMenuItemCfg = [{
					label: 'Group context menu'
				},{
					// show unShare menuitem ONLY if we are on a /photos/home page
            	   label : 'unShare',
            	   onclick : {
            	   		fn : function(){
							var href = this.unshare_href;
				   			window.location.href = href;
               			}
                   }
       				, beforeShow : {
    					fn : function(){
       						try {
       							var controller = PAGE.jsonData.controller;
       							if (controller.keyName == 'photo' && controller.action=='home') { 
		    						var href = this.parent.parent.getNode().one('.thumb-label a').getAttribute('href');
		    						this.unshare_href = href;
		    						this.show();
       							} else {
       								this.hide();
       							}
       						}catch(e) {
       							this.hide();
       						}
    					}
    				}
               }];
				                           
			var groupContextMenuCfg = {
					id : 'menu-groupContext',
					className: 'contextmenu',
					menuItems : groupContextMenuItemCfg
					, afterAttach : {
						fn : function(node){
							var parentNode = node.ancestor('ul.group-roll');
							parentNode.dom().contextMenu = this;
							SNAPPI.Menu.plugInBlur(parentNode, this.getNode(), "> li");
							target.simulate('mousedown');
						}
					}
					, afterShow : {
						fn : function(){
							var contextMenu_target =  this.parent.getNode();  // this = Menu object
							var l = contextMenu_target.get('offsetLeft'),
								t = contextMenu_target.get('offsetTop');
								l += OFFSET_CONTEXT_MENU.group.left;
								t += OFFSET_CONTEXT_MENU.group.top;
							this.getNode().setStyle('left', l + 'px');
							this.getNode().setStyle('top', t + 'px');
						}
					}
				}
			
			var groupContextMenu = new SNAPPI.Menu(groupContextMenuCfg);
			groupContextMenu.attach(target, true, 'click');
			// i think i still need to call show() explicitly, because we cannot assume all user will like to open menu 
			// right after the menu is attached to somewhere. so show() cannot be a built-in function inside the menu.attach()
			groupContextMenu.show();
		},		

		setupLightboxMenus : function(){
			// p : parent
			var p_organizeBtn = 'organizeBtn',
				p_createBtn = 'createBtn';
			
			if(SNAPPI.lightbox.node != null){
				SNAPPI.Menu.plugInBlur(Y.one('#' + p_organizeBtn));
				SNAPPI.Menu.plugInBlur(Y.one('#' + p_createBtn));
			}
		},
		
		renderLightboxOrganize : function (){
			var label_organizeBtnMenuGroup = 'group',
				label_organizeBtnMenuPrivacy = 'privacy',
				label_groupMenuShare = 'share',
				label_groupMenuUnShare = 'unShare';
			
			var organizeBtnMenu = this.renderOrganizeBtn();

			organizeBtnMenu.show();
			
		},
		
		renderPhotoContextMenu : function(target){
			var t = target.get('offsetTop'),
				class_ratingGrp = '.ratingGroup',
				class_LI_thumbnail = '.FigureBox',
				id_contextMenuRatingGrp = 'menuItem-contextRatingGrp', 
				id_contextMenuInfo = 'menu-photoRollContext';
			
			var label_contextMenu = 'more info',
				id_contextMenuInfo = 'menuItem-contextInfo', 
				id_photoRollContext = 'menu-photoRollContext';	
		
			var photoRollNode = Y.one('section.gallery.photo');
			
			/*
			 * private helper methods
			 */
			var _getPhotoRoll = function(contextMenu_target){
				try {
					var photoRoll = contextMenu_target.ancestor('section.gallery.photo').Gallery; 
				} catch (e) {
					// for ul.filmstrip and ul.hiddenshots
					photoRoll = contextMenu_target.ancestor('ul').dom().Gallery; 
				}	
				return photoRoll;
			};
			
			var contextMenuItemCfg = [
			    {	// more info item
			    	id : id_contextMenuInfo,
					label : label_contextMenu
					, onclick : {
	        			fn : function(node){
			    			// pop dialog box for photo info
		    				var cfg = {
		    						title : 'photo info',
		    						buttons : SNAPPI.dialogbox.cfg.BUTTONS_OK_CANCEL
		    					};	
			    			var	nodeCfg = {
			    					label : node.ancestor('ul.contextmenu').dom().audition.Audition.Photo.DateTaken
			    				};
		    				cfg.body = Y.Node.create(Y.substitute('<li id="{id}">{label}</li>', nodeCfg));
//		    			SNAPPI.dialogbox.render(cfg);
	    				
	    				var p = new SNAPPI.Property({data : cfg});
	    				p.renderAsDialog();
		        		}
	        		}
	        	},
	        	{
	        		// rating item
	        		label : '',
	        		afterAttach : {
	        			fn :// the first time context menu is attached to one .FigureBox
	        				// happens before the menu's afterAttach. 
	        				// will not be called after this time.
	        				function(target){
	        					var menuItem = this.getNode();
	        					var menuNode = this.getMenu().getNode();
	        					var contextMenu_target =  target;
	        					var audition = contextMenu_target.dom().audition;
	        					var photoRoll = _getPhotoRoll(contextMenu_target);
		            			// add new rating group as LI > DIV
	        					SNAPPI.Rating.pluginRating(menuItem, menuItem, target.audition.rating);
		            			SNAPPI.Rating.startListeners(menuNode);
		                    	menuNode.one('.ratingGroup').setAttribute('id', 'menuItem-contextRatingGrp');
		        			}
	        			, args: [target]	// target is only valid on attach, note beforeShow
	        		},
	        		beforeShow : {
	        			fn : function(target){
	        				var menuItem = this.getNode();
							var menuNode = this.getMenu().getNode();
							var contextMenu_target =  this.getMenu().parent.getNode();
							var audition = contextMenu_target.dom().audition;
							var ratingCfg = {
									v : audition.rating,
									uuid : audition.id,
									listen : false
								};
							SNAPPI.Rating.attach(parent, ratingCfg);							
		        		}
	        		}
	        		
	        	},
	        	{
	        		// 'show duplicates' item
	        		label: "show hidden",
	        		id   : 'menuItem-showDuplicates',
	        		afterShow: {
	        			fn: function() {
	        				// this instanceof MenuItem
		                	try {
		                		var contextMenu_target =  this.getMenu().parent.getNode();
		                		shotId = contextMenu_target.dom().audition.Audition.Substitutions.id;
		                		this.show();
		        			}catch(e){
		        				this.hide();
		        			}
	        			}
	        		}
	        		, onclick: {
	        			fn: function(node){		// pop dialog box for duplicates
	        				try {
	        					// this instanceof MenuItem
	        					// selected = this.dom().MenuItem.getMenu().getNode().dom().audition;
	        					var cfg = {
	    							title : 'duplicates',
	    							buttons: [],
	    							body  : null
	    						};
	        					
	    						var menu = this.getMenu();
	    						var contextMenu_target =  menu.parent.getNode();
	    						var audition = contextMenu_target.dom().audition;
	    						var photoRoll = _getPhotoRoll(contextMenu_target);
	    						var shotType = /^circle|^group|^wedding|^event/.test(SNAPPI.STATE.controller.alias) ? 'Groupshot' : 'Usershot';
	    						SNAPPI.Gallery.showHiddenShotsAsPreview.call(photoRoll, audition, shotType , SNAPPI.dialogbox.node.one('.body > ul'));
	        				} catch (e) {
	        				}
	        			},
	        			args:[]
	        		}
	        	},
	        	{
	        		// 'show Group' item
	        		label: "Group",
//	        		id   : 'menuItem-group',
	        		afterShow: {
	        			fn: function() {
	        				// this instanceof MenuItem
	                		var contextMenu_target =  this.getMenu().parent.getNode();
	                		if (contextMenu_target.hasClass('selected')) {
	                			var photoRoll = _getPhotoRoll(contextMenu_target);
	    						if (photoRoll.getSelected().count()>1) this.show();
	                			return;
	                		}
	                		// otherwise
	                		this.hide();
	        			}
	        		}
	        		, onclick: {
	        			fn: function(node){		
	        				try {
	        					// this instanceof MenuItem
	    						var contextMenu_target =  this.getMenu().parent.getNode();
//	    						var audition = contextMenu_target.dom().audition;
	    						var photoRoll = _getPhotoRoll(contextMenu_target); 
	    						var shotType = /^circle|^group|^wedding|^event/.test(SNAPPI.STATE.controller.alias) ? 'Groupshot' : 'Usershot';
	    						photoRoll.groupAsShot(null, {
	    							loadingNode: this.getNode(),
	    							shotType: shotType,
	    							uuid: SNAPPI.STATE.controller.xhrFrom.uuid
	    						});
	        				} catch (e) {
	        				}
	        			},
	        			args:[]
	        		}
	        	}, 
	        	{
	        		// 'show unGroup' item
	        		label: "unGroup",
//	        		id   : 'menuItem-unGroup',
	        		afterShow: {
	        			fn: function() {
		    				// this instanceof MenuItem
	        				try {
			            		var contextMenu_target =  this.getMenu().parent.getNode();
			            		if (contextMenu_target.audition.Audition.Substitutions) {
			            			this.show();
			            		} else this.hide();
	        				} catch (e) {
	        					this.hide();
	        				}
	        			}
	        		}
	        		, onclick: {
	        			fn: function(node){		// pop dialog box for duplicates
	        				try {
	        					// this instanceof MenuItem
	    						var contextMenu_target =  this.getMenu().parent.getNode();
	    						var photoRoll = _getPhotoRoll(contextMenu_target);
	    						var selected = photoRoll.getSelected();
	    						if (selected.get(contextMenu_target.dom().audition)) {
	    							photoRoll.unGroupShot(null, {loadingNode: this.getNode()});
	    						} else {
	    							selected.add(contextMenu_target.dom().audition);
	    							photoRoll.unGroupShot(selected, {loadingNode: this.getNode()});
	    						} 
	        				} catch (e) {
	        				}
	        			},
	        			args:[]
	        		}
	        	}	        	
	        	];
			
			var contextMenuCfg = {
					id: id_photoRollContext,
					className: 'contextmenu',
					menuItems : contextMenuItemCfg,
					// openBy: the open method from the second time
					/*
					openBy : {
						event : 'mouseup',
						parent: photoRollNode,
						handler : function(){
							return function(e, photoRoll){
					        	
					        	// check if right click
					        	if (e.button == 3 || e.button == 2)
								    {
					        		var target = e.currentTarget,
					        			menuNode = this.getNode();
					        		
					        		photoRoll.contextMenu.parentNode = target;
					        		photoRoll.contextMenu.show();
					    			}
					           }
						},
			           selector : '.FigureBox',
			           args : photoRollNode.dom().Gallery
					},
					*/
					afterAttach : {
						fn : function(target){
						
							var menuNode = this.getNode();
							var contextMenu_target =  target; // this = Menu object 
							var audition = contextMenu_target.dom().audition;
							var photoRoll = _getPhotoRoll(contextMenu_target);
							
							// bind new audition
							Y.Lang.isArray(audition.bindTo) ? audition.bindTo.push(menuNode) : audition.bindTo = [menuNode];
							menuNode.dom().audition = audition;
							
							// add parent and dom property
							photoRoll.contextMenu = this;	// add this property for contextMenu only

	            			SNAPPI.Menu.plugInBlur(photoRoll.container, menuNode, "> .FigureBox");
	            			// simulate mousedown on target activate plugInBlur the first time
	            			contextMenu_target.simulate('mousedown');	            			

						}
						, args: [target]	// target is only valid on attach, note beforeShow
					}
					, beforeShow : {
						fn : function(){
							var menuNode = this.getNode();
							var contextMenu_target =  this.parent.getNode();  // this = Menu object 
							var audition = contextMenu_target.dom().audition;
							var photoRoll = _getPhotoRoll(contextMenu_target); 
							
							SNAPPI.Menu.unbindOldAudition(menuNode);
				            
				            // bind this.container to "new" audition
				            menuNode.dom().audition = audition;
				            Y.Lang.isArray(audition.bindTo) ? audition.bindTo.push(menuNode) : audition.bindTo = [menuNode];
							
							// set focus
							photoRoll.setFocus(contextMenu_target);
						},
						args: []
					}
					, afterShow : {
						fn : function(){
							var contextMenu_target =  this.parent.getNode();  // this = Menu object
							var l = contextMenu_target.get('offsetLeft'),
								t = contextMenu_target.get('offsetTop');
							this.getNode().setStyle('left', l + 'px');
							this.getNode().setStyle('top', t + 'px');
						}
					}
					
				};
			
			var photoRollContextMenu = new SNAPPI.Menu(contextMenuCfg);
			photoRollContextMenu.attach(target, true, 'click');
			// i think i still need to call show() explicitly, because we cannot assume all user will like to open menu 
			// right after the menu is attached to somewhere. so show() cannot be a built-in function inside the menu.attach()
			photoRollContextMenu.show();
		},
		/**
		 *	for Show Hidden Shots Dialog Box 
		 * @param target
		 * @return
		 */
		renderSubstituteContextMenu : function(target){
			var t = target.get('offsetTop'),
				class_ratingGrp = '.ratingGroup',
				class_LI_thumbnail = '.FigureBox',
				id_contextMenuRatingGrp = 'menuItem-contextRatingGrp', 
				id_contextMenuInfo = 'menu-photoRollContext';
			
			var label_contextMenu = 'more info',
				id_contextMenuInfo = 'menuItem-contextInfo', 
				id_photoRollContext = 'menu-photoRollContext';	
		
			var photoRollNode = Y.one('section.gallery.photo');
			
			/*
			 * private helper methods
			 */
			var _getPhotoRoll = function(contextMenu_target){
				try {
					var photoRoll = contextMenu_target.ancestor('section.gallery.photo').Gallery; 
				} catch (e) {
					// for ul.filmstrip and ul.hiddenshots
					photoRoll = contextMenu_target.ancestor('ul').dom().Gallery; 
				}	
				return photoRoll;
			};
			
			var contextMenuItemCfg = [
			    {	// more info item
//			    	id : id_contextMenuInfo,
					label : label_contextMenu
					, onclick : {
	        			fn : function(node){
			    			// pop dialog box for photo info
		    				var cfg = {
		    						title : 'photo info',
		    						buttons : SNAPPI.dialogbox.cfg.BUTTONS_OK_CANCEL
		    					};	
			    			var	nodeCfg = {
			    					label : node.ancestor('ul.contextmenu').dom().audition.Audition.Photo.DateTaken
			    				};
		    				cfg.body = Y.Node.create(Y.substitute('<li id="{id}">{label}</li>', nodeCfg));
//		    			SNAPPI.dialogbox.render(cfg);
	    				
	    				var p = new SNAPPI.Property({data : cfg});
	    				p.renderAsDialog();
		        		}
	        		}
	        	},
	        	{
	        		// 'show Group' item
	        		label: "Remove from Group"
	        		, onclick: {
	        			fn: function(node){		
	        				try {
	        					// this instanceof MenuItem
	    						var contextMenu_target =  this.getMenu().parent.getNode();
	    						var audition = contextMenu_target.dom().audition;
	    						var photoRoll = _getPhotoRoll(contextMenu_target); 
	    						var selected = photoRoll.getSelected();
	    						if (selected.get(contextMenu_target.dom().audition)) {
	    							photoRoll.removeFromShot(null, {loadingNode: this.getNode()});
	    						} else {
	    							selected.add(contextMenu_target.dom().audition);
	    							photoRoll.removeFromShot(selected, {loadingNode: this.getNode()});
	    						} 	    						
	        				} catch (e) {
	        				}
	        			},
	        			args:[]
	        		}
	        	}, 
	        	{
	        		// 'show unGroup' item
	        		label: "unGroup",
	        		afterShow: {
	        			fn: function() {
		    				// this instanceof MenuItem
	        				try {
			            		var contextMenu_target =  this.getMenu().parent.getNode();
			            		if (contextMenu_target.audition.Audition.Substitutions) {
			            			this.show();
			            		} else this.hide();
	        				} catch (e) {
	        					this.hide();
	        				}
	        			}
	        		}
	        		, onclick: {
	        			fn: function(node){		// pop dialog box for duplicates
	        				try {
	        					// this instanceof MenuItem
	    						var contextMenu_target =  this.getMenu().parent.getNode();
	    						var photoRoll = _getPhotoRoll(contextMenu_target);
	    						var selected = photoRoll.getSelected();
	    						if (selected.get(contextMenu_target.dom().audition)) {
	    							photoRoll.unGroupShot(null, {loadingNode: this.getNode()});
	    						} else {
	    							selected.add(contextMenu_target.dom().audition);
	    							photoRoll.unGroupShot(selected, {loadingNode: this.getNode()});
	    						} 
	        				} catch (e) {
	        				}
	        			},
	        			args:[]
	        		}
	        	}	        	
	        	];
			
			var contextMenuCfg = {
					id: id_photoRollContext,
					className: 'contextmenu',
					menuItems : contextMenuItemCfg,
					afterAttach : {
						fn : function(target){
						
							var menuNode = this.getNode();
							var contextMenu_target =  target; // this = Menu object 
							var audition = contextMenu_target.dom().audition;
							var photoRoll = _getPhotoRoll(contextMenu_target);
							
							// bind new audition
							Y.Lang.isArray(audition.bindTo) ? audition.bindTo.push(menuNode) : audition.bindTo = [menuNode];
							menuNode.dom().audition = audition;
							
							// add parent and dom property
							photoRoll.contextMenu = this;	// add this property for contextMenu only

	            			SNAPPI.Menu.plugInBlur(photoRoll.container, menuNode, "> .FigureBox");
	            			// simulate mousedown on target activate plugInBlur the first time
	            			contextMenu_target.simulate('mousedown');	            			

						}
						, args: [target]	// target is only valid on attach, note beforeShow
					}
					, beforeShow : {
						fn : function(){
							var menuNode = this.getNode();
							var contextMenu_target =  this.parent.getNode();  // this = Menu object 
							var audition = contextMenu_target.dom().audition;
							var photoRoll = _getPhotoRoll(contextMenu_target); 
							
							SNAPPI.Menu.unbindOldAudition(menuNode);
				            
				            // bind this.container to "new" audition
				            menuNode.dom().audition = audition;
				            Y.Lang.isArray(audition.bindTo) ? audition.bindTo.push(menuNode) : audition.bindTo = [menuNode];
							
							// set focus
							photoRoll.setFocus(contextMenu_target);
						},
						args: []
					}
					, afterShow : {
						fn : function(){
							var contextMenu_target =  this.parent.getNode();  // this = Menu object
							var l = contextMenu_target.get('offsetLeft'),
								t = contextMenu_target.get('offsetTop');
							this.getNode().setStyle('left', l + 'px');
							this.getNode().setStyle('top', t + 'px');
						}
					}
					
				};
			
			var photoRollContextMenu = new SNAPPI.Menu(contextMenuCfg);
			photoRollContextMenu.attach(target, true, 'click');
			photoRollContextMenu.show();
		},
		
		renderLightboxCreate : function(){
			
			var label_pageGallery = 'PageGallery',
				title_pageGallery = 'Make PageGallery from Lightbox selection',
				id_createBtnMenu = 'menu-createBtn'; 
			
			var p_createBtn = 'createBtn',
				createBtn = Y.one('#' + p_createBtn);
			
			var createMenuItemCfg = [
									{	// PageGallery item
										label : label_pageGallery,
										onclick : {
//											fn : SNAPPI.lightbox.launchPageGallery
//											, context : SNAPPI.lightbox
											fn : function(){
												window.location.href = '/my/pagemaker';
											}										

										},
										title : title_pageGallery
									}];

			var createSubCfg = {
				id : id_createBtnMenu,
				menuItems : createMenuItemCfg
			};
			
			var createSub = new SNAPPI.Menu(createSubCfg);
			createSub.attach(createBtn, true, 'click');
			createSub.show();
		},
		renderOrganizeBtn : function(){

			var label_organizeBtnMenuRating = 'rating: ',
				label_organizeBtnMenuTag = 'tags',
				label_organizeBtnMenuGroup = 'group',
				label_organizeBtnMenuPrivacy = 'privacy',
				label_organizeBtnMenuSub = 'group as Shot',
				label_organizeBtnMenuUnSub = 'remove from Shot',
				id_organizeMenuRating = 'lbx-rating';
			
			var	id_organizeBtnMenu = 'menu-organizeBtn'; 
			
			var p_organizeBtn = 'organizeBtn',
				organizeBtn = Y.one('#' + p_organizeBtn);
			
			/*
			 * create the parent menu under organize button
			 */
			var organizeBtnMenuItemCfg = [
				         	{	// rating menuitem
								id: id_organizeMenuRating,
								label : label_organizeBtnMenuRating
								, afterAttach : {
									fn : SNAPPI.lightbox.renderRating
									, context : SNAPPI.lightbox
								}
							},
							{	// tag item
								label : label_organizeBtnMenuTag
								, afterAttach : {
									fn : SNAPPI.lightbox.renderTagInput
									, context : SNAPPI.lightbox
								}
							},{	// group item
								label : label_organizeBtnMenuGroup,
								hasChild: true,
								firstMouseover : {
									fn : this.renderGroupChoices
								}
							},{
								// privacy item
							   label : label_organizeBtnMenuPrivacy,
							   id    : 'menuitem-privacy',
							   hasChild: true,
							   firstMouseover : {
									fn: this.renderPrivacyChoices
								}
							}, {	// substitutes item
								label : label_organizeBtnMenuSub,
								onclick : {
									fn : SNAPPI.lightbox.groupAsShot
									, context : SNAPPI.lightbox
								}
							}, {	// unsubstitutes item
								label : label_organizeBtnMenuUnSub,
								onclick : {
									fn : SNAPPI.lightbox.unGroupShot
									, context : SNAPPI.lightbox
								}
							}];

			var organizeBtnMenuCfg = {
				id : id_organizeBtnMenu,
				menuItems : organizeBtnMenuItemCfg
			};
			
			var organizeBtnMenu = new SNAPPI.Menu(organizeBtnMenuCfg);
			organizeBtnMenuNode = organizeBtnMenu.attach(organizeBtn, true, 'click');
// organizeBtnMenu.show();
			return organizeBtnMenu;
		},
		renderPrivacyChoices : function(){
			var id_organizeMenuPrivacy = 'privacy-list',
				uri_privacy = '/my/groups/.json',
				
				// menuItem_privacy definition
				menuItem_privacy = this.getNode();
			
			var afterAttach_privacy = {
		            complete: function(id, o, args){
						var privacySettings = [
					       				{
					       					id : 519,
					       					title : "Public - are publicly listed and visible to anyone.",
					       					label : 'Public'
					       				},
					       				{
					       					id : 71,
					       					title : "Members only - are NOT publicly listed, and are visible only when shared in Groups or Events, and only by Group members.",
					       					label : 'Members only'
					       				},
					       				{
					       					id : 7,
					       					title : "Private - are NOT publicly listed and visible only to me.",
					       					label : 'Private'
					       				} ];

						_renderPrivacyMenu(menuItem_privacy, privacySettings);
		            }
		        };
			
			var _renderPrivacyMenu = function(node, jsonGroups){
				var json = (Y.Lang.isString(jsonGroups)) ? eval('(' + jsonGroups + ')')
						: jsonGroups,
						label_groupMenuMore = 'more groups...',
						class_groupMenuMore = 'more-btn-todialog';;
				
				var menuItems = [];
				var more = false;
				for (var i in json) {
					var itemCfg = {
						id: json[i].id,
						title: json[i].title,
						label: json[i].label
					};
					
					if(i >= 5){
	                	if (!more){

	                    	more = true;
	                    	itemCfg.label = label_groupMenuMore;
	                    	itemCfg.className = class_groupMenuMore;
	                    	
	                	}else {

	                    	itemCfg.className = 'hide';
	                	}
	                }else {
	                }
					menuItems.push(itemCfg);
				};
				
				var menuCfg_privacy = {
					menuItems : menuItems
					, afterAttach : {
						fn     : SNAPPI.cfg.DialogCfg.initPrivacyMenuDBox
					}
				};
				
				// create menu with menu config 
				var menu_privacy = new SNAPPI.Menu(menuCfg_privacy);

				// attach it to the current node.
				menu_privacy.attach(menuItem_privacy);
				menu_privacy.show();
				
//				SNAPPI.AsyncLoading.detach(menuItem_privacy);
				Y.fire('snappi:completeAsync', menuItem_privacy);
				menu_privacy.parent.autoShowChild();
			};
			
			var asyncCfg = {
	        		fn     : SNAPPI.io.get,
					node   : menuItem_privacy,	
					context: this,  			
					args   : [uri_privacy, afterAttach_privacy]
	        };
			
			var async = new SNAPPI.AsyncLoading(asyncCfg);
			async.execute();
			
//			SNAPPI.AsyncLoading.render(asyncCfg);
		},
		renderGroupChoices : function(){
			var label_groupMenuShare = 'share',
				label_groupMenuUnShare = 'unShare',
				node = this.getNode(),
				groupMenuItemCfg = [
	    		    {
	    		    	label : label_groupMenuShare,
	    		    	firstMouseover : {
					    	fn : SNAPPI.cfg.MenuCfg.renderGroupSubChoices
	    		    	},
						hasChild: true
	    		    },
	    			,{
	    				label : label_groupMenuUnShare,
	    				firstMouseover : {
					    	fn : SNAPPI.cfg.MenuCfg.renderGroupSubChoices
	    		    	},
						hasChild: true
	            	}];
			            		
			var menuCfg_group = {
				menuItems : groupMenuItemCfg
			};
			
			var menu_group = new SNAPPI.Menu(menuCfg_group);
			menu_group.attach(node);
			menu_group.show();
			
			menu_group.parent.autoShowChild();
			return menu_group;
		},
		
		renderGroupSubChoices : function(){
			if(this.getNode().one(' > ul') !== null){
				this.firstHoverListener.detach();
				return false;
			}
			
			var uri = '/my/groups/.json',
				id_organizeMenuShare = 'share-list',
				id_organizeMenuUnShare = 'unShare-list',
				label_groupMenuMore = 'more groups...',
				class_groupMenuMore = 'more-btn-todialog';		
			
			var afterAttach_groupSubChoices = {
	            complete: function(id, o, args){
					_renderGroupSubMenu(args.node, o.responseText, args.Y);
	            }
	        };
			
			var _renderGroupSubMenu = function(node, jsonGroups, Y){
				var json = eval('(' + jsonGroups + ')');
				var groups;
				if (json.Groups) groups = json.Groups;
				else groups = json.Membership;
				
				var menuItems = [];
				var more = false;
				
				var thisGroupItem =  {
    		    	label : 'current group',
    		    	className : 'group',
    		    	onclick : {
						fn : SNAPPI.lightbox.applyShareInBatch
						, context : SNAPPI.lightbox
						, args : [null]
					},
					beforeShow: {
						fn : function(node){
							var hbar = node.get('nextSibling');
							if(SNAPPI.STATE.controller.keyName == "group" && SNAPPI.STATE.controller.xhrFrom.uuid != null)
							{
								node.removeClass('hide');
								// should add this to beforeShow in hbar to remove '.hide', but it's easy to manage to it here. 
								hbar.removeClass('hide');
							}else {
								node.addClass('hide');
								hbar.addClass('hide');
							}
						}
					}
    		    };
				menuItems.push(thisGroupItem);
				
				var bar = { 
					label : 'hbar'
				};
				menuItems.push(bar);
				
				for (var i in groups) {
					var itemCfg = {
						id: groups[i].id,
						label: groups[i].title,
						className : 'group'
					};
					
					if(i >= 5){
	                	if (!more){
	                    	more = true;
	                    	itemCfg.label = label_groupMenuMore;	                    	
	                	}else {
	                    	itemCfg.className = 'hide';
	                	}
	                }else {
	                }

					menuItems.push(itemCfg);

				};
				
				var bar_ = {	
					label : 'hbar'
				};
				menuItems.push(bar_);
				
				var publicGroupsItem = {
					label : 'browse public groups',
					onclick : {
						fn : function(){
							window.location.href = '/groups/open';
						}
					}
				};
				menuItems.push(publicGroupsItem);
				
				var check = this;
				var menuCfg_share = {
					id: id_organizeMenuShare,
					menuItems : menuItems
					, afterAttach : {
						fn     : SNAPPI.cfg.DialogCfg.initGroupMenuDBox
						, args : [node.dom().MenuItem.getMenu().getNode(), ' ul > li.group']
					}
				};
				var menu_share = new SNAPPI.Menu(menuCfg_share);
				var menu_shareNode = menu_share.attach(node);
				menu_share.show();
				Y.fire('snappi:completeAsync', node)
				menu_share.parent.autoShowChild();

				var menuCfg_unshare = {
					id: id_organizeMenuUnShare,
					menuItems : menuItems
				};
				var menu_unShare = new SNAPPI.Menu(menuCfg_unshare);
				var menu_unShareNode = menu_unShare.attach(node.get('nextSibling'));
				menu_unShare.parent.autoShowChild();
			};
			
			var asyncCfg = {
	        		fn     : SNAPPI.io.get,
					node   : this.getNode(),	
					context: this,
					args   : [uri, afterAttach_groupSubChoices, null, null, {node : this.getNode(), Y: SNAPPI.Y}]
	        };
			
			var async = new SNAPPI.AsyncLoading(asyncCfg);
			async.execute();
		},
		
		renderHeaderMenus : function(){
			var p_discoverBtn = 'discoverBtn',
				p_searchBtn = 'searchBtn',
				p_userAccountBtn = 'userAccountBtn',
				p_sectionHeaderMoreBtn = 'primaryActionsMoreBtn',
				id_discoverBtnMenu = 'menu-discover',
				id_searchBtnMenu = 'menu-search',
				id_userAccountBtnMenu = 'menu-userAccount',
				id_sectionHeaderMoreBtnMenu = 'menu-sectionHeaderMore',
				id_toggleLightboxMenu = 'menu-toggleLightbox';
			
			var discoverBtn = Y.one('#' + p_discoverBtn);
			if(discoverBtn){
				var discoverBtnMenuCfg = {
					id : id_discoverBtnMenu
				};
				
				var discoverBtnMenu = new SNAPPI.Menu(discoverBtnMenuCfg, PAGE.jsonData.menu.discover);
				discoverBtnMenu.attach(discoverBtn, true, 'click');
				discoverBtnMenu.hide();
				
				SNAPPI.Menu.plugInBlur(discoverBtn);
			}
			
			var searchBtn = Y.one('#' + p_searchBtn);
			
			if(searchBtn){
				var searchBtnMenuCfg = {
					id : id_searchBtnMenu
				};
				
				var searchBtnMenu = new SNAPPI.Menu(searchBtnMenuCfg, PAGE.jsonData.menu.search);
				searchBtnMenu.attach(searchBtn, true, 'click');
				searchBtnMenu.hide();
				
				SNAPPI.Menu.plugInBlur(searchBtn);
				
				ssNode = searchBtnMenu.getNode();
				var list = ssNode.all('li');
				var search = "Search " + PAGE.controllerKeyName;
				list.some(function(node){
					
					var text = node.one('a').get('innerHTML');
					if(text == search){
						node.addClass('snappi-submenu-ex-triangle');
						return true;
					}
				});
			};
			
			var userAccBtn = Y.one('#' + p_userAccountBtn);
			
			if(userAccBtn){
				var label_show = 'show',
					label_hide = 'hide',
					label_showLightbox = 'Show Lightbox';
				
				var class_selected = 'selected';
				
				var userAccBtnMenuCfg = {
					id : id_userAccountBtnMenu
				};
				
				var userAccBtnMenu = new SNAPPI.Menu(userAccBtnMenuCfg, PAGE.jsonData.menu.user);
				userAccBtnMenu.attach(userAccBtn, true, 'click');
				userAccBtnMenu.hide();

				// get 'show lightbox' menu item
				var menuItem_showLightbox = userAccBtnMenu.getItem('Show Lightbox').getNode();

				var menuItemCfg = [
					    			    {
					    			    	label : 'show',
					    			    	className : 'selected'
					    			    },
					        			,{
						    				label : 'hide'
						            	}, {
						            		label : 'none',
						            		className : 'hide'
						            	}];

	    		var handleShowHide = function(e) {
	    			var target = e.target,
	    				postData,
	    				lightbox = SNAPPI.lightbox.node;
	        		
	    			var callback = {
	            		complete: function(id, o, args){
	    		            var check;
	    	            },
	    				failure : function(id, o, args) {
	    					var check;
	    				}
	    			};
	    			
	    			if(!lightbox){
	    				return;
	    			}
	    			
	    			// menu_toggleLightboxNode
	    			this.getNode().all('li').removeClass(class_selected);
	    			target.addClass(class_selected);
	    			lightbox.toggleClass('hide');
	    			
	    			postData = {'lightbox.visible': (!lightbox.hasClass('hide'))};
	    			SNAPPI.io.writeSession(postData, callback, '');
	    	    };
				var menuCfg_toggleLightbox = {
					id: id_toggleLightboxMenu,
					menuItems : menuItemCfg,
					delegateEvents : {
						type : 'click',
						handler   : function(){
							return handleShowHide;
						}
					},
					afterAttach: {
						
						// check if the lightbox is on or hide, then render the switch
						fn : function(){
							var menu_toggleLightboxNode = menu_toggleLightbox.getNode(),
								menuItem_show = menu_toggleLightboxNode.one('> li');
									
							try{	
								if(Y.one('#lightbox')){
									if(PAGE.jsonData.lightbox.visible === 'false'){
					            		
						        		var menuItem_hide = menuItem_show.get('nextSibling');
						        		menuItem_hide.addClass(class_selected);
						        		menuItem_show.removeClass(class_selected);
					            	} else throw("show Lightbox"); 
								} else {
									
				            		this.getItem('none').show();
				            		this.getItem('hide').hide();
				            		this.getItem('show').hide();
								}
								
				    		}catch(e){
		
				    		}			
						}
					}
				};
					    			
				var menu_toggleLightbox = new SNAPPI.Menu(menuCfg_toggleLightbox);
				menu_toggleLightbox.attach(menuItem_showLightbox);
				menu_toggleLightbox.parent.autoShowChild();
				
				SNAPPI.Menu.plugInBlur(userAccBtn);
			};
			
			var primaryMoreBtn = Y.one('#' + p_sectionHeaderMoreBtn);
			
			if(primaryMoreBtn){
				
				var value = PAGE.jsonData.menu.more;
				
				var sectionHeaderMoreBtnMenuCfg = {
						id : id_sectionHeaderMoreBtnMenu
						, beforeShow : {
							fn : function(node){
								var t = primaryMoreBtn.get('offsetHeight') + OFFSET_CONTEXT_MENU.header.top;
								var l = primaryMoreBtn.get('offsetLeft') + OFFSET_CONTEXT_MENU.header.left;
								node.setStyle('top', t);
								node.setStyle('left', l);
							}
						}
					};
					
				var sectionHeaderMoreBtnMenu = new SNAPPI.Menu(sectionHeaderMoreBtnMenuCfg);
				var primacyMoreBtnMenuNode = sectionHeaderMoreBtnMenu.attach(primaryMoreBtn, true, 'click');

				for (i in value) {
					
					value[i] = "<li>" + value[i] + "</li>"; 
					var n = Y.Node.create(value[i]);
					primacyMoreBtnMenuNode.append(n);
				}
				
				sectionHeaderMoreBtnMenu.hide();
				SNAPPI.Menu.plugInBlur(primaryMoreBtn);
			
			SNAPPI.DragDrop.pluginDrop(Y.one('.section-header .thumbnail'));
			}
		},
		
		renderPerpageMenu : function(){
			
			var perpageBtn = Y.one('#perpage_button');
			
			if(perpageBtn && (!Y.one('#submenu-perpage'))){
				
				var currentLocation = window.location.href;
				
				var perpageBtnMenuItemCfg = [
                     	{
						label : '10',
						href  : (SNAPPI.IO.setNamedParams(window.location.href, {
									       perpage:10
								      }))
					},{
						label : '50',
						href  : (SNAPPI.IO.setNamedParams(window.location.href, {
								       perpage:50
								}))
					},{
							label : '100',
						href  : (SNAPPI.IO.setNamedParams(window.location.href, {
								       perpage:100
							      	}))									
						},{
						label : '200',
						href  : (SNAPPI.IO.setNamedParams(window.location.href, {
								       perpage:200
							      	}))	
					},{
						label : '1000',
						href  : (SNAPPI.IO.setNamedParams(window.location.href, {
								       perpage:1000
							      	}))	
					}];
				
				var perpageBtnMenuItemCfg = {
					id : 'submenu-perpage',
					menuItems : perpageBtnMenuItemCfg
				};
				
				var perpageBtnMenu = new SNAPPI.Menu(perpageBtnMenuItemCfg);
				perpageBtnMenu.attach(perpageBtn, true, 'click');
				
				var callback = {
					complete : function(){},
					failure  : function(){}
				};
				
				var postData = [];
				var controllerKeyName;
				
				// TODO: will rewrite the following code after the menuitem/menu api is fixed
				perpageBtnMenu.getNode().delegate('click', function(e){
					var controllerAttrs = SNAPPI.STATE.controller;
					controllerKeyName = 'profile.' + controllerAttrs.action + '.perpage';
					postData[controllerKeyName] = e.target.get('textContent');
					SNAPPI.io.writeSession(postData, callback, '');
				}, 'li');
				
				SNAPPI.Menu.plugInBlur(perpageBtn);
				
			}	
		}
	};
	
	SNAPPI.cfg.MenuCfg = new MenuCfg();
	
})();