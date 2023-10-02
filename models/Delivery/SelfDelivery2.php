<?php

class Shop_Model_Delivery_SelfDelivery2 extends Shop_Model_Delivery
{
	
	public function getName()
	{
		return 'Самовывоз МКАД 86';
	}
	
	public function getDescr()
	{
		$url = $this->getRoadmapUrl('mkad86');
		$shopSmapper = new CatCommon_Model_Mapper_Shops();
		$findDeliveryDay = $shopSmapper->find(2);
		$getDeliveryDayCount = $findDeliveryDay->deliveray_day_beznal ? $findDeliveryDay->deliveray_day_beznal : '1 день'; 
		return 'Срок резерва ' . $getDeliveryDayCount . ', получение товара в магазине на <a href="' . $url . '" target="_blank">ул. МКАД 86км.</a>';//При безналичном способе оплаты';
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