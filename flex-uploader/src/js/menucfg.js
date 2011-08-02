
console.log("load BEGIN: menucfg.js");
(function(){
	
	var Y = SNAPPI.Y;
	
	var OFFSET_CONTEXT_MENU = {group : {left : 20, top : 5}};
		OFFSET_CONTEXT_MENU.header = {left : 0, top : 5};
	var MenuCfg = function(){};
	
	MenuCfg.prototype = {
		listenToUploaderRoll : function(){
			var Y = SNAPPI.Y;
			var rootNode = Y.one('#uploader div.panels');
			if(rootNode){
				rootNode.delegate('contextmenu', function(e, rootNode){
		           	e.preventDefault();
		       		var target = e.currentTarget,
	       			rootNodeDOM = rootNode.dom();
	       		
		       		if(!rootNodeDOM.contextMenu){
		       			// need to add stop listener.
		       			SNAPPI.AIR.MenuCfg.renderUploaderContextMenu(target);
		       		}else {
		       			rootNodeDOM.contextMenu.parent.container = target;
		       			rootNodeDOM.contextMenu.show();
		       		}
				}, 'ul.page > li', {}, rootNode);
				LOG("listen to UploaderRoll  STARTED. <<<<<<<<<<<<<<<<<<<<<");
			}else {
				LOG("listen to UploaderRoll  NOT STARTED. <<<<<<<<<<<<<<<<<<<<<");
			}
		}, 
		renderUploaderContextMenu : function(target){
			var uploaderContextMenuItemCfg = [{
					label: 'Remove Photo',
					onclick: {
            	   		fn : function(){
							var contextMenu_target =  this.getMenu().parent.getNode();
							var uuid = contextMenu_target.getAttribute('uuid');
							SNAPPI.AIR.uploadQueue.action_remove(uuid);
							this.hide();
						}
					}
				},{
	         	   label : 'Retry Upload',
	         	   onclick : {
	         	   		fn : function(){
								LOG("Retry");
	            			}
	                	}
	    				, beforeShow : {
	 					fn : function(e){
		       					var menuNode = this.getMenu().getNode();
								var contextMenu_target =  this.getMenu().parent.getNode();
								if (contextMenu_target.hasClass('status-cancelled')) this.hide();
	  					}
	 				}
				},{
            	   label : 'Cancel Upload',
            	   onclick : {
            	   		fn : function(){
							LOG("CANCEL");
               			}
                   }
       				, beforeShow : {
    					fn : function(e){
	       					var menuNode = this.getMenu().getNode();
							var contextMenu_target =  this.getMenu().parent.getNode();
							if (contextMenu_target.hasClass('status-cancelled')) this.hide();
							if (contextMenu_target.hasClass('status-error')) this.hide();
     					}
    				}
               }];
				                           
			var uploaderContextMenuCfg = {
					id : 'menu-groupContext',
					className: 'contextmenu',
					menuItems : uploaderContextMenuItemCfg
					, afterAttach : {
						fn : function(node){
							var parentNode = node.ancestor('ul.page');
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
			
			var uploaderContextMenu = new SNAPPI.Menu(uploaderContextMenuCfg);
			uploaderContextMenu.attach(target, true, 'click');
			// i think i still need to call show() explicitly, because we cannot assume all user will like to open menu 
			// right after the menu is attached to somewhere. so show() cannot be a built-in function inside the menu.attach()
			uploaderContextMenu.show();
		},		
		renderPhotoContextMenu : function(target){
			var t = target.get('offsetTop'),
				class_ratingGrp = '.ratingGroup',
				class_LI_thumbnail = 'li.thumbnail',
				id_contextMenuRatingGrp = 'menuItem-contextRatingGrp', 
				id_contextMenuInfo = 'menu-photoRollContext';
			
			var label_contextMenu = 'more info',
				id_contextMenuInfo = 'menuItem-contextInfo', 
				id_photoRollContext = 'menu-photoRollContext';	
		
			var photoRollNode = Y.one('#paging-photos-inner .photo-roll');
			
			/*
			 * private helper methods
			 */
			var _getPhotoRoll = function(contextMenu_target){
				try {
					var photoRoll = contextMenu_target.ancestor('ul.photo-roll').dom().PhotoRoll; 
				} catch (e) {
					// for ul.filmstrip and ul.substitutes
					photoRoll = contextMenu_target.ancestor('ul').dom().PhotoRoll; 
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
	        		// TODO: there's a bug, that is, the ratingGrp is in a <div>, if i use SNAPPI.Rating.pluginRating, then
	        		// i would add a div to the menu, but now it works, but i need to make it more logical 
	        		// refactor or write a new function to plugginRating as <li>
	        		
	        		// rating item
	        		label : '',
	        		afterAttach : {
	        			fn :// the first time context menu is attached to one li.thumbnail
	        				// happens before the menu's afterAttach. 
	        				// will not be called after this time.
	        				function(target){
	        					var menuNode = this.getMenu().getNode();
	        					var contextMenu_target =  target;
	        					var audition = contextMenu_target.dom().audition;
	        					var photoRoll = _getPhotoRoll(contextMenu_target);
		            			// add new rating group
	        					SNAPPI.Rating.pluginRating.call(menuNode, menuNode, target.audition.rating);
		            			SNAPPI.Rating.startListeners(menuNode);
		            			
		                    	menuNode.one('.ratingGroup').setAttribute('uuid', audition.id);
		            			menuNode.one('.ratingGroup').setAttribute('id', 'menuItem-contextRatingGrp');
		        			}
	        			, args: [target]	// target is only valid on attach, note beforeShow
	        		},
	        		beforeShow : {
	        			fn : function(){
							var menuNode = this.getMenu().getNode();
							var contextMenu_target =  this.getMenu().parent.getNode();
							var audition = contextMenu_target.dom().audition;
							var photoRoll = _getPhotoRoll(contextMenu_target);						
        					SNAPPI.Rating.pluginRating.call(menuNode, menuNode, audition.rating);
				        	menuNode.one('.ratingGroup').setAttribute('uuid', audition.id);
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
	        					
	        					SNAPPI.dialogbox.render(cfg);
	    						var menu = this.getMenu();
	    						var contextMenu_target =  menu.parent.getNode();
	    						var audition = contextMenu_target.dom().audition;
	    						var photoRoll = _getPhotoRoll(contextMenu_target);

	    						var asyncCfg = {
	    							fn : SNAPPI.PhotoRoll.renderSubstituteAsPreview,
	    							node : SNAPPI.dialogbox.node.one('.body > ul'),
	    							context : photoRoll,
	    							size: 'huge',
	    							args : [audition, SNAPPI.dialogbox.node.one('.body > ul')]
	    						};
	    						
	    						var async = new SNAPPI.AsyncLoading(asyncCfg);
	    						async.execute();
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
	    						photoRoll.groupAsShot(null, {loadingNode: this.getNode()});
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
			           selector : 'li.thumbnail',
			           args : photoRollNode.dom().PhotoRoll
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

	            			SNAPPI.Menu.plugInBlur(photoRoll.container, menuNode, "> li.thumbnail");
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
		}		
	};
	
	SNAPPI.AIR.MenuCfg = new MenuCfg();
	LOG("load complete: menucfg.js");		
})();