/**
 * 
 * Copyright (c) 2009-2011, Snaphappi.com. All rights reserved.
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
 * 
 */
(function() {
console.log("load BEGIN: helpers.js");	

	var _Y = null;
    SNAPPI.namespace('SNAPPI.onYready');
    SNAPPI.onYready.Helpers = function(Y){
		if (_Y === null) _Y = Y;
		SNAPPI.AIR.Helpers = Helpers;
	}	
	/***************************************************************************
	 * Helpers Static Class
	 * 	Helpers.= Helpers;
	 */
	var Helpers = function() {}
	
	SNAPPI.namespace('SNAPPI.STATE.displayPage');
	
	Helpers.go = function(){
		var node = Helpers.init_GalleryLoadingMask();
		LOG(">>>>>>>>>>>>>>>>>>>>>>>>  AIR_init <<<<<<<<<<<<<");
		
   		/*
   		 *  flex_onYuiDomReady: js global defined in snaphappi.mxml,
   		 */
		flex_onYuiDomReady();
		
		
		
		
		LOG(">>>>>>>>>>>>>>>>>>>>>>>>  YUI/domready BEGIN <<<<<<<<<<<<<");
		Helpers.pageOnLoadComplete();		// show body
	
	    var datasource, uploader; 
	    
	    /*
	     * configure Datasource for uploader
	     */
		try {
			if (!flexAPI_Datasource) throw new Exception();
			datasource = new SNAPPI.AIR.CastingCallDataSource();
		} catch (e) {
			LOG(">>>>>>>>>>>>>>>>  WARNING: USING JAVASCRIPT DATASOURCE  >>>>>>>>>>>>>>>>>>>");
			datasource = _JS_DATASOURCE;
		}   
		SNAPPI.DATASOURCE = datasource;
		
		uploader = new SNAPPI.AIR.UploadQueue({
	    	container: _Y.one('#gallery-container'),
	    	datasource: datasource
	    });
	    SNAPPI.AIR.uploadQueue = uploader;
	    
	    /*
	     * globals
	     * 
	     SNAPPI.AIR.uploadQueue = SNAPPI.AIR.UploadQueue.instance
	     SNAPPI.AIR.uploadQueue.ds = SNAPPI.DATASOURCE
	     NOTE: SNAPPI.AIR.uploadQueue.flexUploadAPI bypasses SNAPPI.DATASOURCE, 
	     	access global _flexAPI_UI directly Flex UploaderUI.as
	     * 
	     */
	    
	    
	    
	    /*
	     * DEV/Testing config
	     */
		if (0 && 'test') {
			Helpers.DEV_runTestSuite();
		}
		
		// magic login for AIR Test user
		Helpers.DEV_addProviderKeyAsTestUser(datasource.getConfigs().provider_key);
		// add all photos to uploadQueue
	//	SNAPPI.AIR.UIHelper.actions.addToUploader(uploader, '');
		// var host = SNAPPI.AIR.host=='dev2.snaphappi.com' ? 'remote' : 'local' ;
		Helpers.DEV_setRuntimeHost(uploader);		// local or remote
		
		// add login menu
		SNAPPI.MenuAUI.initMenus({
			'menu-sign-in-markup':1,
			'menu-uploader-batch-markup':1,
			'menu-select-all-markup':1,
			// 'contextmenu-photoroll-markup':1,	// init from UIHelper.toggle_ContextMenu()
		});
		// use batchid==null on startup
		Helpers.initUploadGallery(uploader, 1, null, null);
		// start listeners, as necessary
		var listeners = {
			'WindowOptionClick':null, 
			'DisplayOptionClick':null,
			'ContextMenuClick':null, 
			'MultiSelect':null,
		};
		for (var listen in listeners) {
			if (listeners[listen]!==false) SNAPPI.AIR.UIHelper.listeners[listen](listeners[listen]);
		}
		Helpers.hide_StartupLoadingMask();
		LOG(">>>>>>>>> DONE");	
	
	
		var detach = _Y.on('snappi-air:begin-import', function(){
			_Y.one('#item-body .import-progress-wrap').removeClass('hide');		
		});
		
		LOG(">>>>>>>>>>>>>>>>>>>>>>>>  YUI/domready COMPLETE <<<<<<<<<<<<<");
		
	};
	
	Helpers.DEV_setRuntimeHost = function(uploader, host) {
		if (!SNAPPI.AIR.host) {
			alert("helpers.js: SNAPPI.AIR.host is not set");
			// should already be set in flex & base.js
			// // deprecate
			// host = host || 'local';
		    // /*
		     // * set upload server
		     // */
			// switch (host) {
			// case 'remote':
				// // upload REMOTE
				// SNAPPI.AIR.host = "dev2.snaphappi.com";
		        // // firefox dev2.snaphappi.com uuid
				// break;
			// case 'local':
			// default:
				// 
				// SNAPPI.AIR.host =  _Y.one('base').getAttribute('host');
				// break;
			// }			
		}

	    var uploadHost = {
	    		local: "http://"+SNAPPI.AIR.host+"/my/desktop_upload",
	    		remote: "http://dev2.snaphappi.com/my/desktop_upload",
	    };		
		// login
		var login_Url = "http://" + SNAPPI.AIR.host + "/users/signin/.json?optional=1";
		SNAPPI.AIR.XhrHelper.login_Url = login_Url;
		LOG("login_Url > "+login_Url);
		
		// upload
		var upload_Url = "http://"+SNAPPI.AIR.host+"/my/desktop_upload";
		LOG("upload_Url > "+upload_Url);
		uploader.flexUploadAPI.setUploadFilePOSTurl(upload_Url);
		try {
			LOG(">>> UPLOAD HOST="+flexAPI_Datasource.cfg.uploadHost);
			LOG(">>> UPLOAD HOST="+flexAPI_UI.datasource.cfg.uploadHost);
		} catch (e) {}
	}
	
	// deprecate: use UIHelper.isAuthorized()
	Helpers.isAuthorized = function() {
		try {
			var userid = SNAPPI.STATE.user.id;
			return userid;
		} catch (e) {
		}
		return false;
	}	
	
	Helpers.pageOnLoadComplete = function() {
		_Y.one('body').setAttribute('style','');
	}
	
	/*
	 * add provider_key to magic login for testing, DEV only
	 */
	// TODO: providerKey !== provider_account_key/id, right???
	Helpers.DEV_addProviderKeyAsTestUser = function(uuid) {
		var option = _Y.one('form#UserLoginForm select > option.hr');
		option.insert("<option value='" + uuid
				+ "'>AIR upload test user</option>", 'after');
	}
	
	Helpers.DEV_runTestSuite = function(){
		
	    try {	
			LOG("TEST: get new SNAPPI.CastingCallDataSource()");
			var datasource = new SNAPPI.AIR.CastingCallDataSource();
			var test = SNAPPI.AIR.Test;
			LOG("start Test suite");
			test.init(datasource, dsCfg);
			test.go();
			
	        
	        /*
	         * test hover
	         */
	       	var startDrop = function() {
	    		LOG("hover start");
	    	};
	    	var stopDrop = function() {
	    		LOG("hover stop");
			}; 
	    	_Y.one('#upload').on('hover', startDrop, stopDrop, this);	
	  	} 
	    catch (e) {
	        LOG("SNAPPI.AIR.Test not available");
	    } 		
	}
	
	/**
	 * Automatically adds imported photos to upload queue
	 *	@params uploader 
	 *	@params folderpath string OPTIONAL 
	 */
	Helpers.DEV_importFolder = function(uploader, folderpath){
		var datasource = uploader.ds;
		folderpath = folderpath || 'C:\\USERS\\michael\\Pictures\\importTest';
	    /*******************************
	     * 
	     *  import photos
	     */
		var importPhoto_callback = {
	        success: function(o, baseurl){
	            LOG("******************   AFTER IMPORT PHOTOS **************************");
	            LOG("baseurl="+baseurl);
	            datasource.setConfig({
	                baseurl: baseurl
	            });
//	            var count = SNAPPI.AIR.UIHelper.actions.addToUploader(uploader, baseurl);
	        }, 
			failure: function(o) {
				alert('addPhotos failure');
				LOG(o);
			},
			progress: function(){
				var progress = datasource.getImportProgress();
				LOG(progress);
//	    									if (progress.scanned > 5) datasource.cancelImport();
			}
	    }
		datasource.importPhotos(folderpath, importPhoto_callback);		            
	}
	/**
	 * deprecated. moved to  UIHelper.actions.addToUploader() 
	 * add imported photos (by baseurl) to uploadQueue, creates a new batchid
	 *	@params uploader OPTIONAL, uses global SNAPPI.AIR.UploadQueue
	 *	@params baseurl string OPTIONAL, add all baseurls if null 
	 *	@return int - count of photos added
	 */	
	Helpers.addToUploader = function(uploader, baseurl){
//  baseurl = baseurl || 'C:\\USERS\\michael\\Pictures\\importTest';
		uploader = uploader || SNAPPI.AIR.uploadQueue;
        var query = {	
        		page: 1,
                perpage: 1999,
                baseurl: baseurl  
            };
        var added = 0;
        LOG("Helpers.addToUploader  ****************************************************");
        var datasource = uploader.ds;
        datasource.getAuditions_all(query, function (auditions) {
        	LOG("datasource.getAuditions_all");
            //before adding photos to upload queue set batch_id first
            added = uploader.flexUploadAPI.addToUploadQueue(auditions);
            var batchId = uploader.flexUploadAPI.getLastOpenBatchId();
            LOG("added to uploadQueue, count="+added+", open batchId="+batchId);			                
            uploader.show("reload");
        }, datasource, false);
        // open added folder
		var delayed = new _Y.DelayedTask( function() {
			var uploader = SNAPPI.AIR.uploadQueue;
			uploader.initQueue('pending', {
				folder: baseurl, 				// folder='all' => baseurl=''
				page: 1,
			});
			uploader.view_showPage();
			delete delayed;		
			_Y.one('body').removeClass('wait');
		});
		delayed.delay(100);  // wait 100 ms	        
        return added;
    }
    Helpers.show_login = function() {
    	
    	// SNAPPI.Helper.Dialog.showLogin();
    	if (SNAPPI.AIR.debug && _Y.one('#login select.postData')) {
    		_Y.one('#login select.postData').removeClass('hide');
    	}
    }
    /*
     * initialize Uploader for the first time
     * @params page int, page number, defaults to SNAPPI.STATE.displayPage.perpage
     * @params uploadQueue SNAPPI.AIR.UploadQueue, same as SNAPPI.AIR.uploadQueue
     */
	Helpers.initUploadGallery = function(uploadQueue, page, perpage, batchId, folder, filter) {
LOG("Helpers.initUploadGallery, BATCHID="+batchId+", folder="+folder+", page="+page);		
		uploadQueue = uploadQueue || SNAPPI.AIR.uploadQueue;
		page = page || 1;
		perpage = perpage || SNAPPI.STATE.displayPage.perpage || 24;
		SNAPPI.STATE.displayPage.page = page;
		SNAPPI.STATE.displayPage.perpage = perpage;
		
		// init/show upload queue
		
		
		// check .filter for current focus
		if (filter) {
			// set filter focus
			var filterBtns = uploadQueue.container.all('.gallery-display-options .settings .filter li.btn');
			filterBtns.each(function(n,i,l){
				var action = n.getAttribute('action').split(':').pop();
				if (action == filter) n.addClass('focus'); 
				else n.removeClass('focus');
			});
		}
		var hasFocus = uploadQueue.container.one('.gallery-display-options .settings .filter li.btn.focus');
		if (hasFocus) {
			filter = hasFocus.getAttribute('action').split(':').pop();
		} else filter = 'pending';	// Ready for upload
		uploadQueue.initQueue(filter, {
			batchId: batchId,
			folder: folder, 				// folder='all' => baseurl=''
			perpage: perpage,
			page: page,
		});
		// show initial page using Paginator
		
		var node = _Y.one('#gallery-container .gallery.photo');
		// init gallery listeners
		Helpers.init_GalleryLoadingMask(node);
		var p = SNAPPI.Paginator.find['PhotoAirUpload'];
		if (!p) {
			var paginateTarget = _Y.one('#gallery-container .gallery.photo .container');
			paginateTarget.UploadQueue = uploadQueue;
			p = SNAPPI.Paginator.paginate_PhotoAirUpload(paginateTarget, page, SNAPPI.STATE.displayPage.perpage, uploadQueue.count_totalItems);
		}
		SNAPPI.Paginator._getPageFromAirDs(p.container, page);
		// other init steps
		// Helpers.set_Filter_FolderSelect();
// c = paginateTarget;
// LOG("CHECK MULTISELECT LISTEN")
// LOG(c);
	}
	
	/*
	 * use HTML5 startup loading mask to show before JS is ready
	 */
	Helpers.hide_StartupLoadingMask = function(){
		
		var mask = _Y.one('#startup-loading-mask');
		if (mask) {
			mask.get('parentNode').append(mask.one('*'));
			mask.empty().destroy();
		}
	}
	
	
	Helpers.init_GalleryLoadingMask = function(pluginNode, target){
		// Helpers.hide_StartupLoadingMask();
		// show initial page using Paginator, doesn't work
		pluginNode = pluginNode || _Y.one('#gallery-container .gallery.photo');
		target = target || pluginNode;
		if (target.ancestor('.gallery.photo')) target = target.ancestor('.gallery.photo');
		
		if (!pluginNode.loadingmask) {				// add loadingmask ASAP
			var loadingmaskTarget = target;
			// set loadingmask to parent
			pluginNode.plug(_Y.LoadingMask, {
				pluginNode: loadingmaskTarget
			});    			
			pluginNode.loadingmask._conf.data.value['pluginNode'] = loadingmaskTarget;
			pluginNode.loadingmask.overlayMask._conf.data.value['pluginNode'] = pluginNode.loadingmask._conf.data.value['pluginNode'];
			// pluginNode.loadingmask.set('pluginNode', pluginNode);
			// pluginNode.loadingmask.overlayMask.set('pluginNode', pluginNode);
			pluginNode.loadingmask.set('zIndex', 10);
			pluginNode.loadingmask.overlayMask.set('zIndex', 10);
			pluginNode.loadingmask.show();
		} else {
			pluginNode.loadingmask.refreshMask();
			pluginNode.loadingmask.show();
		}		
		return pluginNode;
	}
	
})();


// LOG("load complete: helpers.js");	