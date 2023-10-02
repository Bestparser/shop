<?php
// Кирилл yandexDelivery
class Shop_Model_Delivery_YandexDelivery extends Shop_Model_Delivery
{
    public function getName()
    {
        return 'Экспресс. Яндекс доставка (до 80 км. от магазина)';
    }

    public function getCost()
    {
        if ($_POST['yandexDeliveryIntervalPrice'] > 0){
            $deliveryCost = $_POST['yandexDeliveryIntervalPrice'];
        } else {
            $deliveryCost = 0;
        }
        return $deliveryCost;
    }

}