<?php

class Shop_Model_DbTable_UserPresets extends Zend_Db_Table_Abstract
{

    protected $_name = 'shop_user_preset';
	protected $_primary = 'id';

	public function getList($userId)
	{
		$select = $this->select();
		$select->where('user = ?', $userId);
		$select->order('updated desc');
		return $this->fetchAll($select);
	}
	

	
}

