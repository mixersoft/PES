
try {
/*
 * safari seems to crash when initializing this block, 
 * try-catch seems to stop crash
 */

(function(){
var BUTTONS_OK_CANCEL = [{
			text: 'OK',
			handler: null
		},{
			test: 'Cancel',
			handler: this.close()
		}],
	BUTTONS_CLOSE =[{
		text: 'Close',
		handler: this.close()
	}];

var DEFAULT_CFG_dialog = {
		centered: true,
		constrain2view: true,
		draggable: true,
		resizble: false,
		destroyOnClose: true,
		height: 250,
		width: 500,
		close: true,
		buttons: [],
		end:null
	};
var DEFAULT_CFG_io = {
	};
	

	var Dialog = function(){
		if (Dialog.doClassInit) Dialog.classInit();
	};
	Dialog.prototype = {};
	Dialog.CFG  = {};
	
		
	/*
	 * DialogCfgs
	 */
	
	var CFG_Dialog_Hidden_Shots = function(){}; 
	/*
	 * Photoroll Hidden Shots dialog
	 */
	CFG_Dialog_Hidden_Shots.load = function(cfg){
		var Y = SNAPPI.Y;
		var CSS_ID = 'dialog-photo-roll-hidden-shots';
		var _cfg = {
			title: 'Hidden Shots',
			id: CSS_ID,
			width: (3*145+64),	// 19 px for scrollbar
			height: (2*97+146),
			destroyOnClose: false,
			modal: false			
		}
		cfg = cfg || {};
		_cfg = Y.merge(DEFAULT_CFG_dialog, _cfg, cfg);
		
		var dialog = new Y.Dialog(_cfg);
		dialog.listen = {};
		dialog.cellOffsets = {
			boundingBoxOffset: {w:64, h:146}, // +19 px for scrollbar
			cellSize:{w:145, h:97}
		}
		if (cfg.autoLoad !== false) dialog.render();
		// save reference
		Dialog.find[CSS_ID] = dialog;
		return dialog;		
	}
	
	var CFG_Dialog_Select_Circles = function(){}; 
	/*
	 * Lightbox, choose circles dialog
	 */
	CFG_Dialog_Select_Circles.load = function(cfg){
		var Y = SNAPPI.Y;
		var CSS_ID = 'dialog-select-circles';
		var _cfg = {
			title: 'My Circle',
			id: CSS_ID,
			width: 678,	// 2 columns, for now
			height: 395,	// 3 rows
			destroyOnClose: false,
			modal: true,
			buttons: [
			{
				// TODO: convert this toggle button to tabs in the contentBox
				text: 'Show Public Circles',
				handler: function() {
					var dialog = this;
					var uri = dialog.io.get('uri');
					if (uri != '/groups/open') {
						// toggle to public
						var cfg = {
							text: 'Show My Circles',
							title: 'Public Circles',
							uri: '/groups/open' 
						}
					} else {
						// toggle to memberships
						cfg = {
							text: 'Show Public Circles',
							title: 'My Circles',
							uri: '/my/groups' 
						}
					}
					dialog.set('title', cfg.text);
					dialog.io.set('uri', cfg.uri);    			
					dialog.io.start();
					var detach = dialog.on('bodyContentChange', function(e, cfg){
						var footer = this.get('footerContent');
						footer.one('button').set('innerHTML', cfg.text);
						detach.detach();
					}, dialog, cfg);
				}
			},
			{
				text: 'Remove from Circle',
				handler: function() {
					var check;
					var content = this.get('contentBox');
					var selected = content.one('.selected');
					var gid = selected.get('id');
					var detach = SNAPPI.Y.on('snappi:share-complete', function(lightbox, loading){
						loading.loadingmask.hide();
						// update asset count in dialog
						detach.detach();
					});
					var options = {
						data: {
							'data[Asset][unshare]': 1
						},
						uri: '/photos/setprop/.json'
					};
					SNAPPI.lightbox.applyShareInBatch(gid, selected, options);
				}
			},
			{
				text: 'Share with Circle',
				handler: function() {
					var check;
					var content = this.get('contentBox');
					var selected = content.one('.selected');
					var gid = selected.get('id');
					var detach = SNAPPI.Y.on('snappi:share-complete', function(lightbox, loading){
						loading.loadingmask.hide();
						// update asset count in dialog
						detach.detach();
					});
					SNAPPI.lightbox.applyShareInBatch(gid, selected);
				}
			}
			]			
		}
		cfg = cfg || {};
		_cfg = Y.merge(DEFAULT_CFG_dialog, _cfg, cfg);
		
		var dialog = new Y.Dialog(_cfg);
		dialog.listen = {};
		dialog.listen['select'] = SNAPPI.Dialog.listen_select(dialog);
		
		if (cfg.autoLoad !== false) dialog.render();
		// save reference
		Dialog.find[CSS_ID] = dialog;
		return dialog;		
	}
	
	var CFG_Dialog_Select_Privacy = function(){}; 
	/*
	 * Lightbox, choose circles dialog
	 */
	CFG_Dialog_Select_Privacy.load = function(cfg){
		var Y = SNAPPI.Y;
		var CSS_ID = 'dialog-select-privacy';
		var _cfg = {
			title: 'Privacy Settings',
			id: CSS_ID,
			// width: 678,	// 2 columns, for now
			height: 300,	// 3 rows
			destroyOnClose: false,
			modal: true,
			buttons: [
			{
				text: 'Apply',
				handler: function() {
					var content = this.get('contentBox');
					var selected = content.one('.selected');
					var value = parseInt(selected.getAttribute('value'));
					var detach = SNAPPI.Y.on('snappi:privacy-complete', function(lightbox, loading){
						loading.loadingmask.hide();
						// update asset count in dialog
						detach.detach();
					});
					SNAPPI.lightbox.applyPrivacyInBatch(value, selected);
				}
			}
			]			
		}
		cfg = cfg || {};
		_cfg = Y.merge(DEFAULT_CFG_dialog, _cfg, cfg);
		
		var dialog = new Y.Dialog(_cfg);
		dialog.listen = {};
		dialog.listen['select'] = SNAPPI.Dialog.listen_select(dialog);
		
		if (cfg.autoLoad !== false) dialog.render();
		// save reference
		Dialog.find[CSS_ID] = dialog;
		return dialog;		
	}
	
	// save CFG in static
	Dialog.CFG = {
		'dialog-photo-roll-hidden-shots': CFG_Dialog_Hidden_Shots,
		'dialog-select-circles': CFG_Dialog_Select_Circles,
		'dialog-select-privacy': CFG_Dialog_Select_Privacy
	};
		
	
	/*
	 * static properties and methods
	 */
	Dialog.doClassInit = true;
	Dialog.listen = {};
	Dialog.find = {};	// keep track of dialog instances for reuse
	
	Dialog.classInit = function() {
		var Y = SNAPPI.Y;
		Dialog.doClassInit = false;
	};
	
	Dialog.listen_select = function(d) {
		var content = d.get('contentBox');
		var detach = content.delegate('click', function(e,i,o){
			var target = e.currentTarget;
			e.halt(true);
			target.get('parentNode').all('.selected').removeClass('selected');
			target.addClass('selected');
		}, 'li', d);
		return detach;
	}
	Dialog.listen_close = function(d) {
		var detach = d.('closeChange', function(e){
			for (var i in this.listen) {
				this.listen[i].detach();
			}
		}, d)
		return detach;		
	}

	
	SNAPPI.Dialog = Dialog;
})();


} catch (e) {
	var check;
}
