<?php
class Cache
{
	private $repo;
	private $ext = 'cache';
	
	public function __construct($config)
	{
		$repo = 'MainCache';
		
		if (isset($config['repo']))
		{
			$repo = $config['repo'];
		}
			
		if (isset($config['ext']))
		{
			$this->ext = $config['ext'];
		}
		
		$repo = str_replace("\\", "/", $repo); 
		
		if (substr($repo, -1) != "/")
			$repo .= "/";
			
		$this->repo = $repo;
	}

	/**
	 * Decode a cache file safely.
	 *
	 * Old versions of WarcryCMS used PHP serialize()/unserialize() for cache files.
	 * That is unsafe if a cache file is ever poisoned because unserialize() can
	 * instantiate PHP objects. We now use JSON only. Old serialized cache files are
	 * treated as invalid and will be rebuilt automatically by the CMS.
	 */
	private function decode_cache($cache_str)
	{
		if (!is_string($cache_str) || trim($cache_str) === '')
			return false;

		$cache = json_decode($cache_str, true);

		if (json_last_error() !== JSON_ERROR_NONE || !is_array($cache))
			return false;

		if (!isset($cache['expires']) || !array_key_exists('data', $cache))
			return false;

		$cache['expires'] = (int)$cache['expires'];

		return $cache;
	}

	private function cache_file($var)
	{
		return $this->repo . $var . '_' . $this->ext;
	}
	
	public function get($var)
	{
		$cache_file = $this->cache_file($var);
		$cache_str = @file_get_contents($cache_file);
		
		if (empty($cache_str))
			return false;
			
		$cache = $this->decode_cache($cache_str);

		if ($cache === false)
		{
			@file_put_contents($cache_file, '');
			return false;
		}
			
		if ($cache['expires'] < time())
		{
			@file_put_contents($cache_file, '');
			return false;
		}
		
		return $cache['data'];
	}
	
	public function store($name, $val, $expires = 600, $group = '')
	{
		$cache = array('expires' => time() + (int)$expires, 'data' => $val);
		
		if ($group != '')
			$cache['group'] = $group;
			
		$cache_str = json_encode($cache, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		
		if ($cache_str === false)
			return false;
		
		return @file_put_contents($this->cache_file($name), $cache_str) !== false;
	}
	
	public function clear($var)
	{
		if (!is_array($var))
			$var = array($var);
			
		foreach ($var as $v)
		{
			file_put_contents($this->cache_file($v), '');
		}
	}
	
	public function clear_all()
	{
		$ext = '_' . $this->ext;
		
		if ($handle = opendir($this->repo)) 
		{
		    while (false !== ($file = readdir($handle)))
			{
    			if (substr($file, strlen($ext) * -1) === $ext && $file != '.' && $file != '..')
		    		file_put_contents($this->repo . $file, '');
		    }
		    closedir($handle);
		}
	}
	
	public function clear_group($group)
	{
		$ext = '_' . $this->ext;
		
		if ($handle = opendir($this->repo)) 
		{
		    while (false !== ($file = readdir($handle)))
			{
    			if (substr($file, strlen($ext) * -1) === $ext && $file != '.' && $file != '..')
    			{
    				$cache_file = $this->repo . $file;
    				$cache_str = file_get_contents($cache_file);
    				$cache = $this->decode_cache($cache_str);
					
    				if ($cache === false)
    				{
    					file_put_contents($cache_file, '');
    					continue;
    				}
					
    				if (!isset($cache['group']) || $cache['group'] != $group)
    					continue;
						
		    		file_put_contents($cache_file, '');
    			}
		    }
		    closedir($handle);
		}
	}
	
}
