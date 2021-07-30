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


	public const LOAD_MSG_FIELDS = [
		"tg_msg_id"		=> 0,
		"reply_to_tg_msg_id"	=> 1,
		"msg_type"		=> 2,
		"has_edited_msg"	=> 3,
		"is_forwarded_msg"	=> 4,
		"is_deleted"		=> 5,
		"text"			=> 6,
		"tg_date"		=> 7
	];


	/**
	 * @param array &$data
	 * @return \PDOStatement
	 */
	private function loadMessageBuildStmt(array &$data): PDOStatement
	{
		$data[] = $this->chatId;
		$query  = <<<SQL
			SELECT 
				a.tg_msg_id,
				a.reply_to_tg_msg_id,
				a.msg_type,
				a.has_edited_msg,
				a.is_forwarded_msg,
				a.is_deleted,
				c.text,
				c.tg_date
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
	 * @return void
	 */
	public function loadMessage(): void
	{
		$data = [];
		$st   = $this->loadMessageBuildStmt($data);
		$st->execute($data);

		$cc = self::LOAD_MSG_FIELDS;
		echo "{\"fields\":".json_encode($cc);
		echo ",\"data\":[";
		foreach ($st->fetchAll(PDO::FETCH_NUM) as $k => $data) {
			$data[$cc["tg_msg_id"]] = (int)$data[$cc["tg_msg_id"]];
			$data[$cc["has_edited_msg"]] = (int)$data[$cc["has_edited_msg"]];
			$data[$cc["reply_to_tg_msg_id"]] = (int)$data[$cc["reply_to_tg_msg_id"]];
			$data[$cc["is_forwarded_msg"]] = (int)$data[$cc["is_forwarded_msg"]];
			$data[$cc["is_deleted"]] = (int)$data[$cc["is_deleted"]];
			echo ($k ? "," : "").json_encode($data);
		}
		echo "]}";
	}
}

header("Content-Type: application/json");
$st = new messageContext(-1001483770714);
$st->loadMessage();
