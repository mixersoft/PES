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
	
	import flash.utils.describeType;
	import flash.utils.getDefinitionByName;
	import flash.utils.getQualifiedClassName;
	
	
	public class ClassNameFinder
	{
		public function ClassNameFinder()
		{
		}
		
		public static function classNameForMock(mock:Object, simple:Boolean = true):String {
        	
        	var name:String = getQualifiedClassName(mock);
        	
        	var data:XML = describeType(getDefinitionByName(name));
        	
        	var className:String = "";
        	
        	if(data..implementsInterface.length() > 0){
        		
        		for(var i:int = 0; i < data..implementsInterface.length(); i++){
        			
        			var endMark:String;
        			
        			if(i <  data..implementsInterface.length() - 1){
        				
        				endMark = "|";
        				
        			}else{
        				
        				endMark = "";
        				
        			}
        			
        			if(simple){
        				
        				className += getSimpleName(data..implementsInterface[i].@type) + endMark;
        			
        			}else{
        				
        				className += data..implementsInterface[i].@type + endMark;
        				
        			}
        			
        		}
        		
        	}else{
        		
        		
        		if(simple){
        			
        			className = getSimpleName(getQualifiedClassName(mock));
        			
        		}else{
        			
        			className = getQualifiedClassName(mock);
        		
        		}
        	}
        	
        	return className;
    
    	}
    	
    	private static function getSimpleName(value:String):String{
		
			return value.substr(value.indexOf("::") + 2);
		
		}

	}
	
	
}