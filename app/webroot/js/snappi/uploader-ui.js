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
			if (taskType=='ur') {
				SNAPPI.ThriftUploader.action.refresh('restart');
			}
			ThriftUploader.ui._no_ui_update = 0;
			var target = _Y.Lang.sub("snaphappi://{authToken64}_{sessionId64}_", PAGE.jsonData.nativeUploader);
			var uri = target+taskType;
			window.location.href = uri;
		},
		refresh: function(start) {
			if (start) {
				if (ThriftUploader.timer === false && start!=='restart') return;
				ThriftUploader.timer = _Y.later(1000, SNAPPI.xhrFetch, function(){
						var n = _Y.one( ThriftUploader.container_id );
						var uri = n.getAttribute('xhrsrc');
						uri += '/.json';			// use JSON request to refresh
						ThriftUploader.util.getFolderState(
							function(json) {
								var response = json.response;
								SNAPPI.ThriftUploader.ui.renderFolderState(response.folders);
								if (response.taskState['ThriftSession']['is_cancelled']=='0') {
									SNAPPI.ThriftUploader.action.refresh(true);
								}
							}
						);
					}, 
					null, false
				);
			} else {
				ThriftUploader.timer.cancel();
				ThriftUploader.timer = false;
			} 
		}
	}

	ThriftUploader.ui = {
		_folderState: null,
		_no_ui_update: 0,
		renderFolderState: function(folders) {
			var i, row, rowid, uploaded, queued, row_updated, ui_updated;
			var parent = _Y.one('#uploader-ui-xhr');
			var old_FolderState = ThriftUploader.ui._folderState; 
			ui_updated = false;
			for (i in folders) {
				row = folders[i];
				rowid = '#fhash-'+row['ThriftFolder']['native_path_hash'];
				row['ThriftFolder']['uploaded'] = row['0']['uploaded']; 
				queued = row['ThriftFolder']['is_scanned']=='0' || row['ThriftFolder']['is_watched']=='1';
				if ( queued	) {
					try {
						row_updated = row['ThriftFolder']['uploaded'] != old_FolderState[i]['ThriftFolder']['uploaded'];	
						row_updated = row_updated || row['ThriftFolder']['count'] != old_FolderState[i]['ThriftFolder']['count'];
					} catch (e) {
						row_updated = true;					
					}
					if ( row_updated ){
						// updated uploaded file
						label = _Y.Lang.sub("{native_path} ({uploaded}/{count})", row['ThriftFolder']);
						parent.one(rowid+' li.label').setContent(label);
						parent.one(rowid).addClass('active');
						ui_updated = true;
						continue;
					} else {
						if (parent.one(rowid).hasClass('no-change'))
							parent.one(rowid).removeClass('active').removeClass('no-change');
						else parent.one(rowid).addClass('no-change'); // no
					}
				}
				parent.one(rowid).removeClass('active').removeClass('no-change');  
			}
			ThriftUploader.ui._folderState = _Y.merge(folders);
			// cancel ui updates if no action for too long
			if (!ui_updated) {
				ThriftUploader.ui._no_ui_update++; 
				if (ThriftUploader.ui._no_ui_update > 5) ThriftUploader.action.refresh(false);
			} else ThriftUploader.ui._no_ui_update = 0;
		}
	}
	ThriftUploader.util = {
		xhrJsonRequest: function(uri, reponse_key, successJson) {
			var ioCfg = {
					uri: uri ,
					parseContent: true,
					method: 'GET',
					dataType: 'json',
					context: ThriftUploader,	
					on: {
						success:  function(id, o, args) {
							if (o.getResponseHeader('Content-Type')!='application/json') return false;
							var json = _Y.JSON.parse(o.responseText);
							if (json.success) {
								if (reponse_key) json.response = json.response[reponse_key]; 
								successJson(json);
							}
							return true;
						}
					}
				};
			_Y.io(uri, ioCfg);
		},
		setFolderState: function(n) {
			var uri = '/thrift/set_watched_folder/.json';
			var postData = {};
			postData[n.getAttribute('name')] = n.getAttribute('value');
			postData["data[ThriftFolder][is_watched]"] = n.get('checked') ? '1' : '0';
			var loadingNode = n;
			if (loadingNode.io == undefined) {
				var ioCfg = SNAPPI.IO.pluginIO_RespondAsJson({
					uri: uri ,
					parseContent:true,
					method: 'POST',
					qs: postData,
					dataType: 'json',
					context: n,
					// arguments: args, 
					on: {
						successJson:  function(e, id, o, args) {
							// launch sw task
	                		SNAPPI.ThriftUploader.action.launchTask("sw");
							return false;
						}
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
		getFolderState: function(successJson) {
			// helper method which calls /my/uploader_ui/.json
			// NOT A THRIFT API method
			var uri = '/my/uploader_ui/.json';
			this.xhrJsonRequest(uri, null, successJson);
		}, 
	}
	ThriftUploader.listeners = {

        /*
         *  listener/handlers
         * 	start 'click' listener for action=
         */
        WatchFolderClick : function() {
        	delegate_container = _Y.one('#uploader-ui-xhr');        	
        	if (!delegate_container) return;
        	var action = 'WatchFolderClick';
        	delegate_container.listen = delegate_container.listen || {};
            if (delegate_container.listen[action] == undefined) {
				delegate_container.listen[action] = delegate_container.delegate('click', 
	                function(e){
	                	// context = delegate_container
	                	// save folder state
	                	ThriftUploader.util.setFolderState(e.currentTarget);
	                }, 'ul.folder > li > input[type=checkbox]', delegate_container);
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