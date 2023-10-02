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

		$adapter = Lv7CMS::getInstance()->getDbAdapter('cat_common');

		$table = 'discount_card';
		$pdo = $adapter->getConnection();
		
		$timeUpdate = date('Y-m-d 00:00:00', time() - 632000);

		$stmt = $pdo->query("SELECT * FROM $table WHERE (timestamp >= '$timeUpdate')");

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
					
					$con = $pdoClient->query("SELECT id,phone,discount_card,user FROM `crm_client` WHERE phone = " . $cardPhone)->fetch();

					$con2 = $pdoClient2->query("SELECT id,phone,discount_card,user FROM `crm_client` WHERE discount_card = " . $cardNumber)->fetch();

					if (!$cardActivity) {
						$cardNumber = null; // Удаляем карту если неактивна
					}

					if ($cardActivity) {
						$cardNumber = $row['number'];
					}


					$updatedTime = date('Y-m-d H:i:s');

					if ($con['id'] != $con2['id'] && $cardNumber == $con2['discount_card'] && !empty($con2)){
						$clientId = $con2['id'];
						$clientUser = $con2['user'];
						if ($cardNumber != $con2['discount_card']) {
							$pdoClient2->exec("UPDATE `crm_client` SET `discount_card`='$cardNumber', `updated`='$updatedTime' WHERE `id`='$clientId'");
							if (!empty($clientUser)) {
								$pdoClient2->exec("UPDATE `_users` SET `discount_card`='$cardNumber', `updated`='$updatedTime' WHERE `id`='$clientUser'");
							}
						}
					}

					if ($con['id'] != $con2['id'] && $con['discount_card'] != $cardNumber && $cardPhone == $con['phone'] && !empty($con)){
						$clientId = $con['id'];
						$clientUser = $con['user'];
						$pdoClient->exec("UPDATE `crm_client` SET `discount_card`='$cardNumber', `updated`='$updatedTime' WHERE `id`='$clientId'");
						if (!empty($clientUser)) {
							$pdoClient->exec("UPDATE `_users` SET `discount_card`='$cardNumber', `updated`='$updatedTime' WHERE `id`='$clientUser'");
						}
					}	

					$c++;
				}

				$microtime = explode(' ', microtime());
				$end = $microtime[1] . substr($microtime[0], 1);

				if ($processLogger) {
					$processLogger->log('Проверено карт: ' . $c . ', время загрузки ' . ($end - $start), Zend_Log::INFO);
				}

			
		

	}

} 
