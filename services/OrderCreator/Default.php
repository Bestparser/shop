<?php

class Shop_Service_OrderCreator_Default extends Shop_Service_OrderCreator
{
	protected $_view;

	protected $_user;

	protected $_checkoutData;
	protected $_basket;
	protected $_calculator;
    protected $orderManager;
	protected $_xmlFilename;

	public function __construct()
	{
		$viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
		$this->_view = $viewRenderer->view;

		$this->_basket = Shop_Service_Config::getScheme()->getBasket();
		$this->_calculator = Shop_Service_Config::getScheme()->getCalculator();
		$this->_checkoutData = Shop_Service_Config::getScheme()->getCheckoutData();
        $this->orderManager = new Shop_Service_OrderManager();
		$this->_user = Zend_Registry::get('user');
	}


    /**
     * @return |null
     * @throws Lv7CMS_Exception
     * @throws Lv7_Exception
     * @throws Zend_Exception
     */
	public function create()
	{
		$items = $this->_basket->getItems();
		if (!is_array($items) || !count($items)) {
			throw new Lv7CMS_Exception('Basket is empty!');
		}

		$isHallOrder = Zend_Registry::get('acl')->can('shopHallOrderProcessing');


		$order = $this->_storeOrder();
		//$orderNumber = $order->number;

		if (!$this->_user->isGuest() && $this->_checkoutData->store_address && !$isHallOrder) {
			$this->_storePreset();
		}

		// вызов события о создании заказа
		Lv7CMS_Service_EventManager::getInstance()->trigger('orderCreated', $order);

		//сохранение файла заказа на сервере при условии что клиент обладает правами
		if ($isHallOrder) {

            $orderManager = new Shop_Service_OrderManager();

            //создаем заказы поставщикам
            $orderManager->sendToSuppliers($order->id);

            //send sms
            $orderManager->sendSmsToClient($order->id);

        }

        if ($order->params->faceType == 'legal' && $order->params->paymentMethod == 'NonCash') {
        	$sendOrder = $this->orderManager->PostProcessing($order);
        }

        if ($order->params->faceType == 'natural' && $order->params->delivery == 'OtherCity' || $isHallOrder) {
			//create xml file
            $xmlData = $this->orderManager->createXML($order);
            $options = Lv7CMS::getInstance()->getOptions();
            $filePath = $options["cms"]["orderFilesPath"];
            if(!empty($filePath) && !empty($xmlData)){
                $filePath = rtrim($filePath, '\\/');
                file_put_contents($filePath . DIRECTORY_SEPARATOR . sprintf("order_%s.xml", $order->id), $xmlData);
            }
        }

		return $order;
	}


	protected function _storeOrder()
	{
		$orderMapper = new Shop_Model_Mapper_Orders();

		// удаляем старые заказы
		$orderMapper->deleteOldOrders(180);

		$order = $orderMapper->createObject();
		if ($this->_checkoutData->siteId) {
			$order->site = $this->_checkoutData->siteId;
		} else {
			$order->site = Lv7CMS::getInstance()->getSiteId();
		}
		if ($this->_checkoutData->userId) {
			$order->user = ($this->_checkoutData->userId == 'no-set') ? null : $this->_checkoutData->userId;
		} else {
			$order->user = intval($this->_user->id);
		}
		if ($this->_checkoutData->managerId) {
			$order->manager = $this->_checkoutData->managerId;
		} else {
			$order->manager = 0;
		}
		$order->status = Shop_Model_Mapper_OrderStatus::NEW_ORDER;
		if ($this->_checkoutData->orderTypeId) {
			$order->type = $this->_checkoutData->orderTypeId;
		} else {
			$order->type = Shop_Model_Mapper_OrderTypes::NORMAL;
		}


        if ( Zend_Registry::get('acl')->can('shopHallOrderProcessing')) {
            $order->type = Shop_Model_Mapper_OrderTypes::ORDERHALL;
            $order->doc_type = Shop_Model_Mapper_OrderDocTypes::KKM_PARTNER_ORDER_DOC_TYPE;
            if (!$order->manager) {
            	$order->manager = '24278';
            }
            /*
            if ($this->_checkoutData->delivery == 'SelfDelivery') {
                $order->doc_type = Shop_Model_Mapper_OrderDocTypes::ISHOP_SELFDELIVERY_DOC_TYPE;
            } else {
                $order->doc_type = Shop_Model_Mapper_OrderDocTypes::ISHOP_DELIVERY_DOC_TYPE;
            }
            */
        }

		if ($this->_checkoutData->extObjId) {
			$order->ext_obj = $this->_checkoutData->extObjId;
		}

		if (strpos($this->_checkoutData->delivery, 'SelfDelivery') !== false && $this->_checkoutData->faceType != 'legal') {
			$shopId = preg_replace("/[^0-9]/", '', $this->_checkoutData->delivery);
		} else {
			$shopId = $this->_checkoutData->shop;
		}
		if (empty($shopId)) {
			$shop = CatCommon_Service_Facade::getInstance()->getDefaultShopBySite($order->site);
			$shopId = $shop->id;
		}
		$order->shop = $shopId;

		$order->params->faceType = $this->_checkoutData->faceType;
		$order->params->contactFace = $this->_checkoutData->contact_face;
		$order->params->phone = Lv7_Service_Text::normalizePhoneNumber($this->_checkoutData->phone);
		$order->params->email = $this->_checkoutData->email;
		if (strpos($this->_checkoutData->delivery, 'SelfDelivery') !== false) {
			$order->params->address = NULL;
		} else {
			$order->params->address = $this->_checkoutData->address;
		}
		$order->params->passport = $this->_checkoutData->passport;

		$order->params->discountCard = $this->_checkoutData->discount_card;
		$order->params->vinNumber = $this->_checkoutData->vin_number;
		$order->params->comments = $this->_checkoutData->comments;
		$order->params->company_inn = $this->_checkoutData->company_inn;
		if (isset($this->_checkoutData->barcode)) {
            $order->params->barcode = $this->_checkoutData->barcode;
		}

		if ($this->_checkoutData->delivery) {
			$delivery = Shop_Model_DeliveryFactory::create($this->_checkoutData->delivery);
			$order->params->delivery = $this->_checkoutData->delivery;
			$order->params->deliveryName = $delivery->getName();
			if ($delivery->getId() == 'MkadOut') {
				$order->params->deliveryName .= ' (дистанция от МКАД: ' . $this->_checkoutData->mileage . ' км)';
				$order->params->deliveryMileage = $this->_checkoutData->mileage;
			}
            // территорию для заказа выставляем согласно выбранному способу доставки
            $order->territory_id = $delivery->getSettingValue('territory');
		}

		if ($this->_checkoutData->paymentMethod) {
			$paymentMethod = Shop_Model_PaymentMethodFactory::create($this->_checkoutData->paymentMethod);
			$order->params->paymentMethod = $this->_checkoutData->paymentMethod;
			$order->params->paymentMethodName = $paymentMethod->getName();
		}

		$discount = 0;
		if (strlen($order->params->discountCard)) {
			$discountCardMapper = new Shop_Model_Mapper_DiscountCards();
			$discountCard = $discountCardMapper->findByNumber($order->params->discountCard);
			$discountCardNumber = null;
			if ($discountCard && $discountCard->activity) {
				$discount = $discountCard->discount;
				$discountCardNumber = $discountCard->number;
			}
		}

		$order->params->discount = $discount;

        /* // Кирилл закомментировал
		$order->params->goodsCost = $this->_calculator->getGoodsCost($discount);
		$order->params->deliveryCost = $this->_calculator->getDeliveryCost();
		$order->params->totalCost = $this->_calculator->getTotalCost($discount);
        */
        // Кирилл yandexDelivery: это чтобы при Яндекс Такси стоимость за доставку в админку передавалась
        $order->params->goodsCost = $this->_calculator->getGoodsCost($discount);
        if ($this->_checkoutData->YandexDeliveryCost > 0){
            $order->params->deliveryCost = $this->_checkoutData->YandexDeliveryCost;
            $order->params->totalCost = $order->params->goodsCost + $order->params->deliveryCost;
        } else {
            $order->params->deliveryCost = $this->_calculator->getDeliveryCost();
            $order->params->totalCost = $this->_calculator->getTotalCost($discount);
        }
        // end Кирилл


		// предоплата и флаг установки значения предоплаты вручную - используется для внутренних заказов
		$order->params->prepaidManualSet = true;

		if (!$this->_user->isGuest()) {
			//$order->user = $this->_user->id;
		} else if (class_exists('Users_Service_Facade') && strlen($order->params->email)) {
			$usersFacade = Users_Service_Facade::getInstance();
			$user = $usersFacade->findByEmail($order->params->email);
			if (empty($user)) {
				$user = $usersFacade->findByPhone($order->params->phone);
			}
			if ($user) {
				$order->user = $user->id;
			}
		}

		$bindClient = strlen($order->params->contactFace)
			|| strlen($order->params->email)
			|| strlen($order->params->phone);

		if (class_exists('Crm_Service_Facade') && $bindClient) {
			$crmFacade = Crm_Service_Facade::getInstance();
			$client = null;
			if ($order->user) {
				$client = $crmFacade->findClientByUser($order->user);
			}
			if (!$client && strlen($order->params->email)) {
				$client = $crmFacade->findClientByEmail($order->params->email);
			}
			if (!$client && strlen($order->params->phone)) {
				$client = $crmFacade->findClientByPhone($order->params->phone);
			}
			if (!$client && strlen($order->params->company_inn)) {
				$client = $crmFacade->findClientByCompanyInn($order->params->company_inn);
			}
			if (!$client) {
				$client = $crmFacade->createClient($order->params->contactFace, $order->params->phone, $order->params->email, $order->user, $discountCardNumber, $order->params->faceType, $order->params->company_inn);
			}
			$order->client = $client->id;
		}

        if ($order->params->faceType == 'natural' && $order->params->delivery == 'OtherCity') {
			if ($order->doc_type != 13) {
				$order->doc_type = 13;
			}
        }
		
		// Кирилл: Здесь подключается документ 0247
        if ($order->params->faceType == 'natural' && $order->params->delivery == 'YandexDelivery') {
			$order->doc_type = 10;
        }
		

		$order = $orderMapper->insert($order);

		if (class_exists('Crm_Service_Facade') && $bindClient) {
			$crmFacade->updateClient($client->id, $discountCardNumber, $order->params->phone, $order->params->faceType);
		}

		$recalc = false;
		if (is_array($items = $this->_basket->getItems())) {
			$posMapper = new Shop_Model_Mapper_OrderPos();
			$orderType = $order->type;
			foreach ($items as $item) {
				if ($item->getToCashbox()) {
					$recalc = true;
					continue;
				}
				$pos = $posMapper->createObject();
				$pos->order = $order->id;
				$pos->type = $item->getType() ? $item->getType() : Shop_Model_Mapper_OrderPosTypes::WAREHOUSE;
				$pos->code = $item->getCode();
				$pos->name = $item->getName();
				$pos->articul = $item->getArticul();
				$pos->price = $item->getPrice();
				$pos->quantity = $item->getQuantity();
				$pos->analog = (int) $item->getAnalog();
				$pos->supplier = (int) $item->getSupplier();
				$pos->sale = (int) $item->getSale();
				$pos->price_purchase = $item->getPricePurchase();
				$pos->man_brand = $item->getManBrand();
				$pos->man_number = $item->getManNumber();

				if ($pos->supplier > 1) {
					$supplier = Distrib_Service_Facade::getInstance()->getSupplier($pos->supplier);
					if ($supplier->type == Distrib_Model_Mapper_SupplierTypes::SUPPLIER) {
						$orderType = Shop_Model_Mapper_OrderTypes::DISTRIB;
						$pos->type = Shop_Model_Mapper_OrderPosTypes::SUPPLIERS;
						$pos->status = Shop_Model_Mapper_OrderPosStatus::UNORDERED;
					}
					if ($supplier->type == Distrib_Model_Mapper_SupplierTypes::PARTNER_WAREHOUSE) {
						$pos->type = Shop_Model_Mapper_OrderPosTypes::PARTNER;
						$pos->status = Shop_Model_Mapper_OrderPosStatus::UNORDERED;
					}
				} else {
					$pos->status = Shop_Model_Mapper_OrderPosStatus::ON_WAREHOUSE;
				}
				/*
				if ($pos->type == Shop_Model_Mapper_OrderPosTypes::SUPPLIERS) {
					$orderType = Shop_Model_Mapper_OrderTypes::DISTRIB;
					$pos->status = Shop_Model_Mapper_OrderPosStatus::UNORDERED;
				} else {
					$pos->status = Shop_Model_Mapper_OrderPosStatus::ON_WAREHOUSE;
				}
				*/
				$pos = $posMapper->insert($pos);

				if (($order->type != Shop_Model_Mapper_OrderTypes::VIN) && ($orderType != $order->type)) {
					$order->type = $orderType;
					$order = $orderMapper->update($order);
				}
			}
		}

		if ($recalc) {
			$orderManager = new Shop_Service_OrderManager();
			$orderManager->recalcOrder($order->id);
			$order = $orderMapper->find($order->id);
		}

		return $order;
	}

	protected function _storePreset()
	{
		$presetMapper = new Shop_Model_Mapper_UserPresets();
		$presets = $presetMapper->getList($this->_user->id);
		$map = array('contact_face', 'phone', 'email', 'address', 'delivery', 'mileage', 'paymentMethod');
		$add = true;
		if (is_array($presets)) {
			foreach ($presets as $preset) {
				$exist = true;
				foreach ($map as $field) {
					if ($this->_checkoutData->{$field} != $preset->params->{$field}) {
						$exist = false;
						break;
					}
				}
				if ($exist) {
					// обновляем пресет, чтобы потом его вытащить по updated desc
					$presetMapper->update($preset);
					$add = false;
					break;
				}
			}
		}
		if ($add) {
			$preset = $presetMapper->createObject();
			$preset->user = $this->_user->id;
			foreach ($map as $field) {
				$preset->params->{$field} = $this->_checkoutData->{$field};
			}
			$presetMapper->insert($preset);
		}
	}

}