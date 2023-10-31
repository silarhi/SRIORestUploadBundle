<?php

namespace SRIO\RestUploadBundle\Request;

use LogicException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;

class RequestContentHandler implements RequestContentHandlerInterface
{
    protected int $cursor = 0;

    /**
     * @var string|resource
     */
    protected $content;

    /**
     * Constructor.
     */
    public function __construct(protected Request $request)
    {
    }

    /**
     * Get a line.
     *
     * If false is return, it's the end of file.
     */
    public function gets(): false|string
    {
        $content = $this->getContent();
        if (is_resource($content)) {
            $line = fgets($content);
            $this->cursor = ftell($content);

            return $line;
        }

        $next = strpos($content, "\r\n", $this->cursor);
        $eof = $next === 0 || false === $next;

        if ($eof) {
            $line = substr($content, $this->cursor);
        } else {
            $length = $next - $this->cursor + strlen("\r\n");
            $line = substr($content, $this->cursor, $length);
        }

        $this->cursor = $eof ? -1 : $next + strlen("\r\n");

        return $line;
    }

    public function getCursor(): int
    {
        return $this->cursor;
    }

    /**
     * Is end of file ?
     */
    public function eof(): bool
    {
        return -1 === $this->cursor || (is_resource($this->getContent()) && feof($this->getContent()));
    }

    /**
     * Get request content.
     *
     * @return resource|string
     *
     * @throws RuntimeException
     */
    public function getContent()
    {
        if (null === $this->content) {
            try {
                $this->content = $this->request->getContent(true);
            } catch (LogicException) {
                $this->content = $this->request->getContent(false);

                if ('' === $this->content || '0' === $this->content) {
                    throw new RuntimeException('Unable to get request content');
                }
            }
        }

        return $this->content;
    }
}
