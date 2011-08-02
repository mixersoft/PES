<?php 
if (!defined('STAGING_SLOTS')) {
	define('STAGING_SLOTS', Configure::read('Staging.slots'));
}
class Shard {
	public static $algo='md5';
	public static $prefix='stage';
	public static $slots = STAGING_SLOTS;	//top level folders for photos

	
	/**
	 * map a slot number to a storage destination
	 * 		use simple mapping for now, i.e. stagepath['baseurl']."/{$prefix}XX/"
	 */
	private static function mapSlotToDest ($slot_id) {
		return Shard::$prefix.$slot_id;
	}
	
	/*
	 * get a slot for a given key. uses Configure::read class to 
	 * 	determine current config for slots.
	 */
	public static function getSlot($key, $slots=null) {
		$slots = $slots==null ? Shard::$slots : $slots; 
		// path = /Summer2009/img001.jpg
		$shard = hash( Shard::$algo, $key);
		$key = hexdec(substr($shard, -4));	// take the last 4 digits of hex md5
		$slot_id = $key % $slots;
		return Shard::mapSlotToDest($slot_id);
	}
	
	/**
	 * get reassigned slots for a given key when the number of slots has changed
	 * 		use return data to relocate keys
	 */
	public static function reassign($fromSlots, $toSlots, $key) {
		$src = Shard::getSlot($key, $fromSlots);
		$dest = Shard::getSlot($key, $toSlots);
		if ($src==$dest) return array();
		else return array('src'=>$src, 'dest'=>$dest, 'key'=>$key);
	}
}
?>
