package com.gnstudio.nabiro.utilities.mock.creation
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
	
	import __AS3__.vec.Vector;
	
	import flash.net.registerClassAlias;
	import flash.system.ApplicationDomain;
	import flash.utils.getQualifiedClassName;
	
	/**
	 * Class used to dinamically define the Class to use as the data type
	 * of a Vector, it can be useful when you generate a class from an object
	 * and you want to use the Vector for constience
	 */ 
	
	public class VectorLoader
	{
		static private const  VECTOR_CLASS_NAME:String = getQualifiedClassName(Vector);

     	static public function getVectorDefinition(itemDefinition:Class, applicationDomain:ApplicationDomain = null):Class{
			
			var className:String = getQualifiedClassName(itemDefinition)
			registerClassAlias(className, itemDefinition);
			
            if(!applicationDomain) applicationDomain = ApplicationDomain.currentDomain;

            return applicationDomain.getDefinition(VECTOR_CLASS_NAME + '.<' + getQualifiedClassName(itemDefinition) + '>') as Class;

      	}

     
		static public function createCustomVector(itemDefinition:Class, length:uint=0, fixed:Boolean = false, applicationDomain:ApplicationDomain = null):Vector.<*>{
	
			var definition:Class = getVectorDefinition(itemDefinition, applicationDomain);
	
	        return new definition(length, fixed);
	
	    }

	}
}