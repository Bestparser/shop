<?php

class Shop_Service_WorkerDiscountCards
{

	public function updatePhone($processLogger = null)
	{
		ini_set('memory_limit', '2048M');
		ini_set('max_execution_time', 0);

		$cardsMapper = new Shop_Model_Mapper_DiscountCards();
		$clientsMapper = new Crm_Model_Mapper_Clients();

		$adapter = Lv7CMS::getInstance()->getDbAdapter('cat_common');

		$table = 'discount_card';

		$pdo = $adapter->getConnection();
	
		$stmt = $pdo->query("SELECT * FROM $table");

			foreach ($stmt as $row) {
				if (isset($row['phone'])) {
					$phoneC = preg_replace('![^0-9]+!', '', $row['phone']);
					if (substr($phoneC, 0, 1) === '7' && $phoneC != $row['phone'] ||  substr($phoneC, 0, 1) === '8' && $phoneC != $row['phone']) { 
						$phoneNew = "+7" . substr($phoneC, 1);
					} else if (substr($phoneC, 0, 1) === '4' && $phoneC != $row['phone'] || substr($phoneC, 0, 1) === '9' && $phoneC != $row['phone'] || substr($phoneC, 0, 1) === '3' && $phoneC != $row['phone']) {
						$phoneNew = "+7" . $phoneC;
					} else if (strlen($phoneC) == 7 && $phoneC != $row['phone']) {
						$phoneNew = "+7495" . $phoneC;
					} else if ($phoneC != $row['phone']) {
						$phoneNew = $phoneC;
					}
					$cardId = $row['id'];
					$cardNumber = $row['number'];
					$cardActivity = $row['activity'];
					$card = $cardsMapper->find($cardId);
					$findClientByCard = $clientsMapper->findByDiscountCard($cardNumber);
					if ($phoneNew != $row['phone']) {
						$card->phone = $phoneNew;
						$updateCard = $cardsMapper->update($card);
					}
					if (!empty($findClientByCard) && $cardActivity == 1 && $findClientByCard->discount_card != $cardNumber) {
						$findClientByCard->discount_card = $cardNumber;
						$updateClientCard = $clientsMapper->update($findClientByCard);
					}
				}
			}

	}

	public function clientsBinding($processLogger = null)
	{
		ini_set('memory_limit', '2048M');
		ini_set('max_execution_time', 0);
		ini_set('set_time_limit', 0);

		$clientsMapper = new Crm_Model_Mapper_Clients();
		//$userMapper = new Users_Model_Mapper_User();
		//$userDataMapper = new Users_Model_Mapper_UserData();
		$adapter = Lv7CMS::getInstance()->getDbAdapter('cat_common');

		$table = 'discount_card';

		$pdo = $adapter->getConnection();
	
		//$stmt = $pdo->query("SELECT * FROM $table WHERE (date_issue >= '2010-01-01 00:00:00')");
		$stmt = $pdo->query("SELECT * FROM $table WHERE (id <= '455000' AND id >= '405000')");

		$adapterN = Lv7CMS::getInstance()->getDbAdapter('default');
		$pdoClient = $adapterN->getConnection();
		$pdoClient2 = $adapterN->getConnection();

			$microtime = explode(' ', microtime());
			$start = $microtime[1] . substr($microtime[0], 1);	

			$c = 0;
			foreach ($stmt as $row) {
				if (!strlen(trim($row['phone'])) || !strlen(trim($row['number']))) {
					continue;
				}
				
					$cardPhone = $row['phone'];
					$cardActivity = $row['activity'];
					$cardNumber = $row['number'];

					/*
					$findClientByCard = $clientsMapper->findByDiscountCard($cardNumber); // Ищем клиента по номеру карты
					$clientId = $findClientByCard->id;

					if (!empty($findClientByCard) && $cardActivity == 0 && $findClientByCard->discount_card == $cardNumber) {
						$cardNumber = null; // Удаляем карту если неактивна
					}

					if (!empty($findClientByCard) && $cardActivity == 1 && $findClientByCard->discount_card != $cardNumber) {

						$cardNumber = $row['number'];

					}
					*/
					//$findClientByPhone = $clientsMapper->findByPhone($cardPhone); // Ищем клиента по номеру телефона

					$con = $pdoClient->query("SELECT id,phone,discount_card FROM `crm_client` WHERE phone = " . $cardPhone)->fetch();

					$con2 = $pdoClient2->query("SELECT id,phone,discount_card FROM `crm_client` WHERE discount_card = " . $cardNumber)->fetch();

					if ($cardActivity == 0) {
						$cardNumber = null; // Удаляем карту если неактивна
					}

					if ($cardActivity == 1) {
						$cardNumber = $row['number'];
					}


					$updatedTime = date('Y-m-d H:i:s');


					if ($con2['id'] && $con2['discount_card'] != $cardNumber && $con['id'] != $con2['id']){
						$clientId2 = $con2['id'];
							$pdoClient2->exec("UPDATE `crm_client` SET `discount_card`='$cardNumber', `updated`='$updatedTime' WHERE `id`='$clientId2'");
							//$sql = "UPDATE `crm_client` SET discount_card=?, updated=? WHERE id=?";
							//$pdoClient->prepare($sql)->execute([$cardNumber, $updatedTime, $clientId2]);
						$c++;

					}

					if ($con['id'] && $con['discount_card'] != $cardNumber){
						$clientId = $con['id'];
							$pdoClient2->exec("UPDATE `crm_client` SET `discount_card`='$cardNumber', `updated`='$updatedTime' WHERE `id`='$clientId'");
							//$sql = "UPDATE `crm_client` SET discount_card=?, updated=? WHERE id=?";
							//$pdoClient->prepare($sql)->execute([$cardNumber, $updatedTime, $clientId]);
						$c++;
					}	

				}

				$microtime = explode(' ', microtime());
				$end = $microtime[1] . substr($microtime[0], 1);
				echo 'Время загрузки ' . ($end - $start);
				//if ($processLogger) {
					//$processLogger->log('Загружено карт: ' . $c . ', время загрузки ' . ($end - $start), Zend_Log::INFO);
				//}

			
		

	}

} 
