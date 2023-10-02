<?php

class Shop_Model_Mapper_OrdersLogTypes extends Lv7CMS_Mapper_List
{

	const SAVE = 1;
	const DELETE = 2;
	const SEND_SUPPLIERS = 3;
	const SEND_EMAIL = 4;
	const SEND_SMS = 5;
	const CHANGEPOS = 6;
	const CHANGESTATUS = 7;
	const DOWNLOAD_EXCEL = 8;
	const DOWNLOAD_XML = 9;
	const PRINTS = 10;
	const EMAILPAYMENT = 11;
	const PAYMENTREFUND = 12;
	const PAYMENTREVERSE = 13;
	const PAYMENTCOMPLATE = 14;
	const POSMULTI = 15;


	protected function _init()
	{
		$this->_add(self::SAVE, 'Сохранение (Изменение цен, кол-ва позиций и т.д.)');
		$this->_add(self::DELETE, 'Удаление');
		$this->_add(self::SEND_SUPPLIERS, 'Отправлены заказы поставщикам');
		$this->_add(self::SEND_EMAIL, 'Отправлен Email клиенту');
		$this->_add(self::SEND_SMS, 'Отправлено sms клиенту');
		$this->_add(self::CHANGEPOS, 'Добавлены или изменены позиции');
		$this->_add(self::DOWNLOAD_EXCEL, 'Скачан как Excel');
		$this->_add(self::DOWNLOAD_XML, 'Скачан как XML');
		$this->_add(self::PRINTS, 'Распечатан');
		$this->_add(self::EMAILPAYMENT, 'Письмо об оплате отправлено клиенту');
		$this->_add(self::PAYMENTREFUND, 'Средства за оплату заказа возвращены клиенту');
		$this->_add(self::PAYMENTREVERSE, 'Авторизация оплаты отменена');
		$this->_add(self::PAYMENTCOMPLATE, 'Оплата прошла успешно');
		$this->_add(self::POSMULTI, 'Кратность товара изменена');

	}	

}




