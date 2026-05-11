<?php

namespace Podlove\Model;

use Podlove\Log;
use Podlove\Model\Probe\HttpMediaFileProbe;
use Podlove\Model\Probe\MediaFileProber;
use Podlove\Model\Probe\MediaFileProbeResult;

class MediaFile extends Base
{
    use KeepsBlogReferenceTrait;

    public function __construct()
    {
        $this->set_blog_id();
    }

    /**
     * Fetches file size if necessary.
     *
     * @override Base::save()
     *
     * @param mixed $determine_size
     */
    public function save($determine_size = true)
    {
        if ($determine_size && !$this->size) {
            $this->determine_file_size();
        }

        return parent::save();
    }

    /**
     * Find the related show model.
     *
     * @return null|\Podlove\Model\EpisodeAsset
     */
    public function episode_asset()
    {
        return $this->with_blog_scope(function () {
            return EpisodeAsset::find_by_id($this->episode_asset_id);
        });
    }

    /**
     * Find one downloadable example file.
     *
     * - JOIN episode to avoid dead media files
     * - ORDER BY e.id DESC, mf.id ASC: get a recent episode and the first asset
     */
    public static function find_example()
    {
        $episode = Episode::latest();

        if (!$episode) {
            return;
        }

        $files = $episode->media_files();

        $files = array_filter($files, function ($file) {
            $asset = $file->episode_asset();

            if (!$asset) {
                return false;
            }

            $file_type = $asset->file_type();

            if (!$file_type) {
                return false;
            }

            return $asset->downloadable && $file_type->type == 'audio';
        });

        return reset($files);
    }

    public static function find_or_create_by_episode_id_and_episode_asset_id($episode_id, $episode_asset_id)
    {
        if (!$file = self::find_any_by_episode_id_and_episode_asset_id($episode_id, $episode_asset_id)) {
            $file = new MediaFile();
            $file->episode_id = $episode_id;
            $file->episode_asset_id = $episode_asset_id;
            $file->active = true;
            $file->save();
        }

        return $file;
    }

    /**
     * Finds an active media file for given episode and asset.
     *
     * TODO: Maybe rename to include the `active` condition in the function name.
     *
     * @param mixed $episode_id
     * @param mixed $episode_asset_id
     */
    public static function find_by_episode_id_and_episode_asset_id($episode_id, $episode_asset_id)
    {
        $where = sprintf(
            'episode_id = "%s" AND episode_asset_id = "%s" AND active = 1',
            $episode_id,
            $episode_asset_id
        );

        return MediaFile::find_one_by_where($where);
    }

    /**
     * Finds a media file for given episode and asset, no matter if it is active or not.
     *
     * @param mixed $episode_id
     * @param mixed $episode_asset_id
     */
    public static function find_any_by_episode_id_and_episode_asset_id($episode_id, $episode_asset_id)
    {
        $where = sprintf(
            'episode_id = "%s" AND episode_asset_id = "%s"',
            $episode_id,
            $episode_asset_id
        );

        return MediaFile::find_one_by_where($where);
    }

    /**
     * Is this media file valid?
     *
     * @return bool
     */
    public function is_valid()
    {
        return $this->size > 0;
    }

    /**
     * Return public file URL.
     *
     * A source must be provided, an additional context is optional.
     * Example sources: webplayer, download, feed, other
     * Example contexts: home/episode/archive for player source, feed slug for feed source
     *
     * @param string      $source  download source
     * @param null|string $context optional download context
     *
     * @return string
     */
    public function get_public_file_url($source, $context = null)
    {
        return $this->with_blog_scope(function () use ($source, $context) {
            if (empty($source) && empty($context)) {
                return $this->get_file_url();
            }

            $params = [
                'source' => $source,
                'context' => $context,
            ];

            $url = '';

            switch ((string) \Podlove\get_setting('tracking', 'mode')) {
                case 'ptm':
                    // when PTM is active, add $source and $context but
                    // keep the original file URL
                    $url = $this->add_ptm_parameters(
                        $this->get_file_url(),
                        $params
                    );

                    break;
                case 'ptm_analytics':
                    // we track, so we need to generate a shadow URL
                    if (get_option('permalink_structure')) {
                        $path = '/podlove/file/'.$this->id;
                        $path = $this->add_ptm_routing($path, $params);
                    } else {
                        $path = '?download_media_file='.$this->id;
                        $path = $this->add_ptm_parameters($path, $params);
                    }
                    $url = home_url($path);

                    break;

                default:
                    // tracking is off, return raw URL
                    $url = $this->get_file_url();

                    break;
            }

            return apply_filters('podlove_enclosure_url', $url);
        });
    }

    public function add_ptm_routing($path, $params)
    {
        if (isset($params['source'])) {
            $path .= "/s/{$params['source']}";
        }

        if (isset($params['context'])) {
            $path .= "/c/{$params['context']}";
        }

        $path .= '/'.$this->urlencode_path_segments($this->get_download_file_name());

        return $path;
    }

    public function add_ptm_parameters($path, $params)
    {
        // trim params
        $params = array_map(function ($p) {
            return trim((string) $p);
        }, $params);

        $connector = function ($path) {
            return strpos($path, '?') === false ? '?' : '&';
        };

        // add params to path
        foreach ($params as $param_name => $value) {
            $path .= $connector($path).'ptm_'.$param_name.'='.$this->urlencode_path_segments($value);
        }

        // at last, add file param, so wget users get the right extension
        $path .= $connector($path).'ptm_file='.$this->urlencode_path_segments($this->get_download_file_name());

        return $path;
    }

    /**
     * Return real file URL.
     *
     * For public facing URLs, use ::get_public_file_url().
     *
     * @return string
     */
    public function get_file_url()
    {
        return $this->with_blog_scope(function () {
            $podcast = Podcast::get();

            $episode = $this->episode();
            $episode_asset = EpisodeAsset::find_by_id($this->episode_asset_id);
            $file_type = FileType::find_by_id($episode_asset->file_type_id);

            if (!$episode_asset || !$file_type || !$episode) {
                return '';
            }

            $template = (string) $podcast->get_url_template();
            $template = apply_filters('podlove_file_url_template', $template);
            $template = str_replace('%media_file_base_url%', $podcast->get_media_file_base_uri(), $template);
            $template = str_replace('%episode_slug%', \Podlove\prepare_episode_slug_for_url($episode->slug), $template);
            $template = str_replace('%suffix%', $episode_asset->suffix ?? '', $template);
            $template = str_replace('%format_extension%', $file_type->extension, $template);

            return trim($template);
        });
    }

    public function episode()
    {
        return $this->with_blog_scope(function () {
            return Episode::find_by_id($this->episode_id);
        });
    }

    public function get_file_name()
    {
        $asset = $this->episode_asset();
        $suffix = $asset->suffix ?? '';
        $extension = $asset->file_type()->extension;

        return $this->episode()->slug.$suffix.'.'.$extension;
    }

    /**
     * Build file name as it appears when you download the file.
     *
     * @return string
     */
    public function get_download_file_name()
    {
        $file_name = $this->get_file_name();

        return apply_filters('podlove_download_file_name', $file_name, $this);
    }

    /**
     * Determine file size by probing the media file URL.
     */
    public function determine_file_size()
    {
        $probe = $this->probe_file();
        $this->validate_file_probe($probe);
        $this->update_file_size_from_probe($probe);

        return $probe->to_legacy_header();
    }

    /**
     * Retrieve header data for the media file URL.
     *
     * @return array
     */
    public function curl_get_header()
    {
        $probe = $this->probe_file();
        $this->validate_file_probe($probe);

        return $probe->to_legacy_header();
    }

    /**
     * @todo  use \Podlove\Http\Curl
     *
     * @param mixed      $url
     * @param null|mixed $etag
     *
     * @return array
     */
    public static function curl_get_header_for_url($url, $etag = null)
    {
        return HttpMediaFileProbe::get_header_for_url($url, $etag);
    }

    private function probe_file()
    {
        return MediaFileProber::probe($this->get_file_url(), $this->etag, $this);
    }

    private function update_file_size_from_probe($probe)
    {
        if ($probe->unchanged()) {
            return;
        }

        if ($probe->reachable()) {
            if ($probe->size() > 0) {
                $this->size = $probe->size();
            } elseif ($probe->source() === MediaFileProbeResult::SOURCE_HTTP) {
                // HTTP confirmed the file exists but did not provide a usable size.
                // Having a proper state would be nice, but this "size = 1 byte" hack works for now.
                $this->size = 1;
            } else {
                $this->size = 0;
            }
        } elseif ($probe->source() === MediaFileProbeResult::SOURCE_LOCAL || $probe->status_code() >= 400) {
            $this->size = 0;
        }

        if ($this->size <= 0) {
            $this->etag = null;
        }
    }

    /**
     * Validate media file probe result.
     *
     * @todo  $this->id not available for first validation before media_file has been saved
     *
     * @param MediaFileProbeResult $probe
     */
    private function validate_file_probe($probe)
    {
        // skip unsaved media files
        if (!$this->id) {
            return;
        }

        if ($probe->source() === MediaFileProbeResult::SOURCE_HTTP && $probe->error()) {
            Log::get()->addError(
                'Curl Error: '.$probe->error(),
                [
                    'media_file_id' => $this->id,
                    'probe' => $probe->to_legacy_header()
                ]
            );
        }

        // skip validation if ETag did not change
        if ($probe->unchanged()) {
            return;
        }

        $this->etag = $probe->etag();

        do_action('podlove_media_file_content_has_changed', $this->id);

        if (!$probe->reachable()) {
            if ($probe->source() === MediaFileProbeResult::SOURCE_LOCAL) {
                Log::get()->addError(
                    'Local media file could not be verified.',
                    [
                        'media_file_id' => $this->id,
                        'path' => $probe->path(),
                        'exists' => $probe->exists(),
                        'readable' => $probe->readable(),
                        'url' => $probe->public_url()
                    ]
                );
            } else {
                Log::get()->addError(
                    'Unexpected http response when trying to access remote media file.',
                    ['media_file_id' => $this->id, 'http_code' => $probe->status_code()]
                );
            }

            return;
        }

        $mime_type = $this->episode_asset()->file_type()->mime_type;

        if ($probe->size() === null) {
            Log::get()->addWarning(
                'Unable to read "Content-Length" header. Impossible to determine file size.',
                ['media_file_id' => $this->id, 'mime_type' => $probe->mime_type(), 'expected_mime_type' => $mime_type]
            );
        } elseif ($probe->size() <= 0) {
            Log::get()->addWarning(
                'Media file size is zero bytes.',
                ['media_file_id' => $this->id, 'mime_type' => $probe->mime_type(), 'expected_mime_type' => $mime_type]
            );
        } elseif ($probe->size() != $this->size) {
            Log::get()->addInfo(
                'Change of media file content length detected.',
                ['media_file_id' => $this->id, 'old_size' => $this->size, 'new_size' => $probe->size()]
            );
        }

        // check if mime type matches asset mime type
        if ($probe->mime_type() != $mime_type) {
            Log::get()->addWarning(
                'Media file mime type does not match expected asset mime type.',
                ['media_file_id' => $this->id, 'mime_type' => $probe->mime_type(), 'expected_mime_type' => $mime_type]
            );
        }
    }

    /**
     * urlencode all segments of a path.
     *
     * We need to respect that slugs are allowed to contain slashes. That's why
     * we need to urlencode the path segments instead of the whole path.
     *
     * @param mixed $path
     */
    private function urlencode_path_segments($path)
    {
        if (empty($path)) {
            return '';
        }

        $parts = explode('/', $path);
        $encoded = array_map('urlencode', $parts);

        return implode('/', $encoded);
    }
}

MediaFile::property('id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY');
MediaFile::property('episode_id', 'INT');
MediaFile::property('episode_asset_id', 'INT');
MediaFile::property('size', 'INT');
MediaFile::property('etag', 'VARCHAR(255)');
MediaFile::property('active', 'TINYINT');
