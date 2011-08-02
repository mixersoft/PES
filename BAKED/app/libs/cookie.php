<?php
/*
 * use Cookie class for storing commonly accessed session data
 * initialized in app_controller
 */
class Cookie {
	public static $userid = null;	// id of logged in user
	public static $uuid = null;		// id of current item
	public static $role = null;
	public static $displayname = null;
	public static $auth = null;
	public static $context = array('class'=>null, 'uuid'=>null);
	public static $breadcrumb = null;		// DEPRECATE. use $trail instead
	public static $controllerAlias = null;	// controller name before Routing
	public static $data = array();
	public static $lookup = array();
	public static $trail = array();	// stack of $values with uniqueness by $key


	/**
	 * persist Cookie::$context into Session for subsequent pageviews
	 * @param $class mixed. array or class of uuid
	 * @param $id
	 * @param $extras
	 * @return unknown_type
	 */
	public static function set_context($class, $uuid=null, $extras=null) {
		if (is_array($class)) {
			Cookie::$context = array_merge(Cookie::$context, $class);
		} else {
			if ($class ===null || $class ==='remove') {
				Cookie::$context = array('class'=>null, 'uuid'=>null);
			} else if ($uuid) {	
				Cookie::$context = array('class'=>$class, 'uuid'=>$uuid);
			} else {
				// copy from Cookie::$trail
				Cookie::$context = array('class'=>$class)+Cookie::$trail[$class];
			}
			if (is_array($extras)) {
				Cookie::$context = array_merge($extras, Cookie::$context);
			}
		}
//		$_SESSION['Current']['cookie_context'] = Cookie::$context;
		Session::write('lookup.context', Cookie::$context);
		return Cookie::$context;
	}
	/*
	 * use Cookie::push($key, $value) to push onto Cookie::$last, saves to session
	 * 		guarantees that last $value is saved for a given $key, while preserving order across $keys
	 * use array_pop() to find most recent $value by $key as stored in Cookie::$last
	 */
	public static function trail_push($key, $value) {
		if (isset(Cookie::$trail[$key])) unset(Cookie::$trail[$key]);
		Cookie::$trail[$key]=$value;
		if (isset(Cookie::$context['uuid']) && Cookie::$context['uuid'] == Cookie::$trail[$key]['uuid'] ) {
			Cookie::$context=array('class'=>$key)+$value;
//			$_SESSION['Current']['cookie_context'] = Cookie::$context;
			Session::write('lookup.context', Cookie::$context);
		}
//		$_SESSION['Current']['cookie_trail'] = Cookie::$trail;
		Session::write('lookup.trail', Cookie::$trail);
	}
	// replace trail, but do not change order, trail_last() does not change
	public static function trail_replace($key, $value) {
		if (isset(Cookie::$trail[$key])) Cookie::$trail[$key]=$value;
//		$_SESSION['Current']['cookie_trail'] = Cookie::$trail;
		Session::write('lookup.trail', Cookie::$trail);
	}
	public static function trail_append($key , $value) {
		if (!is_array($value)) $value = array($value);

		if (isset(Cookie::$trail[$key])) {
			Cookie::$trail[$key]=array_merge(Cookie::$trail[$key],$value);
			if (isset(Cookie::$context['uuid']) && Cookie::$context['uuid'] == Cookie::$trail[$key]['uuid'] ) {
				Cookie::$context=array_merge(Cookie::$context,$value);
				Session::write('lookup.context', Cookie::$context);
//				$_SESSION['Current']['cookie_context'] = Cookie::$context;
			}
//			$_SESSION['Current']['cookie_trail'] = Cookie::$trail;
			Session::write('lookup.trail', Cookie::$trail);
		}
	}	
	public static function trail_last() {
		return end(Cookie::$trail);
	}
	public static function write($key, $value) {
//		$_SESSION['Current'][$key] = $value;
		Session::write("Current.{$key}", $value);
	}
}
?>