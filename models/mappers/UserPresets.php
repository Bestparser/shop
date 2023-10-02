<?php

class Shop_Model_Mapper_UserPresets extends Lv7CMS_Mapper_Abstract
{

	protected $_domainName = 'Shop_Model_UserPreset';
	protected $_tableName = 'Shop_Model_DbTable_UserPresets';

	protected $_ignoreProp = array('params');

	public function getList($userId)
	{
		$result = $this->_table->getList($userId);
		return $this->_rowsetToObjList($result);
	}
	
	public function createObject()
	{
		$obj = parent::createObject();
		$obj->params = new Shop_Service_UserPresetParamsLazyLoader();
		return $obj;
	}

	public function insert($obj)
	{
		$obj = parent::insert($obj);

		if (isset($obj->params) && !($obj->params instanceof Shop_Service_UserPresetParamsLazyLoader)) {
			$this->_getOptionLoader()->setValues($obj->id, (array) $obj->params);
		}

		return $obj;
	}

	public function update($obj)
	{
		$obj = parent::update($obj);

		if (isset($obj->params) && !($obj->params instanceof Shop_Service_UserPresetParamsLazyLoader)) {
			$this->_getOptionLoader()->setValues($obj->id, (array) $obj->params);
		}
		
		return $obj;
	}

	public function delete($obj) 
	{
		parent::delete($obj);
		$this->_getOptionLoader()->deleteValues($obj->id);
	}

	public function rowToObject($row)
	{
		return $this->_mapRowToObject($row, $this->createObject());
	}
	
	protected function _getOptionLoader()
	{
		if (null == $this->_optionLoader) {
			$this->_optionLoader = new Lv7_Service_EntityOptions(new Shop_Model_Mapper_UserPresetParams());
		}
		return $this->_optionLoader;
	}	

}




