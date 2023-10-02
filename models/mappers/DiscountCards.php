<?php

class Shop_Model_Mapper_DiscountCards extends Lv7CMS_Mapper_Abstract
{

    protected $_domainName = 'Shop_Model_DiscountCard';

    protected $_tableName = 'Shop_Model_DbTable_DiscountCards';

    public function findByNumber($number)
    {
    	$number = substr($number, 0, 12);
    	$row = $this->_table->findByNumber($number);
    	return $this->_mapRowToObject($row, $this->createObject());
    }

    public function findByPhone($phone)
    {
        $row = $this->_table->findByPhone($phone);
        return $this->_mapRowToObject($row, $this->createObject());
    }

    public function getListByDateLastPurchase($date, $subYear = null, $subMonth = null)
    {
        $cards = $this->_table->getListByDateLastPurchase($date, $subYear, $subMonth);
        return $this->_rowsetToObjList($cards);
    }
}

