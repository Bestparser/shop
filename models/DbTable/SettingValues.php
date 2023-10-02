<?php

class Shop_Model_DbTable_SettingValues extends Zend_Db_Table_Abstract
{

    protected $_name = 'shop_settings';
	protected $_primary = 'id';

	public function getList($scheme)
	{
		$select = $this->select();
		$select->where('scheme = ?', $scheme);
		return $this->fetchAll($select);
	}
	
	public function findByKey($scheme, $key)
	{
		$select = $this->select();
		$select->where('`scheme` = ?', $scheme);
		$select->where('`key` = ?', $key);
		return $this->fetchRow($select);
	}	
	
}

