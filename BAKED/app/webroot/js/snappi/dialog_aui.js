
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
	
	var CFG_Dialog_Hidden_Shots = function(){}; CFG_Dialog_Hidden_Shots.prototype = {};			
	
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
		dialog.cellOffsets = {
			boundingBoxOffset: {w:64, h:146}, // +19 px for scrollbar
			cellSize:{w:145, h:97}
		}
		if (cfg.autoLoad !== false) dialog.render();
		// save reference
		Dialog.find[CSS_ID] = dialog;
		return dialog;		
	}
	// save CFG in static
	Dialog.CFG['dialog-photo-roll-hidden-shots'] = CFG_Dialog_Hidden_Shots;
		
	
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
	

	
	SNAPPI.Dialog = Dialog;
})();


} catch (e) {
	var check;
}
