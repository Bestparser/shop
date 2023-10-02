<?php

class Shop_Service_Calculator_Default extends Shop_Service_Calculator
{

	public function getGoodsCost($discount = 0)
	{
		$scheme = Shop_Service_Config::getScheme();
		$checkoutData = $scheme->getCheckoutData();
		$cost = 0;
		$basket = Shop_Service_Config::getScheme()->getBasket();
		if (is_array($items = $basket->getItems())) {
			foreach ($items as $item) {
                $itemDiscount = $this->itemDiscount($discount, $item->getName(), $item->getSale());
                $price = floor(max(1, $item->getPrice() * 1 * (100 - $itemDiscount) / 100));
                $price = $price * $item->getQuantity();
                $cost += $price;
			}
		}
		return $cost;
	}

	public function itemDiscount($orderDiscount, $itemName, $sale)
	{
		if (Lv7CMS::getInstance()->getSiteId() == 'avtomrk') {
			return 0;
		}

		if (!$orderDiscount || $sale) {
			return 0;
		}
		// на товар который начинается на 'Диск колесн','Покрыш','Колесо в СБ'  действует максимальная скидка 5%
		$words = array(
			'Диск колесн',
			'Автодиск',
			'Шина',
			'Автошина',
			'Колесо в СБ'
		);
		foreach ($words as $word) {
			if (strpos($itemName, $word) === 0) {
				//return min($orderDiscount, 5);
				return 0;
			}
		}
		return $orderDiscount;
	}

	public function getDeliveryCost($discount = 0)
	{
		$checkoutData = Shop_Service_Config::getScheme()->getCheckoutData();
		if ($checkoutData->delivery) {			
			if ($checkoutData->delivery == 'SelfDelivery') {
				$checkoutData->delivery = 'SelfDelivery2';
			}
			$delivery = Shop_Model_DeliveryFactory::create($checkoutData->delivery);
			return $delivery->getCost();
		}
		return 0;		
	}

	public function getTotalCost($discount = 0)
	{
		return $this->getGoodsCost($discount) + $this->getDeliveryCost($discount);
	}


}
