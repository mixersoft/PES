package api
{
		import flash.external.ExternalInterface;
		import flash.utils.Proxy;
		import flash.utils.flash_proxy;
		
		public dynamic class JavascriptVariableProxy extends Proxy
		{
			/**
			 * Retrieves the variable from the javascript environment.
			 * 
			 * @private
			 */
			override flash_proxy function getProperty(name:*):*
			{
				return ExternalInterface.call("function() { return " + name.toString() + "; }");
			}
			
			/**
			 * Passes the variable through to the javascript environment.
			 * 
			 * @private
			 */
			override flash_proxy function setProperty(name:*, value:*):void
			{
				ExternalInterface.call("function() { " + name + " = '" + value.toString() + "'; }");
			}
			
			/**
			 * call JS method
			 * 
			 */
			public function bootstrapReady(value:Boolean):void
			{
				try {
					ExternalInterface.call("bootstrapReady", value);
				} catch (e:Error) {
					ExternalInterface.call("alert", "Exception, from ActionScript");					
				}
				
			}
			public function refreshUI():void
			{
				ExternalInterface.call("SNAPPI.ThriftUploader.action.refresh", 'restart');
			}
			
			
		}
		
}