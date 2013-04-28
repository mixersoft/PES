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
    SNAPPI.onYready.ThriftUploader = function(Y){
		if (_Y === null) _Y = Y;
		SNAPPI.ThriftUploader = ThriftUploader;
		if (SNAPPI.is_TopLevelFolder_installed != undefined) {
		 	ThriftUploader.action.bootstrapReady(SNAPPI.is_TopLevelFolder_installed);	
		}
	}	
	var ThriftUploader = function(cfg) {	}; 
	
	/*
	 * static methods/properties
	 */
	ThriftUploader.container_id = '#uploader-ui-xhr';
	
	ThriftUploader.listen = {};		// global ref to active listeners
	
	ThriftUploader.nav = {
	};
	ThriftUploader.action = {
		timer: null,
		launchTask: function(taskType) {
			_Y.one('#content > div.messages').setContent();
			switch(taskType) {
				case 'ur': 
				case 'uo':
					/*
					 * NOTE: this method directly changes ThriftSession.IsCancelled
					 */
					ThriftUploader.util.pauseUploader(false, function(json){
						ThriftUploader.ui._no_ui_update_count = 0;
						SNAPPI.ThriftUploader.action.refresh('restart');
						// LAUNCH native-uploader
						var target = _Y.Lang.sub("snaphappi://{authToken64}_{sessionId64}_", PAGE.jsonData.nativeUploader);
						var uri = target+taskType;
						// called asynch from callback
						window.location.href = uri;
					});				
					break;
				case 'sw':
					// call directly
				 	var target = _Y.Lang.sub("snaphappi://{authToken64}_{sessionId64}_", PAGE.jsonData.nativeUploader);
					var uri = target+taskType;
					window.location.href = uri;
					break;
			}
			// ERROR: this call was canceling the pause method
			// ThriftUploader.ui._no_ui_update = 0;
			// var target = _Y.Lang.sub("snaphappi://{authToken64}_{sessionId64}_", PAGE.jsonData.nativeUploader);
			// var uri = target+taskType;
			// window.location.href = uri;
		},
		refresh: function(start) {
			if (start) {
				if (ThriftUploader.timer === false && start!=='restart') return;
				_Y.one('#uploader-ui-xhr').addClass('active');
				var starttime = new Date().getTime(),
					delay = 1000*Math.pow(2,ThriftUploader.ui._no_ui_update_count); //  + ThriftUploader.ui._elapsed;
				ThriftUploader.timer = _Y.later(delay, 
					ThriftUploader.ui, 
					function(){
						ThriftUploader.util.getFolderState(
							function(json) {
								var response = json.response;
								ThriftUploader.ui._elapsed = Math.min(new Date().getTime() - starttime, 5000);
								ThriftUploader.ui.renderFolderState(response.folders);
								ThriftUploader.action.refresh(true);
								if (response.taskState['ThriftSession']['IsCancelled']=='0') {
									// refresh() will auto cancel after NO_UI_UPDATE_LIMIT
									// SNAPPI.ThriftUploader.action.refresh(true);
								}
							}
						);
					}, 
					null, false
				);
			} else {
				if (ThriftUploader.timer) ThriftUploader.timer.cancel(); // cancels UI refresh
				ThriftUploader.timer = false;
				// TODO: set ThriftSession.IsCancelled=1
				ThriftUploader.util.pauseUploader(true, function(json){
					var check;
					_Y.one('#uploader-ui-xhr').removeClass('active');
				});
			} 
		},
		show_RestartMessage: function(){
			var i, row, folderState = ThriftUploader.ui._folderState;
			for (i in folderState) {
				row = folderState[i];
				if (row['ThriftFolder']['is_scanned']=='0' && row['ThriftFolder']['is_watched']=='0') {
					var restart_markup = _Y.one('#restart-markup').getContent();
					_Y.one('#content > div.messages').setContent("<div class='message'>"+restart_markup+"</div>");
					break;
				}
			};
		},
		post_WatchFolder: function(loadingNode) {
			var	folder_id = loadingNode.ancestor('tr').get('id').substr(6);	// strip off 'fhash-'	
			var watch = loadingNode.get('checked');
			var success = function() {
				// launch sw task
        		ThriftUploader.action.launchTask("sw");
				return false;
			}
        	ThriftUploader.util.setWatchedFolder(folder_id, watch, success);
		},
		post_RemoveFolder: function(loadingNode) {
			var	folder_id = loadingNode.ancestor('tr').get('id').substr(6);	// strip off 'fhash-'	
			var row = loadingNode.ancestor('tr');
			var success = function() {
				row.remove();
				return false;
			}
        	ThriftUploader.util.removeFolder(folder_id, success);
		},	
		bootstrapReady: function(is_TopLevelFolder_installed) {
			// called by TLFBootstrapper flash app, 
			if (is_TopLevelFolder_installed) {
				ThriftUploader.action.waitForDeviceId();
			} else {
				_Y.one('#checking-config').remove();
				_Y.one('#download-wrap').removeClass('hide');
				_Y.one('#snappi-uploader-wrap').addClass('hide');
			}
			ThriftUploader.ui._is_TopLevelFolder_installed = is_TopLevelFolder_installed;
		},	
		waitForDeviceId: function(){
			// native-uploader installed, 
			var deviceId_found = function() {
				ThriftUploader.listeners.FolderRowClick();
				_Y.one('#checking-config').remove();
				_Y.one('#download-wrap').addClass('hide');
				_Y.one('#snappi-uploader-wrap').removeClass('offscreen');
				
				var label = _Y.one('#thrift-uploader-wrap #device-label').remove().getAttribute('value');
				_Y.one('#thrift-uploader-wrap li.device-label').setContent('Device: '+label);
				
				// start UI if '.empty-folders-message' found
				var firstTime = _Y.one('.thrift-folders .empty-folders-message');
				if (firstTime) ThriftUploader.action.refresh('restart');
			}
			var folders_node = _Y.one('#uploader-ui-xhr');
			if (folders_node.hasClass('device-id-found')) {
				// DeviceID is saved in Session
				deviceId_found();
				return;
			}
			// DeviceID unknown, LAUNCH uploader to get from TaskID 
			var target = _Y.Lang.sub("snaphappi://{authToken64}_{sessionId64}_ur", PAGE.jsonData.nativeUploader);
			window.location.href = target;
			
			// try to get folders until successful, implies DeviceID is available 
			folders_node.replaceClass('xhr-get-disabled', 'xhr-get');
			SNAPPI.xhrFetch.fetchXhr(folders_node);
			var i=0, timeout = 30;
			var cancel = _Y.later(1000, ThriftUploader, function(){
				if (++i > timeout && cancel.cancel) {
					cancel.cancel();
					_Y.one('#checking-config').remove();
					_Y.one('#download-wrap').addClass('hide');
					_Y.one('#snappi-uploader-wrap').removeClass('offscreen');
					return;
				} 
				if (folders_node.one('table') ) {
					if (!folders_node.one('.device-id-unknown')) {
						cancel.cancel();
						deviceId_found();
						return;
					}	
				}
				// call SNAPPI.io.fetchXhr() until success
				if (!folders_node.hasClass('xhr-loading')) {
					SNAPPI.xhrFetch.fetchXhr(folders_node);
				}
			}, null, true);
			
		},
		show_DownloadPage: function() {
			// override, show download page in case user needs to re-install
			_Y.one('#download-wrap').removeClass('hide');
		}
	}

	ThriftUploader.ui = {
		REFRESH_MS: 2000,
		NO_UI_UPDATE_LIMIT: 5,	// 2^5 = 32 seconds since last check
		_folderState: null,
		_is_TopLevelFolder_installed: null,
		_no_ui_update_count: 0,
		_elapsed: 0,
		renderFolderState: function(folders) {
			var i, row, folder_row_node, rowid, uploaded, queued, row_updated, ui_updated, getCount,
				parent = _Y.one('#uploader-ui-xhr');
			var UI = ThriftUploader.ui;
			var old_FolderState = UI._folderState; 
			ui_updated = false;
			for (i in folders) {
				row = folders[i];
				rowid = '#fhash-'+row['ThriftFolder']['native_path_hash'];
				folder_row_node = parent.one(rowid);
				if (!folder_row_node) {
					// render new row
					var beforeNode, p = parseInt(i)-1;  
					beforeNode = parent.all('tr.folder').item(p);
					if (!beforeNode) {		// first row
						beforeNode = parent.one('tr.header');
					}
					var clone = _Y.one('#folder-row-markup').clone();
					clone.set('id', 'fhash-'+row['ThriftFolder']['native_path_hash']);
					clone.one('.progress').addClass('active');
					UI.renderFolderRow(row, clone);
					beforeNode.insert(clone,'after');
					try {
						_Y.one('.empty-folders-message').remove();
					} catch(e){}
					ui_updated = true;
					continue;
				}
				row['ThriftFolder']['uploaded'] = row['0']['uploaded']; 
				getCount = /\?\)$/.test(folder_row_node.one('.label').getContent());
				queued = getCount 
					|| row['ThriftFolder']['is_scanned']=='0' 
					|| row['ThriftFolder']['is_watched']=='1'
					|| folder_row_node.one('.progress').hasClass('active');
					
				if ( queued	) {
					try {
						row_updated = row['ThriftFolder']['uploaded'] != old_FolderState[i]['ThriftFolder']['uploaded'];	
						row_updated = row_updated 
							|| row['ThriftFolder']['count']!==null && row['ThriftFolder']['count'] != old_FolderState[i]['ThriftFolder']['count']
							|| row['ThriftFolder']['count']!==null && getCount;
					} catch (e) {
						row_updated = true;					
					}
					if ( row_updated ){
						// updated uploaded file
						UI.renderFolderRow(row, folder_row_node);
						ui_updated = true;
						continue;
					} else {
						if (folder_row_node.one('.progress').hasClass('no-change'))
							folder_row_node.one('.progress').removeClass('active').removeClass('no-change');
						else folder_row_node.one('.progress').addClass('no-change'); // no
					}
				}
				if (row['ThriftFolder']['is_scanned']=='1' && row['ThriftFolder']['is_watched']!='1')  folder_row_node.one('.progress').replaceClass('pending', 'done');
				folder_row_node.removeClass('active').removeClass('no-change');  
			}
			UI._folderState = _Y.merge(folders);
			// cancel ui updates if no action for too long
			if (!ui_updated) {
				UI._no_ui_update_count++; 
				if (UI._no_ui_update_count > UI.NO_UI_UPDATE_LIMIT) {
					ThriftUploader.action.refresh(false);
					ThriftUploader.action.show_RestartMessage();
				}
			} else UI._no_ui_update_count = 0;
		},
		renderFolderRow: function(row_data, row_node) {
			if (row_data['ThriftFolder']['count']===null) row_data['ThriftFolder']['count'] = '?';
			if (!row_data['ThriftFolder']['uploaded']) row_data['ThriftFolder']['uploaded'] = '0';
			label = _Y.Lang.sub("{native_path} ({uploaded}/{count})", row_data['ThriftFolder']);
			row_node.one('td.label').setContent(label);
			row_node.one('.progress').addClass('active');
			// pending/done
			// progress bar
			var percent = Math.round( 100* row_data['ThriftFolder']['uploaded']/row_data['ThriftFolder']['count']);
			if (isNaN(percent)) row_node.one('.progress span.fill').setStyles({width:'100%'}).setAttribute('title', 'one moment...');
			else row_node.one('.progress span.fill').setStyles({width:percent+'%'}).setAttribute('title', percent+'%');
		}
	}
	ThriftUploader.util = {
		xhrJsonRequest: function(uri, cfg, successJson) {
			cfg = cfg || {method: 'GET'};
			var reponse_key = cfg.response_key;
			var ioCfg = {
					uri: uri ,
					parseContent: true,
					method: cfg.method,
					dataType: 'json',
					context: ThriftUploader,
					arguments: {success: successJson},	
					on: {
						success:  function(id, o, args) {
							if (o.getResponseHeader('Content-Type')!='application/json') return false;
							var json = _Y.JSON.parse(o.responseText);
							if (json.success) {
								if (reponse_key) json.response = json.response[reponse_key]; 
								args.success(json);
							} else {
								// TODO: possible expired Session, 
								// windows.location.reload();
							}
							return true;
						}
					}
				};
			if (cfg.postData) {
				ioCfg.data = SNAPPI.IO.object2querystring(cfg.postData);
			}				
			_Y.io(uri, ioCfg);
		},
		// @deprecated
		setFolderState: function(n, uri, postData, success) {
			var loadingNode = n;
			if (loadingNode.io == undefined) {
				var ioCfg = SNAPPI.IO.pluginIO_RespondAsJson({
					uri: uri ,
					parseContent:true,
					method: 'POST',
					qs: postData,
					dataType: 'json',
					context: n,
					arguments: {success: success}, 
					on: {
						successJson:  function(e, id, o, args) {
							return args.success();
						},
					}
				});
	            loadingNode.plug(_Y.Plugin.IO, ioCfg );
			} else {
				loadingNode.io.set('data', postData);
				loadingNode.io.set('context', n);
				loadingNode.io.set('uri', uri);
				// loadingNode.io.set('arguments', args);
				loadingNode.io.start();
	        }
		},
		getTaskState: function(successJson){
			var uri = '/thrift/task_helper/fn:GetState/.json';
			this.xhrJsonRequest(uri, 'GetState', successJson);
		},
		getFolders: function(successJson){
			var uri = '/thrift/task_helper/fn:GetFolders/.json';
			this.xhrJsonRequest(uri, 'GetFolders', successJson);
		},
		pauseUploader: function(pause, successJson){
			pause = pause ? 1 : 0;
			var cfg = {
				postData: {'data[pause]': pause},
				method: 'POST',
				response_key: 'PauseUpload',
			}
			var uri = '/thrift/task_helper/fn:PauseUpload/.json';
			this.xhrJsonRequest(uri, cfg, successJson);
		},
		getFolderState: function(successJson) {
			// helper method which calls /my/uploader_folders/.json
			// NOT A THRIFT API method
			var uri = '/my/uploader_folders/.json';
			this.xhrJsonRequest(uri, null, successJson);
		}, 
		removeFolder: function(hash, successJson){
			var uri = '/thrift/task_helper/fn:RemoveFolder/.json';
			var cfg = {
				postData: {'data[hash]': hash},
				method: 'POST',
				response_key: 'RemoveFolder',
			}
			this.xhrJsonRequest(uri, cfg, successJson);
		},
		setWatchedFolder: function(hash, watch, successJson){
			watch = watch ? 1 : 0;
			var uri = '/thrift/task_helper/fn:SetWatchedFolder/.json';
			var cfg = {
				postData: {
					'data[hash]': hash,
					'data[watch]': watch ? 1 : 0
				},
				method: 'POST',
				response_key: 'SetWatchedFolder',
			}
			this.xhrJsonRequest(uri, cfg, successJson);
		},
	}
	ThriftUploader.listeners = {

        /*
         *  listener/handlers
         * 	start 'click' listener for action=
         */
        FolderRowClick : function() {
        	delegate_container = _Y.one('table.thrift-folders');        	
        	if (!delegate_container) return;
        	var action = 'FolderRowClick';
        	delegate_container.listen = delegate_container.listen || {};
            if (delegate_container.listen[action] == undefined) {
				delegate_container.listen[action] = delegate_container.delegate('click', 
	                function(e){
	                	// context = delegate_container
	                	switch(e.currentTarget.getAttribute('action')) {
	                		case 'watch':
	                			ThriftUploader.action.post_WatchFolder(e.currentTarget);
	                			break;
	                		case 'remove':
	                			ThriftUploader.action.post_RemoveFolder(e.currentTarget);
		                		break;
	                	}
	                }, 'input', delegate_container);
				// back reference
				ThriftUploader.listen[action] = delegate_container.listen[action];	                
			}
        },
        /*
         * Montage or Gallery
         */
 		SectionOptionClick : function(node) {
        	node = node || _Y.one('nav.section-header');        	
        	if (!node) return;
        	var action = 'SectionOptionClick';
        	node.listen = node.listen || {};
        	var delegate_container = node;
            if (delegate_container && node.listen[action] == undefined) {
            	delegate_container.removeClass('hide');
				node.listen[action] = delegate_container.delegate('click', 
	                function(e){
	                	// action=[section-view:[montage|gallery]
	                	// context = node
                		var action = e.currentTarget.getAttribute('action').split(':');
                		try {
			    		switch(action[0]) {
			    			case 'section-view':
			    				ThriftUploader.action['section-view'][ action[1] ](e, action[1]);
			    				break;
			    			case 'xxx':
			    				break;
			    		}} catch(e) {
			    			console.error("ThriftUploader.listeners.SectionOptionClick(): possible error on action name.");
			    		}	
	                }, 'ul > li', node);
				// back reference
				ThriftUploader.listen[action] = node.listen[action];	   
			}
        },        
        DragDrop : function(){
        	SNAPPI.DragDrop.pluginDrop(_Y.all('.droppable'));
        	SNAPPI.DragDrop.startListeners();
        },
	}
	
	
})();