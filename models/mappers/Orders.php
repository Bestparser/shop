<?php

class Shop_Model_Mapper_Orders extends Lv7CMS_Mapper_Abstract
{

	protected $_domainName = 'Shop_Model_Order';
	protected $_tableName = 'Shop_Model_DbTable_Orders';

	protected $_ignoreProp = array('params');

	public function getList($clientId = null)
	{
		$result = $this->_table->getList($clientId);
		return $this->_rowsetToObjList($result);
	}

	public function createObject()
	{
		$obj = parent::createObject();
		$obj->params = new Shop_Service_OrderParamsLazyLoader();
		return $obj;
	}

	public function insert($obj)
	{
		if (!$obj->number) {
			$obj->number = $this->getNewNumber();
		}
		$obj = parent::insert($obj);

		if (isset($obj->params) && !($obj->params instanceof Shop_Service_OrderParamsLazyLoader)) {
			$this->_getOptionLoader()->setValues($obj->id, (array) $obj->params);
		}

		return $obj;
	}

	public function update($obj)
	{
		$obj = parent::update($obj);

		if (isset($obj->params) && !($obj->params instanceof Shop_Service_OrderParamsLazyLoader)) {
			$this->_getOptionLoader()->setValues($obj->id, (array) $obj->params);
		}

		return $obj;
	}

	public function delete($obj)
	{
		$posMapper = new Shop_Model_Mapper_OrderPos();
		$posMapper->deleteByOrder($obj->id);
		parent::delete($obj);
		$this->_getOptionLoader()->deleteValues($obj->id);
	}

    public function findByNumber($orderId)
    {
    	$row = $this->_table->findByNumber($orderId);
    	return $this->_mapRowToObject($row, $this->createObject());
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

			$orderTableName = 'shop_orders';
			$orderTableAlias = 'o';
			$paramsTableName = 'shop_order_params';
			$paramsTableExt = 'main.external_object';
			$paramsTableExtAlias = 'ex';
			$paramsTablePay = 'main.finance_payment_instruction';
			$paramsTablePayAlias = 'fi';

			$added = array();

			$adapter = $this->getDbAdapter();
			$select = new Zend_Db_Select($adapter);
			$select->from(array($orderTableAlias => $orderTableName));
			$selfFields = array('id', 'type', 'site', 'number', 'user', 'manager', 'shop', 'territory_id', 'status', 'doc_type', 'created', 'updated');
			foreach ($conditions as $condition) {
				$key = $condition['key'];
				$added[] = $key;
				$op = $condition['op'] ? $condition['op'] : '=';
				$of = $condition['of'] ? $condition['of'] : '=';
				if (strtoupper($op) == 'IN' || strtoupper($of) == 'IN') {
					$value = (array) $condition['value'];
					$vr = array();
					foreach ($value as $v) {
						$vr[] = $adapter->quote($v);
					}
					$quotedValue = '(' . implode(',', $vr) . ')';
				} else {
					$quotedValue = $adapter->quote($condition['value']);
				}
				if ($condition['of']) {
					$paramsTableAlias = 'of.' . $key;
					$paramsFieldAlias = $key;
					$select->joinLeft(
						array($paramsTableExtAlias => $paramsTableExt),
						"`o`.`id` = `$paramsTableExtAlias`.`obj_id` AND `$paramsTableExtAlias`.`obj_type` = 'shop_order'",
						array('ex.obj_id' => 'id')
						);
					$select->where("`ex`.`obj_id` IS NOT NULL");
					$select->joinLeft(
						array($paramsTablePayAlias => $paramsTablePay),
						"`ex`.`id` = `$paramsTablePayAlias`.`ext_obj`",
						array('fi.status' => 'status')
						);
					$select->where("`fi`.`status` $of $quotedValue");
				} else if (!in_array($key, $selfFields)) {
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

	public function rowToObject($row)
	{
		return $this->_mapRowToObject($row, $this->createObject());
	}

	protected function _getOptionLoader()
	{
		if (null == $this->_optionLoader) {
			$this->_optionLoader = new Lv7_Service_EntityOptions(new Shop_Model_Mapper_OrderParams());
		}
		return $this->_optionLoader;
	}

	public function getNewNumberByCurrentYear()
	{
		return $this->_table->getNewNumberByCurrentYear();
	}

	public function getNewNumber()
	{
		return $this->_table->getNewNumber();
	}

	public function deleteOldOrders($daysAgo = 120)
	{
		$orders = $this->_rowsetToObjList($this->_table->getListOld($daysAgo));
		if (is_array($orders)) {
			foreach ($orders as $order) {
				$this->delete($order);
			}
		}
	}

	public function getCountOrdersByClient($clientId)
	{
		return $this->_table->getCountOrdersByClient($clientId);
	}

	public function getNewDistribOrders($shopId = null)
	{
		return $this->getNewOrders(Shop_Model_Mapper_OrderTypes::DISTRIB, $shopId);
	}

	public function getNewOrders($type = null, $shopId = null)
	{
		return $this->_table->getNewOrders($type, $shopId);
	}


}




