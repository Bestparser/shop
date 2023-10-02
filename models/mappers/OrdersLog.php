<?php

class Shop_Model_Mapper_OrdersLog extends Lv7CMS_Mapper_Abstract
{

    protected $_domainName = 'Shop_Model_OrdersLog';

    protected $_tableName = 'Shop_Model_DbTable_OrdersLog';

    public function findByOrder($orderId)
    {
    	$result = $this->_table->findByOrder($orderId);
    	return $this->_rowsetToObjList($result);
    }

    public function findByManager($managerId)
    {
        $result = $this->_table->findByManager($managerId);
        return $this->_rowsetToObjList($result);
    }

    /**
     * @return Zend_Db_Table
     */
    public function getDbSelect($conditions = null, $additions = null)
    {
        if (is_null($conditions)) {
            $select = $this->_table->select();
            $select->order('created desc');
        } else {
            if (!is_array($conditions)) {
                throw new Lv7CMS_Exception('Conditions should be an array!');
            }
            
            $orderTableName = 'shop_orders_log';
            $orderTableAlias = 'o';
            $paramsTableName = 'shop_order_params';
            
            $added = array();
            
            $adapter = $this->getDbAdapter();
            $select = new Zend_Db_Select($adapter);
            $select->from(array($orderTableAlias => $orderTableName));
            $selfFields = array('id', 'type', 'site', 'number', 'user', 'manager', 'shop', 'status', 'doc_type', 'created', 'updated');
            foreach ($conditions as $condition) {
                $key = $condition['key'];
                $added[] = $key;
                $op = $condition['op'] ? $condition['op'] : '=';
                if (strtoupper($op) == 'IN') {
                    $value = (array) $condition['value'];
                    $vr = array();
                    foreach ($value as $v) {
                        $vr[] = $adapter->quote($v);
                    }
                    $quotedValue = '(' . implode(',', $vr) . ')';
                } else {
                    $quotedValue = $adapter->quote($condition['value']);
                }
                if (!in_array($key, $selfFields)) {
                    $paramsTableAlias = 't_' . $key;
                    $paramsFieldAlias = $key;
                    $select->joinLeft(
                        array($paramsTableAlias => $paramsTableName), 
                        "`$paramsTableAlias`.`order` = `$orderTableAlias`.`id` AND `$paramsTableAlias`.`key` = '$key'", 
                        array($paramsFieldAlias => 'value')
                        );
                    $select->where("`$paramsTableAlias`.`value` $op $quotedValue");
                } else {
                    $select->where("`$orderTableAlias`.`$key` $op $quotedValue");
                }
            }
            
            if (is_array($additions)) {
                foreach ($additions as $key) {
                    if (in_array($key, $added)) {
                        continue;
                    }
                    $paramsTableAlias = 't_' . $key;
                    $paramsFieldAlias = $key;
                    $select->joinLeft(
                        array($paramsTableAlias => $paramsTableName), 
                        "`$paramsTableAlias`.`order` = `$orderTableAlias`.`id` AND `$paramsTableAlias`.`key` = '$key'", 
                        array($paramsFieldAlias => 'value')
                    );
                }
            }
        }
        
        return $select;
    }
    
    public function clearLog($days)
    {
        $date = new Zend_Date();
        $date->subDay($days);
        $this->_table->clearBefore($date);
    }

}

