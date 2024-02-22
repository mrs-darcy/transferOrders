<?php
use \Bitrix\Sale\Order;
use \Bitrix\Main\Loader;
use \Bitrix\Main\SystemException;
use \Bitrix\Main\UserTable;
use \Bitrix\Main\Result;

/**
 * Первое значение - идентификатор пользователя, от которого переносим заказы (и все остальное).
 * Второе значение - идентификатор пользователя, которому переносим заказы (и все остальное).
 */ 
const USER_IDS = [1, 2];

$result = transferOrders(USER_IDS);
print_r($result);

/**
 * Передаем заказы.
 *
 * @param  mixed $ids
 * @throws \Bitrix\Main\SystemException
 * @return array
 */
function transferOrders (array $ids): array {

	if (!Loader::includeModule('sale')) {
		throw new SystemException('Не удалось подключить модуль iblock.');
	}

	if (count($ids) > 2) {
		throw new SystemException('Ошибка количества передаваемых идентификаторов. Максимальное число - 2.');
	}

	$userIds = checkUserIds($ids);
	if (!empty($userIds)) {
		return getOrders($userIds[0], $userIds[1]);
	}

}

/**
 * Проверяем что указанные в массиве идентификаторы есть в таблице пользователей.
 *
 * @param  mixed $ids
 * @return array
 */
function checkUserIds (array $ids): array {
	return array_column(UserTable::getList([
		'select' => ['ID'],
		'order' => ['ID' => $ids[0] < $ids[1] ? 'ASC' : 'DESC'],
		'filter' => [
			'=ID' => $ids,
		],
	])->fetchAll(), 'ID');
}

/**
 * Получаем заказы первого пользователя, меняем пользователя данных заказов.
 *
 * @param  mixed $from
 * @param  mixed $to
 * @throws \Bitrix\Main\SystemException
 * @return array
 */
function getOrders(int $from, int $to): array {
	try {
		$rsData = Order::getList([
			'select' => ['ID'],
			'filter' => [
				"USER_ID" => $from,    
			],
			'order' => ['ID' => 'DESC'],
			'limit' => 2,
		]);
	} catch (Exception $e) {
		throw new SystemException('Ошибка! ' . $e->getMessage());
	}

	$arrRes = [];

	while ($arRow = $rsData->fetch()) {

		$order = Order::load($arRow['ID']);

		if (!$order) {
			throw new SystemException('Не удалось получить заказ пользователя.');
		}

		$order->setFieldNoDemand('USER_ID', $to);
		$res = $order->save();

		if(!$res->isSuccess()) {
			throw new SystemException(implode("<br>\n", $res->getErrorMessages()));
		}

		$arrRes[] = ['orderId' => $arRow['ID'], 'isSave' => $res->isSuccess()];
	}

	if (!$arrRes) {
		throw new SystemException('Не удалось получить заказы пользователя.');
	}
	return $arrRes;
}