<?php

class Shop_Model_DbTable_DiscountCards extends Zend_Db_Table_Abstract
{

    protected $_name = 'discount_card';

    protected $_primary = 'id';


    public function findByNumber($number)
    {
    	$select = $this->select();
    	$select->where('number LIKE ?', $number);
    	return $this->fetchRow($select);
    }

    public function findByPhone($phone)
    {
        $select = $this->select();
        $select->where('phone LIKE ?', $phone);
        return $this->fetchRow($select);
    }

    public function getListByDateLastPurchase($date, $subYear = null, $subMonth = null)
    {
        $select = $this->select();
        $date = new Zend_Date();
        if (isset($subYear)) {
            $date->subYear($subYear); // Минус % года от текущей даты
        }
        if (isset($subMonth)) {
            $date->subMonth($subMonth);// Минус % месяца от текущей даты
        }
        $select->where('date_last_purchase >= ?', Lv7_Service_Datetime::ZendDateToClearAtom($date));
        return $this->fetchAll($select);
    }
}

