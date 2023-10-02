<?php

class Shop_Model_DbTable_LfDoc extends Zend_Db_Table_Abstract
{
	protected $_name = 'shop_order_api';
	protected $_primary = 'id';

	public function dataDoc($dbConfig, $params, $get) // Получение параметров для генерации word договора
	{
		// Забираем данные о клиенте из ООП
			$wordDocData['vin_number'] = $params['VIN_NUMBER'];
			$wordDocData['prepaid'] = $get['prepaid'];
			$wordDocData['client_contact_face'] = $params['CONTACT_FACE']; // ФИО клиента
			$wordDocData['client_phone'] = $params['PHONE']; // телефон клиента
			$wordDocData['client_e_mail'] = $params['E_MAIL']; // почта клиента				
			$wordDocData['client_passport'] = $params['PASSPORT']; // почта клиента	
			if (strlen($get['address_register']) > 1){
				$wordDocData['client_address'] = $get['address_register']; // адрес регистрации
			} else {
				$wordDocData['client_address'] = $params['ADDRESS']; // адрес доставки						
			}
			$wordDocData['manager_name'] = $get['manager']; // почта клиента				
			$wordDocData['products'] = $params['GOODS_TABLE']; // почта клиента				

		// Забираем из LF данные по договору
			$select = $this->select();
			$select->where('order_id = ?', $get['orderId']);				
			foreach ($this->fetchAll($select) as $k){
				$wordDocData['lf_nomdoc'] = $k->nomdoc; // служебный номер заказа
				$wordDocData['lf_barCode'] = $k->barCode; // штрихкод заказа
				$wordDocData['lf_document'] = $k->document; // номер документа заказа
				$wordDocData['lf_kod_mb'] = $k->kod_mb; // код LF от реквизитов продавца-организации
				$wordDocData['lf_date'] = $k->date; // дата для договора
			}

		// Забираем из SQL данные - реквизиты продавца-организации
			$link = mysqli_connect($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], 'main') or die ('Error connect db');
			mysqli_query($link, 'SET NAMES utf8');
			mysqli_query($link, 'SET CHARACTER SET utf8');
			$query = "SELECT * FROM `crm_client` WHERE `code_client_lf`= '".$wordDocData['lf_kod_mb']."' ";
			$result = mysqli_query($link, $query);
			if (mysqli_num_rows($result) > 0){
				$wordDocData['org_id'] = mysqli_fetch_assoc(mysqli_query($link, $query))['id']; // порядковый номер crm_client
				$wordDocData['org_name'] = mysqli_fetch_assoc(mysqli_query($link, $query))['company_name']; // наименование организации продавца
				$wordDocData['org_inn'] = mysqli_fetch_assoc(mysqli_query($link, $query))['company_inn']; // ИНН организации продавца
				$wordDocData['org_phone'] = mysqli_fetch_assoc(mysqli_query($link, $query))['phone']; // телефон организации продавца
			}

			$query = "SELECT * FROM `crm_client_data` WHERE `client`= '".$wordDocData['org_id']."' ";
			$result = mysqli_query($link, $query);
			while ($row = mysqli_fetch_assoc($result)){
				if ($row['key'] == 'off_adress') $wordDocData['org_off_adress'] = $row['value']; // официальный адрес организации продавца
				if ($row['key'] == 'fiz_adress') $wordDocData['org_fiz_adress'] = $row['value']; // физический адрес организации продавца
				
				if ($row['key'] == 'rschet') $wordDocData['org_rschet'] = $row['value']; // РС организации продавца
				if ($row['key'] == 'bank') $wordDocData['org_bank'] = $row['value']; // Банк где РС организации продавца
				if ($row['key'] == 'kschet') $wordDocData['org_kschet'] = $row['value']; // КС организации продавца
				if ($row['key'] == 'bik') $wordDocData['org_bik'] = $row['value']; // БИК организации продавца
			}

		return $wordDocData;
	}

	public function showWord($wordDocData) // Вывод word договора (генерация html)
	{
		?>
			<style>
				table{
					width: 100%; font-size: 12px;
				}
				div{
					display: none;
				}
				p{
					font-size: 12px;
				}
				#barcoode{
					display: block !important;
				}
				.requisites p{
					margin: 5px 0px;
				}
			</style>
		
			<table>
				<tr>
					<td align="right">
						<style>
							@font-face {
								font-family: 'EanGnivc';
								src: url(/fonts/EANG000.TTF); 
							}
							 
							#barcoode {
								font-family: 'EanGnivc';
								font-size: 48px;
							}
						</style>
						<div id="barcoode"><?php echo $wordDocData['lf_barCode']; ?></div>
					</td>
				</tr>
				<tr>
					<td align="middle">
						<p style="font-weight: bold;">Договор № <?php echo $wordDocData['lf_document']; ?></p>
						<p>купли-продажи товара</p>
					</td>
				</tr>
			</table>
			<table>
				<tr>
					<td align="left">
						<p><?php echo $wordDocData['lf_date']; ?></p>
					</td>
					<td align="right">
						<p>г. Мытищи</p>
					</td>					
				</tr>
			</table>
			<table>
				<tr>
					<td align="justify">
						<p>&nbsp;&nbsp;&nbsp;<?php echo $wordDocData['org_name']; ?>, именуемое в дальнейшем Продавец, в лице "<?php echo $wordDocData['manager_name']; ?>", действующего на основании доверенности №1 от 05.03.2014 г., и <?php echo $wordDocData['client_contact_face']; ?>, именуемый(ая) в дальнейшем Покупатель, заключили настоящий договор купли-продажи товара (далее – Договор) на следующих условиях:</p>
						<p>&nbsp;&nbsp;&nbsp;1.	Продавец обязуется передать в собственность Покупателю запасные части, номерные агрегаты и аксессуары для автомобиля (далее – Товар).</p>
						<p>&nbsp;&nbsp;&nbsp;2.	Параметры транспортного средства Покупателя:</p>
					</td>
				</tr>			
			</table>
			<table border="1" cellspacing="0" cellpadding="2">
				<tr>
					<td width="2%"><p>1.</p></td>
					<td width="20%"><p style="font-weight: bold;">Марка, модель</p></td>
					<td></td>
				</tr>
				<tr>
					<td><p>2.</p></td>
					<td><p style="font-weight: bold;">VIN</p></td>
					<td><?php echo $wordDocData['vin_number']; ?></td>
				</tr>
				<tr>
					<td><p>3.</p></td>
					<td><p style="font-weight: bold;">АБС</br>Кондиционер</br>Усилитель руля</p></td>
					<td></td>
				</tr>
				<tr>
					<td><p>4.</p></td>
					<td><p style="font-weight: bold;">Год выпуска</p></td>
					<td></td>
				</tr>
				<tr>
					<td><p>5.</p></td>
					<td><p style="font-weight: bold;">Прочие данные</p></td>
					<td></td>
				</tr>
			</table>
			<p>&nbsp;&nbsp;&nbsp;Достоверность указанных сведений подтверждаю: ______________ <?php echo $wordDocData['client_contact_face']; ?></p>
			<p>&nbsp;&nbsp;&nbsp;3.	Перечень товаров:</p>
			<?php echo $wordDocData['products']; ?>
			<p>&nbsp;&nbsp;&nbsp;4.	Продавец не несет ответственность за невозможность использования и (или) установки Товара на транспортное средство, если:</p>
			<p>&nbsp;&nbsp;&nbsp;-	Покупателем при заключении Договора не была предоставлена Продавцу достоверная информация о марке, модели, идентификационном номере (VIN), годе выпуска и других параметрах транспортного средства, на которое должен быть установлен Товар или</p>
			<p>&nbsp;&nbsp;&nbsp;-	если на транспортном средстве произведены изменения, не соответствующие данным идентификационного номера (VIN) или</p>
			<p>&nbsp;&nbsp;&nbsp;-	если номер и (или) другие параметры детали указаны Покупателем.</p>
			<p>&nbsp;&nbsp;&nbsp;5.	Покупатель обязуется оплатить и принять Товар на условиях, указанных в Договоре.</p>
			<p>&nbsp;&nbsp;&nbsp;6.	Условия оплаты: <?php if ($wordDocData['prepaid']) {echo $wordDocData['prepaid'];} else {echo '0';} ?> рублей 00 копеек. в день подписания Договора, оставшаяся часть стоимости Товара – в день получения Товара. Товар отгружается при условии его полной оплаты.</p>
			<p>&nbsp;&nbsp;&nbsp;7.	Предоплата обеспечивает требования Продавца в части оплаты Товара, а так же неустоек, штрафов, пени и возмещения убытков, связанных с ненадлежащим исполнением Покупателем условий Договора.</p>
			<p>&nbsp;&nbsp;&nbsp;8.	За просрочку оплаты Товара, предусмотренную п.п. 3 и 6 Договора, Покупатель уплачивает Продавцу пени в размере 0,1 % от стоимости неоплаченного товара за каждый день просрочки.</p>
			<p>&nbsp;&nbsp;&nbsp;9.	Порядок передачи Товара: со склада Продавца, расположенного по адресу: 141031, Московская область, городской округ Мытищи, Автодорога МКАД, 86-й километр, вл. 13. строение 1А</p>
			<p>&nbsp;&nbsp;&nbsp;10. Покупатель обязан получить Товар в течение 10 дней с момента уведомления о готовности Товара к передаче, а в случае неполучения такового – в течение 5 дней с момента истечения срока выполнения заказа. При пропуске указанного срока Продавец оставляет за собой право расторгнуть Договор в одностороннем порядке и реализовать третьим лицам неполученный Покупателем Товар.</p>
			<p>&nbsp;&nbsp;&nbsp;11. Право собственности на Товар переходит к Покупателю с момента полной оплаты договорной стоимости в соответствии с п.3 настоящего Договора. Проверка качества, комплектности, тары Товара производится Покупателем в момент передачи товара на складе Продавца. В случае выявления несоответствий Товара условиям Договора, Покупатель обязан незамедлительно сообщить об этом Продавцу в письменной форме, а если таковые не заявлены, то считается, что Продавец выполнил условия настоящего Договора (поставил Товар надлежащего качества, согласованной комплектности, в соответствующей таре и/или упаковке).</p>
			<p>&nbsp;&nbsp;&nbsp;12. Гарантийный срок на Товар устанавливается заводом-изготовителем. Если гарантийный срок на Товар изготовителем не установлен, то он составляет 30 (тридцати) дней с момента передачи Товара Покупателю.</p>
			<p>&nbsp;&nbsp;&nbsp;13. В случае невозможности передачи Покупателю Товара (части Товара) Продавец по согласованию с Покупателем либо продлевает срок поставки Товара, либо возвращает Покупателю сумму произведенной за такой Товар (часть Товара) предоплаты. При этом проценты за пользование денежными средствами Покупателя, а также возможные убытки Покупателю не возмещаются.</p>
			<p>&nbsp;&nbsp;&nbsp;14. Товар, относящийся к деталям электрической группы, текстильные изделия (чехлы, коврики и т.д.), Товар, имеющий индивидуально-определенные свойства (заказывался в соответствии с предоставленным VIN автомобиля или по индивидуальным параметрам), а также бывший в употреблении или устанавливавшийся на автомобиль, неоригинальные запасные части, имеющие конструктивные отличия от оригинальных запасных частей, но пригодные к установке и эксплуатации на автомобиле Покупателя, возврату и обмену не подлежат.</p>
			<p>&nbsp;&nbsp;&nbsp;15. Официальная замена номеров запасных частей производителем не является причиной для отказа или возврата Товара.</p>
			<p>&nbsp;&nbsp;&nbsp;16. В случаях, когда Покупатель в нарушение условий настоящего Договора аннулирует заказ, не принимает Товар в установленный срок или отказывается от принятого товара (возврат), Продавец вправе потребовать от Покупателя принять Товар или отказаться от исполнения договора, при этом сумма внесенной предоплаты возвращается Покупателю за вычетом <span style="font-size: 24px; font-weight: bold;">штрафа в размере 20,0 %</span> от договорной цены товара и иных расходов Продавца связанных с исполнением договора (в том числе, но не ограничиваясь: за хранение, транспортные расходы и т.п.).</p>
			<p>&nbsp;&nbsp;&nbsp;17. Требования Покупателя, связанные с недостатками Товара, заявляются в письменной форме с приложением документов, обосновывающих эти требования, в том числе документов, подтверждающих факт приобретения Товара, технического паспорта или заменяющего его документа, гарантийного талона, а также документов, подтверждающих недостатки Товара.</p>
			<p>&nbsp;&nbsp;&nbsp;Претензии по недостаткам оригинального Товара рассматриваются только при условии установки такого Товара в дилерском центре производителя.</p>
			<p>&nbsp;&nbsp;&nbsp;18. Покупателем получена полная и исчерпывающая информация об основных потребительских свойствах Товара, адресе Продавца, месте изготовления Товара, данных производителя Товара, цене, условиях приобретения Товара, сроке доставки, сроке службы Товара.</p>
			
			</br>
	
			
			<table class="requisites">
				<tr valign="top">
					<td width="50%">
						<p style="font-size: 14px; font-weight: bold;">Продавец</p>
						<p style="font-size: 14px;"><?php echo $wordDocData['org_name']; ?></p>
						<p style="font-size: 14px;">ИНН <?php echo $wordDocData['org_inn']; ?></p>
						<p style="font-size: 14px;">Юридический адрес:</p>
						<p style="font-size: 14px;"><?php echo $wordDocData['org_off_adress']; ?></p>
						<p style="font-size: 14px;">Фактический адрес:</p>
						<p style="font-size: 14px;"><?php echo $wordDocData['org_fiz_adress']; ?></p>
						<p style="font-size: 14px;">р/с <?php echo str_replace("'", "", $wordDocData['org_rschet']); ?></p>							
						<p style="font-size: 14px;"><?php echo str_replace("'", '"', $wordDocData['org_bank']); ?></p>
						<p style="font-size: 14px;">к/с <?php echo str_replace("'", "", $wordDocData['org_kschet']); ?></p>
						<p style="font-size: 14px;">БИК <?php echo $wordDocData['org_bik']; ?></p>
						<p style="font-size: 14px;">Статус заказа</p>
						<p style="font-size: 14px;">Телефон: <?php echo $wordDocData['org_phone']; ?></p>
						</br>
						<p style="font-size: 14px;">___________________/____________/</p>
					</td>
					<td width="50%">
						<p style="font-size: 14px; font-weight: bold;">Покупатель</p>
						<p style="font-size: 14px;">ФИО: <?php echo $wordDocData['client_contact_face']; ?></p>
						<p style="font-size: 14px;">Паспортные данные: <?php echo $wordDocData['client_passport']; ?></p>
						<p style="font-size: 14px;">Адрес: <?php echo $wordDocData['client_address']; ?></p>
						<p style="font-size: 14px;">Телефон: <?php echo $wordDocData['client_phone']; ?></p>
						<p style="font-size: 14px;">E-mail: <?php echo $wordDocData['client_e_mail']; ?></p>
						</br>
						</br>
						</br>
						</br>
						</br>
						</br>
						</br>
						</br>
						</br>
						<p>_____________________ <?php echo $wordDocData['client_contact_face']; ?></p>
					</td>
				</tr>
			</table>
			</br>
			</br>						
			<p>Товар, указанный в пункте 3 Договора, получил. С пунктом 16 ознакомлен. Претензий по количеству, ассортименту, комплектности и внешнему виду Товара не имею.</p>
			<p>_______________________________ <?php echo $wordDocData['client_contact_face']; ?></p>
			<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(подпись, дата)</p>
		<?php
	}
	
	public function getLfManagerCode($dbConfig, $managerId, $orderShopId) // Получение LF кода сотрудника
	{
		if ($managerId > 0){
			$link = mysqli_connect($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], 'main') or die ('Error connect db');
			mysqli_query($link, 'SET NAMES utf8');
			mysqli_query($link, 'SET CHARACTER SET utf8');

			// Выясняем: стучимся в sql по колонке `mkad_sotr_id` или по `com_sotr_id`
				$query = "SELECT 
					`_users`.`id`,
					`_users`.`default_sotr_id`,
					`_users`.`mkad_sotr_id`,
					`_users`.`com_sotr_id`
					FROM `_users`
					WHERE `_users`.`id` = '".$managerId."' ";			
				
				$default_sotr_id = mysqli_fetch_assoc(mysqli_query($link, $query))['default_sotr_id'];
				$mkad_sotr_id = mysqli_fetch_assoc(mysqli_query($link, $query))['mkad_sotr_id'];
				$com_sotr_id = mysqli_fetch_assoc(mysqli_query($link, $query))['com_sotr_id'];			
				
				if (($default_sotr_id == 'mkad') or ($default_sotr_id == 'com')){ // проставлено по умолчанию
					if ($default_sotr_id == 'mkad') $colum = 'mkad_sotr_id';
					if ($default_sotr_id == 'com') $colum = 'com_sotr_id';				
				} else { // не проставлено по умолчанию
					// Смотрим, что в базе вообще проставлено: mkad или com (для менеджеров, у которых проставлено одно из двух: либо мкад либо ком)
					if ($mkad_sotr_id) $colum = 'mkad_sotr_id';
					if ($com_sotr_id) $colum = 'com_sotr_id';
				}
				// Если менеджер все-таки принудительно поставил магазин в заказе, то смотрим по магазину
				if ($orderShopId > 0){
					if ($orderShopId == 2) $colum = 'mkad_sotr_id';
					if ($orderShopId > 2) $colum = 'com_sotr_id';
				}
			// end Выясняем: стучимся в sql по колонке `mkad_sotr_id` или по `com_sotr_id`
			
			// Непосредственно достаем коды
				$query = "SELECT 
					`_users`.`id`,
					`_users`.`".$colum."`,				
					`_users_csv2`.`sotr_id`,
					`_users_csv2`.`kod_mb`,
					`_users_csv2`.`cfo`
					FROM `_users` 
					INNER JOIN `_users_csv2` ON `_users`.`".$colum."` = `_users_csv2`.`sotr_id`
					WHERE `_users`.`id` = '".$managerId."' ";			
				
				$code_mdManager = mysqli_fetch_assoc(mysqli_query($link, $query))['kod_mb']; // код LF ответственный менеджер
				$idManager = mysqli_fetch_assoc(mysqli_query($link, $query))['sotr_id']; // код сотрудника		
				$cfo = mysqli_fetch_assoc(mysqli_query($link, $query))['cfo']; // код сотрудника		
			
		} else {
			$code_mdManager = 0;
			$idManager = 0;
			$cfo = 'ГС';
		}			
		$res = array(
			'code_mdManager' => $code_mdManager,
			'idManager' => $idManager,
			'cfo' => $cfo
		);
		
		return $res;
	}
	
	public function getLfManagerCode2($dbConfig, $orderId, $orderShopId) // Получение LF код менеджера в заказах-файлах
	{	
		// Получаем код менеджера
			$link = mysqli_connect($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], $dbConfig['dbname']) or die ('Error connect db');
			mysqli_query($link, 'SET NAMES utf8');
			mysqli_query($link, 'SET CHARACTER SET utf8');
			
			$query = "SELECT `id`, `manager` FROM `distrib_file_order` WHERE `id`= '".$orderId."' ";
			$result = mysqli_query($link, $query);
			if (mysqli_num_rows($result) > 0) $managerId = mysqli_fetch_assoc(mysqli_query($link, $query))['manager']; // получаем код менеджера			
		// end Получаем код менеджера
		
		if ($managerId > 0){	

			$link = mysqli_connect($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], 'main') or die ('Error connect db');
			mysqli_query($link, 'SET NAMES utf8');
			mysqli_query($link, 'SET CHARACTER SET utf8');				
		
			// Выясняем: стучимся в sql по колонке `mkad_sotr_id` или по `com_sotr_id`
				$query = "SELECT 
					`_users`.`id`,
					`_users`.`default_sotr_id`,
					`_users`.`mkad_sotr_id`,
					`_users`.`com_sotr_id`
					FROM `_users`
					WHERE `_users`.`id` = '".$managerId."' ";			
				
				$default_sotr_id = mysqli_fetch_assoc(mysqli_query($link, $query))['default_sotr_id'];
				$mkad_sotr_id = mysqli_fetch_assoc(mysqli_query($link, $query))['mkad_sotr_id'];
				$com_sotr_id = mysqli_fetch_assoc(mysqli_query($link, $query))['com_sotr_id'];			
				
				if (($default_sotr_id == 'mkad') or ($default_sotr_id == 'com')){ // проставлено по умолчанию
					if ($default_sotr_id == 'mkad') $colum = 'mkad_sotr_id';
					if ($default_sotr_id == 'com') $colum = 'com_sotr_id';				
				} else { // не проставлено по умолчанию
					// Смотрим, что в базе вообще проставлено: mkad или com (для менеджеров, у которых проставлено одно из двух: либо мкад либо ком)
					if ($mkad_sotr_id) $colum = 'mkad_sotr_id';
					if ($com_sotr_id) $colum = 'com_sotr_id';
				}
				// Если менеджер все-таки принудительно поставил магазин в заказе, то смотрим по магазину
				if ($orderShopId > 0){
					if ($orderShopId == 2) $colum = 'mkad_sotr_id';
					if ($orderShopId > 2) $colum = 'com_sotr_id';
				}		
			// end Выясняем: стучимся в sql по колонке `mkad_sotr_id` или по `com_sotr_id`
			
			
			// Непосредственно достаем коды
				$query = "SELECT 
					`_users`.`id`,
					`_users`.`".$colum."`,				
					`_users_csv2`.`sotr_id`,
					`_users_csv2`.`kod_mb`,
					`_users_csv2`.`cfo`
					FROM `_users` 
					INNER JOIN `_users_csv2` ON `_users`.`".$colum."` = `_users_csv2`.`sotr_id`
					WHERE `_users`.`id` = '".$managerId."' ";			
				
				$code_mdManager = mysqli_fetch_assoc(mysqli_query($link, $query))['kod_mb']; // код LF ответственный менеджер
				$idManager = mysqli_fetch_assoc(mysqli_query($link, $query))['sotr_id']; // код сотрудника		
				$cfo = mysqli_fetch_assoc(mysqli_query($link, $query))['cfo']; // код сотрудника
		} else {
			$code_mdManager = 0;
			$idManager = 0;
			$cfo = 'ГС';
		}			
		$res = array(
			'code_mdManager' => $code_mdManager,
			'idManager' => $idManager,
			'cfo' => $cfo
		);
		
		return $res;		

	}
	
	public function insertXML($dbConfig, $id, $xml) // insert/update в sql данных заказа
	{
		$resLF = 0; // Датчик ответа LF (изначально - 0. Если 1 - то ответ положительный из LF пришел)
		
		// Забираем ответ LF
		$select = $this->select();
		$select->where('order_id = ?', $id);	
		$d = 0; // Датчик: количество строк в sql
		foreach ($this->fetchAll($select) as $k){
			$d++;
			$nomdoc = $k->nomdoc; // служебный номер заказа
			$barCode = $k->barCode; // штрихкод заказа
			$document = $k->document; // номер документа заказа
			$status = $k->status; // Если Куксов подхватил, то статус изменяется с "new" на "processing"
		}

		// Если из LF пришел уже ответ
		if ($status == 'finish') $resLF = 1;		

		// По просьбе Куксова в xml убераем двойные кавычки и экранирование
		$xml = str_replace('"', '', $xml); // убираем двойные кавычки
		$xml = stripcslashes($xml); // убираем обраный слеш

		if ($d > 0){ // Если запись в sql уже существует, то просто update xml (вдруг менеджер изменил данные)
			$data = array(
				'xml' => $xml,
				'updated' => date('Y-m-d H:i:s')
			);
			$where = $this->_db->quoteInto('order_id = ?', $id);
			$this->update($data, $where);			
		} else { // Если записи такой нет, то делаем новую запись insert (в данном заказае менеджер впервые нажал кнопку LF)
			$xml2 = simplexml_load_string($xml);
			$data = array(
				'order_id' => $id,
				'xml' => $xml,
				'status' => 'new',
				'cfo' => $xml2->cfo
			);
			$this->insert($data);
		}
		?><div style="display: none;" class="result"><?php echo $resLF; ?></div><?php
	}
	
	public function selectOrder($orderId)
    {
        $select = $this->select();
        $select->where('order_id = ?', $orderId);
        return $this->fetchAll($select);
    }
	
}
