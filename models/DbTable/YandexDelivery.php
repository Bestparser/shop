<?php
// Кирилл yandexDelivery
class Shop_Model_DbTable_YandexDelivery extends Zend_Db_Table_Abstract
{
    protected $_name = 'shop_yandex_delivery_log';
    protected $_primary = 'id';

    public function addLog($data)
    {
        $this->insert($data);
    }

}
