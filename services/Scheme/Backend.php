<?php

class Shop_Service_Scheme_Backend extends Shop_Service_Scheme
{
	protected $_basketFormContent = '';
	/**
	 * Singleton instance
	 *
	 * @var Shop_Service_Scheme_Backend
	 */
	protected static $_instance = null;

	/**
	 * Singleton instance
	 *
	 * @return Shop_Service_Scheme_Backend
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
		return 'Backend';
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
		if ($item = $basket->getItem($uid)) {
			$quantity = $item->getQuantity();
		} else {
			$quantity = $this->_view->translate('кол-во');
		}
		$formContent = $this->_getBasketFormContent();
		//$formContent = $this->_view->action('form', 'basket', 'shop');
		$formContent = str_replace('{uid}', $uid, $formContent);
		$formContent = str_replace('{quantity}', $quantity, $formContent);
		return $formContent;
	}

	public function getBasketInfo()
	{
		return $this->_view->action('info', 'backendbasket', 'shop');
	}
	
	
	public function getBasketUrl()
	{
		return $this->_view->url(array(), 'shop::backendBasket');
	}

	public function getCheckoutUrl()
	{
		return null;
	}

	public function getSettingCollection()
	{
		return null;
	}

	public function getDeliveries()
	{
		return null;
	}

	public function getPaymentMethods()
	{
		return null;
	}

	public function getOrderAcceptUrl()
	{
		return null;
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
	
	
	protected function _getBasketFormContent()
	{
		if ($this->_basketFormContent == '') {
			$this->_basketFormContent = $this->_view->action('form', 'backendbasket', 'shop');
		}
		return $this->_basketFormContent;
	}
	
}