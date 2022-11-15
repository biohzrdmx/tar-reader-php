<?php

declare(strict_types = 1);

/**
 * TarReader
 * Tar file reading library
 * @author 	biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 * @license MIT
 */

namespace TarReader;

use InvalidArgumentException;
use RuntimeException;

class TarReader {

	/**
	 * Archive path
	 * @var string
	 */
	protected $path = '';

	/**
	 * Archive handle
	 * @var mixed
	 */
	protected $handle = null;

	/**
	 * Archive format
	 * @var string
	 */
	protected $format = '';

	/**
	 * Constructor
	 * @param string $path Archive path
	 */
	public function __construct(string $path) {
		$this->format = $this->getFormat($path);
		$this->path = $path;
		switch ($this->format) {
			case 'gz':
				$this->handle = @gzopen($path, 'rb');
			break;
			case 'bz':
				$this->handle = @bzopen($path, 'r');
			break;
			case 'tar':
				$this->handle = @fopen($path, 'rb');
			break;
			default:
				throw new InvalidArgumentException('Unsupported file format');
		}
	}

	/**
	 * Close archive
	 * @return void
	 */
	public function close(): void {
		switch ($this->format) {
			case 'gz':
				@gzclose($this->handle);
			break;
			case 'bz':
				@bzclose($this->handle);
			break;
			case 'tar':
				@fclose($this->handle);
			break;
		}
		$this->handle = null;
	}

	/**
	 * Get archive entries
	 * @return array
	 */
	public function getEntries(): array {
		$ret = [];
		$offset = 0;
		while ( $read = $this->readBytes(512) ) {
			$header = $this->parseHeader($read);
			if (! is_array($header) ) {
				continue;
			}
			$bytes = (int) ceil($header['size'] / 512 ) * 512;
			$this->skipBytes($bytes);
			# Adjust offset
			$offset += 512;
			$header['offset'] = $offset;
			$offset += $bytes;
			$ret[] = (object) $header;
		}
		return $ret;
	}

	/**
	 * Read entry
	 * @param  mixed $entry Entry object
	 * @return string
	 */
	public function readEntry($entry): string {
		switch ($this->format) {
			case 'gz':
				@gzrewind($this->handle);
			break;
			case 'bz':
				# There is no seek in bzip2, we simply close and re-open the stream
				@bzclose($this->handle);
				$this->handle = @bzopen($this->path, 'r');
			break;
			case 'tar':
				@rewind($this->handle);
			break;
		}
		$this->skipBytes($entry->offset);
		$ret = $this->readBytes($entry->size);
		return $ret;
	}

	/**
	 * Read the specified length of bytes from the archive
	 * @param  int    $length Length of bytes
	 * @return string
	 */
	protected function readBytes(int $length): string {
		$ret = '';
		switch ($this->format) {
			case 'gz':
				$ret = @gzread($this->handle, $length);
			break;
			case 'bz':
				$ret = @bzread($this->handle, $length);
			break;
			case 'tar':
				$ret = @fread($this->handle, max(1, $length));
			break;
		}
		return $ret ?: '';
	}

	/**
	 * Skip the specified length fo bytes from the archive
	 * @param  int    $bytes Length of bytes
	 * @return void
	 */
	protected function skipBytes(int $bytes): void {
		switch ($this->format) {
			case 'gz':
				@gzseek($this->handle, $bytes, SEEK_CUR);
			break;
			case 'bz':
				# There is no seek in bzip2, we simply read on; bzread allows to read a max of 8kb at once
				while($bytes) {
					$toread = min(8192, $bytes);
					@bzread($this->handle, $toread);
					$bytes -= $toread;
				}
			break;
			case 'tar':
				@fseek($this->handle, $bytes, SEEK_CUR);
			break;
		}
	}

	/**
	 * Parse header
	 * @param  string $block The block data
	 * @return mixed
	 */
	protected function parseHeader(string $block) {
		$ret = [];
		if (!$block || strlen($block) != 512) {
			throw new RuntimeException('Unexpected length of header');
		}

		# Null byte blocks are ignored
		if(trim($block) === '') return false;

		for ($i = 0, $chks = 0; $i < 148; $i++) {
			$chks += ord($block[$i]);
		}

		for ($i = 156, $chks += 256; $i < 512; $i++) {
			$chks += ord($block[$i]);
		}

		$header = @unpack("a100filename/a8perm/a8uid/a8gid/a12size/a12mtime/a8checksum/a1typeflag/a100link/a6magic/a2version/a32uname/a32gname/a8devmajor/a8devminor/a155prefix", $block);
		if (!$header) {
			throw new RuntimeException('Failed to parse header');
		}

		$ret['checksum'] = octdec(trim($header['checksum']));
		if ($ret['checksum'] != $chks) {
			throw new RuntimeException('Header does not match its checksum');
		}

		$ret['filename'] = trim($header['filename']);
		$ret['perm']     = octdec(trim($header['perm']));
		$ret['uid']      = octdec(trim($header['uid']));
		$ret['gid']      = octdec(trim($header['gid']));
		$ret['size']     = octdec(trim($header['size']));
		$ret['mtime']    = octdec(trim($header['mtime']));
		$ret['typeflag'] = $header['typeflag'];
		$ret['link']     = trim($header['link']);
		$ret['uname']    = trim($header['uname']);
		$ret['gname']    = trim($header['gname']);
		$ret['offset']   = 0;

		# Handle ustar Posix compliant path prefixes
		if (trim($header['prefix'])) {
			$ret['filename'] = trim($header['prefix']).'/'.$ret['filename'];
		}

		# Handle Long-Link entries from GNU Tar
		if ($ret['typeflag'] == 'L') {
			# Following data block(s) is the filename
			$filename = trim($this->readBytes((int) ceil($ret['size'] / 512) * 512));
			# Next block is the real header
			$block  = $this->readBytes(512);
			$ret = $this->parseHeader($block);
			# Overwrite the filename
			$ret['filename'] = $filename;
		}

		return $ret;
	}

	/**
	 * Get format
	 * @param  string $path File path
	 * @return string
	 */
	protected function getFormat(string $path): string {
		if ( file_exists($path) && is_readable($path) ) {
			$handle = @fopen($path, 'rb');
			if(! $handle ) return '';
			$magic = fread($handle, 5);
			fclose($handle);
			if ($magic) {
				if(strpos($magic, "\x42\x5a") === 0) return 'bz';
				if(strpos($magic, "\x1f\x8b") === 0) return 'gz';
			}
		}
		preg_match('/(\.tar)(\.gz|\.bz2)?$/', $path, $matches);
		return $matches ? ( $this->format = preg_replace('/^\./', '', $matches[2] ?? '.tar') ) : '';
	}
}
