<?php



	abstract class KilliCache
	{
		public static function getuser()
		{
			if(isset($_SESSION['_USER']))
			{
				return $_SESSION['_USER'];
			}

			return false;
		}

		public static function transformkey($prefix, &$key, $with_prefix_id = True)
		{
			$user = KilliCache::getuser();
			
			if($user === False)
				return $key;

			if(!isset($user[$prefix . '_id']))
			{
				throw new Exception("User key $prefix does not exists");
			}

			$key = $prefix . ($with_prefix_id ? '_' . $user[$prefix . '_id'] : '') . '_' . $key;
		}


		public static function available(){}
		public static function get($key, $prefix = null, $with_prefix_id = False){}
		public static function set($key, $value, $prefix = null, $with_prefix_id = False, $expire = 0){}
		public static function exists($key){}
		public static function delete($key, $prefix = null, $with_prefix_id = False){}
		public static function clear($prefix = null){}
		public static function getstack(){}
		protected static function pushinstack($key){}
		protected static function popinstack($key){}

	}


