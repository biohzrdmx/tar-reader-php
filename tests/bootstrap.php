<?php

declare(strict_types = 1);

/**
 * TarReader
 * Tar file reading library
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 * @license MIT
 */

# Define the absolute path
define( 'BASE_DIR', dirname( dirname(__FILE__) ) );

# Composer initialization
if ( file_exists( BASE_DIR . '/vendor/autoload.php' ) ) {
	include BASE_DIR . '/vendor/autoload.php';
}