<?php

class Shop_Service_ExternalObject_Order extends ExternalObjects_Service_Object
{
	protected $_mapper = null;
	protected $_orders = array();
	
	public function getName($id)
	{
		$order = $this->_getOrder($id);
		return $order ? $this->_getView()->translate('Заказ') . ' #' . $order->number : '--- заказ не найден (' . $id .') ---';
	}
	
	public function getFrontendUrl($id)
	{
		return $this->_getView()->url(array('id' => $id), 'shop::userOrder');
	}
	
	public function getBackendUrl($id)
	{
		return $this->_getView()->url(array('id' => $id), 'shop::adminOrder');
	}
	
	public function setPaid($id, $value) 
	{

		if (!$id) {
			throw new Lv7CMS_Exception('Неизвестный id заказа! (' . $id . ')');
		}

		if ($value) {
			// если заказ оплачен (через сбер) - ставим ему статус "НОВЫЙ ЗАКАЗ"
			$ordersMapper = new Shop_Model_Mapper_Orders();
			$order = $ordersMapper->find($id);
			$order->status = Shop_Model_Mapper_OrderStatus::NEW_ORDER;
			$ordersMapper->update($order);


			// рассылаем письма
			$orderManager = new Shop_Service_OrderManager();
			$orderManager->sendOrderMailToManager($id);
			$orderManager->sendOrderMailToClient($id);
			
			// очищаем корзину
			Shop_Service_Config::getScheme()->getBasket()->clear();
		}
		/*
		$statusId = $value ? Shop_Model_OrderStatus::PAID : Shop_Model_OrderStatus::WAIT_PAY;
		$this->_getMapper()->setOrderStatus($id, $statusId);
		if ($statusId == Shop_Model_OrderStatus::PAID) {
			$orderManager = new Shop_Service_OrderManager();
			$orderManager->orderPaidClientNotify($id);
		}
		*/
	}
	
	protected function _getOrder($id)
	{
		if (!isset($this->_orders[$id])) {
			$this->_orders[$id] = $this->_getMapper()->find($id);
		}
		return $this->_orders[$id];
	}
	
	/**
	 * 
	 * @return Shop_Model_Mapper_Orders
	 */
	protected function _getMapper()
	{
		if (is_null($this->_mapper)) {
			$this->_mapper = new Shop_Model_Mapper_Orders();
		}
		return $this->_mapper;
	}
	
}