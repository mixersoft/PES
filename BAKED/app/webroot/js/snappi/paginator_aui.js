(function(){	
	var Paginator = function(){
		if (Paginator.doClassInit) Paginator.classInit();
	};
	Paginator.prototype = {};
	
	/*
	 * static properties and methods
	 */
	Paginator.doClassInit = true;
	Paginator.listen = {};
	Paginator.find = {};	// keep track of dialog instances for reuse
	
	Paginator.classInit = function() {
		var Y = SNAPPI.Y;
		Paginator.doClassInit = false;
	};

	/**
	 * add Paginator to gallery
	 * - uses A.DelayedTask, A.PluginIO
	 * @param gallery
	 * @return [Paginator | 'delayed'], if created, or False if not created
	 */
	Paginator.paginate_Photoroll = function(gallery){
			var self = gallery;	// gallery
			var target = self.container;
			var NAME = '.gallery.photo';
			var DELAY = 1000;	// delay_task

			var paginateContainer = target.get('parentNode').next('div.paging-numbers');
			if (!paginateContainer) {
				if (target.ancestor('section.gallery')) {
					// auto-create paging DIV
					paginateContainer = target.create("<div class='paging-control paging-numbers' />");
					target.get('parentNode').insert(paginateContainer,'after');
				} else {
					return false;	// this is a preview, do NOT auto-create paging DIV
				}
			}
			if (Paginator.find[NAME]) {
				// already created, just reuse
				return Paginator.find[NAME];
			}
			
			var Y = SNAPPI.Y;
			var controller = SNAPPI.STATE.controller;
			var displayPage = SNAPPI.STATE.displayPage;
			
			/**
			 * get requested page, uses A.Plugin.IO + SNAPPI.IO.pluginIO_RespondAsJson pattern
			 * access == closure
			 * @param pageNumber
			 * @return 
			 */
			var _getPage = function(pageNumber){
				if (pageNumber == SNAPPI.STATE.displayPage.page) return;
				var uri = controller.here + "/.json";				
				var nameData = {page: pageNumber};
				if (target.io) {
					// already plugged, just reuse
					uri = SNAPPI.IO.setNamedParams(uri, nameData);
					target.io.set('uri', uri).start();
					return;
				}
				// uses pluginIO_RespondAsJson() with Plugin.IO
				target.plug(Y.Plugin.IO, SNAPPI.IO.pluginIO_RespondAsJson({
					uri: uri ,
					parseContent:true,
					nameData: nameData,
					dataType: 'json',
					context: gallery.node,	// test
					on: {
						success: function(e, id, o, args) {
							// gallery = this.Gallery;
							if (o.responseJson) {
								PAGE.jsonData = o.responseJson.response;
								SNAPPI.mergeSessionData();
								SNAPPI.domJsBinder.bindAuditions2Photoroll();
								// TODO: update paginateContainer.Paginator.set('total'), etc									
								return false;	// plugin.IO already rendered
							}
						}	
					}
				}));
				return;
			};
			
			
			var pageCfg = {
					page: displayPage.page,
					total: displayPage.total,  
					maxPageLinks: 10,
					rowsPerPage: displayPage.perpage,
					rowsPerPageOptions: [displayPage.perpage, 12,24,48,96],
					alwaysVisible: false,
					containers: paginateContainer,
					on: {
						changeRequest: function(e) {
							// this == Paginator
							// var self = gallery.node.Gallery;
							var newState = e.state;
							var userClicked = newState.before != undefined;
							if (userClicked) {
								console.warn('Page.changeRequest: page='+newState.page);
								_getPage(newState.page);
							} 
							this.setState(newState);
						}
					}
			};
			if (DELAY) {
				var delayed = new Y.DelayedTask( function() {
					paginateContainer.Paginator = new Y.Paginator(pageCfg).render();
					Paginator.find[NAME] = paginateContainer.Paginator;
				}, this);
				delayed.delay(DELAY);
				return "delayed";
			} else {
				paginateContainer.Paginator = new Y.Paginator(pageCfg).render();
				Paginator.find[NAME] = paginateContainer.Paginator;
				return paginateContainer.Paginator;
			}
	};

	Paginator.paginate_Grouproll = function(grouproll){
			var Y = SNAPPI.Y;
			var target, self;
			var NAME = 'div.element-roll.group';
			var DELAY = 1000;	// delay_task			
			if (grouproll) {
				// legacy, from photoRoll, json rendering
				self = grouproll;
				target = self.container;
			} else {
				target = Y.one(NAME +' ul.group-roll');	// should be the ul
			}


			var paginateContainer = target.ancestor(NAME).one('div.paging-numbers');
			if (!paginateContainer) {
				if (target.ancestor('#paging-groups')) {
					// auto-create paging DIV
					paginateContainer = target.create("<div class='paging-control paging-numbers' />");
					target.get('parentNode').insert(paginateContainer,'after');
				} else {
					return false;	// this is a preview, do NOT auto-create paging DIV
				}
			}
			if (Paginator.find[NAME]) {
				// already created, just reuse
				return Paginator.find[NAME];
			}
			
			var controller = SNAPPI.STATE.controller;
			var displayPage = SNAPPI.STATE.displayPage;
			
			/**
			 * get requested page, uses A.Plugin.IO + SNAPPI.IO.pluginIO_RespondAsJson pattern
			 * access == closure
			 * @param pageNumber
			 * @return 
			 */
			var _getPage = function(pageNumber){
				if (pageNumber == SNAPPI.STATE.displayPage.page) return;
				var uri = controller.here + "/.json";		// TODO: use json rendering for groups
				var uri = controller.here;					// deprecate, use json rendering when ready
				var nameData = {page: pageNumber};
				if (target.io) {
					// already plugged, just reuse
					uri = SNAPPI.IO.setNamedParams(uri, nameData);
					target.io.set('uri', uri).start();
					return;
				}
				// uses pluginIO_RespondAsJson() with Plugin.IO
				uri = uri;
			  	if (nameData) {		// for XHR response
			    	uri = SNAPPI.IO.setNamedParams(uri, nameData);
		    	};
				target.plug(Y.Plugin.IO, {
					uri: uri ,
					parseContent:true,
					on: {
						success: function(e, id, o , args) {
							SNAPPI.STATE.displayPage.page = pageNumber;
						}
					},
					// for pluginIO_RespondAsJson() only 
					// nameData: nameData,		
					// dataType: 'json',
					// context: grouproll,	// test
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
					end: null
				});
				return;
			};
			
			
			var pageCfg = {
					page: displayPage.page,
					total: displayPage.total,  
					maxPageLinks: 10,
					rowsPerPage: displayPage.perpage,
					rowsPerPageOptions: [displayPage.perpage, 12,24,48,96],
					alwaysVisible: false,
					containers: paginateContainer,
					on: {
						changeRequest: function(e) {
							// this == Paginator
							var self = grouproll;
							var newState = e.state;
							var userClicked = newState.before != undefined;
							if (userClicked) {
								console.warn('Page.changeRequest: page='+newState.page);
								_getPage(newState.page);
							} 
							this.setState(newState);
						}
					}
			};
			if (DELAY) {
				var delayed = new Y.DelayedTask( function() {
					paginateContainer.Paginator = new Y.Paginator(pageCfg).render();
					Paginator.find[NAME] = paginateContainer.Paginator;
				}, this);
				delayed.delay(DELAY);
				return "delayed";
			} else {
				paginateContainer.Paginator = new Y.Paginator(pageCfg).render();
				Paginator.find[NAME] = paginateContainer.Paginator;
				return paginateContainer.Paginator;
			}
	};
	
	SNAPPI.Paginator = Paginator;
})();