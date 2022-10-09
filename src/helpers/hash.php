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
	public function delete(string ...$fields):void{
		try{
			$this->cache->hDel($this->key, ...$fields);
		}catch(RedisException $e){
			$this->log('cache hash error: '.$e->getMessage());
		}
	}
	public function exists(string ...$fields):int{
		try{
			return $this->cache->hExists($this->key, ...$fields);
		}catch(RedisException $e){
			$this->log('cache hash error: '.$e->getMessage());
			return -1;
		}
	}
	public function get(string $field, mixed $callback=null):mixed{
		try{
			$data=$this->cache->hGet($this->key, $field);
		}catch(RedisException $e){
			$this->log('cache hash error: '.$e->getMessage());
			$data=null;
		}
		if((null === $data || false === $data) && is_callable($callback)){
			$data=call_user_func($callback, $field, $this);
			$this->set($field, $data);
		}else $data=json_decode($data, true);
		return $data;
	}
	public function keys():array{
		try{
			return $this->cache->hKeys($this->key);
		}catch(RedisException $e){
			$this->log('cache hash error: '.$e->getMessage());
			return [];
		}
	}
	public function length():int{
		try{
			return $this->cache->hLen($this->key);
		}catch(RedisException $e){
			$this->log('cache hash error: '.$e->getMessage());
			return -1;
		}
	}
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
	public function set(string $field, mixed $value):bool{
		try{
			return $this->cache->hSet($this->key, $field, json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		}catch(RedisException $e){
			$this->log('cache hash error: '.$e->getMessage());
			return false;
		}
	}
	public function setNotExits(string $field, mixed $value):bool{
		try{
			return $this->cache->hSetNx($this->key, $field, json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		}catch(RedisException $e){
			$this->log('cache hash error: '.$e->getMessage());
			return false;
		}
	}
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
}