<?php

class Shop_Service_Scheme_Avtomrk extends Shop_Service_Scheme_Default
{
	
	protected static $_instance = null;
	
	/**
	 * Singleton instance
	 *
	 * @return Shop_Service_Scheme_Avtomrk
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
		return 'Настройки магазина "Автомаркет"';
	}
	
	public function getDeliveries()
	{
		if ($this->_deliveries === null) {
			$this->_deliveries[] = Shop_Model_DeliveryFactory::create('MkadIn');
			$this->_deliveries[] = Shop_Model_DeliveryFactory::create('MkadOut');
			$this->_deliveries[] = Shop_Model_DeliveryFactory::create('MMO');
			$this->_deliveries[] = Shop_Model_DeliveryFactory::create('OtherCity');
			$this->_deliveries[] = Shop_Model_DeliveryFactory::create('SelfDelivery');
		}
		return $this->_deliveries;
	}
	
	public function getPaymentMethods()
	{
		if ($this->_paymentMethods === null) {
			$this->_paymentMethods[] = Shop_Model_PaymentMethodFactory::create('Cash');
			$this->_paymentMethods[] = Shop_Model_PaymentMethodFactory::create('BankPrepay');
			$this->_paymentMethods[] = Shop_Model_PaymentMethodFactory::create('NonCash');
			//$this->_paymentMethods[] = Shop_Model_PaymentMethodFactory::create('Sberbank');
			//$this->_paymentMethods[] = Shop_Model_PaymentMethodFactory::create('Custom');
		}
		return $this->_paymentMethods;		
		
	}
	
	
	
}