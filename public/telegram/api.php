<?php
// SPDX-License-Identifier: GPL-2.0
/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com>
 * @license GPL-2.0
 * @package {no package}
 *
 * Copyright (C) 2021  Ammar Faizi <ammarfaizi2@gmail.com>
 */

require __DIR__."/../../src/init/web.php";

class messageContext
{
	/**
	 * @var int
	 */
	private int $chatId;

	/**
	 * @var int
	 */
	private int $startAt;

	/**
	 * @var int
	 */
	private int $limit;

	/**
	 * @var \PDO
	 */
	private PDO $pdo;

	/**
	 * Constructor.
	 *
	 * @param int $chatId	Chat ID
	 * @param int $startAt	Start at message id
	 * @param int $limit	Limit rows
	 */
	public function __construct(int $chatId, int $startAt = 0,
				    int $limit = 10)
	{
		$this->chatId  = $chatId;
		$this->startAt = $startAt;
		$this->limit   = $limit;
		$this->pdo     = DB::pdo();
	}


	/**
	 * @param array $fields
	 * @return array
	 */
	private static function cutPrx(array $fields): array
	{
		$ret = [];
		foreach ($fields as $k => $v)
			$ret[explode(".", $v)[1] ?? $v] = $k;

		return $ret;
	}


	/**
	 * @return array
	 */
	private static function dumpMessagesFields(bool $cutPrx = false)
	{
		static $fields = [
			"a.tg_msg_id",
			"a.reply_to_tg_msg_id",
			"a.msg_type",
			"a.has_edited_msg",
			"a.is_forwarded_msg",
			"a.is_deleted",
			"a.user_id",
			"c.text",
			"c.tg_date"
		];

		return $cutPrx ? self::cutPrx($fields) : $fields;
	}


	/**
	 * @param array &$data
	 * @return \PDOStatement
	 */
	private function loadStMessages(array &$data): PDOStatement
	{
		$fields = implode(",", self::dumpMessagesFields());
		$data[] = $this->chatId;
		$query  = <<<SQL
			SELECT 
				{$fields}
			FROM gw_group_messages AS a
			INNER JOIN gw_groups AS b ON b.id = a.group_id
			INNER JOIN gw_group_message_data AS c ON a.id = c.msg_id
			WHERE b.tg_group_id = ?

		SQL;
		if ($this->startAt) {
			$data[] = $this->startAt;
			$query .= " AND c.msg_id <= ? ";
		}
		$query .= " ORDER BY c.tg_date DESC LIMIT {$this->limit}";
		return $this->pdo->prepare($query);
	}


	/**
	 * @return array
	 */
	private static function dumpUsersFields(bool $cutPrx = false)
	{
		static $fields = [
			"a.id",
			"a.tg_user_id",
			"a.username",
			"a.first_name",
			"a.last_name",
			"a.is_bot"
		];

		return $cutPrx ? self::cutPrx($fields) : $fields;
	}


	/**
	 * @param array|int	$userIds
	 * @param array		&$data
	 * @return \PDOStatement
	 * @throws \Exception
	 */
	private function loadStUsers($userIds, array &$data): PDOStatement
	{
		if (!is_array($userIds))
			throw \Exception("userIds is not an array");

		if (!count($userIds))
			throw \Exception("userIds is empty");

		$fields = implode(",", self::dumpUsersFields());
		$query  = <<<SQL
			SELECT {$fields} FROM gw_users AS a WHERE a.id IN
		SQL;
		$i = 0;
		$query .= "(";
		foreach ($userIds as $userId => $v) {
			$data[] = $userId;
			$query .= ($i++ ? "," : "")."?";
		}
		$query .= ");";
		return $this->pdo->prepare($query);
	}


	private const JSON_FLAGS = JSON_UNESCAPED_SLASHES;

	/**
	 * @return void
	 */
	public function loadMessage(): void
	{
		$userIds = [];
		$data    = [];

		$st = $this->loadStMessages($data);
		$st->execute($data);
		echo "{\"messages\":{\"fields\":";
		$cc = self::dumpMessagesFields(true);
		echo json_encode($cc, self::JSON_FLAGS);
		echo ",\"data\":[";
		foreach ($st->fetchAll(PDO::FETCH_NUM) as $k => $data) {
			$data[$cc["user_id"]] = (int)$data[$cc["user_id"]];
			$userIds[$data[$cc["user_id"]]] = NULL;
			$data[$cc["tg_msg_id"]] = (int)$data[$cc["tg_msg_id"]];
			$data[$cc["has_edited_msg"]] = (int)$data[$cc["has_edited_msg"]];
			$data[$cc["reply_to_tg_msg_id"]] = (int)$data[$cc["reply_to_tg_msg_id"]];
			$data[$cc["is_forwarded_msg"]] = (int)$data[$cc["is_forwarded_msg"]];
			$data[$cc["is_deleted"]] = (int)$data[$cc["is_deleted"]];
			echo ($k ? "," : "").json_encode($data, JSON_UNESCAPED_SLASHES);
		}


		$data = [];
		$st = $this->loadStUsers($userIds, $data);
		$st->execute($data);
		echo "]},\"users\":{\"fields\":";
		$cc = self::dumpUsersFields(true);
		echo json_encode($cc, self::JSON_FLAGS);
		echo ",\"data\":{";
		$arr = $st->fetchAll(PDO::FETCH_NUM);
		foreach ($arr as $k => $data) {
			echo "\"{$data[$cc["id"]]}\":";
			$data[$cc["id"]] = (int)$data[$cc["id"]];
			$data[$cc["tg_user_id"]] = (int)$data[$cc["tg_user_id"]];
			$data[$cc["is_bot"]] = (int)$data[$cc["is_bot"]];
			echo json_encode($data, JSON_UNESCAPED_SLASHES);
			if (isset($arr[$k + 1]))
				echo ",";
		}
		echo "}}}";
	}
}


header("Content-Type: application/json");
$status  = "error";
$startAt = 0;
$limit   = 100;

if (!isset($_GET["group_id"]) || !is_string($_GET["group_id"]) ||
    !is_numeric($_GET["group_id"])) {
    	$code   = 400;
	$errMsg = "Missing group_id";
	goto err;
}

$groupId = (int)$_GET["group_id"];

if (isset($_GET["start_at"])) {
	if (!is_string($_GET["start_at"]) || !is_numeric($_GET["start"])) {
		$code    = 400;
		$errMsg  = "start_at must be numberic";
		goto err;
	}
	$startAt = (int)$_GET["start_at"];
}


if (isset($_GET["limit"])) {
	if (!is_string($_GET["limit"]) || !is_numeric($_GET["limit"])) {
		$code    = 400;
		$errMsg  = "limit must be numberic";
		goto err;
	}
	$limit = (int)$_GET["limit"];
}


try {
	ob_start();
	$st = new messageContext($groupId, $startAt, $limit);
	echo substr(json_encode([
		"status"	=> "ok",
		"code"		=> 200,
		"msg"		=> 1
	]), 0, -2);
	$st->loadMessage();
	echo "}";
} catch (\Excpetion $e) {
	$code   = 500;
	$errMsg = $e->getMessage();
	goto err_ob_clean;
} catch (\PDOException $e) {
	$code   = 500;
	$errMsg = $e->getMessage();
	goto err_ob_clean;
} catch (\Error $e) {
	$code   = 500;
	$errMsg = $e->getMessage();
	goto err_ob_clean;
}
exit(0);

err_ob_clean:
throw $e;
ob_get_clean();


err:
echo json_encode([
	"status"	=> $status,
	"code"		=> $code,
	"msg"		=> $errMsg
], JSON_UNESCAPED_SLASHES);
