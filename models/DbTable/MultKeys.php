<?php

class Shop_Model_DbTable_MultKeys extends Zend_Db_Table_Abstract
{

    protected $_name = 'shop_mult_keys';

    protected $_primary = 'id';

	public function getList($activity = null)
	{
        $select = $this->select();
		if (!is_null($activity)) {
			$select->where('activity = ?', $activity);
		}
		return $this->fetchAll($select);
	}


}

