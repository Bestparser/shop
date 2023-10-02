<?php

class Shop_Model_Delivery_SelfDelivery4 extends Shop_Model_Delivery
{
	
	public function getName()
	{
		return 'Самовывоз ул. Осташковская';
	}
	
	public function getDescr($addDay = null)
	{
		$url = $this->getRoadmapUrl('konsulavto');
		$shopSmapper = new CatCommon_Model_Mapper_Shops();
		$findDeliveryDay = $shopSmapper->find(4);
		if (empty($addDay)) {
			$getDeliveryDayCount = $findDeliveryDay->deliveray_day_beznal ? $findDeliveryDay->deliveray_day_beznal : '1 день';
		} else {
			if ($addDay < 1) {
				$getDeliveryDayCount = '2 дня';
			} else {
				if ($addDay <= 4) {
					$getDeliveryDayCount = $addDay . ' дня';
				} else {
					$getDeliveryDayCount = $addDay . ' дней';
				}
			}
		}
		return 'Срок резерва ' . $getDeliveryDayCount . ', получение товара в магазине на <a href="' . $url . '" target="_blank">ул. Лескова.</a>';//При безналичном способе оплаты';
	}

	public function setDescr($addDay = null)
	{
		return self::getDescr($addDay);
	}	
	
	public function getRoadmapUrl($site)
	{
		switch ($site) {
			case 'konsulavto': return '/shops/4';
			case 'mkad86': return '/shops/2';
			case 'planetiron': return '/shops/3';
			default:
				return 'http://www.планетазапчастей.рф/contact';
		}
	}
	
	
}