<?php

class Shop_Form_Checkout extends Lv7CMS_Form
{

	protected $_backetItems;

	public function __construct($backetItems = null)
	{
		$this->_backetItems = $backetItems;
		parent::__construct();
	}


	protected function _init()
	{
		$this->setPersonalDataAlert(true);
		$shopMapper = new CatCommon_Model_Mapper_Shops();
		$shops = $shopMapper->getListActivity();

		$element = new Zend_Form_Element_Radio('faceType', array('escape'=>false));
		$element->setLabel('<dt id="faceType-label" class="new__label required-marked"><i class="chekout__number">1</i> Выберите <sup>*</sup></dt>');
		$element->addDecorator('Label', array('escape'=>false));
		$element->addMultiOption('natural', '<div class="radio">Физическое лицо</div>');
		$element->addMultiOption('legal', '<div class="radio">Юридическое лицо</div>');
		$this->addElement($element);

		$this->addPanel(
			array('faceType'),
			'type-face-panel',
			array(Lv7CMS_Form::D_TWO_COL, Lv7CMS_Form::D_BORDER));



		if (is_array($deliveries = Shop_Service_Config::getScheme()->getDeliveries())) {
			if ($shops) {
				foreach ($shops as $shop) {
					if ($shop && $shop->buy_in_stocks) {
					$setNewDescr = array();
						if (is_array($this->_backetItems)) {
								foreach ($this->_backetItems as $item) {
								$itemStocks = $item->getStocks();
								$itemSupplier = $item->getSupplier() ? $item->getSupplier() : Products_Service_Config::SUPPLIER_SELF;
								$itemDaysStock = $item->getStockday();
								if (empty($itemStocks[$shop->id]) && empty($itemStocks[2]) && $itemSupplier == Products_Service_Config::SUPPLIER_SELF) {
										$deliveryNew = Shop_Model_DeliveryFactory::create('SelfDelivery', $shop->id);
										$setNewDescr[$deliveryNew->getId()] = $deliveryNew->setDescr(true);
								} else if (!empty($itemDaysStock) && empty($itemStocks[2]) && empty($itemStocks[$shop->id]) && $shop->id != 2) {
										$deliveryNew = Shop_Model_DeliveryFactory::create('SelfDelivery', $shop->id);
										$setNewDescr[$deliveryNew->getId()] = $deliveryNew->setDescr($itemDaysStock);
								}
							}
						}
					}
				}
			}

			$element = new Zend_Form_Element_Radio('delivery', array('escape'=>false));
			$element->setLabel('<dt id="delivery-label" class="new__label"><i class="chekout__number">2</i> Способ получения <sup>*</sup></dt>');
			$element->addDecorator('Label', array('escape'=>false));
			$element->setRequired(true);

			array_push($deliveries, array('name' => 'Самовывоз'));
			ksort($deliveries);
			foreach ($deliveries as $delivery) {
				if (is_array($delivery) && $delivery['name'] == 'Самовывоз') {
					$deliveryDescr = '';
					$deliveryName = 'Самовывоз';
					$deliveryId = 'SelfDeliveryFirst';
				} else {
					$deliveryDescr =  $delivery->getDescr();
					$deliveryName = $delivery->getName();
					$deliveryId = $delivery->getId();
				}

				if ( ($deliveryId == "SelfDelivery3" || $deliveryId == "SelfDelivery4") && !empty($this->_backetItems)) {
					$deliveryDescr = $setNewDescr[$deliveryId] ? $setNewDescr[$deliveryId] : $deliveryDescr;
				}
				$value = $deliveryName . '<br><small>' . $deliveryDescr . '</small>';
				$element->addMultiOption($deliveryId, '<div class="radio">' . $value . '</div>');
			}
			$this->addElement($element);

			$settings = Shop_Service_Config::getScheme()->getSettings();
			$element = new Zend_Form_Element_Select('distance');
			$element->setLabel('Дистанция от МКАД');
			$element->setAttrib('id', 'distance-box');
			$element->addMultiOption('0', 'менее ' . $settings['mkadout_distance_min'] . ' км');
			for ($i = $settings['mkadout_distance_min']; $i <= $settings['mkadout_distance_max']; $i++) {
				$element->addMultiOption($i, $i . ' км');
			}
			$this->addElement($element);

            // Кирилл YandexDelivery:
                // input type text - адресная строка
                $element = new Zend_Form_Element_Text('YandexDeliveryAddress', array('escape'=>false));
                $element->setAttrib('placeholder', 'Введите адрес');
                if ($_POST['delivery'] == 'YandexDelivery') $element->setRequired(true);
                $this->addElement($element);

                // селектор с офферами для express
                $element = new Zend_Form_Element_Select('YandexDeliveryIntervals', array('escape'=>false));
                $element->setAttrib('class', ' form-control smooth');                
                $element->setAttrib('onFocus', 'expand(this)');
                $validator = new Zend_Validate_InArray(array(1,2,3,4,5));
                if ($_POST['YandexDeliveryIntervals'] == 0){
					$validator->setMessage('Вы не выбрали тариф. Введите заново адрес и выберите тариф');					
				}				
                $element->addValidator($validator);
                $this->addElement($element);				

                // в этот input пишем адрес клиента на русском языке после получения координат по API
                $element = new Zend_Form_Element_Hidden('YandexDeliveryAddressAPI', array('escape'=>false));
                $this->addElement($element);

                // для валидатора: в случае если цена не посчиталась
                $element = new Zend_Form_Element_Hidden('YandexDeliveryCost', array('escape'=>false));
                $this->addElement($element);

                // input координата точки А
                $element = new Zend_Form_Element_Hidden('clientPointA', array('escape'=>false));
                $this->addElement($element);

                // input координата точки B
                $element = new Zend_Form_Element_Hidden('clientPointB', array('escape'=>false));
                $this->addElement($element);

                // input идентификатор payload
                $element = new Zend_Form_Element_Hidden('YandexDeliveryPayload', array('escape'=>false));
                $this->addElement($element);
            // end Кирилл YandexDelivery

			$element = new Zend_Form_Element_Text('cost_info');
			//$element->setLabel('Стоимость');
			$element->setAttrib('size', 50);
			$this->addElement($element);

		//$element = new Zend_Form_Element_Select('shop');
		$element = new Zend_Form_Element_Radio('shop', array('escape'=>false));
		//$element->setLabel('Адрес самовывоза');
		$catCommonFacade = new CatCommon_Service_Facade();
		$shops = $catCommonFacade->getAvailableShopsBySite(Lv7CMS::getInstance()->getSiteId());
		//$element->addMultiOption('', 'выберите адрес');
		if ($shops) {
			if (!is_array($shops)) {
				$shops = array($shops);
			}
			foreach ($shops as $shop) {
				$url = $this->getRoadmapUrl($shop->id);
				$element->addMultiOption($shop->id, '<div class="radio" style="margin-left:6px;">' .  'Самовывоз - ' . '<a href="'.$url.'" target="_blank">' . $shop->short_name . '</a></div>');
			}
		}
		$this->addElement($element);


			$this->addPanel(
                array('delivery', 'shop', 'YandexDeliveryAddress', 'YandexDeliveryIntervals', 'distance', 'cost_info'),		// Кирилл yandexDelivery: добавил YandexDeliveryAddress и YandexDeliveryIntervals
				'delivery-panel',
				array(Lv7CMS_Form::D_TWO_COL, Lv7CMS_Form::D_BORDER));
		}

		if (is_array($paymentMethods = Shop_Service_Config::getScheme()->getPaymentMethods())) {
			$element = new Zend_Form_Element_Radio('paymentMethod', array('escape'=>false));
			$element->setLabel('<dt id="paymentMethod-label" class="new__label required-marked"><i class="chekout__number">3</i> Способ оплаты <sup>*</sup></dt>');
			$element->addDecorator('Label', array('escape'=>false));
			foreach ($paymentMethods as $paymentMethod) {
				if ($paymentMethod->getId() == 'Bnpl') {
					$text = Lv7CMS_Resources::getTextTemplates()->shopPaymentBnplInfo->getText();
					$element->addMultiOption($paymentMethod->getId(), '<div class="radio">' . $paymentMethod->getName() . ' <b class="payment-infos" data-toggle="tooltip" data-placement="top" title="'.$text.'"><img src="/images/znak.png" width="15px"></b></div>');
				} else {
					$element->addMultiOption($paymentMethod->getId(), '<div class="radio">' . $paymentMethod->getName() . '</div>');
				}

			}
			$this->addElement($element);

			$this->addPanel(
				array('paymentMethod'),
				'payment-panel',
				array(Lv7CMS_Form::D_TWO_COL, Lv7CMS_Form::D_BORDER));
		}

		$element = new Zend_Form_Element_Text('contact_face');
		$element->setLabel('<dt id="contact_face-label" class="new__label"><i class="chekout__number">4</i> Контакты <sup>*</sup></dt>');
		$element->addDecorator('Label', array('escape'=>false));
		$element->setAttrib('placeholder', 'Контактное лицо');
		$element->setAttrib('size', 50);
		$element->setRequired(true);
		$this->addElement($element);

		$element = new Zend_Form_Element_Text('companyinn');
		$element->setAttrib('placeholder', 'ИНН организации');
		$numberValidate = new Zend_Validate_Digits();
        $numberValidate->setMessage('ИНН может состоять только из цифр');
        $element->addValidator($numberValidate);
        $validator = new Zend_Validate_StringLength(array('min' => 9, 'max' => 12));
        $element->addValidator($validator);
		$element->setAttrib('size', 50);
		$element->setRequired(false);
		$this->addElement($element);

		/*$element = new Zend_Form_Element_Text('passport');
		//$element->setLabel('Серия и номер паспорта');
		$element->setDescription('Требуются для внесения в транспортную накладную');
		$element->setAttrib('placeholder', 'Серия и номер паспорта');
		$element->setAttrib('size', 30);
		$element->setRequired(true);
		$this->addElement($element);*/


		$element = new Lv7CMS_Form_Element_Phone('phone');
		//$element->setLabel('Телефон');
		$element->setAttrib('placeholder', 'Ваш номер телефона');
		$element->setAttrib('size', 40);
		$element->setRequired(true);
		$this->addElement($element);

		/*
		$element = new Zend_Form_Element_Text('phone');
		$element->setLabel('Телефон');
		$element->setDescription('введите 10 цифр номера (код города/оператора + номер телефона, например 4951234567)');
		$element->setAttrib('size', 40);
		$element->setAttrib('class', 'mask-numeric');
		$element->setRequired(true);
		$validator = new Zend_Validate_StringLength(10, 10);
		$validator->setMessage('Ошибка в номере телефона. Общая длина номера (код города/оператора + номер телефона) должна составлять 10 цифр!');
		$element->addValidator($validator);
		$element->addFilter(new Zend_Filter_StringTrim());
		$element->addFilter(new Zend_Filter_Digits());
		$this->addElement($element);
		*/
		$element = new Zend_Form_Element_Text('email');
		//$element->setLabel('E-mail');
		$element->setAttrib('placeholder', 'E-mail');
		$element->setAttrib('size', 40);
		$element->addFilter(new Zend_Filter_StringTrim());
		//$validator = new Zend_Validate_EmailAddress(Zend_Validate_Hostname::ALLOW_DNS | Zend_Validate_Hostname::ALLOW_LOCAL);
		$validator = new Zend_Validate_EmailAddress();
		$validator->setOptions(array('domain' => FALSE));
		$validator->getHostnameValidator()->setValidateIdn(false);
		$validator->setMessage('Формат почты не соответствует.'); // Кирилл: выводим сообщение пользователю
		$element->addValidator($validator);
		// Кирилл: добавил второй валидатор с выводом сообщения
		$validator2 = new Zend_Validate_NotEmpty();											
		$validator2->setMessage('При доставке Яндекс Такси - почта обязательна!');
		$element->addValidator($validator2);														
		$this->addElement($element);

		$element = new Zend_Form_Element_Textarea('address');
		//$element->setLabel('Адрес доставки');
		$element->setAttrib('placeholder', 'Адрес доставки');
		$element->setRequired(true);
		$element->setAttrib('cols', 50);
		$element->setAttrib('rows', 3);
		if ($_POST['delivery'] == 'YandexTaxi'){ // Кирилл: временное решение. Потом убрать. (Это потому что при яндекс такси сначала без почты, а потом валидация на почту срабатывает и не пользователь отправляет без адреса)
			$validator = new Zend_Validate_NotEmpty();											
			$validator->setMessage('');
			$element->addValidator($validator);
			if ($_POST['YTadres'] == ''){
				?>				
					<script type="text/javascript">
						var element = document.querySelector('#YTsuggest');	
						element.setAttribute('style', 'border: 1px solid red; background: rgb(243, 193, 193);');
					</script>
				<?php
			}
		} // end Кирилл		
		$this->addElement($element);


		$element = new Zend_Form_Element_Checkbox('store_address');
		$element->setLabel('Сохранить адрес в настройках');
		$user = Zend_Registry::get('user');
		if ($user->isGuest()) {
			$element->setDescription('сохранение адреса в настройках доступно только авторизованным пользователям');
		}
		$this->addElement($element);


		$this->addPanel(
			array('contact_face', 'companyinn', 'phone', 'email', 'address', 'store_address'),
			'contacts-panel',
			array(Lv7CMS_Form::D_TWO_COL, Lv7CMS_Form::D_BORDER));

		$element = new Zend_Form_Element_Text('discount_card');
		$element->setLabel('Дисконтная карта');
		$element->setAttrib('placeholder', 'Если у вас есть дисконтная карта - укажите здесь ее номер');
		//$element->setDescription('Если у вас есть дисконтная карта - укажите здесь ее номер');
		$element->setAttrib('size', 30);
			// Кирилл: валидация - свою ли дисконтную карту вводит пользователь
				$post_discount_cart = substr(str_replace('-', '', $_POST['discount_card']), 0, -1); // дисконтную карту производим в числовое значение (убираем маску с тире) и отсекаем последний номер, потому что в sql все дисконтные карты как и в LF - без последнего номера
				if ($user->isGuest()){ // если пользователь не авторизованный
					$discountValidate = new Zend_Validate_Db_RecordExists( // спец.валидатор: проверяем в sql на наличие такой дисконтной карты
						array(
							'table' => 'discount_card', // название таблицы
							'field' => 'number', // название колонки
							'adapter' => $shopMapper->getDbAdapter() // подключение к sql
						)
					);
					if (!$discountValidate->isValid($post_discount_cart)){
						$discountValidate->setMessage('Дисконтной карты с таким номером не существует'); // если есть ошибка (нет такой дисконтной карты в sql)
						$element->addValidator($discountValidate);
					}	
				} else { // Если пользователь авторизованный, то просто сравниваем закрепленную за ним дисконтную карту со значением, которое он ввел
					if ($user->id != '24278'){ // Выключаем проверку дисконтной карты для продавца зала
						// Вычисляем дисконтную карту сразу из двух мест (user и client), закрепленную за авторизованным пользователем
							if ($user->discount_card){ // если в sql введена карта в таблице `_users` (смотрим в первую таблицу)
								$AuthorizedDiscountCart = $user->discount_card;
							} else { // если в первой таблице нет, то смотрим в другую таблицу sql в `crm_client`
								$mapperCrmClients = new Crm_Model_Mapper_Clients();
								$client = $mapperCrmClients->findByUser($user->id);
								if ($client->discount_card){
									$AuthorizedDiscountCart = $client->discount_card;
								}
							}
							$AuthorizedDiscountCart = substr($AuthorizedDiscountCart, 0, 12); // урезаем 13-ю цифру (а то в sql такой фокус есть: в _users - 13, а по факту работаем с 12)
						// Валидация
							$discountValidate = new Zend_Validate_InArray(array($AuthorizedDiscountCart)); // спец.валидатор: проверяем в массиве на наличие такой дисконтной карты (а в массиве всего-лишь один эелемент - закрепленная за пользователем дисконтная карта)
							if (!$discountValidate->isValid($post_discount_cart)){
								$discountValidate->setMessage('Дисконтная карта с данным значением не относится к Вашему аккаунту. Или проверьте: привязана ли дисконтная карта в Вашем личном кабинете на сайте.');
								$element->addValidator($discountValidate);
							}
					}
				}
			// end Кирилл
		$this->addElement($element);

		$element = new Zend_Form_Element_Text('vin_number');
		$element->setLabel('VIN-номер Вашего автомобиля');
		$element->setDescription('Укажите vin-номер автомобиля (для проверки соответствия заказанных запчастей)');
		$element->setAttrib('size', 40);
		$this->addElement($element);

		$element = new Zend_Form_Element_Textarea('comments');
		$element->setLabel('Дополнительные пожелания');
		$element->setAttrib('placeholder', 'Комментарии, пожелания, уточнения');
		$element->setAttrib('cols', 50);
		$element->setAttrib('rows', 3);
		$this->addElement($element);

		$element = new Zend_Form_Element_Text('barcode');
		$element->setRequired(true);
		$element->setLabel('Barcode продавца');
		$element->setDescription('Поставьте курсор в это поле и отсканируйте свой barcode');
		$element->setAttrib('size', 30);
		$this->addElement($element);

		$this->addPanel(
		        array('discount_card', 'vin_number', 'comments', 'barcode'),
    			'add-panel',
	       		array(Lv7CMS_Form::D_TWO_COL, Lv7CMS_Form::D_BORDER));

		$this->setAddStdButton(false);

		$element = new Zend_Form_Element_Submit('submit');
		$element->setLabel('Отправить заказ');
		$element->setAttrib('class', 'button');
		$this->addElement($element);

		$this->addPanel(
			array('submit'),
			'btn-panel',
			array(Lv7CMS_Form::D_TWO_COL, Lv7CMS_Form::D_BORDER));

	}

	public function getRoadmapUrl($siteId)
	{
		switch ($siteId) {
			case '4': return '/shops/4';
			case '2': return '/shops/2';
			case '3': return '/shops/3';
			default:
				return 'http://www.планетазапчастей.рф/contact';
		}
	}

}