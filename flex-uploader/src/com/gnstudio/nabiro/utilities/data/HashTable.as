package com.gnstudio.nabiro.utilities.data
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
	
	import com.gnstudio.nabiro.utilities.data.events.HashTableEvent;
	import com.gnstudio.nabiro.utilities.mock.core.Pair;
	
	import flash.events.Event;
	import flash.events.EventDispatcher;
	import flash.events.IEventDispatcher;
	import flash.utils.Dictionary;
	
	import mx.collections.ArrayCollection;
	import mx.collections.ICollectionView;
	
	public class HashTable implements IEventDispatcher
	{
		
		protected var table:Dictionary;
		protected var duplicates:Dictionary;
		protected var lookUp:Dictionary;
		
		private var eventDispatcher:EventDispatcher;
		
		private const MIN_TABLE_SIZE:int =10;
		
		private var initSize:int;
		private var maxSize:int;
		private var currentSize:int;
		
		private var first:HashItem;
		private var last:HashItem;
		private var current:HashItem;
		
		public function HashTable(size:int = 500){
			
			eventDispatcher = new EventDispatcher();
			
			table = new Dictionary(true);
			duplicates = new Dictionary(true);
			lookUp = new Dictionary(true);
			
			initSize = maxSize = Math.max(MIN_TABLE_SIZE, size);
			currentSize = 0;
			
			var item:HashItem = new HashItem();

			first = last = item;

			var k:int = initSize + 1;

			for (var i:int = 0; i < k; i++){

				item.next = new HashItem();

				item = item.next;

			}

			last = item;
			
		}
		
		public function insert(key:*, obj:*):Boolean{
			
			var duplicate:Boolean;
			
			if (key == null)  return false;
			if (obj == null)  return false;

			if (table[key]){
				
				dispatchEvent(new HashTableEvent(HashTableEvent.DUPLICATE_ADDED, new Pair(key, obj)));
				duplicate = true;
				
			} 
			
			if (currentSize++ == maxSize){

				var k:int = (maxSize += initSize) + 1;

				for (var i:int = 0; i < k; i++)	{

					last.next = new HashItem();
					last = last.next;

				}
				
				dispatchEvent(new HashTableEvent(HashTableEvent.SIZE_INCREASED, new Pair(key, obj)));

			}
			
			var item:HashItem = first;

			first = first.next;

			item.name = key;
			item.value = obj;

			item.next = current;

			if (current) current.previous = current;

			current = item;
			
			if(duplicate){
				
				duplicates[key] = item;
				currentSize--;
				
			}else{
				
				table[key] = item;
				
			}
			
			lookUp[obj] ? lookUp[obj]++ : lookUp[obj] = 1;
			
			return true;
			
		}
		
		public function find(key:*):*{

			var item:HashItem = table[key];
			var duplicate:HashItem = duplicates[key];
				
			if(duplicate){
					
				dispatchEvent(new HashTableEvent(HashTableEvent.KEY_FOUND_IN_DUPLICATES, new Pair(key, duplicate.value)));
				
			}
			
			if(item){
			
				return item.value;
				
			}else{
				
				return null;
				
			}

		}
		
		public function remove(key:*):*	{

			var item:HashItem = table[key];

			if (item){

				var obj:* = item.value;

				delete table[key];

				if (item.previous) item.previous.next = item.next;
				if (item.next) item.next.previous = item.previous;
				if (item == current) current = item.next;

				item.previous = null;
				item.next = null;

				last.next = item;
				last = item;

				if (--lookUp[obj] <= 0){

					delete lookUp[obj];
					
				}

				if (--currentSize <= (maxSize - initSize)){

					var k:int = (maxSize -= initSize) + 1;

					for (var i:int = 0; i < k; i++){

						first = first.next;
						
					}

				}

				return obj;

			}

			return null;

		}
		
		
        /**
		 * Return the entries of the Dictionary as an ICollectionView
		 * @return ICollectionView
		 */ 
        public function getICollectionView():ICollectionView{
        	        	
        	var list:ArrayCollection = new ArrayCollection();

            for (var key:* in table){
            	
                list.addItem(table[key]);
                
            }
            
            var view:ICollectionView = list as ICollectionView;
            
            return view;
        	
        }
		
		
		public function get size():int{

			return currentSize;

		}

		public function isEmpty():Boolean{

			return currentSize == 0;

		}
		
		public function contains(obj:*):Boolean{

			return lookUp[obj] > 0;

		}
		
		public function get firstItem():*{
			
			return first.next;
			
		}
		
		public function get lastItem():*{
			
			return last.previous;
			
		}

		
		public function dump():String{

			var s:String = "HashTable:\n";

			for each (var p:HashItem in table){

				s += "[key: " + p.name + ", val:" + p.value + "]\n";
				
			}

			return s;

		}

		
		
		/************************************
		* IEventDispatcher immplementation
		*************************************/ 
		public function addEventListener(type:String, listener:Function, useCapture:Boolean=false, priority:int=0.0, useWeakReference:Boolean=false):void{
			
			eventDispatcher.addEventListener(type, listener, useCapture, priority, useWeakReference);
			
		}
		
		public function removeEventListener(type:String, listener:Function, useCapture:Boolean=false):void	{
			
			eventDispatcher.removeEventListener(type, listener, useCapture);
			
		}
		
		public function dispatchEvent(event:Event):Boolean {
			
			return eventDispatcher.dispatchEvent(event);
		}
		
		public function hasEventListener(type:String):Boolean {
			
			return eventDispatcher.hasEventListener(type);
		}
		
		public function willTrigger(type:String):Boolean {
			
			return eventDispatcher.willTrigger(type);
			
		}

	}
}