<?php

require __DIR__."/../../config.php";

/**
 * @param string $className
 * @return void
 */
function classAutoloader(string $className): void
{
	$file = str_replace("\\", "/", $className).".php";
	if (file_exists(BASE_PATH."/src/classes/".$file))
		require $file;
}
