<?php

	class XCache extends KilliCache
	{
		public static function available()
		{
			return extension_loaded("xcache");
		}

		public static function get($key, $prefix = null, $with_prefix_id = False)
		{
			if(XCache::available())
			{
				if(!is_null($prefix))
				{
					KilliCache::transformkey($prefix, $key, $with_prefix_id);
				}

				if(XCache::exists($key))
				{
					return xcache_get($key);
				}
				else
				{
					XCache::popinstack($key);
				}
			}

			return False;
		}

		public static function set($key, $value, $prefix = null, $with_prefix_id = False, $expire = 0)
		{
			if(XCache::available())
			{
				if(is_object($value) || is_resource($value))
				{
					throw new Exception("XCache module does not support object and resource registry");
					return False;
				}

				if(!is_null($prefix))
				{
					KilliCache::transformkey($prefix, $key, $with_prefix_id);
				}

				$expire *= 60;
				XCache::pushinstack($key);
				return xcache_set($key, $value, $expire);
			}

			return False;
		}

		public static function exists($key)
		{
			if(XCache::available())
			{
				return xcache_isset($key);
			}

			return False;
		}

		public static function delete($key, $prefix = null, $with_prefix_id = False)
		{
			if(XCache::available())
			{
				if(!is_null($prefix))
				{
					KilliCache::transformkey($prefix, $key, $with_prefix_id);
				}

				XCache::popinstack($key);
				return xcache_unset($key);
			}

			return False;
		}

		public static function clear($prefix = null)
		{
			if(XCache::available())
			{
				$stack = XCache::getstack();

				foreach($stack as $key => $value)
				{
					if(is_null($prefix) || preg_match("/^$prefix/", $key))
					{
						XCache::delete($key);
					}
				}

				return True;
			}

			return False;
		}

		public static function getstack()
		{
			$stack = xcache_get("__stack");

			if($stack === False)
			{
				return array();
			}

			return $stack;
		}

		protected static function pushinstack($key)
		{
			$stack = XCache::getstack();
			$stack[$key] = 1;

			xcache_unset("__stack");
			xcache_set("__stack", $stack);
		}

		protected static function popinstack($key)
		{
			$stack = XCache::getstack();

			if(isset($stack[$key]))
			{
				unset($stack[$key]);
			}

			xcache_unset("__stack");
			Xcache_set("__stack", $stack);
		}
	}


