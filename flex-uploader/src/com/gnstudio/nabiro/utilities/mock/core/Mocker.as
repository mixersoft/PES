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
	
	import com.gnstudio.nabiro.utilities.mock.configuration.IGenerateRange;
	import com.gnstudio.nabiro.utilities.mock.configuration.MockInfo;
	import com.gnstudio.nabiro.utilities.mock.configuration.RandomProperty;
	import com.gnstudio.nabiro.utilities.mock.creation.VectorLoader;
	
	import flash.utils.describeType;
	import flash.utils.getDefinitionByName;
	
	public class Mocker
	{
		
		private var _clazz:Class;
		private var _info:MockInfo;
		private var _name:MockName;
		
		private var _amount:int;
		private var _randoms:Array;
		private var _randomValues:Vector.<RandomProperty>;
		private var _empties:Array;
		private var _predefineds:Vector.<Pair>;
		
		protected const VECTOR_FINDER:String = "Vector";
		protected const READ_WRITE_CONST:String = "readwrite";
		
		public function Mocker(clazz:Class, info:MockInfo = null, name:MockName = null)	{
			
			_clazz = clazz;
						
			if(!info){
				
				_info = new MockInfo(MockInfo.FOR_FLASH);
				
			}else{
				
				_info = info;
				
			}
			
			if(!name){
				
				_name = new MockName(clazz);
				
			}else{
				
				_name = name
				
			}
			
		}
		
		/**
		 * Define the amount of mock you want
		 * @param amount int
		 * @return Mocker
		 */ 
		public function giveMe(amount:int):Mocker{
			
			_amount = amount;
			
			return this;
			
		}
		
		/**
		 * Define the range of a value expressed trough a Vector of RandomProperty
		 * @param value Vector.<RandomProperty>
		 * @return Mocker
		 */  
		public function withRanges(value:Vector.<RandomProperty>):Mocker{
			
			_randomValues = value;
			
			return this;
			
		}
		
		/**
		 * Define the set of properties to randomize
		 * @param value Array
		 * @return Mocker
		 */
		public function withRandomProperties(value:Array):Mocker{
			
			_randoms = value;
			
			return this;
			
		}
		
		/**
		 * Define which properties have to be null
		 * @param value Array
		 * @return Mocker
		 */ 
		public function withEmptyProperties(value:Array):Mocker{
			
			_empties = value;
			
			return this;
			
		}
		
		/**
		 * Define the predefined values of the mock
		 * @param value value Vector.<Pair>
		 * @return Mocker
		 */ 
		public function withTheseValues(value:Vector.<Pair>):Mocker{
			
			_predefineds = value;
			
			return this;
			
		}
		
		/**
		 * The big boss that build the mock
		 * @return Array
		 */ 
		public function create():Array{
			
			var result:Array = [];
			
			for(var i:int = 0; i < _amount; i++){
				
				var mock:* = new _clazz();
				
				// Explore the properties of the call trough reflection
				var data:XML = describeType(_clazz);
				var accessorList:XMLList = (data..accessor.(@access == READ_WRITE_CONST) as XMLList);
				
				for(var j:int = 0; j < accessorList.length(); j++){
					
					var property:String = XML(accessorList[j]).@name.toXMLString();
					var type:String = XML(accessorList.(@name == property)).@type;
					
				 	if(search(property, _empties)){
						
						// Empties values expressed in the builder
						mock[property] = null;
						
						
					} else	if(search(property, _randoms) == true){
						
						// Random properties marked in the builder						
						var RandomClassToUse:Class = getDefinitionByName(type) as Class;
						
						if(isPrimitive(type)){
							
							var randomizer:IGenerateRange = checkRange(property);
							
							mock[property] = Primitives.random(RandomClassToUse, randomizer)
														
						}else{
							
							var mocka:* = new RandomClassToUse();
							
							// TODO handle Arrays
							if(ClassNameFinder.classNameForMock(mocka).indexOf(VECTOR_FINDER) >= 0){
								
							//	generateVectors(mocka)
								
								mock[property] = generateVectors(mocka);
								
							}else{
								
								mock[property] = new RandomClassToUse();
								
							}
							
						}
						
					}else if (searchPredefined(property, _predefineds)){
					
						// Predefined values expressed in the builder
						mock[property] = searchPredefined(property, _predefineds);
					
					}else{
						
						// Properties not marked in the builder
						if(isPrimitive(type)){
							
							mock[property] = getDefaultPrimitive(type);
							
						}else{
							
							var ClassToUse:Class = getDefinitionByName(type) as Class;
						
							try{
								
								var mockito:* = new ClassToUse();
								
								if(ClassNameFinder.classNameForMock(mockito).indexOf(VECTOR_FINDER) >= 0){
								
									mock[property] = generateVectors(mockito, false)
									
								}else{
								
									mock[property] = new ClassToUse();
									
								}
								
							}catch(e:Error){
								
								if(_info){
									
									if(recoverMapClass(ClassToUse)){
										
										var Mapped:Class = recoverMapClass(ClassToUse);
										
										var mappedData:XML = describeType(Mapped);
										var mappedDataAccessoriesList:XMLList = (mappedData..accessor.(@access == READ_WRITE_CONST) as XMLList);
										
										mock[property] = new Mocker(Mapped, _info).withRandomProperties(generateRandomProps(mappedDataAccessoriesList)).giveMe(1).create()[0];
										
									}
									
								}else{
								
									// Just in case we don't get an Interface
									mock[property] = null;
									
								}
								
							}
							
						}
						
					}
					
				}
				
				result.push(mock)
				
			}
			
			return result;
			
		}
		
		/**
		 * Check if a mock have to be defined between a range
		 * if the properties matches one of the random value ranges provided
		 * to the builder it retur the IGenerateRange implementor
		 * @param value String
		 * @return IGenerateRange
		 */ 
		private function checkRange(value:String):IGenerateRange{
			
			var result:IGenerateRange;
			
			if(_randomValues){
			 
				for(var i:int = 0; i < _randomValues.length; i++){
					
					var random:RandomProperty = _randomValues[i];
					
					if(random.name == value){
						
						result = random.range;
						break;
						
					}
					
				}
			
			}
			
			return result;
			
		}
		
		/**
		 * An interface cannot be used via reflection to generate a class
		 * instance, the Mocker support a mapping expressed trough MockInfo
		 * between an interface and one of its implementors
		 * @param interfaze Class
		 * @return Class
		 */ 
		private function recoverMapClass(interfaze:Class):Class{
			
			var result:Class;
			
			for(var i:int = 0; i < _info.interfacesMap.length; i++){
				
				if( _info.interfacesMap[i].interfaze == interfaze){
					
					result = _info.interfacesMap[i].clazz;
					break;
					
				}
				
			}
			
			return result;
			
		}
		
		/**
		 * Generates vector and it's content, when you meet a Vector through this
		 * method the class is able to iterate and create new nested method builders
		 * in order to generate vectors
		 * The nice thins is that you can create random Vectors also from here, in the
		 * next release we'll upodate for sure the method to give more info about the nested
		 * objects, their values and the way to handle properties
		 * @param mocka * 
		 * @param random Boolean
		 * @return Vector.<*>
		 */ 
		private function generateVectors(mocka:*, random:Boolean = true):Vector.<*>{
			
			var MockaContent:Class;
								
			try{
									
				MockaContent = getDefinitionByName(getSimpleName(ClassNameFinder.classNameForMock(mocka))) as Class;
									
			}catch(error:Error){
				
				MockaContent = generateClassFromInfo(getSimpleName(ClassNameFinder.classNameForMock(mocka)));
									
			}
			
			var mockaData:XML = describeType(MockaContent);
			var mockaAccessoriesList:XMLList = (mockaData..accessor.(@access == READ_WRITE_CONST) as XMLList);
			
			var builder:Array;
			
			if(random){
				
				builder = new Mocker(MockaContent).withRandomProperties(generateRandomProps(mockaAccessoriesList)).giveMe(_amount).create();
				
			}else{
				
				builder = new Mocker(MockaContent).giveMe(_amount).create();
				
			}
						
			var vector:Vector.<*> = VectorLoader.createCustomVector(MockaContent);
								
			vector = populateVector(vector, builder);
			
			return vector;
			
		}
		
		/**
		 * Starting from the XMLList that contains all the accessor (readwrite) the
		 * method generates an array used to say to Mocker to define all the properties
		 * in a random way
		 * @param value XMLList
		 * @return Array
		 */ 
		private function generateRandomProps(value:XMLList):Array{
			
			var result:Array = [];
			
			for(var i:int = 0; i < value.length(); i++){
				
				result[i] = value[i].@name.toXMLString()
				
			}
			
			return result;
			
		}
		
		/**
		 * Starting from an Array it populates a Vecor
		 * @param vector Vector.<*>
		 * @param data:Array
		 * @return vector Vector.<*>
		 */ 
		private function populateVector(vector:Vector.<*>, data:Array):Vector.<*>{
			
			for(var i:int = 0; i < data.length; i++){
				
				vector[i] = data[i]
				
			}
			
			return vector;
			
		}
		
		/**
		 * If you are digging in the package of your models then the reflection
		 * may need additional information such as the path of other folder
		 * to use in order to generae the Class
		 * This information are defined trough the injection of a MockInfo
		 * @param value String
		 * @return Class
		 */ 
		private function generateClassFromInfo(value:String):Class{
			
			var result:Class;
			
			for(var i:int = 0; i < _info.paths.length; i++){
				
				try{
				
					result = getDefinitionByName(_info.paths[i] + value) as Class;	
					
				}catch(e:Error){
					
					result = null;
					
				}
				
			}
			
			return result;	
			
			
		}
		
		/**
		 * Recover the default value for a primitive trough the Primitives class
		 * @param value String
		 * @return *
		 */ 
		private function getDefaultPrimitive(value:String ):*{
			
			var result:*;
			
			for(var i:int = 0; i < Primitives.primitiveDefaultValues.entries.length; i++){
				
				if(Primitives.primitiveDefaultValues.entries[i].name == value){
					
					result = Primitives.primitiveDefaultValues.entries[i].value;
					break;
					
				}
				
			}
			
			return result;
			
		}
		
		/**
		 * Check if one of the data types defined for the mock are primitive or not
		 * @param type String
		 * @return Boolean
		 */ 
		private function isPrimitive(type:String):Boolean{
			
			var data:Array = Primitives.primitiveTypes.getEntriesByName("name").source
			
			return search(type, data);
			
		}
		
		/**
		 * Check if one of the properties defined as random have a specific range of values to follow
		 * The Pair here contains IGenerateRange implementors
		 * @param value String
		 * @param data Vector.<Pair>
		 * @return *
		 */ 
		private function searchPredefined(value:String, data:Vector.<Pair>):*{
			
			var result:* = null;
			
			if(data){
			
				for(var i:int = 0; i < data.length; i++){
					
					if((data[i] as Pair).name == value){
						
						result = (data[i] as Pair).value;
						break;
						
					}
					
				}
			
			}
			
			return result;
			
		}
		
		/**
		 * Recover the class name without any packaging or namespace
		 * @param value String
		 * @return String
		 */ 
		private function getSimpleName(value:String):String{
			
			var shift:int = value.length - 1;
			return value.substring(value.indexOf("::") + 2, shift);
		
		}
		
		/**
		 * Search if a string is in an Array, it's used to manage the 
		 * array the Mocker use to store a reference to the properties
		 * of the class it's mocking
		 * @param value String
		 * @param data Array
		 * @return Boolean
		 */ 
		private function search(value:String, data:Array):Boolean{
			
			var result:Boolean = false;
			
			if(data){
			
				for(var i:int = 0; i < data.length; i++){
					
					if(data[i] == value){
						
						result = true;
						break;
						
					}
					
				}
			
			}
		
			return result;
			
		}

	}
}