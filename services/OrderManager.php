<?php


use Modules\Company\Services\Facade as CompanyFacade;

class Shop_Service_OrderManager
{
	protected $_orderMapper = null;
	protected $_orderPosMapper = null;

	public function updateOrder($orderId, $sendXml = null)
	{
		$this->recalcOrder($orderId, $sendXml);
		//$this->updateOrderStatus($orderId);
	}

	public function recalcOrder($orderId, $sendXml = null)
	{
		$orderMapper = $this->_getOrderMapper();
		$order = $orderMapper->find($orderId);
		$sendXml = $sendXml ? true : false;

		// обновляем свойства-стоимости заказа
		Shop_Service_Config::setSiteId($order->site);
		$scheme = Shop_Service_Config::getScheme();

		$mapperPos = $this->_getOrderPosMapper();
		$posList = $mapperPos->getList($order->id);

		$basket = $scheme->getBasket();
		$basket->clear();
		if ($posList) {
			foreach ($posList as $pos) {
				$basketItem = $basket->createItem();
				$basketItem->setId($pos->id);
				$basketItem->setName($pos->name);
				$basketItem->setPrice($pos->getClientPrice());
				$basketItem->setQuantity($pos->getClientQuantity());
				$basketItem->setSale($pos->sale);
				$basket->add($basketItem);
			}
		}
		$checkoutData = $scheme->getCheckoutData();
		$checkoutData->paymentMethod = $order->params->paymentMethod;
		$checkoutData->delivery = $order->params->delivery;
		$checkoutData->mileage = $order->params->deliveryMileage;
		$checkoutData->faceType = $order->params->faceType;

		$calculator = $scheme->getCalculator();

		$discount = 0;
		if (strlen($order->params->discountCard)) {
			$discountCardMapper = new Shop_Model_Mapper_DiscountCards();
			$discountCard = $discountCardMapper->findByNumber($order->params->discountCard);
			if ($discountCard && $discountCard->activity) {
				$discount = $discountCard->discount;
			}
		}
		$order->params->discount = $discount;

		$order->params->goodsCost = $calculator->getGoodsCost($discount);
		if ($order->params->deliveryCostManualSet) {
			$order->params->totalCost = $order->params->goodsCost + $order->params->deliveryCost;
		} else {
			$order->params->deliveryCost = $calculator->getDeliveryCost($discount);
			$order->params->totalCost = $calculator->getTotalCost($discount);
		}

		if ($order->type == Shop_Model_Mapper_OrderTypes::INNER) {
			if (!$order->params->prepaidManualSet) {
				$order->params->prepaid = round($order->params->totalCost / 2);
			}
		}

		$orderMapper->update($order);

		// Создаем файл с заказом если заказ от Юр. лица и с безналичной оплатой
        if ($order->params->faceType == 'legal' && $order->params->paymentMethod == 'NonCash' && !$sendXml) {
           	$sendOrder = $this->PostProcessing($order);
     	}

		// проверяем, есть ли связанная платежная инструкция, и актуализируем в ней сумму
		$extObjFacade = new ExternalObjects_Service_Facade();
		$extObj = $extObjFacade->findByType('shop_order', $order->id);
		if ($extObj) {
			$pi = Finance_Service_Facade::findPaymentInstructionByExtObj($extObj->id);
			if ($pi) {
				$pi->amount = $pi->client_amount = $order->params->totalCost;
				Finance_Service_Facade::updatePaymentInstruction($pi);
			}
		}


		unset($checkoutData);
		$basket->clear();
		Shop_Service_Config::setSiteId(null);
	}

	/**
	 * Обновление статуса заказа согласно статусам входящих в него товаров
	 * @param int $orderId
	 */
	public function updateOrderStatus($orderId)
	{
		$orderMapper = $this->_getOrderMapper();
		$order = $orderMapper->find($orderId);

		$dontTouch = array(
			Shop_Model_Mapper_OrderStatus::COMPLETE,
			Shop_Model_Mapper_OrderStatus::CANCEL
		);
		if (in_array($order->status, $dontTouch)) {
			return;
		}

		$mapperPos = $this->_getOrderPosMapper();
		$posList = $mapperPos->getList($order->id);
		if ($posList) {
			$statusData = array();
			foreach ($posList as $pos) {
				if (isset($statusData[$pos->status])) {
					$statusData[$pos->status]++;
				} else {
					$statusData[$pos->status] = 1;
				}
			}
			if (count($statusData) == 1
					&& $statusData[Shop_Model_Mapper_OrderPosStatus::ON_WAREHOUSE]) {
				$order->status = Shop_Model_Mapper_OrderStatus::ON_WAREHOUSE;
			} else if ((count($statusData) == 2
					&& $statusData[Shop_Model_Mapper_OrderPosStatus::ON_WAREHOUSE]
					&& $statusData[Shop_Model_Mapper_OrderPosStatus::ORDERED])
				|| (count($statusData) == 1
					&& $statusData[Shop_Model_Mapper_OrderPosStatus::ORDERED])) {
				$order->status = Shop_Model_Mapper_OrderStatus::WAITING;
			} else {
				$order->status = Shop_Model_Mapper_OrderStatus::IN_WORK;
			}

			$orderMapper->update($order);
		}

	}

	/**
	 * Обновление статусов позизций заказа согласно статусу заказа (только "отмена" и "выполнен")
	 * @param int $orderId
	 */
	public function updateOrderPosStatus($orderId)
	{
		$orderMapper = $this->_getOrderMapper();
		$order = $orderMapper->find($orderId);

		$touch = array(
			Shop_Model_Mapper_OrderStatus::COMPLETE,
			Shop_Model_Mapper_OrderStatus::CANCEL
		);
		if (!in_array($order->status, $touch)) {
			return;
		}

		$mapperPos = $this->_getOrderPosMapper();
		$posList = $mapperPos->getList($order->id);
		if ($posList) {
			foreach ($posList as $pos) {
				if ($order->status == Shop_Model_Mapper_OrderStatus::CANCEL) {
					$pos->status = Shop_Model_Mapper_OrderPosStatus::CANCEL;
				}
				if ($order->status == Shop_Model_Mapper_OrderStatus::COMPLETE) {
					$pos->status = Shop_Model_Mapper_OrderPosStatus::SENT;
				}
				$mapperPos->update($pos);
			}
		}

	}

	public function sendOrderMailToClient($orderId, $mailText = false)
	{
		$ordersMapper = $this->_getOrderMapper();

		if ($orderId instanceof Shop_Model_Order) {
			$order = $orderId;
		} else {
			$order = $ordersMapper->find($orderId);
		}

		if (!strlen($order->params->email)) {
			return false;
		}

		$params = $this->getOrderTemplateParams($order);
		if (!$mailText) {
			$mailText = $this->getOrderInfoForClient($order, $params);
		}
		$order->order_mail = $mailText;
		$order->order_mail_sent = date('Y-m-d H:i:s');
		$order = $ordersMapper->update($order);

		$repository = new Lv7CMS_Resource_Repository($order->site);
		$cmsSettings = $repository->findAll(Lv7CMS_Resource_Types::SETTINGS);

		if (Lv7CMS::getInstance()->getCurrentSiteOption('smtp')) {
			$replyTo = Lv7CMS::getInstance()->getCurrentSiteOption('smtpUsername');
		} else {
			$replyTo = $cmsSettings['defaultFromEmail']->getValue();
		}

		$mail = new Zend_Mail('UTF-8');
		$mail->addTo($order->params->email, $order->params->contactFace);
		$mail->setReplyTo($replyTo);
		$mail->setFrom($params['MANAGER_MAIL']);
		$mail->setBodyHtml($mailText);
		$mail->setSubject('Заказ #' . $order->number . ' на сайте ' . $cmsSettings['defaultSiteName']->getValue());
		$mail->send();

		return true;
	}

	public function sendPaymentOrderMailToClient($orderId, $mailText = false)
	{
		$ordersMapper = $this->_getOrderMapper();
		if ($orderId instanceof Shop_Model_Order) {
			$order = $orderId;
		} else {
			$order = $ordersMapper->find($orderId);
		}

		if (!strlen($order->params->email)) {
			return false;
		}

		$repository = new Lv7CMS_Resource_Repository($order->site);

		$params = $this->getOrderTemplateParams($order);
		if (!$mailText) {
			$template = $repository->find(Lv7CMS_Resource_Types::TEXT_TEMPLATES, 'shopMailClientToPayment');
			$mailText = $template->getText($params);
		}
		$order->payment_mail = $mailText;
		$order->payment_mail_sent = date('Y-m-d H:i:s');
		$order = $ordersMapper->update($order);

		$cmsSettings = $repository->findAll(Lv7CMS_Resource_Types::SETTINGS);

		if (Lv7CMS::getInstance()->getCurrentSiteOption('smtp')) {
			$replyTo = Lv7CMS::getInstance()->getCurrentSiteOption('smtpUsername');
		} else {
			$replyTo = $cmsSettings['defaultFromEmail']->getValue();
		}

		$mail = new Zend_Mail('UTF-8');
		$mail->addTo($order->params->email, $order->params->contactFace);
		$mail->setReplyTo($replyTo);
		$mail->setFrom($params['MANAGER_MAIL']);
		$mail->setBodyHtml($mailText);
		$mail->setSubject('Оплата заказа #' . $order->number . ' на сайте ' . $cmsSettings['defaultSiteName']->getValue());
		$mail->send();

		return true;
	}

	public function sendOrderMailToManager($orderId)
	{
		if ($orderId instanceof Shop_Model_Order) {
			$order = $orderId;
		} else {
			$orderMapper = $this->_getOrderMapper();
			$order = $orderMapper->find($orderId);
		}

		$params = $this->getOrderTemplateParams($order);
		if (!$params['MANAGER_MAIL']) {
			return false;
		}

		$mailText = $this->getOrderInfoForManager($order, $params);

		$repository = new Lv7CMS_Resource_Repository($order->site);
		$cmsSettings = $repository->findAll(Lv7CMS_Resource_Types::SETTINGS);

		$shops = CatCommon_Service_Facade::getInstance()->getShops();

		if ($shops && ($shop = $shops[$order->shop])) {
			$shopSmtp = $shop->smtp_host && $shop->smtp_port && $shop->smtp_login && $shop->smtp_pass;

			if ($shopSmtp) {
				$from = $shop->smtp_login;
				$replyTo = $shop->smtp_login;
			} else if (Lv7CMS::getInstance()->getCurrentSiteOption('smtp') && !$shopSmtp) {
				$from = Lv7CMS::getInstance()->getCurrentSiteOption('smtpUsername');
				$replyTo = Lv7CMS::getInstance()->getCurrentSiteOption('smtpUsername');
			} else {
				$from = $shop->mail_from;
				$replyTo = $shop->mail_from;
			}
		}

		$mail = new Zend_Mail('UTF-8');

		if ($shopSmtp && Lv7CMS::getInstance()->getCurrentSiteOption('smtp')) {
			$config = array();
			$host = $shop->smtp_host;
			if (!$host) {
				throw new Lv7CMS_Exception('SMTP Host is not defined!');
			}
			if ($port = $shop->smtp_port) {
				$config['port'] = $port;
			}
			if ($shop->smtp_login) {
				$config['auth'] = 'login';
			}
			if ($shop->smtp_ssl) {
				$config['ssl'] = 'ssl';
			}
			if ($username = $shop->smtp_login) {
				$config['username'] = $username;
			}
			if ($password = $shop->smtp_pass) {
				$config['password'] = $password;
			}

			$newTr = new Zend_Mail_Transport_Smtp($host, $config);
			$mail->setDefaultTransport($newTr);
		}

		$racipients = explode(",", str_replace([" ", ";"], ["", ","], $params['MANAGER_MAIL']));


		if (strlen($order->params->email)) {
			$mail->setFrom($from ? $from : $order->params->email, $order->params->contactFace);
			$mail->setReplyTo($order->params->email, $order->params->contactFace);
		} else {
			$mail->setFrom($from ? $from : $cmsSettings['defaultFromEmail']->getValue());
		}
		$mail->setBodyHtml($mailText);
		$mail->setSubject('Заказ #' . $order->number . ' на сайте ' . $cmsSettings['defaultSiteName']->getValue());
		$mail->createAttachment(
			$this->createXML($order),
			'application/xhtml+xml',
			Zend_Mime::DISPOSITION_ATTACHMENT,
            Zend_Mime::ENCODING_BASE64,
            'order_' . date("Y") . '_' . $order->number . '.xml');
		$mail->addTo($racipients);
		$mail->send();

		return true;
	}

	public function getOrderInfoForClient($orderId, $params = null)
	{
		if ($orderId instanceof Shop_Model_Order) {
			$order = $orderId;
		} else {
			$orderMapper = $this->_getOrderMapper();
			$order = $orderMapper->find($orderId);
		}

		$repository = new Lv7CMS_Resource_Repository($order->site);
		$template = $repository->find(Lv7CMS_Resource_Types::TEXT_TEMPLATES, 'shopMailClient');
		if (is_null($params)) {
			$params = $this->getOrderTemplateParams($order);
		}
		return $template->getText($params);
	}

	public function getOrderInfoForManager($orderId, $params = null)
	{
		if ($orderId instanceof Shop_Model_Order) {
			$order = $orderId;
		} else {
			$orderMapper = $this->_getOrderMapper();
			$order = $orderMapper->find($orderId);
		}

		$repository = new Lv7CMS_Resource_Repository($order->site);
		$template = $repository->find(Lv7CMS_Resource_Types::TEXT_TEMPLATES, 'shopMailManager');
		if (is_null($params)) {
			$params = $this->getOrderTemplateParams($order);
		}
		
		if ($_GET['kComand'] == 'add_to_lf'){ // Кирилл: если нужно распечатать word договор, то передаем только параметры		
			return $params;
		} else {
			return $template->getText($params);
		}					
	}


	public function getOrderTemplateParams($orderId)
	{
		if ($orderId instanceof Shop_Model_Order) {
			$order = $orderId;
		} else {
			$orderMapper = $this->_getOrderMapper();
			$order = $orderMapper->find($orderId);
		}

		//Shop_Service_Config::setSiteId($order->site);
		//$scheme = Shop_Service_Config::getScheme();
		//$settings = $scheme->getSettings();

		$repository = new Lv7CMS_Resource_Repository($order->site);
		$cmsSettings = $repository->findAll(Lv7CMS_Resource_Types::SETTINGS);

		$params['SITE_URL'] = $cmsSettings['defaultSiteUrl']->getValue();
		$params['SITE_NAME'] = $cmsSettings['defaultSiteName']->getValue();
		$params['ORDER_NUMBER'] = $order->number;

		$params['FACE_TYPE'] = ($order->params->faceType == 'natural') ? 'Физическое лицо' : 'Юридическое лицо';
		$params['COMPANY_INN'] = $order->params->company_inn;
		$params['CONTACT_FACE'] = $order->params->contactFace;
		$params['PHONE'] = $order->params->phone;
		$params['E_MAIL'] = $order->params->email;
		$params['ADDRESS'] = $order->params->address;
		$params['PASSPORT'] = $order->params->passport;

		$params['DISCOUNT_CARD'] = $order->params->discountCard;
		$params['DISCOUNT'] = $order->params->discount;
		$params['VIN_NUMBER'] = $order->params->vinNumber;
		$params['COMMENTS'] = $order->params->comments;

		if ($order->shop == 4 && $order->params->faceType == 'legal') {
			$params['DELIVERY'] = 'Самовывоз Осташковская';
		} elseif ($order->shop == 3 && $order->params->faceType == 'legal') {
			$params['DELIVERY'] = 'Самовывоз Лескова';
		} else {
			$params['DELIVERY'] = $order->params->deliveryName;
		}

		/*
		if ($order->params->delivery == 'MkadOut') {
			$params['DELIVERY'] .= ' (дистанция от МКАД: ' . $order->params->deliveryMileage . ' км)';
		}
		*/
		$params['DELIVERY_COST'] = Lv7_Service_Text::asPrice($order->params->deliveryCost, 0) . ' р.';
		$params['TOTAL_COST'] = Lv7_Service_Text::asPrice($order->params->totalCost, 0) . ' р.';

		$params['PAYMENT_METHOD'] = $order->params->paymentMethodName;

		Zend_Registry::set('tableBasketOrderId', $order->id);


		$params['GOODS_TABLE'] = $this->_getView()->action('table', 'basket', 'shop');
		// если вызов из админки - после вызова frontend контроллера отключаем подключенный в нем плагин
		$front = Zend_Controller_Front::getInstance();
		if ($front->hasPlugin('Application_Plugin_Backend')) {
			$front->unregisterPlugin('Application_Plugin_Frontend');
		}

		$params['YEAR'] = date("Y");
		$date = new Zend_Date();
		$params['TIMESTAMP'] = $date->toString('KKK');


		$params['SHOP_ADDRESS'] = '';
		$shopId = $order->shop;
		if ($shopId) {
			$shop = CatCommon_Service_Facade::getInstance()->getShop($shopId);
			if ($shop) {
				// проверка для VIN заказов - есть в них товары от поставщиков или нет.
				// В зависимости от этого выбирается адрес для отправки уведомительного письма
				$orderType = $order->type;
				if ($orderType == Shop_Model_Mapper_OrderTypes::VIN) {
					$orderPosMapper = new Shop_Model_Mapper_OrderPos();
					$posList = $orderPosMapper->getList($order->id);
					if ($posList) {
						foreach ($posList as $pos) {
							if ($pos->type == Shop_Model_Mapper_OrderPosTypes::SUPPLIERS) {
								$orderType == Shop_Model_Mapper_OrderTypes::DISTRIB;
								break;
							}
						}
					}
				}

				if ($orderType == Shop_Model_Mapper_OrderTypes::DISTRIB) {
					$params['MANAGER_MAIL'] = $shop->foreign_cars_mail;
					$params['MANAGER_PHONE'] = $shop->foreign_cars_phone;
				} else if($orderType == Shop_Model_Mapper_OrderTypes::ORDERHALL && ($order->params->delivery == 'MkadOut' || $order->params->delivery == 'MkadIn')) {
					$params['MANAGER_MAIL'] = $shop->moscow_mail;
					$params['MANAGER_PHONE'] = $shop->moscow_phone;
				} else {
					if ($order->params->delivery == 'OtherCity') {
						$params['MANAGER_MAIL'] = $shop->other_city_mail;
						$params['MANAGER_PHONE'] = $shop->other_city_phone;
					} else if ($order->params->paymentMethod == 'NonCash') {
						$params['MANAGER_MAIL'] = $shop->non_cash_mail;
						$params['MANAGER_PHONE'] = $shop->non_cash_phone;
						$params['SHOP_ADDRESS'] = $shop->address;
					} else {
						$params['MANAGER_MAIL'] = $shop->moscow_mail;
						$params['MANAGER_PHONE'] = $shop->moscow_phone;
					}
				}
			}
		}
		//$request = Zend_Controller_Front::getInstance()->getRequest();
		//$siteHost = $request->getScheme() . '://' . $request->getHttpHost();
		$siteHost = $cmsSettings['defaultSiteUrl']->getValue();
		$params['INVOICE_URL'] = $siteHost . $this->_getView()->url(array('orderId' => $order->id), 'shop::orderInvoice');

		//Shop_Service_Config::setSiteId(null);

		return $params;
	}

	public function PostProcessing($order, $addPositions = null)
	{
		$orderMapper = $this->_getOrderMapper();
		if (empty($order->doc_type)) {
			$order->doc_type = 13;
			$orderMapper->update($order);
		}
	    //create xms file // doctype =13
	    $xmlData = $this->createXML($order, $addPositions);
	    $options = Lv7CMS::getInstance()->getOptions();
	    $filePath = $options["cms"]["orderFilesPath"];
	    if(!empty($filePath) && !empty($xmlData)){
	        $filePath = rtrim($filePath, '\\/');
	        file_put_contents($filePath . DIRECTORY_SEPARATOR . sprintf("legal_order_%s.xml", $order->id), $xmlData);
	    }
	}

	protected function _getOrderMapper()
	{
		if ($this->_orderMapper === null) {
			$this->_orderMapper = new Shop_Model_Mapper_Orders();
		}
		return $this->_orderMapper;
	}

	protected function _getOrderPosMapper()
	{
		if ($this->_orderPosMapper === null) {
			$this->_orderPosMapper = new Shop_Model_Mapper_OrderPos();
		}
		return $this->_orderPosMapper;
	}

	protected function _getView()
	{
		$viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
		return $viewRenderer->view;
	}

	public function createXML($order, $addPositions = null)
	{
		Shop_Service_Config::setSiteId($order->site);
		$scheme = Shop_Service_Config::getScheme();
		$calculator = $scheme->getCalculator();

		$docTypes = new Shop_Model_Mapper_OrderDocTypes();

		$suppliers = Distrib_Service_Facade::getInstance()->getSuppliers();


		// если оплата заказа по карте - достаем платежную инструкцию
		if (strpos($order->params->paymentMethod, 'Sberbank') !== false) {
			$extObjFacade = new ExternalObjects_Service_Facade();
			$financeServiceFacade = new Finance_Service_Facade();
			$extObj = $extObjFacade->findByType('shop_order', $order->id);
			if ($extObj) {
				$pi = $financeServiceFacade->findPaymentInstructionByExtObj($extObj->id);
				//$this->view->paymentInstruction = $pi;
				if ($pi) {
					$paymentInfo = Finance_Service_Facade::getPaymentInfo($pi->id, $order->id);
				}
			}

		}

		$order_xml = "<zakaz>\r\n";
		$order_xml .= " <orderId>" . $order->id . "</orderId>\r\n";
		$order_xml .= " <mail>" . $order->params->email . "</mail>\r\n";
		$order_xml .= " <number>Заказ №" . $order->number . "</number>\r\n";
		$order_xml .= " <date>" . date("d-m-Y H:i") . "</date>\r\n";
		$order_xml .= " <faceType>" . (($order->params->faceType == 'natural') ? 'Физическое лицо' : 'Юридическое лицо') . "</faceType>\r\n";
		if ($order->params->company_inn) {
			$order_xml .= " <companyInn>" . $order->params->company_inn . "</companyInn>\r\n";
		}
		$order_xml .= " <fio>" . self::text2xml($order->params->contactFace) . "</fio>\r\n";
		$order_xml .= " <passport>" . $order->params->passport . "</passport>\r\n";
		$order_xml .= " <address_register>" . self::text2xml($order->params->address_register) . "</address_register>\r\n";
		$order_xml .= " <phone>" . self::text2xml($order->params->phone) . "</phone>\r\n";
		$order_xml .= " <address>" . self::text2xml($order->params->address) . "</address>\r\n";
		$order_xml .= " <paymentMethodName>" . $order->params->paymentMethodName . "</paymentMethodName>\r\n";
		$order_xml .= " <deliveryName>" . $order->params->deliveryName . "</deliveryName>\r\n";
		$order_xml .= " <deliveryCost>" . $order->params->deliveryCost . "</deliveryCost>\r\n";
		$order_xml .= " <comment>" . self::text2xml($order->params->comments) . "</comment>\r\n";
		$order_xml .= " <discountCode>" . $order->params->discountCard . "</discountCode>\r\n";
		$order_xml .= " <year>".date("Y")."</year>\r\n";
		$docType = null;
		if ($order->doc_type) {
			$docType = $docTypes->find($order->doc_type);
		}
		$order_xml .= " <docType>" . ($docType ? $docType->name : '') . "</docType>\r\n";
		$order_xml .= " <order>\r\n";

		$orderPosMapper = new Shop_Model_Mapper_OrderPos();

        if ($addPositions) {
			$posList = $addPositions;
		} else {
			$posList = $orderPosMapper->getList($order->id);
		}

		$posStatus = new Shop_Model_Mapper_OrderPosStatus();
		$quantity = 0;
		if (is_array($posList)) {
			$distribFacade = Distrib_Service_Facade::getInstance();
			$multService = new Shop_Service_Multiplicity();
			foreach ($posList as $pos) {
				$order_xml .= "  <orderposition>\r\n";

				$order_xml .= "   <code>" . self::text2xml($pos->hasWarehouseCode() ? $pos->getWarehouseCode() : '') . "</code>\r\n";
				$order_xml .= "   <name>" . self::text2xml($pos->getClientName()) ."</name>\r\n";
				$order_xml .= "   <art>" . self::text2xml($pos->articul) . "</art>\r\n";
				if ($pos->supplier && ($pos->supplier != 1) && !$pos->man_brand) {

                    $pos->man_brand = $pos->getArticleBrandName();
                    $pos->man_number = $pos->getArticleNumber();

					$supplier = Distrib_Service_Facade::getInstance()->getSupplier($pos->supplier);
					if ($supplier->type == Distrib_Model_Mapper_SupplierTypes::SUPPLIER) {
						$unit = $distribFacade->getUnitBySupplierAndAnalog($pos->supplier, $pos->analog);
						if (!$pos->price_purchase) {
							$pos->price_purchase = $unit->price;
						}
					}

                    if (($supplier->type == Distrib_Model_Mapper_SupplierTypes::PARTNER_WAREHOUSE)
                        && !$pos->price_purchase
                        && $pos->hasProduct()
                    ) {
                        $pricePurchase = Products_Service_Facade::getInstance()->findProductPrice($pos->getProduct()->id, 0);
                        if ($pricePurchase) {
                            $pos->price_purchase = $pricePurchase->price;
                        }
                    }
				}
				if ($pos->supplier && $pos->type != Shop_Model_Mapper_OrderPosTypes::WAREHOUSE) {
					if ($multInfo = $multService->check($pos->name)) {
						$pos->multInfo = $multInfo;
					}
				}
				$order_xml .= "   <brand>" . self::text2xml($pos->man_brand) . "</brand>\r\n";
				$order_xml .= "   <number>" . self::text2xml($pos->man_number) . "</number>\r\n";
				$itemDiscount = 0;
				if ($order->params->discount) {
					$itemDiscount = $calculator->itemDiscount($order->params->discount, $pos->name, $pos->sale);
				}
				$price = floor(max(1, $pos->getClientPrice() * 1 * (100 - $itemDiscount) / 100));

			    if ($pos->mult >= 2 && $itemDiscount && !$pos->multInfo) {
			    	$price = floor(max(1, $pos->getClientPrice() * $pos->mult * (100 - $itemDiscount) / 100));
			    } else if ($pos->mult >= 2 && !$itemDiscount && !$pos->multInfo) {
			    	$price = $price * $pos->mult;
			    }

                $order_xml .= "   <price>" . floor($pos->getClientPrice()) ."</price>\r\n";
				$order_xml .= "   <final_price>" . $price ."</final_price>\r\n";
				$order_xml .= "   <count>" . $pos->getClientQuantity() . "</count>\r\n";
				if ($pos->supplier) {
					$supplier = $suppliers[$pos->supplier];
					if ($supplier) {
						$order_xml .= "   <supplier>" . $supplier->lf_code . "</supplier>\r\n";
						$order_xml .= "   <extra_days>" . $supplier->delivery . "</extra_days>\r\n";
						$order_xml .= "   <supplier_price>" . round($pos->price_purchase) . "</supplier_price>\r\n";
					}
				} else {
						$order_xml .= "   <supplier></supplier>\r\n";
						$order_xml .= "   <extra_days></extra_days>\r\n";
						$order_xml .= "   <supplier_price>" . round($pos->price_purchase) . "</supplier_price>\r\n";
				}
				$order_xml .= "   <status>" . $pos->status . "</status>\r\n";
				$order_xml .= "   <statusname>" . $posStatus->find($pos->status)->name . "</statusname>\r\n";
				//$order_xml .= "   <type>".$item['type']."</type>\r\n";
				$order_xml .= "  </orderposition>\r\n";
				$quantity += $pos->quantity;
			}
		}
		$order_xml .= " </order>\r\n";
		$order_xml .= " <totalpos>" . $quantity . "</totalpos>\r\n";
		$order_xml .= " <totalprice>" . round($order->params->totalCost) . "</totalprice>\r\n";
		$order_xml .= " <prepaid>" . round($order->params->prepaid) . "</prepaid>\r\n";
		$order_xml .= " <vin>" . $order->params->vinNumber . "</vin>\r\n";
		$order_xml .= " <sberbankStatus>" . ($paymentInfo ? $paymentInfo->OrderStatusName : '') . "</sberbankStatus>\r\n";
		$order_xml .= " <sberbankDepositAmount>" . ($paymentInfo ? $paymentInfo->depositAmount / 100 : '') . "</sberbankDepositAmount>\r\n";
        if ($order->params->barcode) {
            $order_xml .= " <hallManagerBarcode>" . $order->params->barcode . "</hallManagerBarcode>\r\n";
            $employeeTerritory = CompanyFacade::findEmployeeTerritoryByBarcode($order->params->barcode);
            if ($employeeTerritory) {
                $order_xml .= " <hallManagerId>" . $employeeTerritory->id_lf . "</hallManagerId>\r\n";
                $order_xml .= " <hallManagerCodeMb>" . $employeeTerritory->code_mb . "</hallManagerCodeMb>\r\n";
            }
        } else {
        	$order_xml .= " <hallManagerBarcode></hallManagerBarcode>\r\n";
                $order_xml .= " <hallManagerId></hallManagerId>\r\n";
                $order_xml .= " <hallManagerCodeMb></hallManagerCodeMb>\r\n";
        }
		
		// Кирилл: дописываем в xml данные менеджера
			$db = $orderPosMapper->getDbAdapter();
			$dbConfig = $db->getConfig();
			
			$lfDoc = new Shop_Model_Mapper_LfDoc();
			$getLfManagerCode = $lfDoc->getLfManagerCode($dbConfig, $order->manager, $order->shop);
			$order_xml .= " <sotr_id>".$getLfManagerCode['idManager']."</sotr_id>\r\n";
			$order_xml .= " <sotr_kod_mb>".$getLfManagerCode['code_mdManager']."</sotr_kod_mb>\r\n";
			$order_xml .= " <cfo>".$getLfManagerCode['cfo']."</cfo>\r\n";
			
		$order_xml .= "</zakaz>";
		
		//$this->_xmlFilename = tempnam('/tmp', 'temp');
		//file_put_contents($this->_xmlFilename, $order_xml);
		return $order_xml;
	}

	public static function text2xml($name)
	{
		return str_replace(array('<', '>', '&'), array('', '', ' '), $name);
	}

    /**
     * Создание заказа поставщикам на основе заказа клиента
     * @param $orderId
     */
	public function sendToSuppliers($orderId){

        // Получаем id поставщиков
	    $mapperPos = new Shop_Model_Mapper_OrderPos();

        $posList = $mapperPos->getList($orderId);

        $suppliers = [];
        foreach ($posList as $pos){

            if(!$pos->supplier){
                continue;
            }

            $suppliers[$pos->supplier][]= $pos;
        }

        return $this->sendToSuppliersByIds($orderId,array_keys($suppliers));
    }

    /**
     * Создание заказа поставщикам на основе заказа клиента и списка нужных поставщиков
     * @param $orderId
     * @param array $supplierIds
     */
    public function sendToSuppliersByIds($orderId, array $supplierIds){

        $supplierOrderIds = [];

        $orderPosMapper = new Shop_Model_Mapper_OrderPos();
        $distribFacade = Distrib_Service_Facade::getInstance();
        $suppliers = $distribFacade->getSuppliers();

        $supplierOrderManager = new Distrib_Service_SupplierOrderManager();
		$supplierMapper = new Distrib_Model_Mapper_Units();
        foreach ($supplierIds as $supplierId) {
            $supplier = $suppliers[$supplierId];
            $orderPosBySupplier = $orderPosMapper->getList($orderId, $supplierId, Shop_Model_Mapper_OrderPosStatus::UNORDERED);
            if ($orderPosBySupplier) {

                $items = array();
                foreach ($orderPosBySupplier as $pos) {
                    $item = new stdClass();
                    if ($pos->type == Shop_Model_Mapper_OrderPosTypes::SUPPLIERS) {
                    	if (!$supplier->is_partner) {
                        	$findItem = $supplierMapper->findBySupplierAndName($supplier->id, $pos->name);
                        	$item->id_at_supplier = $findItem->id_at_supplier;
                     	}
                        $item->brand = $pos->man_brand;
                        $item->number = $pos->man_number;
                        $item->name = $pos->name;
                        $item->price = $pos->price_purchase;
                        $item->quantity = $pos->quantity;
                    }
                    if ($pos->type == Shop_Model_Mapper_OrderPosTypes::PARTNER) {
                        $product = Products_Service_Facade::getInstance()->findProduct($pos->code);
                        $item->id_at_supplier = $product->id_at_supplier;
                        $item->brand = $product->maker;
                        $item->number = $product->artikul1;
                        $item->name = $pos->name;
                        $pricePurchase = Products_Service_Facade::getInstance()->findProductPrice($product->id, 0);
                        $item->price = $pricePurchase->price;
                        $item->quantity = $pos->quantity;
                    }
                    $items[] = $item;
                }
                $supplierOrderId = $supplierOrderManager->createByClientOrder($supplier->id,$orderId, $items);

                $supplierOrderIds[]= $supplierOrderId;

                $sentOrder = $supplierOrderManager->send($supplierOrderId);

                if ($sentOrder) {
                    foreach ($orderPosBySupplier as $pos) {
                        $pos->status = Shop_Model_Mapper_OrderPosStatus::ORDERED;
                        $orderPosMapper->update($pos);
                    }
                }
            }
        }

        return $supplierOrderIds;
    }


    /**
     * отправка смс клиенту заказа
     * @param $orderId
     * @return bool
     * @throws Lv7_Exception
     */
    public function sendSmsToClient($orderId)
    {

        $orderMapper = new Shop_Model_Mapper_Orders();
        if(!$order = $orderMapper->find($orderId)){
            return false;
        }

        /*
        $clientMapper = new Crm_Model_Mapper_Clients();
        $client = $clientMapper->findByIds([$order->client])[0];
        $phoneNumber = $client->phone;
        if (!$phoneNumber) {
            return false;
        }
        */
        // отправляем SMS не на номер клиента, а на указанный в заказе номер.
        $phoneNumber = $order->params->phone;

        $repository = new Lv7CMS_Resource_Repository($order->site);

        $params = $this->getOrderTemplateParams($orderId);

        if (!$template = $repository->find(Lv7CMS_Resource_Types::TEXT_TEMPLATES, 'shopSmsClient')) {
            return false;
        }

        $text = $template->getText($params);

        $smsMapper = new Crm_Model_Mapper_Sms();
        $message = $smsMapper->createObject();
        $message->text = $text;
        $message->order = $orderId;
        $message->request = null;
        $message->phone = $phoneNumber;

        $crmServiceFacade = Crm_Service_Facade::getInstance();

        return $crmServiceFacade->sendSms($message);
    }

    /**
     * Логирование действий в заказах
     */
    public function creteOrdersLog($orderId, $managerId, $site, $action)
    {

    	$logMapper = new Shop_Model_Mapper_OrdersLog();
    	$log = $logMapper->createObject();
    	$log->order_id = $orderId;
    	$log->manager = $managerId;
    	$log->site = $site;
    	$log->action_type = $action;
    	$logMapper->insert($log);
    }

    /**
     * @param $orderId
     * @return Shop_Model_Order
     */
    public static function markForExport($orderId)
    {
        $mapper = new Shop_Model_Mapper_Orders();
        $order = $mapper->findOrFail($orderId);
        $order->to_export = 1;
        return $mapper->update($order);
    }


}