tar-reader-php
==============

Tar file reading library.

With TarReader you can browse the contents of a tarball, not just extract it.

### Basic usage

First require `biohzrdmx/tar-reader-php` with Composer.

Then just create a `TarReader` instance passing an archive path:

```php
use TarReader\TarReader;

$tar = new TarReader( __DIR__ . DIRECTORY_SEPARATOR . 'file.tar.gz' );
```

The library supports `tar`, `tar.gz` and `tar.bz2` archives.

To get the contents of the archive call the `getEntries` method:

```php
$entries = $tar->getEntries();
```

This function returns an array of objects, each with the following properties:

- `checksum` - Checksum of the entry, example: `5989`
- `filename` - File name, example: `"lorem.txt"`
- `perm` - Permission mask, example: `511`
- `uid` - Numeric user ID of the file owner, example: `1000`
- `gid` - Numeric group ID of the file owner, example: `1000`
- `size` - Size, in bytes, example: `19`
- `mtime` - Last modified timestamp, example: `1668473650`
- `typeflag` - Type of entry, example: `"0"`
- `link` - Link name, if applicable, example: `""`
- `uname` - User name of the file owner, example: `"www-data"`
- `gname` - Group name of the file owner, example: `"www-data"`
- `offset` - Entry offset, example: `2560`

You can also get the contents of an specific entry with the `readEntry` method:

```php
$entry = $entries[1] ?? null;
if ($entry) {
  $data = $tar->readEntry($entry);
  file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . $entry->filename, $data);
}
```

### Licensing

This software is released under the MIT license.

Copyright © 2022 biohzrdmx.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

### Credits

**Lead coder:** biohzrdmx [github.com/biohzrdmx](http://github.com/biohzrdmx)