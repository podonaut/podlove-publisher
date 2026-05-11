<?php

namespace Podlove\Model\Probe;

class LocalUploadMediaFileProbe
{
    public static function probe($public_url, $verification_url)
    {
        $path = self::path_for_url($verification_url);

        if (!$path) {
            return null;
        }

        $exists = is_file($path);
        $readable = $exists && is_readable($path);
        $size = null;
        $content_type = null;

        if ($readable) {
            $file_size = filesize($path);
            $size = $file_size === false ? null : (int) $file_size;

            $file_type = wp_check_filetype($path);
            $content_type = $file_type['type'] ?: null;
        }

        return MediaFileProbeResult::local(
            $public_url,
            $verification_url,
            $path,
            $exists,
            $readable,
            $size,
            $content_type
        );
    }

    private static function path_for_url($url)
    {
        if (!is_string($url) || $url === '') {
            return null;
        }

        $upload_dir = wp_upload_dir();

        if (empty($upload_dir['baseurl']) || empty($upload_dir['basedir'])) {
            return null;
        }

        $url_without_query = strtok($url, '?#');
        $base_url = untrailingslashit($upload_dir['baseurl']);

        if (strpos($url_without_query, $base_url.'/') !== 0) {
            return null;
        }

        $relative_path = rawurldecode(substr($url_without_query, strlen($base_url) + 1));
        $relative_path = wp_normalize_path($relative_path);

        if ($relative_path === '' || in_array('..', explode('/', $relative_path), true)) {
            return null;
        }

        $base_dir = wp_normalize_path(trailingslashit($upload_dir['basedir']));
        $path = wp_normalize_path($base_dir.$relative_path);

        if (strpos($path, $base_dir) !== 0) {
            return null;
        }

        return $path;
    }
}
