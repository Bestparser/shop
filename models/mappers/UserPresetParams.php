<?php

class Shop_Model_Mapper_UserPresetParams extends Lv7CMS_Mapper_Abstract
implements Lv7_Mapper_EntityOptions_Interface
{

	protected $_domainName = 'Shop_Model_UserPresetParam';
	protected $_tableName = 'Shop_Model_DbTable_UserPresetParams';


	public function findByEntity($presetId)
	{
		$result = $this->_table->findByEntity($presetId);
		$params = $this->_rowsetToObjList($result);
		return $params;
	}

	public function deleteByEntity($presetId)
	{
		$params = $this->findByEntity($presetId);
		if (is_array($params)) {
			foreach ($params as $param) {
				$this->delete($param);
			}
		}
	}

	public function setValue($presetId, $key, $value)
	{
		$param = $this->_table->findByEntityAndKey($presetId, $key);
		if (empty($param)) {
			$this->addValue($presetId, $key, $value);
		} else {
			$param->value = $value;
			$this->update($param);
		}
	}

	public function addValue($presetId, $key, $value)
	{
		$param = $this->createObject();
		$param->preset = $presetId;
		$param->key = $key;
		$param->value = $value;
		$this->insert($param);

	}
	
}




