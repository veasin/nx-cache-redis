<?php
namespace nx\helpers\cache;

use nx\parts\callApp;
use Redis;
use RedisException;

class hash{
	use callApp;

	protected readonly Redis $cache;
	protected readonly string $key;
	protected readonly int $ttl;
	public function __construct(Redis $cache, string $key, int $ttl=600){
		$this->cache=$cache;
		$this->key=$key;
		$this->ttl=$ttl;
	}
	/**
	 * 获取全部的hash和对应的值，如果不存在任何hash，会执行回调并写入回调结果并返回
	 * @param mixed|null $callback
	 * @return array
	 */
	public function getAll(mixed $callback=null):array{
		try{
			$data=$this->cache->hGetAll($this->key);
		}catch(RedisException $e){
			$this->log('cache hash error: '.$e->getMessage());
			$data=[];
		}
		$_data=[];
		foreach($data as $index=>$datum){
			$_data[$index]=json_decode($datum, true);
		}
		if(0 === count($_data) && is_callable($callback)){
			$_data=call_user_func($callback, $this);
			if(is_array($_data)){
				$this->setMore($_data);
			}else $_data=[];
		}
		return $_data;
	}
	/**
	 * 一次删除多个hash key
	 * @param string ...$fields
	 * @return void
	 */
	public function delete(string ...$fields):void{
		try{
			$this->cache->hDel($this->key, ...$fields);
		}catch(RedisException $e){
			$this->log('cache hash error: '.$e->getMessage());
		}
	}
	/**
	 * 判断hash 可以是否都存在
	 * @param string ...$fields
	 * @return int
	 */
	public function exists(string ...$fields):int{
		try{
			return $this->cache->hExists($this->key, ...$fields);
		}catch(RedisException $e){
			$this->log('cache hash error: '.$e->getMessage());
			return -1;
		}
	}
	/**
	 * 获取指定hash key的值，如不存在会执行回调，或设置为指定值
	 * @param string     $field
	 * @param mixed|null $callback 如果为回调会执行，否则会直接返回此值
	 * @return mixed
	 */
	public function get(string $field, mixed $callback=null):mixed{
		try{
			$data=$this->cache->hGet($this->key, $field);
			if(is_string($data)) $data=json_decode($data, true);
		}catch(RedisException $e){
			$this->log('cache hash error: '.$e->getMessage());
			$data=null;
		}
		if((null === $data || false === $data) && is_callable($callback)){
			$data=call_user_func($callback, $field, $this);
			$this->set($field, $data);
		}
		return $data;
	}
	/**
	 * 返回所有hash key的数组
	 * @return array
	 */
	public function keys():array{
		try{
			return $this->cache->hKeys($this->key);
		}catch(RedisException $e){
			$this->log('cache hash error: '.$e->getMessage());
			return [];
		}
	}
	/**
	 * 返回已存在的hash key的个数
	 * @return int
	 */
	public function length():int{
		try{
			return $this->cache->hLen($this->key);
		}catch(RedisException $e){
			$this->log('cache hash error: '.$e->getMessage());
			return -1;
		}
	}
	/**
	 * 一次获取多个hash key的值并拼装为数组
	 * @param string ...$fields
	 * @return array
	 */
	public function getMore(string ...$fields):array{
		try{
			$data=$this->cache->hMGet($this->key, $fields);
		}catch(RedisException $e){
			$this->log('cache hash error: '.$e->getMessage());
			$data=[];
		}
		$_data=[];
		foreach($data as $index=>$datum){
			$_data[$index]=json_decode($datum, true);
		}
		return $_data;
	}
	/**
	 * 一次性设置多个hash key的值，json序列化
	 * @param array $set
	 * @return bool
	 */
	public function setMore(array $set):bool{
		$_set=[];
		foreach($set as $index=>$item){
			$_set[$index]=json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}
		try{
			return $this->cache->hMSet($this->key, $_set);
		}catch(RedisException $e){
			$this->log('cache hash error: '.$e->getMessage());
			return false;
		}
	}
	/**
	 * 设置key的hash key 的值
	 * @param string $field
	 * @param mixed  $value
	 * @return bool
	 */
	public function set(string $field, mixed $value):bool{
		try{
			return $this->cache->hSet($this->key, $field, json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		}catch(RedisException $e){
			$this->log('cache hash error: '.$e->getMessage());
			return false;
		}
	}
	/**
	 * 如果指定的field不存在就设置
	 * @param string $field hash key
	 * @param mixed  $value 值
	 * @return bool
	 */
	public function setNotExits(string $field, mixed $value):bool{
		try{
			return $this->cache->hSetNx($this->key, $field, json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		}catch(RedisException $e){
			$this->log('cache hash error: '.$e->getMessage());
			return false;
		}
	}
	/**
	 * 返回此key的所有的值数组
	 * @return array
	 */
	public function values():array{
		try{
			$data=$this->cache->hVals($this->key);
		}catch(RedisException $e){
			$this->log('cache hash error: '.$e->getMessage());
			$data=[];
		}
		$_data=[];
		foreach($data as $index=>$datum){
			$_data[$index]=json_decode($datum, true);
		}
		return $_data;
	}
	/**
	 * 如果指定的key不存在，就执行。如果回调函数有返回值，默认会更新到此key上
	 * @param callable|null $callback 回调函数
	 * @param bool          $update 是否更新返回值
	 * @return void
	 */
	public function notExitsSet(callable $callback=null, bool $update =true):void{
		try{
			if($this->cache->exists($this->key)) return ;
		}catch(RedisException $e){
			$this->log('cache hash error: '.$e->getMessage());
		}
		$data =is_callable($callback) ?call_user_func($callback) :$callback;
		if(!$data && $update){
			try{
				$this->cache->set($this->key, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $this->ttl);
			}catch(RedisException $e){
				$this->log('cache hash error: '.$e->getMessage());
			}
		}
	}
}