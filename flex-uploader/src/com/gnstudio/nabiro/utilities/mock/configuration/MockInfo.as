package com.gnstudio.nabiro.utilities.mock.configuration
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
	
	import com.gnstudio.nabiro.utilities.mock.exceptions.WrongTarget;
	
	public class MockInfo
	{
		
		public static const FOR_AIR:String = "mockForAirApplications";
		public static const FOR_FLASH:String = "mockForFlashApplications";
		public static const FOR_FLEX:String = "mockForFlexApplications";
		
		public function MockInfo(target:String = ""){
			
			_target = target;
			
			_classPaths = [];
			_interfacesMap = [];
			
		}
		
		/**
		 * Define a correspondence between an interface and a class
		 * @param interfaze Class
		 * @param clazz Class
		 */ 
		public function mapInterfaceTo(interfaze:Class, clazz:Class):void{
			
			// TODO avoid duplicates
			_interfacesMap.push({interfaze: interfaze, clazz: clazz});
			
		}
		
		private var _classPaths:Array;
		
		/**
		 * Define class paths to use in the reflection, imagine this method
		 * as an helper
		 * @param value String
		 */ 
		public function addClassPath(value:String):void{
			
			_classPaths.push(value);
			
		}
		
		public function get paths():Array{
			
			return _classPaths;
			
		}
		
		private var _interfacesMap:Array;
		
		public function get interfacesMap():Array{
			
			return _interfacesMap;
			
		}
		
		private var _target:String;
		
		/**
		 * Target for which you are mocking
		 */ 
		public function set target(value:String):void{
			
			if(value != FOR_AIR || value != FOR_FLASH || value != FOR_FLEX){
				
				throw new WrongTarget();
				
			}else{
				
				_target = value;
				
			}
			
		}
		
		public function get target():String{
			
			return _target;
			
		}

	}
}