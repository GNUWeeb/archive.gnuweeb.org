<?php
// SPDX-License-Identifier: GPL-2.0
/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com>
 * @license GPL-2.0
 * @copyright 2021  Ammar Faizi
 */

final class DB
{
	public static function pdo(): \PDO
	{
		return new \PDO(...PDO_PARAM);
	}
}
