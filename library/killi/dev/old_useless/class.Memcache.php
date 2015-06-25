<?php

	class XMemcache extends KilliCache
	{
		private static $conn = False;
		public static function available()
		{
			return extension_loaded("memcache");
		}

		public static function server_status()
		{
			if(XMemcache::available() && MEMCACHE_ENABLED)
			{
				return memcache_get_server_status(XMemcache::$conn, MEMCACHE_HOST, MEMCACHE_PORT);
			}

			return 0;
		}

		public static function connect()
		{
			if(XMemcache::available() && !is_resource(XMemcache::$conn))
			{
				return XMemcache::$conn = memcache_connect(MEMCACHE_HOST, MEMCACHE_PORT);
			}

			return False;
		}

		public static function disconnect()
		{
			if(XMemcache::available() && XMemcache::server_status() > 0)
			{
				return memcache_close(XMemcache::$conn);
			}

			return False;
		}

		public static function get($key, $prefix = null, $with_prefix_id = False)
		{
			if(XMemcache::available() && XMemcache::server_status() > 0)
			{
				if(!is_null($prefix))
				{
					KilliCache::transformkey($prefix, $key, $with_prefix_id);
				}

				if(XMemcache::exists($key))
				{
					return memcache_get(XMemcache::$conn, $key);
				}
				else
				{
					XMemcache::popinstack($key);
				}
			}

			return False;
		}

		public static function set($key, $value, $prefix = null, $with_prefix_id = False, $expire = 0)
		{
			if(XMemcache::available() && XMemcache::server_status() > 0)
			{
				if(is_resource($value))
				{
					throw new Exception("XMemcache doest not support resource registry");
				}

				if(!is_null($prefix))
				{
					KilliCache::transformkey($prefix, $key, $with_prefix_id);
				}

				$expire *= 60;
				XMemcache::pushinstack($key);

				if(!XMemcache::exists($key))
				{
					return memcache_set(XMemcache::$conn, $key, $value, 0, $expire);
				}
				else
				{
					return memcache_replace(XMemcache::$conn, $key, $value, 0, $expire);
				}
			}

			return False;
		}

		public static function exists($key)
		{
			if(XMemcache::available() && XMemcache::server_status() > 0)
			{
				return memcache_get(XMemcache::$conn, $key) !== False;
			}

			return False;
		}

		public static function delete($key, $prefix = null, $with_prefix_id = False)
		{
			if(XMemcache::available() && XMemcache::server_status() > 0)
			{
				if(!is_null($prefix))
				{
					KilliCache::transformkey($prefix, $key, $with_prefix_id);
				}

				XMemcache::popinstack($key);
				return memcache_delete(XMemcache::$conn, $key);
			}

			return False;
		}

		public static function clear($prefix = null)
		{
			if(XMemcache::available() && XMemcache::server_status() > 0)
			{
				$stack = XMemcache::getstack();

				if(is_null($prefix))
				{
					memcache_flush(XMemcache::$conn);
					return True;
				}

				foreach($stack as $key => $value)
				{
					if(preg_match("/$key/", $prefix))
					{
						XMemcache::delete($key);
					}
				}

				return True;
			}

			return False;
		}

		public static function getstats()
		{
			if(XMemcache::available() && XMemcache::server_status() > 0)
			{
				$stats = memcache_get_extended_stats(XMemcache::$conn);

				return $stats[MEMCACHE_HOST.':'.MEMCACHE_PORT];
			}

			return False;
		}

		public static function getstack()
		{
			$stack = memcache_get(XMemcache::$conn, "__stack");

			if($stack === False)
			{
				return array();
			}

			return $stack;
		}

		protected static function pushinstack($key)
		{
			$stack = XMemcache::getstack();
			$stack[$key] = 1;

			memcache_delete(XMemcache::$conn, "__stack");
			memcache_set(XMemcache::$conn, "__stack", $stack, MEMCACHE_COMPRESSED);
		}

		protected static function popinstack($key)
		{
			$stack = XMemcache::getstack();

			if(isset($stack[$key]))
			{
				unset($stack[$key]);
			}

			memcache_delete(XMemcache::$conn, "__stack");
			memcache_set(XMemcache::$conn, "__stack", $stack, MEMCACHE_COMPRESSED);
		}
	}


