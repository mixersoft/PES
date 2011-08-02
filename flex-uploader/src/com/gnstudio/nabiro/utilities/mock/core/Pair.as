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
	
	/**
	 * The aim of this class is to use it as a way to express a pair of values
	 * defined trough a name and any data type (*)
	 */ 
	
	public class Pair
	{
		public function Pair(n:String = null, v:* = null){
			
			_name = n;
			_value = v;
			
		}
		
		private var _name:String;
		
		/**
		 * The name of the value expressed byt the Pair
		 */ 
		public function get name():String{
			
			return _name;
			
		}
		
		public function set name(value:String):void{
			
			_name = value;
			
		}
		
		private var _value:*;
		
		/**
		 * The value asscoiated to this Pair
		 */ 
		public function get value():*{
			
			return _value;
			
		}
		
		public function set value(v:*):void{
			
			_value = v;
			
		}

	}
}