<?php

/*--------------------------------------------
|
|	Данный класс предназначен для генерации всех документов, которые сейчас и в будущем нужно будет генерить в рамках модуля Shop.
|   Каждый метод отвечает за свой документ.
|	Там, где не обязательный параметр - так и обозначено. Все остальные - обязательные.
*/

class Shop_Service_Docs
{

    public static function clientContractOfSaleAllowed($order) // Кирилл: валидатор - разрешаем ли экспорт в LF (NEW LF BUTTON) при наличии необходимых данных
    {
        $error = 0;
        // Если документ не "пополнение ассортимента", то "контактное лицо" и "телефон" обязательно
        if (($order->doc_type != 6) and ((empty($order->params->contactFace)) or (empty($order->params->phone)))) $error++;
        // Если документ "609" или "264" или "088", то "адрес" доставки обязательно
        if ((in_array($order->doc_type, [3, 9, 15])) and (empty($order->params->address))) $error++;
        return $error == 0;
    }

    public static function clientContractOfSale($orderId) // В этом методе мы собираем и аккумулируем в объект Shop_Service_Docs все необходимые динамические данные (параметры) для вывода в договоре
    {
        $view = new Zend_View([ // сделать экземпляр View и передавать параметры сразу в него
            "scriptPath"    =>  __DIR__."/../views/scripts/orders/"
        ]);

        $order = Shop_Model_Mapper_Orders::findOrFail($orderId); // достаем заказ

        // Данные договора, которые нам присылает Куксов по API
        $view->doc_barcode = $order->doc_barcode; // Штрихкод
        $view->doc_number = $order->doc_number; // Номер документа (например, ИИxxxxxxxx)
        $view->doc_date = $order->doc_date; // Дата документа (ни в коем случае не путать с created. Это отдельная дата, которую Куксов присылает из LF по API)

        // Данные с админки заказа
        $view->order_responsible = $order->responsibleManager()->name; // Ответственный менеджер
        $view->order_vin_number = $order->params->vinNumber; // vin
        $view->order_prepaid = $order->params->prepaid; // Предоплата, внесенная клиентом в заказе

        // Данные о компании - юр.лицо, продающий товар
        $view->company_name = $order->company()->name; // Название юр.лица, продающего товар (компания, организация). Например, ИП Терехов Д.А
        $view->company_inn = $order->company()->inn; // ИНН компании
        $view->company_legal_address = $order->company()->legal_address; // Юридический адрес компании
        $view->company_fiz_address = $order->companyAddress()->address; // Фактический адрес компании

        $view->company_account = $order->companyAccount()->account; // р/с счет компании
        $view->company_bank = $order->companyAccount()->bank; // Банк компании
        $view->company_corr_account = $order->companyAccount()->corr_account; // к/с
        $view->company_bik = $order->companyAccount()->bik; // БИК компании
        $view->company_phone = $order->company_phone; // Номер телефона компании

        // Данные о клиенте, который покупает товар (тоже с админки)
        $view->client_contact_face = $order->params->contactFace; // ФИО клиента
        $view->client_passport = $order->params->passport; // Паспортные данные (не обязательный параметр)
        $view->client_address = $order->params->address_register; // Адрес клиента (не обязательный параметр)
        $view->client_phone = $order->params->phone; // Телефон клиента
        $view->client_email = $order->params->email; // Email клиента (не обязательный параметр)




        // Формируем html таблицу - данные о товарах
        $posList = $order->positions();
        $totalQuantity = 0;
        if (is_array($posList)) {
            foreach ($posList as $pos) {
                $totalQuantity += $pos->getClientQuantity();
            }
        }
        Shop_Service_Config::setSiteId($order->site);
        $scheme = Shop_Service_Config::getScheme();
        $calculator = $scheme->getCalculator();
        $view_posList = new Zend_View([
            "scriptPath"    =>  __DIR__."/../views/scripts/basket/"
        ]);
        $view_posList->posList = $posList;
        $view_posList->quantity = $totalQuantity;
        $view_posList->discount = $order->params->discount;
        $view_posList->cost = $order->params->goodsCost;
        $view_posList->calculator = $calculator;
        $view->order_pos_table = $view_posList->render("table.phtml");
        // end данные о товарах

        $res = $view->render("printdoc.phtml");
        return $res; // на выход даем html код
    }



}