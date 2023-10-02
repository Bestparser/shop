<?php

class Shop_Model_Delivery_SelfDelivery extends Shop_Model_Delivery
{
	
	public function getName()
	{
		return 'Самовывоз';
	}
	
	public function getDescr()
	{
		$url = $this->getRoadmapUrl();
		$shopSmapper = new CatCommon_Model_Mapper_Shops();
		$findDeliveryDay = $shopSmapper->find(2);
		if (!empty($findDeliveryDay->deliveray_day_beznal)) {
			$getDeliveryDayCount = $findDeliveryDay->deliveray_day_beznal;
		} else {
			$getDeliveryDayCount = '1 день';
		}
		
		return 'Срок резерва ' . $getDeliveryDayCount . ', получение товара в магазине на <a href="' . $url . '" target="_blank">ул. МКАД 86км.</a>';//При безналичном способе оплаты';
	}
	
	public function getRoadmapUrl()
	{
		switch (Lv7CMS::getInstance()->getSiteId()) {
			case 'konsulavto': return '/passage';
			case 'mkad86': return '/scheme';
			case 'planetiron': return '/shops/2';
			default:
				return 'http://www.планетазапчастей.рф/contact';
		}
	}
	
	
}