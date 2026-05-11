<?php

namespace Podlove\Model\Probe;

class HttpMediaFileProbe
{
    public static function probe($public_url, $verification_url, $etag = null)
    {
        $response = self::get_header_for_url($verification_url, $etag);
        $header = isset($response['header']) && is_array($response['header']) ? $response['header'] : [];
        $status_code = (int) ($header['http_code'] ?? 0);
        $content_length = $header['download_content_length'] ?? null;
        $size = is_numeric($content_length) && $content_length >= 0 ? (int) $content_length : null;
        $response_body = $response['response'] ?? '';
        $found_etag = null;

        if (
            podlove_is_resolved_and_reachable_http_status($status_code)
            && is_string($response_body)
            && preg_match('/ETag:\s*"([^"]+)"/i', $response_body, $matches)
        ) {
            $found_etag = $matches[1];
        }

        return MediaFileProbeResult::http(
            $public_url,
            $verification_url,
            $status_code,
            $size,
            $header['content_type'] ?? null,
            $found_etag,
            $status_code === 304,
            $response['error'] ?? '',
            $header,
            $response_body
        );
    }

    /**
     * @todo  use \Podlove\Http\Curl
     *
     * @param mixed      $url
     * @param null|mixed $etag
     *
     * @return array
     */
    public static function get_header_for_url($url, $etag = null)
    {
        if (!function_exists('curl_exec')) {
            return [];
        }

        $curl = curl_init();

        if (\Podlove\Http\Curl::curl_can_follow_redirects()) {
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true); // follow redirects
            curl_setopt($curl, CURLOPT_MAXREDIRS, 5);         // maximum number of redirects
        } else {
            $url = \Podlove\Http\Curl::resolve_redirects($url, 5);
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // make curl_exec() return the result
        curl_setopt($curl, CURLOPT_HEADER, true);         // header only
        curl_setopt($curl, CURLOPT_NOBODY, true);         // return no body; HTTP request method: HEAD
        // Don't check SSL certificate in order to be able to use self signed certificates.
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, \Podlove\get_setting('website', 'ssl_verify_peer') == 'on');
        curl_setopt($curl, CURLOPT_FAILONERROR, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 3);          // HEAD requests shouldn't take > 2 seconds

        if ($etag) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, [
                'If-None-Match: "'.$etag.'"',
            ]);
        }

        curl_setopt($curl, CURLOPT_USERAGENT, \Podlove\Http\Curl::user_agent());

        $response = curl_exec($curl);
        $response_header = curl_getinfo($curl);
        $error = curl_error($curl);
        curl_close($curl);

        return [
            'header' => $response_header,
            'response' => $response,
            'error' => $error,
        ];
    }
}
