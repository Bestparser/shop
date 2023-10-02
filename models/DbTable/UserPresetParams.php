<?php

class Shop_Model_DbTable_UserPresetParams extends Zend_Db_Table_Abstract
{

    protected $_name = 'shop_user_preset_params';
	protected $_primary = 'id';


	public function findByEntity($presetId)
	{
		$select = $this->select();
		$select->where('`preset` = ?', $presetId);
		return $this->fetchAll($select);
	}
	
	public function findByEntityAndKey($presetId, $key)
	{
		$select = $this->select();
		$select->where('`preset` = ?', $presetId);
		$select->where('`key` = ?', $key);
		return $this->fetchRow($select);
	}	
	
	
}

