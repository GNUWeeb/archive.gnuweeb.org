<?php
// SPDX-License-Identifier: GPL-2.0
/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com>
 * @license GPL-2.0
 * @package {no package}
 *
 * Copyright (C) 2021  Ammar Faizi <ammarfaizi2@gmail.com>
 */

final class DB
{
	/**
	 * @return \PDO
	 */
	public static function pdo(): \PDO
	{
		return new \PDO(...PDO_PARAM);
	}
}
