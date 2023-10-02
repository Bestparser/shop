<?php

class Shop_Form_Order extends Lv7CMS_Form
{
	
	protected function _init()
	{
		$this->setWidth(800);
		$this->setAddStdButton(true);

		$element = new Zend_Form_Element_Select('doc_type');
		$element->setLabel('Вид документа');
		//$element->setRequired(true);
		$element->addMultiOption('', 'Выберите вид');
		$docTypes = new Shop_Model_Mapper_OrderDocTypes();
		$data = $docTypes->getList(true);
		if ($data) {
			foreach ($data as $item) {
				$element->addMultiOption($item->id, $item->name);
			}
		}
		$this->addElement($element);
		
		$element = new Zend_Form_Element_Select('faceType');
		$element->setLabel('Оформление');
        $element->addMultiOptions(Shop_Model_Mapper_FaceTypes::asOptions());
		$this->addElement($element);

		$element = new Zend_Form_Element_Select('paymentMethod');
		$element->setLabel('Способ оплаты');
		$paymentMethods = Shop_Service_Config::getScheme()->getPaymentMethods();
		foreach ($paymentMethods as $paymentMethod) {
			$element->addMultiOption($paymentMethod->getId(), $paymentMethod->getName());
		}
		$this->addElement($element);

		
		$deliveries = Shop_Service_Config::getScheme()->getDeliveries();
		$element = new Zend_Form_Element_Select('delivery');
		$element->setLabel('Способ получения');
		foreach ($deliveries as $delivery) {
			$element->addMultiOption($delivery->getId(), strip_tags($delivery->getName()));
		}
		$this->addElement($element);

		$settings = Shop_Service_Config::getScheme()->getSettings();
		$element = new Zend_Form_Element_Select('distance');
		$element->setLabel('Дистанция от МКАД');
		$element->addMultiOption('0', 'менее ' . $settings['mkadout_distance_min'] . ' км');
		for ($i = $settings['mkadout_distance_min']; $i <= $settings['mkadout_distance_max']; $i++) {
			$element->addMultiOption($i, $i . ' км');
		}
		$this->addElement($element);
		
		$element = new Zend_Form_Element_Text('contact_face');
		$element->setLabel('Контактное лицо');
		$element->setAttrib('size', 50);
		$element->setRequired(true);
		$this->addElement($element);

		$element = new Zend_Form_Element_Text('passport');
		$element->setLabel('Серия и номер паспорта');
		$element->setDescription('Требуются для внесения в транспортную накладную');
		$element->setAttrib('size', 30);
		$this->addElement($element);
		
		$element = new Zend_Form_Element_Textarea('address_register');
		$element->setLabel('Адрес регистрации');
		$element->setAttrib('cols', 50);
		$element->setAttrib('rows', 2);
		$this->addElement($element);
		
		
		$element = new Lv7CMS_Form_Element_Phone('phone');
		$element->setLabel('Телефон');
		$element->setRequired(true);
		//$validator = new Zend_Validate_StringLength(12, 12);
		//$validator->setMessage('Ошибка в номере телефона. Общая длина номера (код города + номер телефона) должна составлять 10 цифр!');
		//$element->addValidator($validator);
		$this->addElement($element);
		
		
		/*
		$element = new Zend_Form_Element_Text('phone');
		$element->setLabel('Телефон');
		//$element->setDescription('введите 10 цифр номера (код города/оператора + номер телефона, например 4951234567)');
		$element->setAttrib('size', 40);
		$element->setAttrib('class', 'phone-mask');
		//$element->setRequired(true);
		//$validator = new Zend_Validate_StringLength(10, 10); 
		//$validator->setMessage('Ошибка в номере телефона. Общая длина номера (код города/оператора + номер телефона) должна составлять 10 цифр!');
		//$element->addValidator($validator);
		//$element->addFilter(new Zend_Filter_StringTrim());
		//$element->addFilter(new Zend_Filter_Digits());
		$this->addElement($element);
		*/
		
		$element = new Zend_Form_Element_Text('email');
		$element->setLabel('E-mail');
		$element->setAttrib('size', 40);
		$this->addElement($element);

		$element = new Zend_Form_Element_Textarea('address');
		$element->setLabel('Адрес доставки');
		$element->setRequired(true);
		$element->setAttrib('cols', 50);
		$element->setAttrib('rows', 3);
		$this->addElement($element);

		$element = new Zend_Form_Element_Text('discount_card');
		$element->setLabel('Дисконтная карта');
		$element->setDescription('Если у вас есть дисконтная карта - укажите здесь ее номер');
		$element->setAttrib('size', 30);
		$this->addElement($element);
			
		$element = new Zend_Form_Element_Text('vin_number');
		$element->setLabel('VIN-номер авто');
		$element->setAttrib('size', 30);
		$this->addElement($element);
			
		$element = new Zend_Form_Element_Textarea('comments');
		$element->setLabel('Дополнительные пожелания');
		$element->setAttrib('cols', 50);
		$element->setAttrib('rows', 3);
		$this->addElement($element);
		
		$element = new Zend_Form_Element_Select('shop');
		$element->setLabel('Привязка к магазину');
		$element->addMultiOption(0, '---');
		$shops = CatCommon_Service_Facade::getInstance()->getShops();
		foreach ($shops as $shop) {
			$element->addMultiOption($shop->id, $shop->short_name);
		}
		$this->addElement($element);
		
		$element = new Zend_Form_Element_Text('barcode');
		$element->setLabel('Barcode продавца');
		$element->setAttrib('size', 25);
		$this->addElement($element);
		
		$this->addPanel(
			array('doc_type', 'faceType', 'paymentMethod', 'delivery', 'distance', 
				    'contact_face', 'passport', 'address_register', 'phone', 'email', 'address', 
			        'discount_card', 'vin_number', 'comments', 'shop', 'barcode'),
			'contacts-panel', 
			array(Lv7CMS_Form::D_TWO_COL, Lv7CMS_Form::D_BORDER));
		
			
	}
	
}