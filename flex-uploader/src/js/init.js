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
	var _Y = null;
    SNAPPI.namespace('SNAPPI.onYready');
    SNAPPI.onYready.Init = function(Y){
    	if (_Y === null) _Y = Y;
    	SNAPPI.AIR.AIR_init = AIR_init;
    }
    var AIR_init = function(){
		var node = SNAPPI.AIR.Helpers.init_GalleryLoadingMask();
		LOG(">>>>>>>>>>>>>>>>>>>>>>>>  AIR_init <<<<<<<<<<<<<");
		
   		/*
   		 *  flex_onYuiDomReady: js global defined in snaphappi.mxml,
   		 */
		flex_onYuiDomReady();
		
		
		
		
		LOG(">>>>>>>>>>>>>>>>>>>>>>>>  YUI/domready BEGIN <<<<<<<<<<<<<");
		var Helpers = SNAPPI.AIR.Helpers; 
		Helpers.pageOnLoadComplete();		// show body
	
	    var datasource, uploader; 
	    
	    /*
	     * configure Datasource for uploader
	     */
		try {
			if (!flexAPI_Datasource) throw new Exception();
			datasource = new SNAPPI.AIR.CastingCallDataSource();
		} catch (e) {
			LOG(">>>>>>>>>>>>>>>>  USING JAVASCRIPT DATASOURCE  >>>>>>>>>>>>>>>>>>>");
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
	//	Helpers.addToUploader(uploader, '');
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
		
	}
    
LOG("load complete: init.js");
})();