package com.gnstudio.nabiro.utilities.data.events
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
	
	import flash.events.Event;

	public class HashTableEvent extends Event
	{
		
		public static const DUPLICATE_ADDED:String = "duplicateAdded";
		public static const SIZE_INCREASED:String = "sizeIncreased";
		public static const KEY_FOUND_IN_DUPLICATES:String = "keyFoundInDuplicates";
		
		public var object:*;
		
		public function HashTableEvent(type:String, data:* = null, bubbles:Boolean=false, cancelable:Boolean=false){
			
			
			super(type, bubbles, cancelable);
			
			object = data;
			
			
		}
		
	}
}