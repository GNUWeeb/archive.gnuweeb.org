<?php
// SPDX-License-Identifier: GPL-2.0
/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com>
 * @license GPL-2.0
 * @package {no package}
 *
 * Copyright (C) 2021  Ammar Faizi <ammarfaizi2@gmail.com>
 */

require __DIR__."/../../config.php";

/**
 * @param string $className
 * @return void
 */
function classAutoloader(string $className): void
{
	$file = BASE_PATH."/src/classes/".
		str_replace("\\", "/", $className).".php";

	if (file_exists($file))
		require $file;
}
spl_autoload_register("classAutoloader");
