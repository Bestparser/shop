<?php

class Shop_Model_Delivery_MkadOut extends Shop_Model_Delivery
{
	
	public function getName()
	{
		return 'Доставка - Московская область <small>(до ' . $this->getSettingValue('distance_max') . ' км. от МКАД)</small>';
	}
	
	public function getDescr()
	{
		$descr = 'Стоимость доставки ' . $this->getSettingValue('base_cost') . ' р. ';
		$descr .= '+ ' . $this->getSettingValue('distance_tax') . ' р. за каждый км от МКАД. ';
		$descr .= ' При сумме заказа более ' . $this->getSettingValue('cost_free') . ' р. оплачивается только расстояние от МКАД';
		return $descr;
	}

    protected function _initSettings()
    {
        parent::_initSettings();
        $this->_createSetting('base_cost', 'Стоимость доставки (руб)', Lv7CMS_DataTypes::FLOAT);
        $this->_createSetting('cost_free', 'От какой суммы доставка бесплатна (руб)', Lv7CMS_DataTypes::FLOAT);
        $this->_createSetting('distance_min', 'Минимальное расстояние (км)', Lv7CMS_DataTypes::INT);
        $this->_createSetting('distance_max', 'Максимальное расстояние (км)', Lv7CMS_DataTypes::INT);
        $this->_createSetting('distance_tax', 'Стоимость за км (руб)', Lv7CMS_DataTypes::FLOAT);
    }
	

	public function getCost()
	{
		$calculator = Shop_Service_Config::getScheme()->getCalculator();
		$checkoutData = Shop_Service_Config::getScheme()->getCheckoutData();
		$mileage = (int) $checkoutData->mileage;
		$goodsCost = $calculator->getGoodsCost();
		$cost = ($goodsCost >= $this->getSettingValue('cost_free')) ? 0 : $this->getSettingValue('base_cost');
		$cost += $mileage * $this->getSettingValue('distance_tax');
		return $cost;
	}
	
	
}