<?php

abstract class Shop_Service_Scheme
{
	protected $_view;

	protected $_basket = null;
	protected $_calculator = null;
	protected $_orderCreator = null;

	protected $_deliveries = null;
	protected $_paymentMethods = null;
	
	protected $_settingCollection = null;
	protected $_settings = null;

	protected $_checkoutData = null;
	
	public function getId()
	{
		return substr(get_class($this), 20); 
	}
	
	public function getName()
	{
		return $this->getId(); 
	}
	
	/**
	 * @return Shop_Service_Basket
	 */
	abstract public function getBasket();

	abstract public function getBasketForm(Shop_Model_BasketItem $basketItem, $disabled = false);

	abstract public function getBasketInfo();

	abstract public function getBasketUrl();

	abstract public function getCheckoutUrl();

	public function getCheckoutData()
	{
		if ($this->_checkoutData === null) {
			$this->_checkoutData = new Zend_Session_Namespace('shop_checkoutdata_' . $this->getId()); 			
		}
		return $this->_checkoutData;
	}

	public function getSettings()
	{
		if ($this->_settings === null) {
			$this->_settings = array();
			$mapper = new Shop_Model_Mapper_SettingValues();
			if (is_array($settings = $mapper->getList($this->getId()))) {
				foreach ($settings as $setting) {
					$this->_settings[$setting->key] = $setting->value;
				}
			}
		}
		return $this->_settings;	
	}

	abstract public function getSettingCollection();

	abstract public function getDeliveries();

	abstract public function getPaymentMethods();

	abstract public function getOrderAcceptUrl();

	abstract public function getCalculator();

	/**
	 * 
	 * @return Shop_Service_OrderCreator
	 */
	abstract public function getOrderCreator();
	
	protected function __construct()
	{
		$viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
		$this->_view = $viewRenderer->view;
	}
	
}