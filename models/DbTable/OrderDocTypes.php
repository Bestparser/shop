<?php

class Shop_Model_DbTable_OrderDocTypes extends Zend_Db_Table_Abstract
{

    protected $_name = 'shop_order_doc_type';

    protected $_primary = 'id';

	public function getList($activity = null)
	{
        $select = $this->select();
		if (!is_null($activity)) {
			$select->where('activity = ?', $activity);
		}
		$select->order('sort asc');
		return $this->fetchAll($select);
	}


}

