<?php

namespace Podlove\Settings\Dashboard;

use Podlove\Model;

class FileValidation
{
    public static function content()
    {
        global $wpdb;

        $sql = '
		SELECT
			p.post_status,
			mf.episode_id,
			mf.episode_asset_id,
			mf.size,
			mf.id media_file_id,
      mf.active
		FROM
			`'.Model\MediaFile::table_name().'` mf
			JOIN `'.Model\Episode::table_name().'` e ON e.id = mf.`episode_id`
			JOIN `'.$wpdb->posts."` p ON e.`post_id` = p.`ID`
		WHERE
			p.`post_type` = 'podcast'
			AND p.post_status in ('private', 'draft', 'publish', 'pending', 'future')
		";

        $rows = $wpdb->get_results($sql, ARRAY_A);

        $media_files = [];
        foreach ($rows as $row) {
            if (!isset($media_files[$row['episode_id']])) {
                $media_files[$row['episode_id']] = ['post_status' => $row['post_status']];
            }

            $media_files[$row['episode_id']][$row['episode_asset_id']] = [
                'asset_id' => (int) $row['episode_asset_id'],
                'size' => (int) $row['size'],
                'media_file_id' => (int) $row['media_file_id'],
                'active' => (bool) $row['active']
            ];
        }

        $podcast = Model\Podcast::get();
        $episodes = $podcast->episodes(['post_status' => ['private', 'draft', 'publish', 'pending', 'future']]);
        $assets = Model\EpisodeAsset::all();

        $asset_validation_data = [
            'assets' => array_map(function ($asset) {
                return [
                    'id' => (int) $asset->id,
                    'title' => $asset->title,
                ];
            }, $assets),
            'episodes' => array_map(function ($episode) use ($media_files) {
                $files = $media_files[$episode->id] ?? [];
                unset($files['post_status']);

                return [
                    'id' => (int) $episode->id,
                    'post_id' => (int) $episode->post_id,
                    'label' => is_null($episode->slug)
                        ? __('Slug is missing', 'podlove-podcasting-plugin-for-wordpress')
                        : $episode->slug(),
                    'slug_missing' => is_null($episode->slug),
                    'edit_url' => admin_url('post.php?post='.$episode->post_id.'&action=edit'),
                    'status' => get_post_status($episode->post_id),
                    'files' => array_values($files),
                ];
            }, $episodes),
        ];

        \Podlove\load_template('settings/dashboard/file_validation', [
            'asset_validation_data' => $asset_validation_data,
        ]);
    }
}
