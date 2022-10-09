<?php
namespace nx\parts\cache;

trait hash{
	use redis;

	/**
	 * @param string $key    写入缓存的key
	 * @param int    $ttl    缓存时长秒数
	 * @param string $config 配置文件名
	 * @return mixed
	 * @throws \RedisException
	 */
	public function cacheHash(string $key, int $ttl=600, string $config='default'):\nx\helpers\cache\hash{
		return new \nx\helpers\cache\hash($this->cache($config), $key, $ttl);
	}
}