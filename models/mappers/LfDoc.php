<?php

	/*--------------------------------------------
	|
	|	Кирилл: данный маппер создан для выполнения задачи по осуществлению связи между заказом (иномарки) и LF
	|	Т.е. в админке в карточке заказа добавлена кнопка "LF"
	|	Менеджер заполняет карточку заказа, кликает на кнопку "LF". Данные заказа инсертятся в shop_order_api. LF считывает от туда эти данные и отдает по API ответные данные: штрихкод, номер договора
	|
	*/

class Shop_Model_Mapper_LfDoc extends Lv7CMS_Mapper_Abstract
{
	protected $_domainName = 'Shop_Model_LfDoc';
	protected $_tableName = 'Shop_Model_DbTable_LfDoc';


	public function dataDoc($dbConfig, $params, $get) // Получение параметров для генерации word договора
	{
		$result = $this->_table->dataDoc($dbConfig, $params, $get);
		return $result;
	}

	public function showWord($wordDocData) // Вывод word договора (генерация html)
	{
		$result = $this->_table->showWord($wordDocData);
		return $result;
	}

	public function getLfManagerCode($dbConfig, $managerId, $orderShopId) // Получение LF кода сотрудника
	{
		$result = $this->_table->getLfManagerCode($dbConfig, $managerId, $orderShopId);
		return $result;
	}

	public function getLfManagerCode2($dbConfig, $orderId, $orderShopId) // Получение LF код менеджера в заказах-файлах
	{
		$result = $this->_table->getLfManagerCode2($dbConfig, $orderId, $orderShopId);
		return $result;
	}

	public function insertXML($dbConfig, $id, $xml) // insert/update в sql данных заказа
	{
		$result = $this->_table->insertXML($dbConfig, $id, $xml);
		return $result;
	}

	public function selectOrder($orderId) // Быборка
	{
		$result = $this->_table->selectOrder($orderId);
		return $result;
	}


}