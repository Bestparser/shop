<?php

class Shop_Model_DeliveryFactory
{
	
	public static function create($deliveryId, $shopId = null)
	{
		$class = self::getClass($deliveryId, $shopId);
		if (!class_exists($class)) {
			throw new Lv7CMS_Exception('Unknown class name of delivery: ' . $class);
		}
		return new $class();
	}
	
	public static function getClass($deliveryId, $shopId = null)
	{
		return 'Shop_Model_Delivery_' . $deliveryId . $shopId;
	}
	
}