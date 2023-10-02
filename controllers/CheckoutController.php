<?php

class Shop_CheckoutController extends Lv7CMS_Controller_Frontend
{

    public function init()
    {
        parent::init();
        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('index', 'html')->initContext();
    }

    public function preDispatch()
    {
        parent::preDispatch();

    }

    public function postDispatch()
    {
        parent::postDispatch();
        if (Lv7CMS::getInstance()->getSiteId() == 'planetiron') {
            $siteId = '?v12';
        }

        $this->view->headScript()->appendFile($this->_dataUrl . '/js/modules/shop/checkout.js' . $siteId);
        $this->view->headScript()->appendFile('https://api-maps.yandex.ru/2.1/?lang=ru_RU&apikey=e961b539-9a58-46b1-90e8-ce3cdfb71392&suggest_apikey=f1bf8ad8-064e-4944-9992-da81277ed2e0'); // Кирилл yandexDelivery: вывод карты с маршрутом
        $this->view->headScript()->appendFile($this->_dataUrl . '/js/modules/shop/input_validation.js' . $siteId); // Кирилл: yandexDelivery: подсказки с адресом + валидация
    }


    public function indexAction()
    {
        if (!empty($_GET['yandexerror'])) $this->view->yandexerror = '<i class="YandexDeliveryError">Ошибка при создании заказа</i>'; // Кирилл yandexDelivery

        $basket = Shop_Service_Config::getScheme()->getBasket();

        $isHallOrder = $this->_acl->can('shopHallOrderProcessing');

        // настраиваем форму
        $hasSupplierItems = false;
        $basketItems = $basket->getItems();
        if (is_array($basketItems)) {
            foreach ($basketItems as $item) {
                if ($item->getType() == Shop_Model_Mapper_OrderPosTypes::SUPPLIERS) {
                    $hasSupplierItems = true;
                    break;
                }
            }
        }

        // Кирилл: если в корзине встречается хотя-бы один предзаказ, то на радиоточку Яндекс Такси ставим disabled
        $blockingYandexDelivery = false; // флажок
        $basket = Shop_Service_Config::getScheme()->getBasket();
        foreach ($basketItems as $k){
            if ($k->getSupplier() > 1) $blockingYandexDelivery = true; // Активный флажок
        }
        if ($blockingYandexDelivery == true) $this->view->headScript()->appendFile($this->_dataUrl . '/js/modules/shop/yandex-blocking.js');
        // end Кирилл

        $form = new Shop_Form_Checkout($basketItems);

        if (!$hasSupplierItems) {
            $form->removeElement('vin_number');
        }
        if (!$isHallOrder) {
            $form->removeElement('barcode');
        }

        $settings = Shop_Service_Config::getScheme()->getSettings();
        $minOrderAmount = $settings['min_order_amount'];
        $this->view->minOrderAmount = $settings['min_order_amount'];
        $calculator = Shop_Service_Config::getScheme()->getCalculator();
        $cost = $calculator->getGoodsCost();

        // Кирилл:
        // если стоимость товаров - 0, то редирект на пустую корзину, где будет сообщение, что товаров нет
        if ($calculator->getGoodsCost() == 0) header("Location: ".((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/shop/basket' ."");

        // ставим флажок на наличие в корзине хотябы одного товара tecdoc (для того чтобы убрать из синей надписи предупреждения "Яндекс доставка")
        $BasketSave = new Shop_Model_Mapper_BasketSave();
        $this->view->tecdoc = 0;
        foreach ($basket->getItems() as $k){
            $unit = $BasketSave->basketGetTecdoc($k->getId());
            if ($unit['id']) $this->view->tecdoc = 1;
        }
        // end Кирилл

        if ($minOrderAmount && ($cost < $minOrderAmount)) {
            $this->view->smallOrderAmount = true;
        }

        $scheme = Shop_Service_Config::getScheme();
        $checkoutData = $scheme->getCheckoutData();
        $totalCost = $calculator->getTotalCost();

        if($totalCost > 10000){
            $form->getElement('paymentMethod')->removeMultiOption('Bnpl');
        }

        $user = Zend_Registry::get('user');
        if ($user->isGuest()) {
            $this->view->userGuest = true;
            $this->view->authorizeText = Lv7CMS_Resources::getTextTemplates()->shopAuthorize->getText();

            $authForm = new Users_Form_Login();
            $authForm->setWidth('100%');
            $authForm->setAction($this->view->url(array(), 'users::login'));
            $authForm->setDefault('from', $_SERVER['REQUEST_URI']);
            $this->view->authForm = $authForm;
        } else if (!$isHallOrder) {
            $presetMapper = new Shop_Model_Mapper_UserPresets();
            $presets = $presetMapper->getList($user->id);
            if (is_array($presets) && count($presets)) {
                $map = array('contact_face', 'phone', 'email', 'address', 'delivery', 'mileage', 'paymentMethod');
                if (count($presets) > 1) {
                    $presetsArray = array();
                    foreach ($presets as $preset) {
                        $presetData = array();
                        $presetData['id'] = $preset->id;
                        foreach ($map as $field) {
                            $presetData[$field] = $preset->params->{$field};
                        }
                        if (!class_exists(Shop_Model_DeliveryFactory::getClass($preset->params->delivery))) {
                            continue;
                        }
                        $delivery = Shop_Model_DeliveryFactory::create($preset->params->delivery);

                        $presetData['deliveryName'] = $delivery->getName();
                        if ($delivery->getId() == 'MkadOut') {
                            $presetData['deliveryName'] .= ' (дистанция от МКАД: ' . $preset->params->mileage . ' км)';
                        }
                        $paymentMethod = Shop_Model_PaymentMethodFactory::create($preset->params->paymentMethod);
                        $presetData['paymentMethodName'] = $paymentMethod->getName();
                        $presetsArray[] = $presetData;
                    }
                    $this->view->presets = $presetsArray;
                }
                // если пользователь еще не вводил данные...
                if (!strlen($checkoutData->contact_face)) {
                    $preset = $presets[0];
                    foreach ($map as $field) {
                        $checkoutData->{$field} = $preset->params->{$field};
                    }
                }
            } else if (!strlen($checkoutData->contact_face)) {
                $checkoutData->contact_face = $user->getRealname();
                $checkoutData->email = $user->email;
            }

            // Кирилл: у авторизованных пользователей вставляем автоматом дисконтную карту
            if ($user->discount_card){ // если в sql введена карта в таблице `_users` (смотрим в первую таблицу)
                $AuthorizedDiscountCart = $user->discount_card;
            } else { // если в первой таблице нет, то смотрим в другую таблицу sql в `crm_client`
                $mapperCrmClients = new Crm_Model_Mapper_Clients();
                $client = $mapperCrmClients->findByUser($user->id);
                if ($client->discount_card){
                    $AuthorizedDiscountCart = $client->discount_card;
                }
            }
            // Если за авторизованным пользователем в базе закреплена карта, то автоматически вставляем ее в input
            if ($AuthorizedDiscountCart) $checkoutData->discount_card = substr($AuthorizedDiscountCart, 0, 1) . '-' . substr($AuthorizedDiscountCart, 1, 6) . '-' . substr($AuthorizedDiscountCart, 7, 7) . '6';
            // end Кирилл
        }


        //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        if ($this->getRequest()->isPost()) {
            // для безналичных расчетов поле email обязательно!
            $form->email->setRequired(($this->_getParam('delivery') == 'OtherCity') || ($this->_getParam('paymentMethod') == 'NonCash'));

            // Кирилл: для Яндекс Такси поле email обязательно!
            $form->email->setRequired(($this->_getParam('delivery') == 'YandexDelivery'));
            $form->address->setRequired( (strpos($this->_getParam('delivery'), 'SelfDelivery') === false) );
            //$form->passport->setRequired(($this->_getParam('faceType') == 'natural') && ($this->_getParam('delivery') == 'OtherCity'));

            // для не физиков очищаем значение дисконтной карты
            if ($form->getValue('faceType') != 'natural') {
                $form->setDefault('discount_card', '');
            }

            $serviceYandexApi = new Shop_Service_YandexApi();
            if ($this->_getParam('delivery') == 'YandexDelivery'){
                if ($this->_getParam('YandexDeliveryIntervals') == 0) $serviceYandexApi->apivk('Yandex Error: not thoose offer');
                if (empty($this->_getParam('YandexDeliveryAddress'))) $serviceYandexApi->apivk('Yandex Error: empty address');
            }

            if ($form->isValid($this->getRequest()->getPost())){
                $checkoutData->faceType = $form->getValue('faceType');
                $checkoutData->delivery = $form->getValue('delivery');
                $checkoutData->mileage = $form->getValue('distance');
                $checkoutData->paymentMethod = $form->getValue('paymentMethod');//$scheme->getCurrentPaymentMethod($checkoutData->faceType, $checkoutData->delivery);

                $checkoutData->contact_face = $form->getValue('contact_face');
                //$checkoutData->passport = $form->getValue('passport');
                $checkoutData->email = $form->getValue('email');
                $checkoutData->phone = $form->getValue('phone');

                // Кирилл yandexDelivery
                if ($this->_getParam('delivery') == 'YandexDelivery'){ // если доставка Яндекс Такси
                    $checkoutData->address = $this->_getParam('YandexDeliveryAddress'); // перебиваем адрес

                    if (!$this->_getParam('YandexDeliveryCost') > 0){
                        $serviceYandexApi->apivk('Yandex Error: deliveryCost is null');
                        header("Location: ".((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/shop/checkout?yandexerror=yes' ."");
                        exit;
                    }
                    // отправляем данные в ООП
                    $serviceYandexApi->address = $this->_getParam('YandexDeliveryAddressAPI');
                    $serviceYandexApi->comments = htmlspecialchars(str_replace('"', '`', str_replace(array("\r\n", "\r", "\n"), ' ', $this->_getParam('comments'))), ENT_QUOTES);
                    $serviceYandexApi->clientPointA = $this->_getParam('clientPointA');
                    $serviceYandexApi->clientPointB = $this->_getParam('clientPointB');
                    $serviceYandexApi->email = $this->_getParam('email');
                    $serviceYandexApi->name = $this->_getParam('contact_face');
                    $serviceYandexApi->phone = $this->_getParam('phone');
                    $serviceYandexApi->payload = $this->_getParam('YandexDeliveryPayload');

                    $log = $serviceYandexApi->createOrder(); // Создаем заказ

                    // пишем логи
                    if ($log['yandex_order_id'] != ''){
                        $yandexDeliveryLog = new Shop_Model_Mapper_YandexDelivery();
                        $data = array(
                            'request' => $log['request'],
                            'response' => json_encode($log['response']),
                            'status' => $log['status'],
                            'yandex_order_id' => $log['yandex_order_id']
                        );
                        $yandexDeliveryLog->addLog($data);
                        $serviceYandexApi->apivk('New Yandex Delivery. ID - '.$log['yandex_order_id'].'');
                        $checkoutData->YandexDeliveryCost = $this->_getParam('YandexDeliveryCost'); // передаем в создание заказа актуальную цену доставки в обход калькулятора
                    } else {
                        $serviceYandexApi->apivk('Yandex Error: order API');
                        header("Location: ".((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/shop/checkout?yandexerror=yes' ."");
                        exit;
                    }
                } else { // end Кирилл yandexDelivery
                    $checkoutData->address = $form->getValue('address');
                }
                // end Кирилл

                $checkoutData->shop = $form->getValue('shop');
                $checkoutData->store_address = $form->getValue('store_address');
                $checkoutData->company_inn = $form->getValue('companyinn');

                $checkoutData->discount_card = $this->_clearDiscountCardNumber($form->getValue('discount_card'));
                $checkoutData->vin_number = $form->getValue('vin_number');
                $checkoutData->comments = $form->getValue('comments');
                if ($isHallOrder) {
                    $checkoutData->barcode = $form->getValue('barcode');
                }

                $this->_helper->redirector->gotoUrl(Shop_Service_Config::getScheme()->getOrderAcceptUrl());

            } else {
                //var_dump($form->getMessages());
            }


        } else {
            if ($isHallOrder) {
                $form->setDefault('faceType', 'natural');
                $form->setDefault('delivery', 'SelfDelivery2');
                $form->setDefault('paymentMethod', 'Cash');
            } else {
                $form->setDefault('faceType', $checkoutData->faceType ? $checkoutData->faceType : 'natural');
                $form->setDefault('delivery', $checkoutData->delivery ? $checkoutData->delivery : 'MkadIn');
                $form->setDefault('cost_info', 'идет расчет...');
                $form->setDefault('distance', $checkoutData->mileage);
                $form->setDefault('paymentMethod', $checkoutData->paymentMethod ? $checkoutData->paymentMethod : 'Cash');
                $form->setDefault('contact_face', $checkoutData->contact_face);
                $form->setDefault('companyinn', $checkoutData->company_inn);
                //$form->setDefault('passport', $checkoutData->passport);
                $form->setDefault('email', $checkoutData->email);
                $form->setDefault('phone', $checkoutData->phone);
                $form->setDefault('address', $checkoutData->address);
            }

            if ($checkoutData->shop) {
                $form->setDefault('shop', $checkoutData->shop);
            } else {
                $shops = $form->shop->getMultiOptions();
                $ids = array_keys($shops);
                $form->setDefault('shop', $ids[0]);
            }

            $form->setDefault('store_address', isset($checkoutData->store_address) ? $checkoutData->store_address : true);

            $form->setDefault('discount_card', $checkoutData->discount_card);
            $form->setDefault('vin_number', $checkoutData->vin_number);
            //$form->setDefault('comments', $checkoutData->comments); // Кирилл закомментировал чтобы при отказу от оплаты клиент не видел в комментах "from / to" (мы их передаем через коммент в админку)

        }



        $this->view->form = $form;
        $this->view->hasSupplierItems = $hasSupplierItems;

        $this->view->headTitle($this->view->translate('Оформление заказа'), 'SET');
    }

    public function yandexdeliveryAction() // Кирилл yandexDelivery
    {
        $this->_helper->layout->disableLayout();

        $serviceYandexApi = new Shop_Service_YandexApi(); // /Специально соданный сервис для логических операция с Яндекс Такси (там и прописаны API запросы)

        $process = $this->_getParam('process');	// по get process различаем какая нам нужна логическая операция в сервисе
        if ($process == 'point'){ // получить координаты клиента по введенному адресу
            $serviceYandexApi->address = $this->_getParam('address'); // post введенный адрес
            $clientPoints = $serviceYandexApi->getClientPoint(); // получение координат от API запроса

            $this->view->clientPoints = $clientPoints; // передаем в js скрипт для создания карты

            $form = new Shop_Form_Checkout(); // конструируем форму для скрытного вывода в hidden координат (чтобы потом постом передать для создания заказа)
            $form->clientPointA->setValue($clientPoints['clientPointA']);
            $form->clientPointB->setValue($clientPoints['clientPointB']);
            $this->view->clientPointA = $form->clientPointA;
            $this->view->clientPointB = $form->clientPointB;

            $form->YandexDeliveryAddressAPI->setValue($clientPoints['YandexDeliveryAddressAPI']);
            $this->view->YandexDeliveryAddressAPI = $form->YandexDeliveryAddressAPI;
        } elseif ($process == 'intervals'){ // селектор с офферами express
            // передаем в ООП данные:
            $serviceYandexApi->yandexDeliveryAddress = $this->_getParam('yandexDeliveryAddress'); // адрес клиента на русском языке
            $serviceYandexApi->clientPointA = $this->_getParam('clientPointA'); // координата точки А
            $serviceYandexApi->clientPointB = $this->_getParam('clientPointB'); // координата точки B

            if (intval($serviceYandexApi->clientPointB) > 0){ // если координаты получены
                $intervals = $serviceYandexApi->deliveryCostIntervals(); // то запрашиваем от Яндекса по API оффера express

                // Генерируем селектор с офферами express
                    $this->view->kmError = 0;
                    $this->view->errorLimit = 0;
                    $this->view->errorIntervals = 0;

                    $form = new Shop_Form_Checkout();
                    if ($intervals['km'] > 80){
                        $serviceYandexApi->apivk('Yandex Error: km > 80');
                        $form->YandexDeliveryIntervals->addMultiOption(0, 'Расстояние превышает 80 км.');
                        $this->view->kmError = 1;
                    } elseif ($intervals['errorLimit'] > 0){
                        $form->YandexDeliveryIntervals->addMultiOption(0, 'Большие габариты груза');
                        $this->view->errorLimit = 1;
                        $serviceYandexApi->apivk('Yandex Error: gabarit');
                    } elseif (count($intervals) < 3){ // если вдруг у Яндекса API навернется
                        $form->YandexDeliveryIntervals->addMultiOption(0, 'Свободных машин нет');
                        $this->view->errorIntervals = 1;
                        $serviceYandexApi->apivk('Yandex Error: offers is null. API broken');
                    } else {
                        $form->YandexDeliveryIntervals->addMultiOption(0, 'Когда доставить:');
                        $i = 0;
						$i2 = 0;
                        while ($i < count($intervals)-2){
                            $i++;
							if ($intervals[$i]['price'] > 0){
								$i2++;
								$form->YandexDeliveryIntervals->addMultiOption($i, $intervals[$i]['to'] . ': ' . $intervals[$i]['price'] . ' р. ');
							}
                        }
						if ($i2 == 0){
							$form->YandexDeliveryIntervals->addMultiOption(0, 'Свободных машин нет');
							$this->view->errorIntervals = 1;
							$serviceYandexApi->apivk('Yandex Error: offers is null. API broken');							
						}
                        if ($i == 0){ // уже время позднее
                            $form->YandexDeliveryIntervals->addMultiOption(0, 'Свободных машин нет');
                            $this->view->errorIntervals = 1;
                            $serviceYandexApi->apivk('Yandex Error: Later order. Time over');
                        }
                    }
                    $this->view->YandexDeliveryIntervals = $form->YandexDeliveryIntervals;
                    $this->view->intervals = $intervals;
                // end селектор
            }
        }
    } // end Кирилл yandexDelivery

    public function costinfoAction()
    {
        $this->_helper->layout->disableLayout();

        $scheme = Shop_Service_Config::getScheme();
        $checkoutData = $scheme->getCheckoutData();

        $faceType = $this->_getParam('faceType');
        $delivery = $this->_getParam('delivery');
        $mileage = $this->_getParam('distance');
        //$paymentMethod = $this->_getParam('paymentMethod');

        $discount = 0;
        // проверка дисконтной карты
        $discountCardNumber = $this->_clearDiscountCardNumber($this->_getParam('discountCard'));
        $discountCardsMapper = new Shop_Model_Mapper_DiscountCards();
        $discountCard = $discountCardsMapper->findByNumber($discountCardNumber);
        if ($discountCard && $discountCard->activity) {
            $this->view->discount = $discountCard->discount;
            $discount = $discountCard->discount;
        }

        $checkoutData->faceType = $faceType;
        $checkoutData->delivery = $delivery;
        $checkoutData->mileage = $mileage;
        $paymentMethod = $scheme->getCurrentPaymentMethod($faceType, $delivery);
        $checkoutData->paymentMethod = $paymentMethod;

        $calculator = $scheme->getCalculator();
        //$this->view->delivery = $delivery;
        //$this->view->paymentMethod = Shop_Model_PaymentMethodFactory::create($paymentMethod);

        $this->view->goodsCost = $calculator->getGoodsCost();
        $this->view->deliveryCost = $calculator->getDeliveryCost();
        $this->view->totalCost = $calculator->getTotalCost($discount);

        if ($this->_getParam('yandexDeliveryIntervalPayload')){
            $form = new Shop_Form_Checkout();
            $form->YandexDeliveryPayload->setValue($this->_getParam('yandexDeliveryIntervalPayload'));
            $this->view->YandexDeliveryPayload = $form->YandexDeliveryPayload;

            $form->YandexDeliveryCost->setValue($this->view->deliveryCost);
            $this->view->YandexDeliveryCost = $form->YandexDeliveryCost;
        }
    }

    public function checkdiscountcardAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $result = new stdClass();
        $result->found = false;

        $number = $this->_clearDiscountCardNumber($this->_getParam('number'));
        if ($number) {
            $cardMapper = new Shop_Model_Mapper_DiscountCards();
            $card = $cardMapper->findByNumber($number);
            if ($card && $card->activity) {
                $result->found = true;
                $result->dicount = $card->discount;
            }
        }
        echo json_encode($result);
    }

    protected function _clearDiscountCardNumber($number)
    {
        return str_replace('-', '', $number);
    }
}