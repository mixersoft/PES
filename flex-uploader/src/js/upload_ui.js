/**
 *
 * Copyright (c) 2009-2011, Snaphappi.com. All rights reserved.
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
 *
 */
(function() {
	var util = SNAPPI.coreutil;
	/*
	 * this class only used to generate and manage upload queue UI part e.g.
	 * pagination,start/pause/clear all
	 */
	var UploadQueue = function(cfg) {
		var Y = SNAPPI.Y;
		
		this.load = function(cfg) {
			LOG("-----------------------> uploadQueue.load() ");
			
			
			this.container = cfg.container || Y.one('#uploader');
			this.ds = cfg.datasource || null;
			this.isUploading = typeof _flexAPI_UI === "undefined" ? null : _flexAPI_UI.isUploadQueueRunning;
			this.paginate = this.cakeStylePaginate;
			this.XXXsnapshot = {};  		// snapshot of active upload rows
			
			/*
			 * set to initialized batchId
			 */
			this.batchId = null; // should be static

			/*
			 * uploader items perpage
			 */
			this.perpage = __CONFIG.uploader.perpage;					// initialize in JS

			/*
			 * total no of pages available in the queue
			 */
			this.count_totalPages = 0;
			this.count_totalItems = 0;
			this.count_filterPages = 0;
			this.count_filterItems = 0;
			this.status = null;		
			/***********************************************************************
			 * Paging
			 */
			this.activePage = 0; // current page index (shows in Paginator)		
			this.pausedPage = 0; // paused page index, used to resume/start the paused page again	
			this.uploadPage = 0; // page of current active upload 	
			this.page = 0;		

			// uploading
			this.uploadRows = null;		// upload page total items
			this.uploadItemIndex = -1;	// upload page items index
			this.isCancelling = false;	// cancel at the next good moment
			/*
			 * flag to tell that is upload running it is checked by start function
			 * to ignore the extra click on start link option
			 */
			this.isUploading = null;			
			this.isPaused = false;	// pause upload at next chance
			this.flexUploadAPI = new SNAPPI.AIR.Flex_UploadAPI();
		}
		
		/*
		 * init from Flex
		 */
		this.init = function() {
			LOG("-----------------------> uploadQueue.init() ");			
			this.listen_cancel();
			
			// show img after load, doesn't work
//			this.listen_imgLoad();	
			
			// activate drag-drop on Hover, DOESN'T WORK IN JS, DO FROM FLEX
//			this.listen_hover();
		};
		
		this.load(cfg);
		this.init(cfg);
		
		
		/*
		 * when ui working and in progress and someone add more photos to upload
		 * queue then it detects the changes and manage the ui
		 */
		this.addToQueue = function(pages, perpage, rows) {
		};

	};

	UploadQueue.prototype = {
		useMore : function() {
		// doesn't work
			SNAPPI.useMore.call(this, 
//				'AIR-upload-manager', 'AIR-file-progress',
				'snappi-thumbnail-helpers',
				function(Y) {
					LOG(SNAPPI.Y);
					LOG(SNAPPI.MultiSelect2);
					var check;
				}
			);
		}, 
		/**
		 * 
		 * @return boolean === false, wait for XHR response
		 */
		setDatasource : function(cfg, fnContinue) { // OK
			cfg = cfg || {};
			this.ds = cfg.datasource || this.ds || _flexAPI_UI.datasource;
		},
		/***********************************************************************
		 * uploader actions
		 */
		show : function(tab) {
//			this.useMore();
			tab = tab || 'all';
			this.container.removeClass('hide');
			if (tab == 'reload') {
				// add to import queue
				tab = this.status || 'all';
				this.initQueue(tab);
			} else if (this.status && this.status != tab) {
				this.initQueue(tab);
			}
			try {
				SNAPPI.AIR.MenuCfg.listenToUploaderRoll();
			} catch (e) {}
			this.view_showPage();
		},
		hide : function() {
			this.container.addClass('hide');
		},
		/***********************************************************************
		 * upload actions
		 */
		/*
		 * start action handler called when user click on start/resume link it
		 * also resume the paused queue if any
		 */
		action_start : function() {
			
			// UI.startUploadPage > UI.view_showPage > UI.doUpload > ui.nextUploadPage() > ui.startUploadPage
			if (this.isUploading || this.count_totalPages == 0) {
				return;
			}
			// switch to 'all' tab for uploading, upload paging does not work with filtered tabs
//			this.status = 'all';
//			this.pausedPage = 0;
//			this.isPaused = false;
//			this.uploadPage = (this.isPaused) ? this.pausedPage : 1;
			var page = (this.isPaused) ? this.pausedPage : 1;
			this.isUploading = true;
			this.isCancelling = false;
			this.startUploadPage(page);
		},
		/*
		 * pause action handler fired when user clicks on pause link option it
		 * fires the pauseQueue function which internally paused the queue and
		 * pauseQueue called the doPause ui function to update the UI
		 */
		action_pause : function() {
//			htmlctrl.pauseQueue();
			this.doPause();
		},
		/*
		 * retry action handler fired when user do the retry action it requeue
		 * the error flagged items. and after requeue the retryUpload function
		 * fires the doRetry function to update the error items in UI
		 */
		action_retry : function() {
			htmlctrl.retryUpload();
		},
		/*
		 * cancel all action handler runs when user do the clear action it stop
		 * the current uploadpage if any and clear upload queue clear( [all |''
		 * |undefined) closes batch
		 */
		action_clear : function(status) {
			status = status || this.status;
			LOG("clearing for status=" + status + ', batch='
					+ this.batchId);
			this.stopUploadPage();
			htmlctrl.clear(status, this.batchId);
			if (status == 'all' || status == '' || status == undefined) {
				this.batchId = null;
				LOG("cleared for status=" + status + ', batch='
						+ this.batchId);
			}
		},
		/**
		 * cancel item(s) in queue, from onclick cancel, or stopUploadPage([clear | pause])
		 * - if progressContainer == null, cancel everything
		 * @params progressContainer FileProgress OPTIONAL 
		 * 	
		 */
		action_cancel : function(progressContainer) {
			var Y = SNAPPI.Y;
			var done = false;
			// cancel selected progressContainer, only
			if (progressContainer) {
				var progress = progressContainer.FileProgress;
				progress.setCancelled("Cancelled");
				_flexAPI_UI.datasource.setUploadStatus(progress.uuid, 'cancelled', this.batchId);
				progress.row_deprecate.status = 'cancelled';
			} else {
				// if we don't have a cancel target, cancel all active uploads
				SNAPPI.AIR.UploadManager.activeSH.each(function(um){
					um.cancel();
					SNAPPI.AIR.UploadManager.remove(um);
				});
			}
			// htmlctrl.removePhotoFromQueueById(fileId);
		},
		/*
		 * close action handler when user clicked on close button
		 */
		action_close : function() {
			this.action_pause();
			this.hide();
		},
		/**
		 * @params uuids string, CDL of asset Ids
		 */
		action_remove : function(uuids) {
			if (util.isString(uuids)) uuids = uuids.split(',');
			var result = _flexAPI_UI.datasource.deletePhoto(uuids);
			if (result) {
				this.view_showPage();  	// refresh
			}
		},

		/**
		 * DEPRECATE - USE datasource.getOpenBatchId()
		 * query local DB to get FIRST open batchId
		 * 
		 * @return string batchId, or false
		 */
		getOpenBatchId : function() { // OK
LOG("uploader getOpenBatchId has been DEPRECATED");
			return this.flexUploadAPI.getOpenBatchId();
		},
		/***********************************************************************
		 * view methods. move to another object
		 */
		/**
		 * create blank pages, allows for lazy loading of file icons - paginate
		 * links look for 'div.uq-page-#' TODO: maybe we should move this to the
		 * render method for each page?
		 */
		view_createBlankPages : function(count) { // OK
			var parent = this.container.one('#panels').set('innerHTML', '');
			var page_ID_PREFIX = "uq-page-";
			for ( var i = 1; i <= count; i++) {
				var pageTemplate = "<ul id='" + page_ID_PREFIX + i
						+ "' class='page hide'></ul>";
				var pageNode = this.container.create(pageTemplate);
				parent.append(pageNode);
			}
		},
		view_getBlankPage : function(i) { // OK
			var parent = this.container.one('#panels').set('innerHTML', '');
			var page_ID_PREFIX = "uq-page-";
			var pageNode = this.container.one('#page_ID_PREFIX' + i);
			if (!pageNode) {
				var pageTemplate = "<ul id='" + page_ID_PREFIX + i
						+ "' class='page hide'></ul>";
				pageNode = this.container.create(pageTemplate);
				parent.append(pageNode);
			} else pageNode.set('innerHTML', '');
			return pageNode;
		},
		
		
		
		/**
		 * initialize uploader, - sets paging params, binds to datasource
		 * 
		 * @params batchId string
		 * @params filter string - [all | pending | error | cancelled | done ]
		 * @params cfg
		 */
		// this.initQueue = function(pages, perpage, total){
		// TODO: should separate into initQueue and showQueue
		initQueue : function(filter, cfg) { // OK
			var Y = SNAPPI.Y;
			cfg = cfg || {};
			filter = filter || 'all';
			if (this.status != filter) {
				this.status = filter;
				// reset paging to accomodate new filter
				this.page = 0;
			}
			if (cfg.page) {
				this.page = cfg.page;
			}
			if (cfg.perpage) {
				this.perpage = cfg.perpage;
				this.flexUploadAPI.setPerpage(cfg.perpage);
			} else this.perpage = this.flexUploadAPI.getPerpage();
//			this.batch = cfg.batchId || cfg.batch || this.batch || this.flexUploadAPI.getOpenBatchId();
			if (util.isUndefined(cfg.batchId)) {
				this.batchId = this.flexUploadAPI.getOpenBatchId();
			} else {
				this.batchId = cfg.batchId; 
				this.flexUploadAPI.setBatchId(this.batchId);
			}
LOG("initQueue, batchId="+this.batchId+", getconfig() batchId="+this.flexUploadAPI.getBatchId());			
/*
 * uploadQueue should not care about batchId if null
 */			
			this.count_filterItems = this.flexUploadAPI.getCountByStatus(filter, this.batchId, '=');
			this.count_filterPages = Math.ceil(this.count_filterItems / this.perpage);
LOG ("filtered items="+this.count_filterItems + ", pages="+this.count_filterPages);			
			
			// for Total Progress only, move to View
			if (filter == 'all') {
				this.count_totalItems = this.count_filterItems;
			} else {
				this.count_totalItems = this.flexUploadAPI.getCountByStatus('all',this.batchId, '=');
			}
			this.count_totalPages = Math.ceil(this.count_totalItems / this.perpage);

			LOG(">>>>>>>>  uploadQueue.initQueue(), status=" + this.status
					+ ", pages=" + this.count_filterPages + ", perpage=" + this.perpage
					+ ", filter=" + this.count_filterItems + ", total=" + this.count_totalItems);

			LOG("initQueue OK");
		},
		
		/***********************************************************************
		 * Paging
		 */
		/**
		 * show page
		 * @params page int optional - null to show last visible page, i.e. show/hide uploader
		 * @params mode string default='show' - [upload | show] 		
		 */
		view_showPage : function(page, mode) {
			// enforce a notion of activePage (for Paginator) and uploadPage (for uploader)
			page = page || this.page || Math.min(this.count_filterPages, 1);
			
			mode = mode || 'show';
			var Y = SNAPPI.Y;
			var rows;
			// row keys: id, photo_id, batch_id, rel_path, status
			// rows = this.flexUploadAPI.getItemsByStatus(this.status, this.batch);
			// rows = rows.slice(this.perpage*(page-1),this.perpage*page);
			
			if (mode == 'upload') {
				this.status = 'all';	// forces tab change to 'all' on next page
				this.uploadPage = page;
				rows = this.flexUploadAPI.getPageItems(this.uploadPage, this.status);
				this.uploadRows = rows;
			} else {
				rows = this.flexUploadAPI.getPageItems(page, this.status);
			}
			LOG(">>> uploadQueue.getPageItems(), page=" + page
					+ ", previous page=" + this.activePage + ", status="
					+ this.status + ", count=" + rows.length);



			// hide last active page
			if (page != this.activePage) {
				try {
					Y.one('#uq-page-' + this.activePage).addClass('hide');
				} catch (e) {
				}
			}
			/*
			 * view add pages
			 */
//			this.view_createBlankPages(this.count_filterPages);
			// render page
			var pageNode = this.view_getBlankPage(page);
			if (pageNode) {
				// reset/render new page with array of Progress tiles
				pageNode.removeClass('hide');
				for ( var i = 0; i < rows.length; i++) {
//LOG(rows[i]);
					var cfg = {
							row_deprecate: rows[i],
							id : rows[i]['id'], // deprecate
							label : rows[i]['rel_path'],
							uuid : rows[i]['photo_id']
						};
					var uploadTile = new SNAPPI.AIR.FileProgress( cfg, pageNode);
					switch (rows[i]['status']) {
					case 'done':
						uploadTile.setComplete("Upload complete.");
						break;
					case 'error':
						uploadTile.setAlert('Warning: upload failed.');
						break;
					case 'cancelled':
						uploadTile.setCancelled('Upload cancelled.');
						break;
					case 'pending':
					default:
						if (this.isPaused) {uploadTile.setPaused()}
						else uploadTile.setReady();
						break;
					}
				}
			}
			/*
			 * update Paginator
			 */
			this.activePage = page;
			this.activePageNode = pageNode;
			var paginateParent = Y.one('#paginator').set('innerHTML', '');
			this.paginate(paginateParent, this.count_filterPages, this.activePage);
			/*
			 * view methods
			 */			
			this.view_setUploadTotalProgress();
			this.container.removeClass('hide'); // just in case
		},		
		
		
		
		/**************************************************************************
		 * uploading
		 */
		
		/**
		 * starts upload of a given page and makes visible
		 * @param page int optional - if null, continue from last upload page, or from beginning 
		 */
		startUploadPage : function(page) {
			if (page && page === this.uploadPage && this.isUploading && this.status=='all') {
LOG("startUploadPage DUPLICATE CALL");				
				// duplicate call to start same page
			} else {
				// start new page or continue
				page = page || this.uploadPage || 1;
				this.pausedPage = 0;
				this.isPaused = false;
				this.uploadPage = page;		
				
				// now begin uploading
				this.uploadRows = [];
				this.uploadItemIndex = -1;
				this.view_showPage(this.uploadPage, 'upload');
				this.doUpload();
			}
		},

		/*
		 * get next page to upload
		 */
		nextUploadPage : function() {
			if (this.uploadPage < this.count_totalPages) {
				return this.uploadPage + 1;
			}
			return 0;
		},

		/*
		 * this function check the upload page items if have some to upload then
		 * initiates upload object and start upload of file/item. otherwise
		 * check next page and if available or pending then start upload that
		 * page.
		 * 
		 * TODO: this must be modified to work in background, i.e. when DOM
		 * elements are hidden or not available
		 * 
		 */
		doUpload : function() {
			var UploadManager = SNAPPI.AIR.UploadManager;
			while (UploadManager.count() < UploadManager.MAX_CONCURRENT_UPLOADS) {
				this.uploadItemIndex++;
				if (this.uploadItemIndex < this.uploadRows.length) {
					var row = this.uploadRows[this.uploadItemIndex];
					var status = row['status'];
					if (status == 'pending') { // if pending then only start upload
LOG(">>>>>>> upload index = "+ this.uploadItemIndex + ", status=" + status+ " row=" + row.photo_id);
LOG("active count="+UploadManager.count());						
							/*
							 * configure uploadManager, start N uploads
							 */						
							var progress = this.getProgress(row['photo_id']);
							var handler = new UploadManager(this, row, progress);
							this.uploadRows[this.uploadItemIndex].handler = handler;  // backref. necessary?
							this.ds.uploadFile(row['photo_id'], handler);
					} else { // goto next
						this.doUpload();
					}
				} else {
					// start next page if any;
					var page = this.nextUploadPage();
					if (page) {
						// auto page change to next upload page on 'all' tab
						this.startUploadPage(page);
					} else {
						this.isUploading = false;
					}
					break;
				}
			}
		},
		/*
		 * stop current upload page which is running it has two modes 'clear'
		 * and 'pause' according to mode set status of items paused to cancelled
		 */
		stopUploadPage : function(mode) {
			var Y = SNAPPI.Y;
			mode = mode || 'clear';
			if (this.uploadPage != 0
					&& this.uploadItemIndex < this.uploadRows.length) {
				this.isCancelling = true; // stop queue here
//				this.action_cancel();
				// mark ALL li.progress for stop
				this.container.all('li.progress-container').each(function(n,i,l){
					if (n.hasClass('status-pending')){
						var progress = n.FileProgress;
						if (mode == 'clear') {
							progress.setCancelled("Cancelled.");
						} else {
							progress.setPaused("Paused");
					}						
					}
				}, this);

				// if(mode=='clear'){
				this.uploadPage = 0;
				this.uploadRows = [];
				this.uploadItemIndex = -1;
				// }
			}
		},		
		

		/*
		 * this function called by pauseQueue internally to update the ui
		 */
		doPause : function() {
			if (this.uploadPage != 0) {
				this.pausedPage = this.uploadPage;
				this.isUploading = false;
				this.isPaused = true;
				this.stopUploadPage('pause');
			}
		},
		/*
		 * it is fired by retryUpload method to update the error items status in
		 * UI
		 */
		doRetry : function(rows) {
			var Y = SNAPPI.Y;
			if (rows && rows.length) {
				for ( var i = 0; i < rows.length; i++) {
					var row = rows[i];
					var progress = this.getProgress(row['photo_id']);
					if (!progress) continue;
					progress.reset();
					progress.setStatus('pending...');
				}

			}
		},
		
		
		/*****************************************************************************
		 * progress tracking
		 */
		/**
		 * find or create FileProgress object for UploadManager 
		 * @params cfg mixed - uuid for lookup ONLY, or {photo_id, label, id, page}
		 * @return object SNAPPI.AIR.FileProgress, or false if not found 
		 */
		getProgress : function(cfg) {
			var photo_id = cfg && cfg.photo_id ? cfg.photo_id : cfg;
			var progress = false;
			try {
				// TODO: use CSS class instead of id
				var node = this.container.one('#progress-' + photo_id);
				if (node) {
					progress = node.FileProgress;
				} else {
					if (util.isObject(cfg)) {
						// create new progress Node ONLY IF cfg.page provided
						cfg.uuid = cfg.photo_id;
						cfg.label = cfg.label || cfg.rel_path;
						cfg.row_deprecate = cfg.row_deprecate;
						progress = new SNAPPI.AIR.FileProgress( cfg, cfg.page );
					} 
				}
			} catch (e) {
				LOG("Exception: getProgress() for id="+photo_id);				
			}
			return progress;
		},		
		/*
		 * percent complete for the current batch
		 */
		view_setUploadTotalProgress : function(offset) {
			// TODO: get total progress value in 1 SQL stmt using GROUP BY
			var done = this.flexUploadAPI.getCountByStatus('pending',this.batchId, '!=');
			/*
			 * NOTE: importPhotos can change the total/remaining count. 
			 * update in the correct place
			 */
			this.count_totalItems = this.count_totalItems || this.flexUploadAPI.getCountByStatus('all',this.batchId, '=');
			this.count_totalPages = Math.ceil(this.count_totalItems / this.perpage);
		
			var percent = this.count_totalItems ? Math.ceil((done / this.count_totalItems) * 100)
					: 100;
			var color = (percent > 50) ? 'white' : 'black';
			try {
				this.container.one('#total-progress .bar').setStyle('width',
						percent + '%');
				this.container.one('#total-progress .span').set('innerHTML',
						percent + '%').set('color', color);
			} catch (e) {
			}
		},
		
		
		
		/*****************************************************************************
		 * listeners
		 */
		/*
		 * click handler of paginator when click on a page link it called
		 * showPage to show the page
		 */
		gotoPage : function(evt) {
			var target = evt.target;
			if (target && target.nodeName.toLowerCase() == 'a') {
				var page = target.id.replace('page-link-', '');
				this.view_showPage(page);
			}
		},
		/*
		 * configure drop Listener
		 */
    	startDropListener : function() {
    		// BUG: cannot get snappi:hover event when dragging from OS
    		try {
    			_flexAPI_UI.nativeDDAllowed(true);
    		} catch (e) {}
    	},
    	stopDropListener : function() {
   			/*
			 * disable DD listener
			 */
    		try{
    			_flexAPI_UI.nativeDDAllowed(false);
    		} catch (e) {}
		}, 	
		listen_hover : function() {
        	this.container.on('snappi:hover', this.startDropListener, this.stopDropListener, this);			
		},
		// IMG.onload doesn't seem to fire
		onImgLoadListener : function() {
			this.container.delegate('load', function(e) {
				// click on cancel
				var target = e.target;
				e.halt();
				target.removeClass('hidden');
			}, 'img.thumbnail', this);
		},
		listen_cancel : function() {
			/*
			 * initialize delegated event handlers
			 */
			this.container.delegate('click', function(e) {
				// click on cancel
				var target = e.target.ancestor('li.progress-container');
				e.halt();
				if (target) {
					try {
						this.action_cancel(target);
					} catch (ex) {
					}
					return false;
				}
			}, 'div.cancel', this);
		},
		
		onDrop : function(droppedFolder){
	        var cb = {
	            success: function(e, params){
	                LOG("Success importPhotos from onDrop, baseurl=" + params.baseurl);
	            },
	            failure: function(e, params){
	                LOG("Failure importPhotos ", e);
	            },
	            scope: this,
	            arguments: {
	                baseurl: droppedFolder
	            }
	        };
	        LOG('>>> importing folder, path=' + droppedFolder);
	        setTimeout(function(){
	        	_flexAPI_UI.importPhotos(droppedFolder, cb);
	        }, 50);
	        // Y.fire('snappi-air:begin-import');
	    },
	    onImportComplete : function(baseurl) {
	    	baseurl = baseurl || '';
            // ADD TO uploadQueue
	    	LOG("UPLOADER.onImportComplete");
            SNAPPI.AIR.Helpers.addToUploader(this, baseurl);
	    },
		cakeStylePaginate : function(parent, total, current) {
			var Y = SNAPPI.Y;
			LOG(">>>>>>>>> showing page " + current + " of " + total);
			/*
			 * <div class="paging-control paging-numbers"> <span class="disabled"> «</span>
			 * <span class="current">1</span> <span><a href="#"
			 * id="page-link-:page" onclick="return false;">:page</a></span>
			 * <span><a href="#" id="page-link-:page" onclick="return false;"
			 * class="next">» </a></span> </div>
			 */
			var node;
			var prev_markup = '<span><a href="#" id="page-link-:page" onclick="return false;" class="next"> «</a></span>';
			var next_markup = '<span><a href="#" id="page-link-:page" onclick="return false;" class="next">» </a></span>';
			var page_markup = '<span><a href="#" id="page-link-:page" onclick="return false;">:page</a></span>';
			var current_markup = '<span class="current">:page</span>';

			// var parent = Y.Node.create('<div class="paging-control
			// paging-numbers">');
			var pageWidth = parent.get('offsetWidth');
			var MARKER_WIDTH = 32;
			var showPageCount = Math.floor(pageWidth/MARKER_WIDTH - 6);
			var offset = Math.floor(showPageCount/2);
			var start, end;
			current = parseInt(current);
			if (total <= showPageCount) {
				start=1;
				end=total;
			} else if (current <= offset) {
				start = 1;
				end = 2*offset +1;
			} else if (current >= total-(offset)) {
				start = total-2*offset ;
				end = total;
			} else {
				start = current - offset;
				end = current + offset ;
			}
			for ( var n = start; n <= end; n++) {
				if (n == start) {
					if (current == 1) {
						parent.append(Y.Node
								.create('<span class="disabled"> «</span>'));
					} else {
						parent.append(Y.Node.create(prev_markup.replace(/:page/g,
								(parseInt(current) - 1))));
					}
				}
				// page numbers
				if (n == current) {
					parent.append(Y.Node
							.create(current_markup.replace(/:page/g, n)));
				} else
					parent.append(Y.Node.create(page_markup.replace(/:page/g, n)));
				// next
				if (n == end) {
					if (current == total) {
						parent.append(Y.Node
								.create('<span class="disabled">» </span>'));
					} else {
						parent.append(Y.Node.create(next_markup.replace(/:page/g,
								(parseInt(current) + 1))));
					}
				}
			}
			return parent;
		},		
		
		testScript : function() {

			/*******************************************************************
			 * test code only
			 ******************************************************************/
			var lastsync = this.ds.getLastSyncTime();
			LOG("last sync=" + lastsync);

			/*
			 * test POST params to "http://gallery:88/snappi/debugPost";
			 */
			var callback = {
				success : function(e, params) {
					if (e == 'success') {
						console
								.log(">>>>>>>>>>> FROM CALLBACK, setSyncAndSetDataUrl: SUCCESS msg="
										+ e);
						return;
					} else if (e instanceof String) {
						LOG(">>>>>>>>>>>  response msg=" + e);
					} else {
						for ( var i in e) {
							LOG(e[i]);
							console
									.log(">>>>>>>>>>> FROM CALLBACK syncFromServer: check if updatePhotoProperties called automatically");
							// LOG(this.ds.updatePhotoProperties(e[i]));
						}
					}

				},
				failure : function(e) {
					LOG(e);
				}
			};
			// test setUploadHostFromServer
			var testPostUrl = "http://gallery:88/snappi/debugPost";
			LOG(">>>>>>>>>>> test setUploadHostFromServer");
			this.ds.setUploadHostFromServer(testPostUrl, callback);

			// test setSyncFromServerUrl
			LOG(">>>>>>>>>>> test syncFromServer");
			this.ds.setSyncFromServerUrl(testPostUrl);
			var url = this.ds.getSyncFromServerUrl();
			LOG(">>> posting syncFromServer to url=" + url);
			// test syncFromServer, should update 3 ratings
			this.ds.syncFromServer('2010-05-20 09:00:00', callback);

			// test setSyncAndSetDataUrl
			this.ds.setUpdateServerUrl(testPostUrl);
			this.ds.setSyncAndSetDataUrl(testPostUrl);
			var url = this.ds.getSyncAndSetDataUrl();
			LOG(">>> posting syncAndSetData to url=" + url);
			this.ds.syncAndSetData( {
				id : '148F4E44-C22F-4EF0-AEF4-021E26097442',
				tags : 'syncAndSetData',
				rating : 5
			}, callback);
			// /*
			// * reset urls to test location
			// */
			// var baseurl = 'http://' + SNAPPI.AIR.host + '/app/air/';
			// this.ds.setUploadHostFromServer(baseurl + 'getUploadHost.php');
			// this.ds.setSyncFromServerUrl(baseurl +
			// 'syncstatus.php');
			// this.ds.setSyncAndSetDataUrl(baseurl +
			// 'set_syncstatus.php');
			// // test syncFromServer, should update 3 ratings
			// this.ds.syncFromServer('2010-05-20 09:00:00',
			// callback);
			// LOG(">>> posting syncAndSetData to url=" + url);
			// this.ds.syncAndSetData({
			// id: '148F4E44-C22F-4EF0-AEF4-021E26097442',
			// tags: 'syncAndSetData',
			// rating: 5
			// }, callback);

			// var s='error', p='1';
			// var rows = this.flexUploadAPI.getPageItems(p, s, '1277196078');
			// var rows2 = this.flexUploadAPI.getItemsByStatus(s, '1277196078');
			// var count = this.flexUploadAPI.getCountByStatus(s, '1277196078');
			// LOG(">>> CLOSE BATCH uploadQueue.getPageItems(), page=" +
			// p + ", active page=" + this.activePage + ", status=" + s + ",
			// count=" + rows.length+", count2=" + rows2.length+",
			// getCountByStatus()="+count);
			//			
			// s='done';
			// rows = this.flexUploadAPI.getPageItems(p, s, '1277196078');
			// rows2 = this.flexUploadAPI.getItemsByStatus(s, '1277196078');
			// count = this.flexUploadAPI.getCountByStatus(s, '1277196078');
			// LOG(">>> CLOSE BATCH uploadQueue.getPageItems(), page=" +
			// p + ", active page=" + this.activePage + ", status=" + s + ",
			// count=" + rows.length+", count2=" + rows2.length+",
			// getCountByStatus()="+count);
			//			
			//			
			// s='cancelled';
			// rows = this.flexUploadAPI.getPageItems(p, s, '1277196078');
			// rows2 = this.flexUploadAPI.getItemsByStatus(s, '1277196078');
			// count = this.flexUploadAPI.getCountByStatus(s, '1277196078');
			// LOG(">>> CLOSE BATCH uploadQueue.getPageItems(), page=" +
			// p + ", active page=" + this.activePage + ", status=" + s + ",
			// count=" + rows.length+", count2=" + rows2.length+",
			// getCountByStatus()="+count);
			//			
			// s='all';
			// rows = this.flexUploadAPI.getPageItems(p, s, '1277196078');
			// rows2 = this.flexUploadAPI.getItemsByStatus(s, '1277196078');
			// count = this.flexUploadAPI.getCountByStatus(s, '1277196078');
			// LOG(">>> CLOSE BATCH uploadQueue.getPageItems(), page=" +
			// p + ", active page=" + this.activePage + ", status=" + s + ",
			// count=" + rows.length+", count2=" + rows2.length+",
			// getCountByStatus()="+count);

			/*
			 * reset urls of setUpdateServerUrl() to test location
			 */
			var baseurl = 'http://' + SNAPPI.AIR.host + '/app/air/';
			this.ds
					.setUpdateServerUrl(baseurl + 'set_syncstatus.php');
			/*
			 * test startSyncQueue, use updatePhotoProperties to mark items isStale=1 then
			 * startSyncQueue to sync
			 */
			var stale = this.ds.getStaleData();
			LOG("stale, count=" + stale.length);
			LOG(stale[0]);
			var ret = this.ds.updatePhotoProperties( {
				id : '2BEA6CF1-C71C-4740-8231-67DAEA4A7A27',
				tags : 'testing updatePhotoProperties(), again',
				rating : 5,
				isStale : 1
			});
			LOG("updatePhotoProperties for startSyncQueue test, ret=" + ret);
			var ret = this.ds.updatePhotoProperties( {
				id : '144C0335-876C-49FD-889C-88152BB757B7',
				tags : 'setting isStale=1',
				rating : 5,
				isStale : 1
			});
			LOG("updatePhotoProperties for startSyncQueue test, ret=" + ret);
			var ret = this.ds.updatePhotoProperties( {
				id : '3FD418DF-C1C6-49F4-AB97-ED9DD4DF6AC4',
				tags : 'setting isStale=true',
				rating : 5,
				isStale : true
			});
			LOG("updatePhotoProperties for startSyncQueue test, ret=" + ret);
			var ret = this.ds.updatePhotoProperties( {
				id : '8749DF4A-930E-4040-A9D1-92535ACEDDD5',
				tags : 'setting isStale="true"',
				rating : 5,
				isStale : 'true'
			});
			LOG("updatePhotoProperties for startSyncQueue test, ret=" + ret);
			var stale = this.ds.getStaleData();
			LOG("stale, count=" + stale.length);

			// test startSyncQueue, sync all stale rows
			var cfg = this.ds.getConfigs();
			var staleData = this.ds.getStaleData();
			LOG("calling startSyncQueue() with cfg.posturl="
					+ cfg.posturl + ", count=" + staleData.length);
			var syncCb = {
				success : function(e, args) {
					LOG(args);
					LOG("startSyncQueue success, count="
							+ args.staleData.length);

				},
				failure : function(e, args) {
					LOG(e);
					LOG(args);
					LOG("startSyncQueue failed, count="
							+ args.staleData.length);
				},
				arguments : {
					posturl : cfg.posturl,
					staleData : staleData
				}
			};
			this.ds.startSyncQueue(true, syncCb);

			/*
			 * reset urls to test location
			 */
			// var baseurl = 'http://' + SNAPPI.AIR.host + '/app/air/';
			// this.ds.setUploadHostFromServer(baseurl + 'getUploadHost.php');
			// this.ds.setSyncFromServerUrl(baseurl +
			// 'syncstatus.php');
			// this.ds.setUpdateServerUrl(baseurl +
			// 'set_syncstatus.php');
			// this.ds.setSyncAndSetDataUrl(baseurl +
			// 'set_syncstatus.php');
			// // test syncFromServer, should update 3 ratings
			// this.ds.syncFromServer('2010-05-20 09:00:00',
			// callback);
			// LOG(">>> posting syncAndSetData to url=" + url);
			// // shoudl update on server, then local db
			// this.ds.syncAndSetData({
			// id: '148F4E44-C22F-4EF0-AEF4-021E26097442',
			// tags: 'syncAndSetData',
			// rating: 5
			// }, callback);
			/*
			 * test delete photo
			 */
			// var items = this.ds.response.parsedResponse.results;
			// LOG(">>> test deleting photo, count="+items.length);
			// var photoids=[];
			// for (var i in items) {
			// photoids.push(items[i].id);
			// }
			// var uuid = photoids.pop();
			// LOG("deleting photo, ids="+uuid);
			// this.ds.deletePhoto([uuid]);
			// LOG(">>> test deleting deleteBaseurl");
			// var baseurl = this.ds.getConfigs().baseurl;
			// var ret = _flexAPI_Datasource.deleteBaseurl([baseurl]);
			// LOG("deleting photo, baseurl="+baseurl+", ret="+ret);
			/*
			 * test login info
			 */
			// this.ds.saveLoginInfo('abc', '12345');
			// var login = this.ds.getLoginInfo();
			// LOG(login);
			/*
			 * test repository stats
			 */
			var scanResults = this.ds.getImportProgress();
			LOG(scanResults);

			/*******************************************************************
			 * END test code only
			 ******************************************************************/

		},
		

		
		
		
		__endPrototype: null
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	

	SNAPPI.AIR.UploadQueue = UploadQueue;
	
	/*
	 * static methods
	 */
	
	
	/************************************************************************
	 * Add folder status
	 */
	UploadQueue.view_setImportTotalProgress = function() {
		var progress = _flexAPI_UI.getImportProgress();
		var percent = Math.round(progress.scanned/progress.total*100);
		var node = SNAPPI.Y.one('#import-progress');
		
		var color = (percent > 50) ? 'white' : 'black';
		try {
			node.one('.bar').setStyle('width',	percent + '%');
			node.one('.span').set('innerHTML', percent + '%').set('color', color);
		} catch (e) {
		}
	}
	UploadQueue.showImportProgress = function() {
		var node = SNAPPI.Y.one('#import');
		this.view_setImportTotalProgress();
		node.removeClass('hide');
	}
	
	/***********************************************************************
	 * show DropTarget
	 */
	UploadQueue.showDropTarget = function(show) {
		if (show || typeof show == 'undefined') SNAPPI.Y.one('#drop-target').addClass("over");
		else SNAPPI.Y.one('#drop-target').removeClass("over");
	}
	
	UploadQueue.getDropTargetId = function() {
		return "#drop-target";
	}

	
//	firebugLog(SNAPPI);
//	LOG("load complete: upload_ui.js");	
}());



(function(){
/**********************************************************************************
 * helper methods
 */	
	    
	var util = SNAPPI.coreutil;
    /*
     * called inernally when drop a folder
     * called with one param as string i.e. folder's nativepath
     * no return value
     * */
    SNAPPI.AIR.onDrop = function(droppedFolder, uploader){
    	LOG(SNAPPI.AIR.uploadQueue);
    	uploader = uploader ||  SNAPPI.AIR.uploadQueue;
    	uploader.onDrop.call(this, droppedFolder);
    };
    
    
    
    
    
    
    /*
     * Upload Queue data api - provides access to table=uploadQueues
     * singleton object,
     * 		wrapper for all UploadQueue API calls.
     * 		these methods operate on the AIR.uploadQueues DB table
     */
    var Flex_UploadAPI = function (){
    	if (typeof flexAPI_UI == 'undefined') {
    		_flexAPI_UI = _JS_UI;
    		LOG(">>>>>>>>>>>>>>>>>>>>>>>>> using FAKE JAVASCRIPT DATASOURCE FOR JS TESTING ");
    	} else {
    		_flexAPI_UI = flexAPI_UI;
    		htmlctrl = flexAPI_UI;
    		LOG(">>>>>>>>>>>>>>>>>>>>>>>>> JAVASCRIPT FLEX BRIDGE");
    		LOG(_flexAPI_UI);
    	}
    };
    Flex_UploadAPI.prototype = {
        /**
         * To add photos to upload queue
         * It accept two params
         * 		1. uuid or audition (object) Array as an input and see the current
         * 		2. batch_id optional default is active batch
         * upload session if any active session found then append photos to it otherwise
         * start new upload session and initialize new batch_id to it
         * and then add photos to that upload session.
         * @params auditions array = array of photo_ids e.g. photos = ['photo_id','photo_id']
         * @params batchId string - optional DISABLED. use batch from set/getBatchId()
         * @return - count of photos added
         * */
        addToUploadQueue: function(auditions, batch_id){
    		auditions = auditions || [];
    		batch_id = batch_id || this.getBatchIdForUpload();
//LOG("datasource.addToUploadQueue, count="+auditions.length+ ", batchId="+batch_id);	    		
            return _flexAPI_UI.addToUploadQueue(auditions, batch_id);
        },
        /**
         * remove photos from upload queue
         * @params photos array - as any array of photo_ids to remove e.g. photos = ['photo_id','photo_id']
         * @return - number of photos removed from queue
         * */
        removeFromUploadQueue: function(photos){
            return _flexAPI_UI.removeFromUploadQueue(photos);
        },
        /**
         * clear all record from upload queue based on status
         * @params status string [pending|error|cancelled|done|all]. default status is 'all'
         * @params batchId string - optional DISABLED. use batch from set/getBatchId()
         * @return - bool - true/false
         * */
        clear: function(status, batch_id){
            status = status || 'all';
            return _flexAPI_UI.clear(status, batch_id);
        },
        /**
         * used to startQueue
         * params - no params
         * @return - bool true/false
         * */
        startQueue: function(){
            return _flexAPI_UI.startQueue();
        },
        /**
         * pause currently started queue
         * params - no params
         * @return - bool true/false
         * */
        pauseQueue: function(){
            return _flexAPI_UI.pauseQueue();
        },
        /**
         * to get photos from current active upload batch
         * based on status e.g. pending/error/done/all. default status is 'all'
         * @params status string [pending|error|cancelled|done|all]. default status is 'all'
         * @return - array of json of photos e.g. [
         * 										  {
         * 											photo_id : 'photo_id',
         * 											batch_id : 'batch_id',
         * 											status : 'pending/error/done',
         * 											}
         * 										 ]
         *
         * */
        getCurrentUploadStatus: function(status){
            status = status || 'all';
            return _flexAPI_UI.getCurrentUploadStatus(status);
        },
        /**
         * sends error(not uploaded due to some reason) while uploading set back to running queue
         * @return - bool return/false
         * */
        retryUpload: function(){
            return _flexAPI_UI.retryUpload();
        },
        
        /**
         * set upload host server for current upload queue
         * @params host string - a host e.g. http://localhost:8080/test/upload.php
         * @return - no return value
         * */
        setUploadFilePOSTurl: function(host){
        	// same as this.setConfig({uploadHost: host});
        	try {
	            _flexAPI_UI.setUploadFilePOSTurl(host);
        	} catch(e) {
        		LOG("Exception: api.setUploadFilePOSTurl ");
        	}
        },
        /**
         * get upload host server of currently set upload queue
         * @return - return string as a host e.g. http://localhost:8080/test/upload.php or empty string if not set
         * */
        getUploadHostOfQueue: function(){
            return _flexAPI_UI.getUploadHostOfQueue();
        },
        /**
         * set upload status e.g. error/cancelled/done
         * @params uuid string - uuid/photo_id as string
         * @params status string [pending|error|cancelled|done|all]. default status is 'all'
         * @params batchId string - optional DISABLED. use batch from set/getBatchId()
         * @return - boolean if status updated then return true otherwise false
         * */
        setUploadStatus: function(uuid, status, batch_id){
            batch_id = batch_id || '';
            return _flexAPI_UI.datasource.setUploadStatus(uuid, status, batch_id);
        },
        /**
         * get all page items according to page and status and batch_id
         * @params page number - starts with 1
         * @params status string [pending|error|cancelled|done|all]. default status is 'all'
         * @params batchId string - optional DISABLED. use batch from set/getBatchId()
         * @return - json array of items e.g. [
         * 									{
         * 										id : '',
         * 										photo_id : '',
         * 										batch_id : '',
         * 										status : '',
         * 										rel_path : '',
         * 										rating : '',
         * 										tags : ''
         * 									},.......]
         * */
        getPageItems: function(page, status, batch_id){
            status = status || 'all';
            batch_id = batch_id || '';
            try {
            	var rows = _flexAPI_UI.getPageItems(page, status, batch_id);
            } catch (e) {
            	LOG("js test: getPageItems()");
            	rows = SNAPPI.AIR.Helpers.testDs.getPageItems;
            }
            return rows;
        },
        /**
         * get items by status
         * @params status string [pending|error|cancelled|done|all]. default status is 'all'
         * @params batchId string - optional DISABLED. use batch from set/getBatchId()
         * @params operator string - e.g. '=','!=' default is '='
         * @return - json array of items e.g. [
         * 									{
         * 										id : '',
         * 										photo_id : '',
         * 										batch_id : '',
         * 										status : '',
         * 										rel_path : '',
         * 										rating : '',
         * 										tags : ''
         * 									},.......]
         * */
        getItemsByStatus: function(status, batch_id, op){
            status = status || 'all';
            batch_id = batch_id || '';
            op = op || '=';
            rows =  _flexAPI_UI.getItemsByStatus(status, batch_id, op);
            return rows;
        },  
        /**
         * get total items count by status
         * @params status string [pending|error|cancelled|done|all]. default status is 'all'
         * @params batchId string - optional DISABLED. use batch from set/getBatchId()
         * @params operator string - e.g. '=','!=' default is '='
         * @return - return number as total count
         * */
        getCountByStatus: function(status, batch_id, op){
            status = status || 'all';
            batch_id = batch_id || '';
            op = op || '=';
            var count = _flexAPI_UI.getCountByStatus(status, batch_id, op);
            return count;
        },
        
        /**
         * set batch_id for upload queue
         * @params batch_id string - string batch_id e.g. ABC99EUI09DSKJKS
         * */
        setBatchId: function(batch_id){
        	// TODO: wrapper for _flexAPI_UI.saveConfigs('batch_id',batch_id);
            _flexAPI_UI.setBatchId(batch_id);
        },
        /**
         * get batchId saved to _flexAPI_UI.configs, NOT FROM DB.
         * 	- this is the current batchId for the uploader. can be null
         * @return string as batchId
         */
        getBatchId: function() {
        	return _flexAPI_UI.datasource.cfg.batch_id;
        },
        /**
         * get all batchIds from DB, order by ASC
         * 		batch_ids should be unixtime values, so batch_ids returned in chronological order
         * @return - object {
         * 		open : ['batch_id','batch_id','batch_id',...],
         * 		closed : ['batch_id','batch_id','batch_id',...]
         * 	}
         */
        getBatchIdsFromDB: function(){
            return _flexAPI_UI.datasource.getBatchIdsFromDB();
        },
        /**
         * get the first/oldest batchId from rows in uploadQueues DB table, assumes batchId is a unixtime
         * @return string batchId - typically a unixtime
         */
        getOpenBatchId : function(){
        	var batches = this.getBatchIdsFromDB();
			if (batches.open && util.isArray(batches.open) && batches.open.length) {
				return batches.open.shift();
			} else
				return false;       	
        },        
        /**
         * get most recent batchId from rows in uploadQueues DB table, assumes batchId is a unixtime
         * @return string batchId - typically a unixtime
         */
        getLastOpenBatchId : function(){
        	var batches = this.getBatchIdsFromDB();
			if (batches.open && util.isArray(batches.open) && batches.open.length) {
				return batches.open.pop();
			} else
				return false;       	
        },
        /**
         * returns a valid batchId. 
         * 		if lastOpenBatchId < MAX_BATCH_AGE old, then reuse/return lastOpenBatchId
         * @return string batchId - typically a unixtime
         */
        getBatchIdForUpload: function(){
        	var MAX_BATCH_AGE	 = 3600;		// 1 HOUR
        	var batchId = this.getLastOpenBatchId();
        	var now = Math.round(new Date().getTime() / 1000);
        	if (batchId===false || (now - batchId) > MAX_BATCH_AGE) {
        		batchId = now;
        	}
        	return batchId;
        },
        /**
         * set items per page for a upload queue
         * @params n number - e.g. uploadQueuesPerpage = 10,20,35...
         * return - no return value
         * */
        setPerpage: function(n){
        	LOG(_flexAPI_UI);
        	LOG(flexAPI_UI.setUploadQueuePerpage);
//        	_flexAPI_UI.datasource.saveConfig('uploadQueuesPerpage',n + '');
        	_flexAPI_UI.setUploadQueuePerpage(n);
        },
        /**
         * get items per page for a upload queue
         * @return - as a number e.g. uploadQueuesPerpage = 10,20,35...
         * */
        getPerpage: function(){
//        	_flexAPI_UI.datasource.cfg.uploadQueuesPerpage;
            return _flexAPI_UI.getUploadQueuePerpage();
        },
        /*
         * TODO: relocate. this is NOT a datasource method
         * start/stop native drag & drop functionality
         * params - accept one param
         * 		1. allowed as boolean if true then start else stop drag & drop
         * */
        nativeDDAllowed : function(allowed){
        	_flexAPI_UI.nativeDDAllowed(allowed);
            return allowed == true;
        }      
        

    }
    /*
     * static methods
     */
    Flex_UploadAPI.selectFolder = function(){
    	_flexAPI_UI.selectRootFolder();
    }
    SNAPPI.AIR.Flex_UploadAPI = Flex_UploadAPI;
    
    
    LOG("load complete: api_bridge.js : Flex_UploadAPI");	
}());
