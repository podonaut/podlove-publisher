<?php

namespace Podlove\Model\Probe;

class MediaFileProbeResult
{
    public const SOURCE_HTTP = 'http';
    public const SOURCE_LOCAL = 'local';

    private $source;
    private $public_url;
    private $verification_url;
    private $reachable;
    private $status_code;
    private $size;
    private $mime_type;
    private $etag;
    private $unchanged;
    private $error;
    private $path;
    private $exists;
    private $readable;
    private $http_header;
    private $response;

    private function __construct(
        $source,
        $public_url,
        $verification_url,
        $reachable,
        $status_code,
        $size,
        $mime_type,
        $etag,
        $unchanged,
        $error,
        $path,
        $exists,
        $readable,
        $http_header,
        $response
    ) {
        $this->source = $source;
        $this->public_url = $public_url;
        $this->verification_url = $verification_url;
        $this->reachable = (bool) $reachable;
        $this->status_code = (int) $status_code;
        $this->size = $size === null ? null : (int) $size;
        $this->mime_type = $mime_type;
        $this->etag = $etag;
        $this->unchanged = (bool) $unchanged;
        $this->error = (string) $error;
        $this->path = $path;
        $this->exists = $exists;
        $this->readable = $readable;
        $this->http_header = is_array($http_header) ? $http_header : [];
        $this->response = $response;
    }

    public static function local($public_url, $verification_url, $path, $exists, $readable, $size, $mime_type)
    {
        return new self(
            self::SOURCE_LOCAL,
            $public_url,
            $verification_url,
            (bool) $readable,
            $readable ? 200 : ($exists ? 403 : 404),
            $size,
            $mime_type,
            null,
            false,
            $readable ? '' : 'Local media file does not exist or is not readable.',
            $path,
            (bool) $exists,
            (bool) $readable,
            [],
            ''
        );
    }

    public static function http(
        $public_url,
        $verification_url,
        $status_code,
        $size,
        $mime_type,
        $etag,
        $unchanged,
        $error,
        $http_header,
        $response
    ) {
        return new self(
            self::SOURCE_HTTP,
            $public_url,
            $verification_url,
            podlove_is_resolved_and_reachable_http_status((int) $status_code),
            $status_code,
            $size,
            $mime_type,
            $etag,
            $unchanged,
            $error,
            null,
            null,
            null,
            $http_header,
            $response
        );
    }

    public function source()
    {
        return $this->source;
    }

    public function public_url()
    {
        return $this->public_url;
    }

    public function verification_url()
    {
        return $this->verification_url;
    }

    public function reachable()
    {
        return $this->reachable;
    }

    public function status_code()
    {
        return $this->status_code;
    }

    public function size()
    {
        return $this->size;
    }

    public function mime_type()
    {
        return $this->mime_type;
    }

    public function etag()
    {
        return $this->etag;
    }

    public function unchanged()
    {
        return $this->unchanged;
    }

    public function error()
    {
        return $this->error;
    }

    public function path()
    {
        return $this->path;
    }

    public function exists()
    {
        return $this->exists;
    }

    public function readable()
    {
        return $this->readable;
    }

    public function response()
    {
        return $this->response;
    }

    public function to_legacy_header()
    {
        $header = $this->http_header;
        $header['url'] = $this->verification_url;
        $header['content_type'] = $this->mime_type;
        $header['http_code'] = $this->status_code;
        $header['download_content_length'] = $this->size ?? -1.0;
        $header['certinfo'] = $header['certinfo'] ?? [];
        $header['source'] = $this->source;
        $header['public_url'] = $this->public_url;
        $header['verification_url'] = $this->verification_url;
        $header['reachable'] = $this->reachable;
        $header['error'] = $this->error;

        if ($this->path) {
            $header['path'] = $this->path;
        }

        return $header;
    }
}
