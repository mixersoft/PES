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
	
	import __AS3__.vec.Vector;
	
	import com.gnstudio.nabiro.utilities.HashMap;
	import com.gnstudio.nabiro.utilities.StringUtil;
	import com.gnstudio.nabiro.utilities.mock.configuration.IGenerateRange;
	import com.gnstudio.nabiro.utilities.mock.exceptions.WrongPrimitives;
	
	import flash.errors.IllegalOperationError;
	import flash.utils.getQualifiedClassName;
	
	/**
	 * The ami of this class is to store primitive types, if you need to define new
	 * primitives for the application that you are mocking you can poin the the following
	 * properties:
	 * 
	 * primitiveTypes the prmitivie data types
	 * primitiveValues the primitive data types null or empty values
	 * primitiveDefaultValues the primitive data type default values
	 * 
	 */ 
	public class Primitives
	{
		public static var primitiveTypes:HashMap = new HashMap();
		public static var primitiveValues:HashMap = new HashMap();
		public static var primitiveDefaultValues:HashMap = new HashMap();
		
		primitiveTypes.addItem({clazz: Boolean, name: getClassName(Boolean)});
		primitiveTypes.addItem({clazz: String, name: getClassName(String)});
		primitiveTypes.addItem({clazz: Number, name: getClassName(Number)});
		primitiveTypes.addItem({clazz: Array, name: getClassName(Array)});
		primitiveTypes.addItem({clazz: int, name: getClassName(int)});
		primitiveTypes.addItem({clazz: uint, name: getClassName(uint)});
		primitiveTypes.addItem({clazz: Object, name: getClassName(Object)});
		
		primitiveValues.addItem({value: false, name: getClassName(Boolean)});
		primitiveValues.addItem({value: null, name: getClassName(String)});
		primitiveValues.addItem({value: 0, name: getClassName(Number)});
		primitiveValues.addItem({value: [], name: getClassName(Array)});
		primitiveValues.addItem({value: 0, name: getClassName(int)});
		primitiveValues.addItem({value: 0x00, name: getClassName(uint)});
		primitiveValues.addItem({value: {}, name: getClassName(Object)});
		
		primitiveDefaultValues.addItem({value: false, name: getClassName(Boolean)});
		primitiveDefaultValues.addItem({value: "", name: getClassName(String)});
		primitiveDefaultValues.addItem({value: 0, name: getClassName(Number)});
		primitiveDefaultValues.addItem({value: [], name: getClassName(Array)});
		primitiveDefaultValues.addItem({value: 0, name: getClassName(int)});
		primitiveDefaultValues.addItem({value: 0x00, name: getClassName(uint)});
		primitiveDefaultValues.addItem({value: {}, name: getClassName(Object)});
		
		/**
		 * Method used to generate random values for a class, the randomizer param
		 * is used to generate random values for a specific data type
		 * @param clazz Class
		 * @param randomizer IGenerateRange
		 * @return *
		 */ 
		public static function random(clazz:Class, randomizer:IGenerateRange):*{
			
			
			if(!getClassName(clazz)){
				
				throw new WrongPrimitives();
				
			}
			
			var result:*;
			
			switch(true){
				
				case getClassName(clazz) == "Boolean":
				result = getRandomBoolean()
				break;
				
				case getClassName(clazz) == "String":
				result = getRandomString()
				break;
				
				case getClassName(clazz) == "Number":
				result = getRandomNumber(randomizer)
				break;
				
				case getClassName(clazz) == "Array":
				// TODO handle better the arrays
				result = getRandomArray([1,2,3,4,5,6,7,8,9,0])
				break;
				
				case getClassName(clazz) == "int":
				result = getRandomInt(randomizer)
				break;
				
				case getClassName(clazz) == "uint":
				result = getRandomUint(randomizer)
				break;
				
				case getClassName(clazz) == "Object":
				
				var pairs:Vector.<Pair> = new Vector.<Pair>();
				pairs[0] = new Pair("type", getRandomString())
				pairs[0] = new Pair("value", getRandomNumber)
				
				result = getRandomObject(pairs);
				break;
				
				default:
				throw new IllegalOperationError("Not a valid class");
				break;
				
			}
			
			return result;
			
			
		}
		
		/**
		 * Method used to generate a random Object
		 * @param data:Vector.<Pair>
		 * @return Object
		 */
		private static function getRandomObject(data:Vector.<Pair>):Object{
			
			var result:Object = {};
			
			for(var i:int = 0; i < data.length; i++){
				
				var pair:Pair = data[i];
				
				result[pair.name] = pair.value;
				
			}
			
			return result;
		}
		
		/**
		 * Method used to generate a random Array
		 * @param data Array
		 * @return Array
		 */ 
		private static function getRandomArray(data:Array):Array{
			
			var random:Function = function(a:Object, b:Object):int{
				
				return Math.round(Math.random() * 2) - 1
				
			}
			
			return data.sort(random);

		}
		
		/**
		 * Method used to generate a random Number, if the
		 * randomizer is not null the number will be between
		 * a specific range
		 * @param randomizer:IGenerateRange
		 * @return Number
		 */ 
		private static function getRandomNumber(randomizer:IGenerateRange):Number{
			
			var result:Number;
			
			if(randomizer){
				
				result = (Math.random() * (1 + randomizer.max - randomizer.min)) + randomizer.min; 
				
			}else{
			
				if(getRandomBoolean()){
					
					result = getRandomInt(randomizer);
					
				}else{
					
					result = Math.random() * 100;
					
				}
				
			}
			
			return result;
			
		}
		
		/**
		 * Method used to generate a random int, if the
		 * randomizer is not null the number will be between
		 * a specific range
		 * @param randomizer:IGenerateRange
		 * @return int
		 */ 
		private static function getRandomInt(randomizer:IGenerateRange):int{
			
			if(randomizer){
				
				//return randomizer.min+ Math.floor(Math.random() * randomizer.max) + randomizer.max; 
				return randomizer.min + Math.floor(Math.random() * (randomizer.max -randomizer.min)); 
				
			}else{
				
				return Math.floor(Math.random() * 100);
				
			}
			
			
		}
		
		private static function getRandomUint(randomizer:IGenerateRange):int{
			
			if(randomizer){
				
				var progress:int = 5;
				
				var q:Number = 1 - progress;
		        var fromA:uint = (randomizer.min >> 24) & 0xFF;
		        var fromR:uint = (randomizer.min >> 16) & 0xFF;
		        var fromG:uint = (randomizer.min >>  8) & 0xFF;
		        var fromB:uint =  randomizer.min & 0xFF;
		
		        var toA:uint = (randomizer.max >> 24) & 0xFF;
		        var toR:uint = (randomizer.max >> 16) & 0xFF;
		        var toG:uint = (randomizer.max >>  8) & 0xFF;
		        var toB:uint =  randomizer.max        & 0xFF;
		        
		        var resultA:uint = fromA * q + toA * progress;
		        var resultR:uint = fromR * q + toR * progress;
		        var resultG:uint = fromG * q + toG * progress;
		        var resultB:uint = fromB * q + toB * progress;
		        
		        return resultA << 24 | resultR << 16 | resultG << 8 | resultB;
				
			}else{
				
				return Math.round( Math.random()*0xffffff );
				
				
			}
			
		}
		
		/**
		 * Method used to generate a random String, actually
		 * it uses 10 chars but it can be improved in the next release
		 * @return String
		 */ 
		private static function getRandomString():String{
			
			// TODO improve the number of chars per string
			return StringUtil.generateRandomString(Math.floor(Math.random() * 10));
			
		}
		
		/**
		 * Method used to generate a random Boolean
		 * @return Boolean
		 */ 
		private static function getRandomBoolean(chance:Number = 0.5):Boolean {
			
			return (Math.random() < chance);
		
		}

		/**
		 * Method used recover the class name removing all the not needed chars (i.e. packagin)
		 * @return String
		 */ 
		private static function getClassName(clazz:Class, simple:Boolean = false):String{
			
			var name:String;
			
			if(simple){
				
				name = getSimpleName(getQualifiedClassName(clazz));
				
			}else{
				
				name = getQualifiedClassName(clazz);
				
			}
			
			return name;
			
		}
		
		private static function getSimpleName(value:String):String{
		
			return value.substr(value.indexOf("::") + 2);
		
		}
		
	}
	
	
}