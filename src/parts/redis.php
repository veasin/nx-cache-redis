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
	 * @throws \RedisException
	 */
	public function cache(string $name='default'):?\Redis{
		if(!array_key_exists($name, $this->cache_redis)){
			$config=($this['cache/redis'] ?? [])[$name] ?? null;
			if(null === $config){
				$this->throw(500, "cache[$name] config error.");
			}
			$redis=new \Redis();
			if(!empty($config)){
				$redis->connect($config['host'], $config['port'] ?? 6379, $config['timeout'] ?? 1);
				if(array_key_exists('auth', $config)) $redis->auth($config['auth']);
				if(array_key_exists('select', $config)) $redis->select($config['select']);
			}
			$this->cache_redis[$name]=$redis;
		}
		return $this->cache_redis[$name];
	}
	/**
	 * @param string $key      写入缓存的key
	 * @param null   $callback 回调函数
	 * @param int    $ttl      缓存时长秒数
	 * @param string $config   配置文件名
	 * @return mixed
	 * @throws \RedisException
	 */
	public function cacheJson(string $key, mixed $callback=null, int $ttl=600, string $config='default'):mixed{
		$Cache=$this->cache($config);
		$data=$Cache->get($key);
		if(!empty($data)) return json_decode($data, true);
		if(null === $callback) $this->throw(500, '无效的回调函数');
		if(is_callable($callback)){
			$data=call_user_func($callback);
		}else $data=$callback;
		$Cache->set($key, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $ttl);
		return $data;
	}
}