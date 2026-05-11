<?php

use Podlove\Model\Episode;
use Podlove\Model\EpisodeAsset;
use Podlove\Model\FileType;
use Podlove\Model\MediaFile;
use Podlove\Model\Podcast;

/**
 * @internal
 *
 * @coversNothing
 */
class MediaFileVerificationTest extends WP_UnitTestCase
{
    private $created_files = [];

    public function setUp(): void
    {
        parent::setUp();

        podlove_setup_database_tables();
        podlove_setup_file_types();
        podlove_test_reset_podcast_episodes();
        $this->truncate_media_tables();
        delete_option('podlove_podcast');
    }

    public function tearDown(): void
    {
        foreach ($this->created_files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        delete_option('podlove_podcast');
        $this->truncate_media_tables();
        podlove_test_reset_podcast_episodes();

        parent::tearDown();
    }

    public function testDetermineFileSizeUsesFilesystemForLocalUploadUrls(): void
    {
        $file_path = $this->create_local_upload_file('lov022.mp3', 'local upload media fixture');
        $media_file = $this->create_media_file('lov022');

        $info = $media_file->determine_file_size();

        $this->assertSame('local', $info['source']);
        $this->assertTrue($info['reachable']);
        $this->assertSame(filesize($file_path), (int) $media_file->size);
    }

    public function testDetermineFileSizeKeepsEmptyLocalFilesInvalid(): void
    {
        $this->create_local_upload_file('lov023.mp3', '');
        $media_file = $this->create_media_file('lov023');

        $info = $media_file->determine_file_size();

        $this->assertSame('local', $info['source']);
        $this->assertTrue($info['reachable']);
        $this->assertSame(0, (int) $media_file->size);
    }

    public function testDetermineFileSizeMarksMissingLocalFilesInvalid(): void
    {
        $media_file = $this->create_media_file('missing');
        $media_file->size = 123;

        $info = $media_file->determine_file_size();

        $this->assertSame('local', $info['source']);
        $this->assertFalse($info['reachable']);
        $this->assertSame(404, (int) $info['http_code']);
        $this->assertSame(0, (int) $media_file->size);
    }

    private function create_media_file(string $slug): MediaFile
    {
        $this->set_media_file_base_uri();

        $episode = $this->create_episode($slug);
        $asset = $this->create_mp3_asset();

        $media_file = new MediaFile();
        $media_file->episode_id = $episode->id;
        $media_file->episode_asset_id = $asset->id;
        $media_file->active = true;
        $media_file->size = 0;
        $media_file->save(false);

        return $media_file;
    }

    private function create_local_upload_file(string $file_name, string $content): string
    {
        $upload_dir = wp_upload_dir();
        $media_dir = trailingslashit($upload_dir['basedir']).'something';
        wp_mkdir_p($media_dir);

        $file_path = $media_dir.'/'.$file_name;
        file_put_contents($file_path, $content);
        $this->created_files[] = $file_path;

        return $file_path;
    }

    private function set_media_file_base_uri(): void
    {
        $upload_dir = wp_upload_dir();

        $podcast = Podcast::get();
        $podcast->media_file_base_uri = trailingslashit($upload_dir['baseurl']).'something/';
        $podcast->save();
    }

    private function create_episode(string $slug): Episode
    {
        $post_id = wp_insert_post([
            'post_title' => 'Local Upload Verification',
            'post_type' => 'podcast',
            'post_status' => 'draft',
        ]);

        $episode = Episode::find_or_create_by_post_id($post_id);
        $episode->slug = $slug;
        $episode->save();

        return $episode;
    }

    private function create_mp3_asset(): EpisodeAsset
    {
        $file_type = FileType::find_one_by_property('extension', 'mp3');

        $asset = new EpisodeAsset();
        $asset->title = 'MP3 Audio';
        $asset->identifier = 'mp3';
        $asset->file_type_id = $file_type->id;
        $asset->suffix = '';
        $asset->downloadable = 1;
        $asset->save();

        return $asset;
    }

    private function truncate_media_tables(): void
    {
        global $wpdb;

        if (MediaFile::table_exists()) {
            $wpdb->query('TRUNCATE TABLE '.MediaFile::table_name());
        }

        if (EpisodeAsset::table_exists()) {
            $wpdb->query('TRUNCATE TABLE '.EpisodeAsset::table_name());
        }
    }
}
