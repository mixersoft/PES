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
	
	Dialog.get_PhotoRoll = function(cfg){
		var Y = SNAPPI.Y;

		cfg = cfg || {};
		var _cfg = {
			title: "Photos",
			destroyOnClose: false,
			modal: false
		};
		_cfg = Y.merge(DEFAULT_CFG_dialog, _cfg, cfg);
		
		var dialog = new Y.Dialog(_cfg);
		if (cfg.autoLoad !== false) dialog.render();
		
//		var _ioCfg = {
//			uri: cfg.uri,
//			method: 'GET',
//			parseContent: true,
//			autoLoad: (cfg.autoLoad !== false) ? true : false
//		};
//		_ioCfg = Y.merge(DEFAULT_CFG_io, _ioCfg);
//		dialog.plug(Y.Plugin.IO, _ioCfg);
		return dialog;
	};
	
	SNAPPI.Dialog = Dialog;
})();