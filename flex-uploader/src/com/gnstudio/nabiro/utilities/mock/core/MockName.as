package com.gnstudio.nabiro.utilities.mock.core
{
	
	/**
	 *
	 * GNstudio nabiro
	 * =====================================================================
	 * Copyright(c) 2009
	 * http://www.gnstudio.com
	 *
	 *
	 *
	 * This file is part of the nabiro flash platform framework
	 *
	 *
	 * nabiro is free software; you can redistribute it and/or modify
	 * it under the terms of the GNU Lesser General Public License as published by
	 * the Free Software Foundation; either version 3 of the License, or
	 * at your option) any later version.
	 *
	 * nabiro is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	 * GNU General Public License for more details.
	 *
	 * You should have received a copy of the GNU Lesser General Public License
	 * along with Intelligere SCS; if not, write to the Free Software
	 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
	 * =====================================================================
	 *
	 *
	 *
	 *   @package  nabiro
	 *
	 *   @version  0.9
	 *   @idea maker 			Giorgio Natili [ g.natili@gnstudio.com ]
	 *   @author 					Giorgio Natili [ g.natili@gnstudio.com ]
	 *   @request maker 	Igor Varga [ i.varga@gnstudio.com ]
	 *	
	 */
	
	import flash.utils.getQualifiedSuperclassName;
	import flash.utils.getQualifiedClassName;
	
	/**
	 * In order to prevent future needs we define a mock name,
	 * it can be useful in an application that needs to log what's
	 * going on in the filesystem or on a web server.
	 * If no specific name is provided the name is autmoatically generated
	 * so that it's not paniful to track this information
	 * @usage 
	 * var testName:MockName = new MockName(FlexShape);
	 * trace(testName.isSurrogate())
	 * trace(testName.toString())
	 */ 
	public class MockName
	{
		
		private var _mockName:String;
		private var _surrogate:Boolean;
		
		public function MockName(classToMock:Class, mockName:String = null){
			
			if(mockName == null){
				
				_mockName = toInstanceName(classToMock);
				_surrogate = true;
				
			}else{
				
				_mockName = mockName;
				
			}
			
		}
		
		private function toInstanceName(clazz:Class):String {
        
        	var className:String = getSimpleName(getQualifiedClassName(clazz));
        	
        	//lower case first letter
        	return className.substring(0, 1).toLowerCase() + className.substring(1);
    	
    	}
		
		private function getSimpleName(value:String):String{
		
			return value.substr(value.indexOf("::") + 2);
		
		}
		
		public function isSurrogate():Boolean{
			
			return _surrogate;
			
		}
		
		 public function toString():String{
			
			return _mockName;
			
		}

	}
}