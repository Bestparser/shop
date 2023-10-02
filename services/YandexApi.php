<?php

class Shop_Service_YandexApi
{
    protected $apikey = 'y0_AgAAAABmpDFWAAc6MQAAAADVk6APHkdGbY-hQ-ecwNVrintn3Rjnhb4';
    protected $apikeyMap = 'e961b539-9a58-46b1-90e8-ce3cdfb71392';
    protected $contentType = 'Content-Type: application/json';
    protected $apiurl = 'https://b2b.taxi.yandex.net/b2b/cargo/integration/v2/';

    function __construct(){
        $this->urlApiMap = 'https://geocode-maps.yandex.ru/1.x/?apikey='.$this->apikeyMap.'&format=json&geocode=';
        $this->headersApiMap = array(
            $this->contentType
        );
        $this->headersApi = array(
            'Accept-Language: ru',
            $this->contentType,
            'Authorization: Bearer '.$this->apikey.''
        );
    }

    public function requestApi($data) // Общий функционал отправки api для всех методов в этом классе
    {
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $this->url,
            CURLOPT_HTTPHEADER => $this->headers,
            CURLOPT_RETURNTRANSFER => true
        ));
        if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $out = curl_exec($ch);
        $res = json_decode($out, true);

        return $res;
    }

    public function getBasketProductInfo(){ // Получение информации о продуктах из корзины
        $productsMapper = new Products_Model_Mapper_Products();

        $basket = Shop_Service_Config::getScheme()->getBasket();
        $basketProducts = $basket->getItems();
        $i = -1;
        foreach ($basketProducts as $basketProduct) {
            $i++;
            $product[$i]['quantity'] = $basketProduct->getQuantity();
            $product[$i]['price'] = $basketProduct->getPrice();

            $catalogProducts = $productsMapper->getList(array($basketProduct->getId()));
            foreach ($catalogProducts as $catalogProduct){
                $product[$i]['weight'] = $catalogProduct->weight;
                if ($product[$i]['weight'] == 0) $product[$i]['weight'] = '0.05';
                $product[$i]['width'] = intval($catalogProduct->width) / 1000;
                if ($product[$i]['width'] == 0) $product[$i]['width'] = '0.1';
                $product[$i]['length'] = intval($catalogProduct->length) / 1000;
                if ($product[$i]['length'] == 0) $product[$i]['length'] = '0.1';
                $product[$i]['height'] = intval($catalogProduct->height) / 1000;
                if ($product[$i]['height'] == 0) $product[$i]['height'] = '0.1';

                $product[$i]['title'] = $catalogProduct->name;
            }
        }
        return $product;
    }


    public function getClientPoint() // получение координат клиента
    {
        $this->headers = $this->headersApiMap;
        $this->url = $this->urlApiMap . urlencode($this->address);
        //$res = $this->requestApi();


        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $this->url,
            CURLOPT_HTTPHEADER => $this->headers,
            CURLOPT_RETURNTRANSFER => true
        ));
        if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $out = curl_exec($ch);
        $res = json_decode($out, true);


        $point = $res['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['Point']['pos'];
        $YandexDeliveryAddressAPI = $res['response']['GeoObjectCollection']['metaDataProperty']['GeocoderResponseMetaData']['request'];

        $probel = 0;
        $i = -1;
        while ($i < strlen($point)){
            $i++;
            if ($point[$i] == ' ') $probel = $i;
        }

        $clientPoints = array(
            'clientPointA' => substr($point, 0, $probel),
            'clientPointB' => substr($point, $probel+1, strlen($point)-$probel),
            'YandexDeliveryAddressAPI' => $YandexDeliveryAddressAPI
        );
        return $clientPoints;
    }

    public function deliveryCostIntervals() // Получение предварительной цены
    {
        if (intval($this->clientPointA) > 0){ // Если получены кординаты клиента (клиент предварительно ввел адрес и получил координаты)

            $products = $this->getBasketProductInfo(); // Получили характеристики товаров из других классов (ранее задействованных Романом)

            // Строим из характеристик json тело
            $mainProducts = '';
            $errorLimit = 0;
            $i = -1;
            while ($i < count($products)-1){
                $i++;

                if ($i < count($products)) $vergul = ',';
                if ((count($products)-$i) == 1) $vergul = '';
                $mainProducts = $mainProducts . '
						{
							"quantity": '.$products[$i]['quantity'].',
							"size": {
								"height": '.$products[$i]['height'].',
								"length": '.$products[$i]['length'].',
								"width": '.$products[$i]['width'].'
							},
							"weight": '.$products[$i]['weight'].'
						}'.$vergul.'
					';

                if ((($products[$i]['height'] * 100) > 150) or (($products[$i]['length'] * 100) > 150) or (($products[$i]['width'] * 100) > 150)) $errorLimit++;
                $countWeight = $countWeight + $products[$i]['weight'] * $products[$i]['quantity'];
            }
            // end Строим из характеристик json тело

            if ($countWeight > 20) $errorLimit++; // Заранее фиксируем превышение лимита по весу и ДШВ


            $data = '{
				"items": ['.$mainProducts.'],
				"requirements": {
					"taxi_classes": [
					  "express"
					]
				},
				"route_points": [
					{
						"coordinates": [
							37.611104,
							55.905353
						],
						"fullname": "МКАД, 86-й километр, вл13с1А, Москва"
					},
					{
						"coordinates": [
							'.$this->clientPointA.',
							'.$this->clientPointB.'
						],
						"fullname": "'.$this->yandexDeliveryAddress.'"
					}			
				],
				"skip_door_to_door": false	
			}';

            $this->headers = $this->headersApi;
            $this->method = 'offers/calculate';
            $this->url = $this->apiurl . $this->method;
            $res = $this->requestApi($data);

            $i = -1;
            while ($i < count($res['offers'])-1){
                $i++;
                $from = $res['offers'][$i]['delivery_interval']['from'];
                $to = $res['offers'][$i]['delivery_interval']['to'];
                if (date('H', strtotime($to)) < 21){
                    $HourMinFrom = date('H:i', strtotime($from));
                    $HourMinTo = date('H:i', strtotime($to));

                    $intervalPrices[$i+1]['price'] = round($res['offers'][$i]['price']['total_price_with_vat']) + 50;
                    $intervalPrices[$i+1]['to'] = 'До ' . $HourMinTo;
                    $intervalPrices[$i+1]['payload'] = $res['offers'][$i]['payload'];
                }
            }

            $this->method = 'check-price';
            $this->url = $this->apiurl . $this->method;
            $res = $this->requestApi($data);

            $intervalPrices['km'] = round($res['distance_meters']) / 1000;
            $intervalPrices['errorLimit'] = $errorLimit;
            return $intervalPrices;

        } else {
            return 0;
        }
    }

    public function createOrder() // Создать заказ
    {
        // Собираем информацию о продуктах
        $products = $this->getBasketProductInfo();
        $mainProducts = '';
        $errorLimit = 0;
        $i = -1;
        while ($i < count($products)-1){
            $i++;

            if ($i < count($products)) $vergul = ',';
            if ((count($products)-$i) == 1) $vergul = '';
            $mainProducts = $mainProducts . '
					{
						"cost_currency": "RUB",
						"cost_value": "'.$products[$i]['price'].'",
						"pickup_point": 1,
						"droppof_point": 2,								
						"quantity": '.$products[$i]['quantity'].',
						"size": {
							"height": '.$products[$i]['height'].',
							"length": '.$products[$i]['length'].',
							"width": '.$products[$i]['width'].'
						},
						"title": "'.$products[$i]['title'].'",
						"weight": '.$products[$i]['weight'].'
					}'.$vergul.'
				';
        }
        // end Собираем информацию о продуктах

        // Получаем новый номер заказа
        $orders = new Shop_Model_Mapper_Orders();
        $nextOrderId = $orders->getNewNumber();


        $data = '{
			"client_requirements": {
				"taxi_class": "express",
				"door_to_door": true
			},
			"emergency_contact": {
				"name": "Интернет отдел Планета Железяка",
				"phone": "+74959557857"
			},												
			"items": [
				'.$mainProducts.'
			],
			"offer_payload": "'.$this->payload.'",
			"optional_return": false,
			"route_points": [
				{
					"address": {	
						"fullname": "МКАД, 86-й километр, вл13с1А",
						"comment": "Заказ № ЯН'.$nextOrderId.'. ТЦ Планета Железяка. 1 этаж, пункт Самовывоз (рядом с отделом Камаз).",
						"coordinates": [
							37.611104,
							55.905353
						]				
					},
					"contact": {
						"email": "mkad@mkad86.ru",
						"name": "Интернет отдел Планета Железяка",
						"phone": "+74959557857"					
					},
					"point_id": 1,																													
					"skip_confirmation": true,
					"type": "source",
					"visit_order": 1				
				},
				{
					"address": {	
						"fullname": "'.$this->address.'",	
						"comment": "'.$this->comments.'",
						"coordinates": [
							'.$this->clientPointA.',
							'.$this->clientPointB.'
						]				
					},
					"contact": {
						"email": "'.$this->email.'",
						"name": "'.$this->name.'",
						"phone": "+7'.$this->phone.'"
					},	
					"point_id": 2,
					"skip_confirmation": true,
					"type": "destination",
					"visit_order": 2				
				}
			]
		}';

        $this->headers = $this->headersApi;
        $this->method = 'claims/create?request_id='.uniqid().'';
        $this->url = $this->apiurl . $this->method;
        $res = $this->requestApi($data);

        return array(
            'request' => $data,
            'response' => $res,
            'yandex_order_id' => $res['id'],
            'status' => $res['status']
        );
    }

    public function apivk($message){ // уведомлялка о проблемах в ВК
        $data = '{
				"message": "'.$message.'"
			}';
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => 'https://cards.bestparser.com/api/json/index2.php?method=mt_apivk_req',
            CURLOPT_RETURNTRANSFER => true
        ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_exec($ch);
    }


}
