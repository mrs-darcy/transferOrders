<?php
use Bitrix\Sale\Order;
use Bitrix\Main\Loader;
use \Bitrix\Main\SystemException;
use \Bitrix\Main\UserTable;
use \Bitrix\Main\Result;

const USER_IDS = [1, 2];

$result = transferOrders(USER_IDS);
print_r($result);

/**
 * transferOrders
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
 * checkUserIds
 *
 * @param  mixed $ids
 * @return array
 */
function checkUserIds (array $ids): array {
	return array_column(UserTable::getList([
		'select' => ['ID'],
		'order' => ['ID' => 'ASC'],
		'filter' => [
			'=ID' => $ids,
		],
	])->fetchAll(), 'ID');
}

/**
 * getOrders
 *
 * @param  mixed $from
 * @param  mixed $to
 * @throws \Bitrix\Main\SystemException
 * @return array
 */
function getOrders(int $from, int $to): array {
	$rsData = Order::getList([
		'select' => ['ID'],
		'filter' => [
			"USER_ID" => $from,    
		],
		'order' => ['ID' => 'DESC'],
		'limit' => 2,
	]);

	if (!$rsData) {
		return ['success' => false];
	}

	while ($arRow = $rsData->fetch()) {
		$order = Order::load($arRow['ID']);

		if (!$order) {
			throw new SystemException('Не удалось получить заказ пользователя.');
		}

		try {
			$order->setFieldNoDemand('USER_ID', $to);
			$res = $order->save();
		} catch (Exception $e) {
			throw new SystemException('Ошибка! ' . $e->getMessage());
		}
	}
	return ['success' => true];
}
