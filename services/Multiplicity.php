<?php

class Shop_Service_Multiplicity
{
	
	protected $_mapper = null;
	protected $_keys = null;
	
	public function check($posName)
	{
		$keys = $this->_getKeys();
		if (!$keys) {
			return false;
		}
		foreach ($keys as $key => $info) {
			$key = explode(' ', $key);
			$hit = true;
			foreach ($key as $word) {
				if (stripos($posName, $word) === false) {
					$hit = false;
					break;
				}
			}
			if ($hit) {
				return $info;
			}
		}
		return false;
	}
	
	protected function _getKeys()
	{
		if ($this->_keys === null) {
			$mapper = $this->_getMapper();
			$items = $mapper->getList(true);
			if ($items) {
				foreach ($items as $item) {
					$keys = explode("\n", str_replace("\r", "", $item->keys));
					if (is_array($keys)) {
						foreach ($keys as $key) {
							$this->_keys[$key] = array('name' => $item->name, 'mult' => $item->mult);
						}
					}
				}
			}
		}
		return $this->_keys;
	}
	
	protected function _getMapper()
	{
		if ($this->_mapper === null) {
			$this->_mapper = new Shop_Model_Mapper_MultKeys();
		}
		return $this->_mapper;
	}
	
}