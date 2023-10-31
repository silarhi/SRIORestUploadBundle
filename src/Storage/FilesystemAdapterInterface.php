<?php

namespace SRIO\RestUploadBundle\Storage;

use SRIO\RestUploadBundle\Exception\FileExistsException;
use SRIO\RestUploadBundle\Exception\FileNotFoundException;

interface FilesystemAdapterInterface
{
    /**
     * Returns the underlying filesystem.
     */
    public function getFilesystem(): mixed;

    /**
     * Returns the adapter.
     */
    public function getAdapter(): mixed;

    /**
     * Indicates whether the file matching the specified name exists.
     *
     * @param string $path
     *
     * @return bool TRUE if the file exists, FALSE otherwise
     */
    public function has(string $path): bool;

    /**
     * Returns the file matching the specified name.
     *
     * @param string $path Key of the file
     */
    public function get(string $path): FileAdapterInterface;

    /**
     * Writes the given content into the new file.
     *
     * @param string $path    Name of the file
     * @param mixed $content Content to write in the file
     * @param array   $config  Any data or settings to pass down
     *
     * @return bool Whether the write succeeded or not
     *
     * @throws FileExistsException
     */
    public function write(string $path, mixed $content, array $config = []): bool;

    /**
     * Writes the given content into the new file stream.
     *
     * @param string $path     Name of the file
     * @param resource $resource Stream to write in the file
     * @param array     $config   Any data or settings to pass down
     *
     * @return bool Whether the write succeeded or not
     *
     * @throws FileExistsException
     */
    public function writeStream(string $path, $resource, array $config = []): bool;

    /**
     * Writes the given content into the new file or replaces the old contents.
     *
     * @param string $path    Name of the file
     * @param string $content Content to write in the file
     * @param array   $config  Any data or settings to pass down
     *
     * @return bool Whether the write succeeded or not
     */
    public function put(string $path, $content, array $config = []): bool;

    /**
     * Writes the given content into the new file or replaces the old contents.
     *
     * @param string $path     Name of the file
     * @param resource $resource Stream to write in the file
     * @param array     $config   Any data or settings to pass down
     *
     * @return bool Whether the write succeeded or not
     */
    public function putStream(string $path, $resource, array $config = []): bool;

    /**
     * Reads the content from the file.
     *
     * @param string $path Path of the file
     *
     * @return string|false If the file could not be read, false is returned
     *
     * @throws FileNotFoundException
     */
    public function read(string $path): false|string;

    /**
     * Reads the content from the file as a stream.
     *
     * @param string $path Path of the file
     *
     * @return resource|false If the file could not be read, false is returned
     *
     * @throws FileNotFoundException
     */
    public function readStream(string $path);

    /**
     * Deletes the file matching the specified name.
     *
     * @param string $path
     *
     * @throws FileNotFoundException
     */
    public function delete(string $path): bool;

    /**
     * Tries to do an efficient read and copy of the original filesystem stream
     * it overflows onto disk and always permits random reads, writes and seeks.
     *
     * @param string $path Path of the file
     *
     * @return resource
     *
     * @throws FileNotFoundException
     */
    public function getStreamCopy(string $path);

    /**
     * Returns the last modified time of the specified file.
     *
     * @param string $path
     *
     * @return int An UNIX like timestamp
     *
     * @throws FileNotFoundException
     */
    public function getModifiedTimestamp(string $path): int;

    /**
     * Returns the size of the specified file's content.
     *
     * @param string $path
     *
     * @return int File size in Bytes
     *
     * @throws FileNotFoundException
     */
    public function getSize(string $path): int;

    /**
     * Returns the mime type of the specified file.
     *
     * @param string $path
     *
     * @throws FileNotFoundException
     */
    public function getMimeType(string $path): string;
}
