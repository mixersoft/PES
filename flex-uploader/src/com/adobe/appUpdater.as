package com.adobe
{
	import air.update.ApplicationUpdaterUI;
	import air.update.events.UpdateEvent;
	
	import flash.desktop.*;
	import flash.events.ErrorEvent;
	
	import mx.controls.Alert;
	import mx.core.Application;
	
	public class appUpdater
	{
		private var _appUpdater:ApplicationUpdaterUI= new ApplicationUpdaterUI();
		private var _currentVersion:String;
		
		public function appUpdater():void
		{
		}
		public function checkUpdate(updateURL:String):void
		{
			//Decide current version
			/* var appXML:XML = NativeApplication.nativaApplication.applicationDescriptor;
			var ns:Namespace = appXML.namespace();
		    _currentVersion= appXML.ns::version; */
		    
			// we set the URL for the update.xml file
			_appUpdater.updateURL=updateURL;
			//we set the event handlers for INITIALIZED nad ERROR
			_appUpdater.addEventListener(UpdateEvent.INITIALIZED, onUpdate);
			_appUpdater.addEventListener(ErrorEvent.ERROR, onError);
			//we can hide the dialog asking for permission for checking for a new update;
			//if you want to see it just leave the default value (or set true).
			_appUpdater.isCheckForUpdateVisible = false;
			//we initialize the updater
			_appUpdater.initialize();
		}
		
		private function onUpdate(event:UpdateEvent):void 
		{
			//start the process of checking for a new update and to install
			_appUpdater.checkNow();
		}
		
		/**
		 * Handler function for error events triggered by the ApplicationUpdater.initialize
		 * @param ErrorEvent 
		 */ 
		private function onError(event:ErrorEvent):void {
			Alert.show(event.toString());
		}
		
	}
}