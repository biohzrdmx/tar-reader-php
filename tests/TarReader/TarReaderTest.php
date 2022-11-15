<?php

declare(strict_types = 1);

/**
 * TarReader
 * Tar file reading library
 * @author 	biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 * @license MIT
 */

namespace TarReader\Tests\TarReader;

use InvalidArgumentException;
use RuntimeException;

use PHPUnit\Framework\TestCase;

use TarReader\TarReader;

class TarReaderTest extends TestCase {

	public function testUnsupportedFormatFile() {
		$this->expectException(InvalidArgumentException::class);
		$tar = new TarReader( __DIR__ . '/archives/test.zip' );
	}

	public function testWrongExtensionFile() {
		$this->expectException(RuntimeException::class);
		$tar = new TarReader( __DIR__ . '/archives/dummy.tar' );
		$entries = $tar->getEntries();
	}

	public function testTarFile() {
		$tar = new TarReader( __DIR__ . '/archives/test.tar' );
		$entries = $tar->getEntries();
		$this->assertIsArray($entries);
		$this->assertCount(3, $entries);
		$data = $tar->readEntry( $entries[1] );
		$this->assertEquals('Lorem, ipsum dolor.', $data);
		$tar->close();
	}

	public function testGzipFile() {
		$tar = new TarReader( __DIR__ . '/archives/test.tar.gz' );
		$entries = $tar->getEntries();
		$this->assertIsArray($entries);
		$this->assertCount(3, $entries);
		$data = $tar->readEntry( $entries[1] );
		$this->assertEquals('Lorem, ipsum dolor.', $data);
		$tar->close();
	}

	public function testBzipFile() {
		$tar = new TarReader( __DIR__ . '/archives/test.tar.bz2' );
		$entries = $tar->getEntries();
		$this->assertIsArray($entries);
		$this->assertCount(3, $entries);
		$data = $tar->readEntry( $entries[1] );
		$this->assertEquals('Lorem, ipsum dolor.', $data);
		$tar->close();
	}
}
