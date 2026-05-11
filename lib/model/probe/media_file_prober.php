<?php

namespace Podlove\Model\Probe;

use Podlove\Model\MediaFile;

class MediaFileProber
{
    public static function probe($file_url, $etag, MediaFile $media_file)
    {
        $verification_url = apply_filters('podlove_media_file_verification_url', $file_url, $media_file);

        if (!is_string($verification_url) || $verification_url === '') {
            $verification_url = $file_url;
        }

        $local_probe = LocalUploadMediaFileProbe::probe($file_url, $verification_url);

        if ($local_probe) {
            return $local_probe;
        }

        return HttpMediaFileProbe::probe($file_url, $verification_url, $etag);
    }
}
