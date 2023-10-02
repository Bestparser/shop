<?php

class Shop_Service_Facade
{

	/**
	 * Singleton instance
	 *
	 * @var Shop_Service_Facade
	 */
	protected static $_instance = null;

	/**
	 * Singleton instance
	 *
	 * @return Shop_Service_Facade
	 */
	public static function getInstance()
	{
		if (null === self::$_instance) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function getBasketUrl()
	{
		return Shop_Service_Config::getScheme()->getBasketUrl();
	}

	public function getCheckoutUrl()
	{
		return Shop_Service_Config::getScheme()->getCheckoutUrl();
	}

	/**
	 * @return Shop_Model_BasketItem
	 */
	public function createBasketItem()
	{
		return Shop_Service_Config::getScheme()->getBasket()->createItem();
	}

	public function getBasketForm($basketItem, $disabled = false)
	{
		return Shop_Service_Config::getScheme()->getBasketForm($basketItem, $disabled);
	}
	
	public function getBasketForm_BasketSave($basketItem, $disabled = false) // Кирилл: дубликат без вывода формы карточки товара для восстановления кеш-корзины
	{
		return Shop_Service_Config::getScheme()->getBasketForm_BasketSave($basketItem, $disabled);
	}

	public function getBackendBasketForm($basketItem)
	{
		return Shop_Service_Config::getBackendScheme()->getBasketForm($basketItem);
	}

	public function clearBasketTempStorage()
	{
		Shop_Service_Config::getScheme()->getBasket()->clearTempStorage();
	}

	public function getCountOrdersByClient($clientId)
	{
		$ordersMapper = new Shop_Model_Mapper_Orders();
		return $ordersMapper->getCountOrdersByClient($clientId);
	}

	public function getNewDistribOrdersCount()
	{
		$ordersMapper = new Shop_Model_Mapper_Orders();
		$currentProfile = Distrib_Service_Facade::getInstance()->getCurrentProfile();
		$shopId = ($currentProfile && $currentProfile->shop) ? $currentProfile->shop : null;
		return count($ordersMapper->getNewDistribOrders($shopId));
	}

	public function getNewOrdersCount($type = null)
	{
		$ordersMapper = new Shop_Model_Mapper_Orders();
		$currentProfile = Distrib_Service_Facade::getInstance()->getCurrentProfile();
		$shopId = ($currentProfile && $currentProfile->shop) ? $currentProfile->shop : null;
		$getOrders = $ordersMapper->getNewOrders($type, $shopId);
		$data['count'] =  count($getOrders);
		$data['orders'] = $getOrders;
		return $data;
	}

	public function getOrder($orderId)
	{
		$mapper = new Shop_Model_Mapper_Orders();
		return $mapper->find($orderId);
	}

}