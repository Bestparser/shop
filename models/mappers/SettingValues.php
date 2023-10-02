<?php

class Shop_Model_Mapper_SettingValues extends Lv7CMS_Mapper_Abstract
{

	protected $_domainName = 'Shop_Model_SettingValue';
	protected $_tableName = 'Shop_Model_DbTable_SettingValues';


	public function getList($scheme)
	{
		$result = $this->_table->getList($scheme);
		return $this->_rowsetToObjList($result);
	}

	public function findByKey($scheme, $key)
	{
		$row = $this->_table->findByKey($scheme, $key);
		return $this->_mapRowToObject($row, $this->createObject());
	}

	public function update($scheme, $key = null, $value = null)
	{
		if ($obj = $this->findByKey($scheme, $key)) {
			$obj->value = $value;
			parent::update($obj);
		} else {
			$obj = $this->createObject();
			$obj->scheme = $scheme;
			$obj->key = $key;
			$obj->value = $value;
			parent::insert($obj);
		}
	}


}




