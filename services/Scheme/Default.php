<?php

class Shop_Service_Scheme_Default extends Shop_Service_Scheme
{
	protected $_basketFormContent = array();

	/**
	 * Singleton instance
	 *
	 * @var Shop_Service_Scheme_Default
	 */
	protected static $_instance = null;

	/**
	 * Singleton instance
	 *
	 * @return Shop_Service_Scheme_Default
	 */
	public static function getInstance()
	{
		if (null === self::$_instance) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function getName()
	{
		return 'Настройки магазинов ООО "Консул"';
	}


	/**
	 * @return Shop_Service_Basket
	 */
	public function getBasket()
	{
		if ($this->_basket === null) {
			$this->_basket = Shop_Service_Basket::getInstance();
		}
		return $this->_basket;
	}

	public function getBasketForm(Shop_Model_BasketItem $basketItem, $disabled = false)
	{
		$uid = $basketItem->getUid();
		$basket = $this->getBasket();
		$basket->addToTempStorage($basketItem);
		$quantity = 0;
		$stock = $basketItem->getStock();
		$delivery_day = $basketItem->getStockday() ? $basketItem->getStockday() : 0;

		if ($item = $basket->getItem($uid)) {
			$quantity = $item->getQuantity();
		} else {
			$quantity = $this->_view->translate('кол-во');
		}
		$minOrder = $basketItem->getMinOrder() ? $basketItem->getMinOrder() : 1;
		$formContent = $this->_getBasketFormContent($disabled);
		$formContent = str_replace(
				array('{uid}', '{quantity}', '{stock}', '{delivery_day}', '{min_order}'),
				array($uid, $quantity, $stock, $delivery_day, $minOrder),
				$formContent);
		return $formContent;
	}
	
	public function getBasketForm_BasketSave(Shop_Model_BasketItem $basketItem, $disabled = false) // Кирилл: дубликат без вывода формы карточки товара для восстановления кеш-корзины
	{
		$uid = $basketItem->getUid();
		$basket = $this->getBasket();
		$basket->addToTempStorage($basketItem);
		$quantity = 0;
		$stock = $basketItem->getStock();
		$delivery_day = $basketItem->getStockday() ? $basketItem->getStockday() : 0;
	}

	public function getBasketInfo()
	{
		return $this->_view->action('info', 'basket', 'shop');
	}

	public function getBasketUrl()
	{
		return $this->_view->url(array(), 'shop::basket');
	}

	public function getCheckoutUrl()
	{
		return $this->_view->url(array(), 'shop::checkoutDefault');
	}

	public function getSettingCollection()
	{
		if ($this->_settingCollection === null) {
			$this->_settingCollection = array('Основные настройки' => array(
				new Shop_Model_Setting('mail_moscow', 'E-mail для заказов с доставкой по Москве', Lv7CMS_DataTypes::EMAIL),
				new Shop_Model_Setting('phone_moscow', 'Телефон для заказов с доставкой по Москве'),
				new Shop_Model_Setting('mail_other_city', 'E-mail для заказов с доставкой в другой город', Lv7CMS_DataTypes::EMAIL),
				new Shop_Model_Setting('phone_other_city', 'Телефон для заказов с доставкой в другой город'),
				new Shop_Model_Setting('mail_non_cash', 'E-mail для заказов по безналу', Lv7CMS_DataTypes::EMAIL),
				new Shop_Model_Setting('phone_non_cash', 'Телефон для заказов по безналу'),
				new Shop_Model_Setting('mail_foreign_cars', 'E-mail для заказов по иномаркам', Lv7CMS_DataTypes::EMAIL),
				new Shop_Model_Setting('phone_foreign_cars', 'Телефон для заказов по иномаркам'),
				new Shop_Model_Setting('min_order_amount', 'Минимальная сумма заказа'),
			));
			if (is_array($deliveries = $this->getDeliveries())) {
				foreach ($deliveries as $delivery) {
					if (is_array($deliverySettings = $delivery->getSettings())) {
						$this->_settingCollection[$delivery->getName()] = $deliverySettings;
					}
				}
			}
		}
		return $this->_settingCollection;
	}

	public function getDeliveries()
	{	
		if ($this->_deliveries === null) {			
			$getShops = $this->_getShops();
			foreach ($getShops as $shop) {
				if ($shop->pickup_possible) {
					$this->_deliveries[] = Shop_Model_DeliveryFactory::create('SelfDelivery', $shop->id);		   	 				   	 		
				}
			}
			
			$this->_deliveries[] = Shop_Model_DeliveryFactory::create('MkadIn');
			$this->_deliveries[] = Shop_Model_DeliveryFactory::create('MkadOut');
			//$this->_deliveries[] = Shop_Model_DeliveryFactory::create('MMO');
			$this->_deliveries[] = Shop_Model_DeliveryFactory::create('OtherCity');			
            $this->_deliveries[] = Shop_Model_DeliveryFactory::create('YandexDelivery'); // Кирилл YandexDelivery: подключение способа доставки яндекс такси на странице оформления заказа
		}
		return $this->_deliveries;	
	}
 

	public function getPaymentMethods()
	{
		if ($this->_paymentMethods === null) {
			$this->_paymentMethods[] = Shop_Model_PaymentMethodFactory::create('Cash');
			$this->_paymentMethods[] = Shop_Model_PaymentMethodFactory::create('BankPrepay');
			$this->_paymentMethods[] = Shop_Model_PaymentMethodFactory::create('NonCash');
			$getShops = $this->_getShops();
			foreach ($getShops as $shop) {
				if (!empty($shop->sber_host) && !empty($shop->sber_username) && !empty($shop->sber_password)) {
		   	 		$this->_paymentMethods[] = Shop_Model_PaymentMethodFactory::create('Sberbank', $shop->id);
		   		}
		   	}
			if($_SERVER["REMOTE_ADDR"] == '185.6.127.242'){
				$this->_paymentMethods[] = Shop_Model_PaymentMethodFactory::create('Bnpl');
			}
			/*
			$user = Zend_Registry::get('user');
			$accessibleRoles = array('developer', 'administrator');
			if ($user && in_array($user->getRole(), $accessibleRoles)) {
				$this->_paymentMethods[] = Shop_Model_PaymentMethodFactory::create('Sberbank');
			}
			*/
			//$this->_paymentMethods[] = Shop_Model_PaymentMethodFactory::create('Custom');
		}
		return $this->_paymentMethods;

	}

	public function getOrderAcceptUrl()
	{
		return $this->_view->url(array(), 'shop::acceptDefault');
	}

	public function getCalculator()
	{
		if ($this->_calculator === null) {
			$this->_calculator = new Shop_Service_Calculator_Default();
		}
		return $this->_calculator;
	}

	public function getOrderCreator()
	{
		if ($this->_orderCreator === null) {
			$this->_orderCreator = new Shop_Service_OrderCreator_Default();
		}
		return $this->_orderCreator;
	}

	public function getCurrentPaymentMethod($faceType, $delivery)
	{
		switch ($faceType) {
			case 'natural':
				switch ($delivery) {
					case 'OtherCity':
						return 'BankPrepay';
					default:
						return 'Cash';
				}
			case 'legal':
				return 'NonCash';
		}
		return false;
	}

	protected function _getShops()
	{
		$shopSmapper = new CatCommon_Model_Mapper_Shops();
		$getShops = $shopSmapper->getList();
		return $getShops;
	}
	protected function _getBasketFormContent($disabled)
	{
		if (!isset($this->_basketFormContent[$disabled])) {
			$this->_basketFormContent[$disabled] = $this->_view->action('form', 'basket', 'shop', array('disabled' => $disabled));
		}
		return $this->_basketFormContent[$disabled];
	}

}