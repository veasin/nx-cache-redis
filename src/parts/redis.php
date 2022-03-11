<?php
namespace nx\parts\cache;

/**
 * @method throw(int $int, string $string)
 */
trait redis{
	protected array $cache_redis=[];//缓存
	/**
	 * @param string $name app->setup['cache/redis']
	 * @return \Redis|null
	 */
	public function cache(string $name='default'):?\Redis{
		if(!array_key_exists($name, $this->cache_redis)){
			$config =($this->setup['cache/redis']??[])[$name] ?? null;
			if(null ===$config){
				$this->throw(500, "cache[{$name}] config error.");
			}
			$redis =new \Redis();
			if(!empty($config)){
				$redis->connect($config['host'],
					$config['port'] ?? 6379,
					$config['timeout'] ?? 1,
				);
				if(array_key_exists('auth', $config)) $redis->auth($config['auth']);
				if(array_key_exists('select', $config)) $redis->select($config['select']);
			}
			$this->cache_redis[$name] =$redis;
		}
		return $this->cache_redis[$name];
	}
}