<?php

class Shop_Model_PaymentMethodFactory
{
	
	public static function create($paymentMethodId, $shopId = null)
	{
		$class = 'Shop_Model_PaymentMethod_' . $paymentMethodId . $shopId;
		if ($shopId == 2) {
			$class = 'Shop_Model_PaymentMethod_' . $paymentMethodId;
		}
		if (!class_exists($class)) {
			throw new Lv7CMS_Exception('Unknown class name of payment method: ' . $class);
		}
		return new $class();
	}
	
}