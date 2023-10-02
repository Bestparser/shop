<?php
// Кирилл yandexDelivery
class Shop_Model_Mapper_YandexDelivery extends Lv7CMS_Mapper_Abstract
{
    protected $_domainName = 'Shop_Model_YandexDelivery';
    protected $_tableName = 'Shop_Model_DbTable_YandexDelivery';


    public function addLog($data)
    {
        $result = $this->_table->addLog($data);
        return $result;
    }



}