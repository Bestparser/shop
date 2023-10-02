<?php

class Shop_Model_DbTable_OrdersLog extends Zend_Db_Table_Abstract
{

    protected $_name = 'shop_orders_log';

    protected $_primary = 'id';


    public function findByOrder($orderId)
    {
    	$select = $this->select();
    	$select->where('order_id = ?', $orderId);
        $select->order('created desc');
    	return $this->fetchAll($select);
    }

    public function findByManager($managerId)
    {
        $select = $this->select();
        $select->where('manager = ?', $managerId);
        return $this->fetchAll($select);
    }
    
    public function clearBefore(Zend_Date $before)
    {
        $this->delete('created < "' . Lv7_Service_Datetime::ZendDateToClearAtom($before) . '"');
    }

}

