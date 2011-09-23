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
	Paginator.paginate_PhotoGallery = function(gallery){
			var self = gallery.node.Gallery;	// gallery
			var target = self.container;
			var NAME = '.gallery.photo';
			var DELAY = 1000;	// delay_task

			var paginateContainer = target.get('parentNode').next('div.paging-numbers');
			if (!paginateContainer) {
				if (target.ancestor('section.gallery')) {
					// auto-create paging DIV
					paginateContainer = target.create("<div class='paging-control paging-numbers grid_16' />");
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
				var uri = self.castingCall.CastingCall.Request+ "/.json";				
				var nameData = {
					page: pageNumber,
					perpage: this.Paginator.get('rowsPerPage')
				};
				if (target.io) {
					// already plugged, just reuse
					uri = SNAPPI.IO.setNamedParams(uri, nameData);
					target.io.set('arguments', nameData)
					target.io.set('uri', uri).start();
					return;
				}
				// uses pluginIO_RespondAsJson() with Plugin.IO
				target.plug(Y.Plugin.IO, SNAPPI.IO.pluginIO_RespondAsJson({
					uri: uri ,
					parseContent:true,
					nameData: nameData,
					dataType: 'json',
					context: self.node,	// test
					on: {
						success: function(e, id, o, args) {
							// gallery = this.Gallery;
							if (o.responseJson) {
								PAGE.jsonData = o.responseJson.response;
								SNAPPI.mergeSessionData();
								new SNAPPI.Gallery({type:'Photo'});
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
					maxPageLinks: 9,
					rowsPerPage: displayPage.perpage,
					rowsPerPageOptions: [displayPage.perpage, 12,24,48,96],
					alwaysVisible: false,
					containers: paginateContainer,
		firstPageLinkLabel: '&nbsp;',
		prevPageLinkLabel: '&nbsp;',	
		nextPageLinkLabel: '&nbsp;',
		lastPageLinkLabel: '&nbsp;',		
		pageReportLabelTemplate: 'Page {page} of {totalPages}',
		template:  '<div class="wrap"><div class="wrap-rows-per-page">Snaps per Page {RowsPerPageSelect}</div><div class="wrap-page-report">{CurrentPageReport}</div><div class="wrap-paginator-link">{FirstPageLink} {PrevPageLink} {PageLinks} {NextPageLink} {LastPageLink}</div></div>',	
					on: {
						changeRequest: function(e) {
							// this == Paginator
							// var self = gallery.node.Gallery;
							var newState = e.state;
							var userClicked = newState.before != undefined;
							if (userClicked) {
								console.warn('Page.changeRequest: page='+newState.page);
								_getPage.call(paginateContainer,newState.page);
							} 
							this.setState(newState);
						},
						rowsPerPageChange: function(e){
							if (e.newVal == e.prevVal) return;
							SNAPPI.STATE.displayPage.perpage = parseInt(e.newVal);
							SNAPPI.STATE.displayPage.page = null;
						}						
					}
			};
			if (DELAY) {
				var delayed = new Y.DelayedTask( function() {
					var P = new Y.Paginator(pageCfg);
					paginateContainer.Paginator = P;
					paginateContainer.dom().Paginator = P;
					P.target = target;
					Paginator.find[NAME] = P;
					P.render();
				}, this);
				delayed.delay(DELAY);
				return "delayed";
			} else {
				var P = new Y.Paginator(pageCfg);
				paginateContainer.Paginator = P;
				paginateContainer.dom().Paginator = P;
				P.target = target;
				Paginator.find[NAME] = P;
				P.render();			
				return P;
			}
	};

	/**
	 * add aui paginator to Gallery node, 
	 * 		NOTE: tested for Xhr paginate, not .json
	 * 	target is the node which receives the xhr content
	 * 
	 * @params CSS selector to Gallery node, i.e. section.gallery.person
	 */
	Paginator.paginate_CircleMemberGallery = function(selector){
			var Y = SNAPPI.Y;
			var self, target, baseurl, NAME = selector;	
			self = Y.one(NAME);
			target = Y.one(NAME +' div.container');
			var type = /person/.test(selector) ? 'Members' : 'Circles';
				
			var DELAY = 1000;	// delay_task			
			// if (gallery) {
				// self = gallery.node;	// gallery
				// target = self.Gallery.container;
			// } else {
				// self = Y.one(NAME);
				// target = Y.one(NAME +' div.container');	
			// }
			

			var paginateContainer = target.get('parentNode').next('div.paging-numbers');
			if (!paginateContainer) {
				if (target.ancestor('section.gallery')) {
					// auto-create paging DIV
					paginateContainer = target.create("<div class='paging-control paging-numbers grid_16' />");
					target.get('parentNode').insert(paginateContainer,'after');
				} else {
					return false;	// this is a preview, do NOT auto-create paging DIV
				}
			}			
			
			if (Paginator.find[NAME]) {
				// already created, just reuse
				return Paginator.find[NAME];
			}
			
			var displayPage = SNAPPI.STATE.displayPage;
			var pageCfg = {
					page: displayPage.page,
					total: displayPage.total,  
					maxPageLinks: 10,
					rowsPerPage: displayPage.perpage,
					rowsPerPageOptions: [displayPage.perpage, 12,24,48,96],
					alwaysVisible: false,
					containers: paginateContainer,
		firstPageLinkLabel: '&nbsp;',
		prevPageLinkLabel: '&nbsp;',	
		nextPageLinkLabel: '&nbsp;',
		lastPageLinkLabel: '&nbsp;',		
		pageReportLabelTemplate: 'Page {page} of {totalPages}',
		template:  '<div class="wrap"><div class="wrap-rows-per-page">'+type+' per Page {RowsPerPageSelect}</div><div class="wrap-page-report">{CurrentPageReport}</div><div class="wrap-paginator-link">{FirstPageLink} {PrevPageLink} {PageLinks} {NextPageLink} {LastPageLink}</div></div>',	
					on: {
						changeRequest: function(e) {
							// this == Paginator
							// var self = gallery;
							var newState = e.state;
							var userClicked = newState.before != undefined;
							if (userClicked) {
								console.warn('Page.changeRequest: page='+newState.page);
								Paginator._getPage.call(paginateContainer, newState.page);
							} 
							this.setState(newState);
						},
						rowsPerPageChange: function(e){
							if (e.newVal == e.prevVal) return;
							SNAPPI.STATE.displayPage.perpage = parseInt(e.newVal);
							SNAPPI.STATE.displayPage.page = null;
						}
					}
			};
			if (DELAY) {
				var delayed = new Y.DelayedTask( function() {
					paginateContainer.Paginator = new Y.Paginator(pageCfg).render();
					paginateContainer.dom().Paginator = paginateContainer.Paginator;
					paginateContainer.Paginator.target = target;
					Paginator.find[NAME] = paginateContainer.Paginator;
				}, this);
				delayed.delay(DELAY);
				return "delayed";
			} else {
				paginateContainer.Paginator = new Y.Paginator(pageCfg).render();
				paginateContainer.dom().Paginator = paginateContainer.Paginator;
				paginateContainer.Paginator.target = target;
				Paginator.find[NAME] = paginateContainer.Paginator;
				return paginateContainer.Paginator;
			}
	};	
	
	/**
	 * get requested page, uses A.Plugin.IO + SNAPPI.IO.pluginIO_RespondAsJson pattern
	 * @access private 
	 * @param pageNumber
	 * @return 
	 */
	Paginator._getPage = function(pageNumber){
		var Y = SNAPPI.Y;
		var target = this.Paginator.target;  // paginateContainer
		if (pageNumber == SNAPPI.STATE.displayPage.page) return;
		// WARNING: THIS DOES NOT WORK FOR .json XHR REQUESTS, USE new Gallery().render()
		// var uri = controller.here + "/.json";		// TODO: use json rendering for groups
		var baseurl = SNAPPI.STATE.controller.here;	
		var nameData = {
			page: pageNumber,
			perpage: this.Paginator.get('rowsPerPage')
		};
		baseurl = SNAPPI.IO.setNamedParams(baseurl, nameData);
		if (target.io) {
			// already plugged, just reuse
			target.io.set('arguments', nameData)					
			target.io.set('uri', baseurl).start();
		} else {
			// uses pluginIO_RespondAsJson() with Plugin.IO
			target.plug(Y.Plugin.IO, {
				uri: baseurl ,
				parseContent:true,
				arguments: nameData,
				on: {
					success: function(e, id, o , args) {
						SNAPPI.mergeSessionData();
						SNAPPI.STATE.displayPage.page = args.page;
						var check;
					}
				},
				// for pluginIO_RespondAsJson() only 
				// nameData: nameData,		
				// dataType: 'json',
				// context: self,	// test
				// on: {
					// success: function(e, id, o, args) {
						// if (o.responseJson) {
							// PAGE.jsonData = o.responseJson;
							// SNAPPI.mergeSessionData();
							// new SNAPPI.Gallery({type:'Photo'});
							// // TODO: update paginateContainer.Paginator.set('total'), etc									
							// return false;	// plugin.IO already rendered
						// }
					// }	
				// }
				end: null
			});
		}
		return;
	};	
	SNAPPI.Paginator = Paginator;
})();
