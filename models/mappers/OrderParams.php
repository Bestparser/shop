<?php

class Shop_Model_Mapper_OrderParams extends Lv7CMS_Mapper_Abstract
implements Lv7_Mapper_EntityOptions_Interface
{

	protected $_domainName = 'Shop_Model_OrderParam';
	protected $_tableName = 'Shop_Model_DbTable_OrderParams';


	/*
	public function getList($orderId)
	{
		$result = $this->_table->getList($orderId);
		return $this->_rowsetToObjList($result);
	}
	*/

	public function findByEntity($orderId)
	{
		$result = $this->_table->findByEntity($orderId);
		$params = $this->_rowsetToObjList($result);
		return $params;
	}

	public function deleteByEntity($orderId)
	{
		$params = $this->findByEntity($orderId);
		if (is_array($params)) {
			foreach ($params as $param) {
				$this->delete($param);
			}
		}
	}

	public function findByEntityAndKeyN($orderId, $key)
	{
		$row = $this->_table->findByEntityAndKey($orderId, $key);
    	return $this->_mapRowToObject($row, $this->createObject());
	}

	public function setValue($orderId, $key, $value)
	{
		$param = $this->_table->findByEntityAndKey($orderId, $key);
		if (empty($param)) {
			$this->addValue($orderId, $key, $value);
		} else {
			$param->value = $value;
			$this->update($param);
		}
	}

	public function addValue($orderId, $key, $value)
	{
		$param = $this->createObject();
		$param->order = $orderId;
		$param->key = $key;
		$param->value = $value;
		$this->insert($param);

	}
	
	public function getSum($key, $ids = null)
	{
		$adapter = $this->getDbAdapter();
		$select = new Zend_Db_Select($adapter);
		$select->from('shop_order_params', array('s' => new Zend_Db_Expr('SUM(0+value)')));
		$select->where('`key` = ?', $key);
		if (is_array($ids)) {
			$select->where('`order` IN (' . implode(',', $ids) . ')');
		}
		return $adapter->fetchOne($select);
	}
	
}




