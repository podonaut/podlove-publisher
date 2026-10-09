<?php

namespace Podlove\Modules\EpisodeLocation;

use Podlove\Model\Episode;
use Podlove\Modules\EpisodeLocation\Model\EpisodeLocation as LocationModel;

class Episode_Location extends \Podlove\Modules\Base
{
    protected $module_name = 'Episode Location';
    protected $module_description = 'Add a geographic location to podcast episodes using an interactive map powered by OpenStreetMap and Leaflet.js.';
    protected $module_group = 'metadata';

    public function load()
    {
        // Create database table on module activation
        add_action('podlove_module_was_activated_episode_location', [$this, 'was_activated']);

        // Add meta box to episode edit screen
        add_action('add_meta_boxes', [$this, 'add_meta_box']);

        // Save location data when the episode is saved
        add_action('save_post_podcast', [$this, 'save_location_data'], 10, 2);

        // Enqueue scripts and styles only on episode edit pages
        add_action('admin_print_styles', [$this, 'enqueue_scripts_and_styles']);

        // Register template accessors for location data
        \Podlove\Template\Episode::add_accessor(
            'locationName',
            ['\Podlove\Modules\EpisodeLocation\TemplateExtensions', 'accessorEpisodeLocationName'],
            4
        );

        \Podlove\Template\Episode::add_accessor(
            'locationLat',
            ['\Podlove\Modules\EpisodeLocation\TemplateExtensions', 'accessorEpisodeLocationLat'],
            4
        );

        \Podlove\Template\Episode::add_accessor(
            'locationLng',
            ['\Podlove\Modules\EpisodeLocation\TemplateExtensions', 'accessorEpisodeLocationLng'],
            4
        );

        \Podlove\Template\Episode::add_accessor(
            'locationAddress',
            ['\Podlove\Modules\EpisodeLocation\TemplateExtensions', 'accessorEpisodeLocationAddress'],
            4
        );

        // Add location tag to podcast feed entries
        add_action('podlove_append_to_feed_entry', [$this, 'add_location_to_feed'], 10, 4);
    }

    /**
     * Create the database table when the module is activated.
     */
    public function was_activated($module_name)
    {
        LocationModel::build();
    }

    /**
     * Register the location meta box on the episode edit screen.
     */
    public function add_meta_box()
    {
        add_meta_box(
            'podlove_episode_location',
            __('Episode Location', 'podlove-podcasting-plugin-for-wordpress'),
            [$this, 'meta_box_callback'],
            'podcast',
            'normal',
            'low'
        );
    }

    /**
     * Render the location meta box contents.
     *
     * @param \WP_Post $post
     */
    public function meta_box_callback($post)
    {
        $episode = Episode::find_one_by_property('post_id', $post->ID);

        $location_name = '';
        $location_lat = '';
        $location_lng = '';
        $location_address = '';

        if ($episode) {
            $location = LocationModel::find_by_episode_id($episode->id);
            if ($location) {
                $location_name = $location->location_name;
                $location_lat = $location->location_lat;
                $location_lng = $location->location_lng;
                $location_address = $location->location_address;
            }
        }

        // Nonce for security
        wp_nonce_field('podlove_episode_location_save', 'podlove_episode_location_nonce');
        ?>
        <div id="podlove-episode-location-wrapper">
            <div class="podlove-location-search-wrapper">
                <label for="podlove-location-search">
                    <?php _e('Search Location', 'podlove-podcasting-plugin-for-wordpress'); ?>
                </label>
                <div class="podlove-location-search-row">
                    <input
                        type="text"
                        id="podlove-location-search"
                        class="regular-text"
                        placeholder="<?php esc_attr_e('Search for a place or address...', 'podlove-podcasting-plugin-for-wordpress'); ?>"
                    />
                    <button type="button" id="podlove-location-search-btn" class="button">
                        <?php _e('Search', 'podlove-podcasting-plugin-for-wordpress'); ?>
                    </button>
                </div>
                <div id="podlove-location-search-results"></div>
            </div>

            <div id="podlove-location-map"></div>

            <div class="podlove-location-fields">
                <div class="podlove-location-field-row">
                    <label for="podlove-location-name">
                        <?php _e('Location Name', 'podlove-podcasting-plugin-for-wordpress'); ?>
                    </label>
                    <input
                        type="text"
                        id="podlove-location-name"
                        name="podlove_episode_location[location_name]"
                        class="regular-text"
                        value="<?php echo esc_attr($location_name); ?>"
                        placeholder="<?php esc_attr_e('e.g. Berlin, Conference Hall...', 'podlove-podcasting-plugin-for-wordpress'); ?>"
                    />
                </div>

                <div class="podlove-location-field-row podlove-location-coords-row">
                    <div class="podlove-location-coord">
                        <label for="podlove-location-lat">
                            <?php _e('Latitude', 'podlove-podcasting-plugin-for-wordpress'); ?>
                        </label>
                        <input
                            type="text"
                            id="podlove-location-lat"
                            name="podlove_episode_location[location_lat]"
                            class="regular-text"
                            value="<?php echo esc_attr($location_lat); ?>"
                            readonly
                        />
                    </div>
                    <div class="podlove-location-coord">
                        <label for="podlove-location-lng">
                            <?php _e('Longitude', 'podlove-podcasting-plugin-for-wordpress'); ?>
                        </label>
                        <input
                            type="text"
                            id="podlove-location-lng"
                            name="podlove_episode_location[location_lng]"
                            class="regular-text"
                            value="<?php echo esc_attr($location_lng); ?>"
                            readonly
                        />
                    </div>
                </div>

                <div class="podlove-location-field-row">
                    <label for="podlove-location-address">
                        <?php _e('Address', 'podlove-podcasting-plugin-for-wordpress'); ?>
                    </label>
                    <input
                        type="text"
                        id="podlove-location-address"
                        name="podlove_episode_location[location_address]"
                        class="large-text"
                        value="<?php echo esc_attr($location_address); ?>"
                        placeholder="<?php esc_attr_e('Full address (auto-filled from search)', 'podlove-podcasting-plugin-for-wordpress'); ?>"
                    />
                </div>

                <p class="podlove-location-hint">
                    <?php _e('Search for a location or click on the map to set the pin. Drag the marker to adjust.', 'podlove-podcasting-plugin-for-wordpress'); ?>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Save location data when the post is saved.
     *
     * @param int      $post_id
     * @param \WP_Post $post
     */
    public function save_location_data($post_id, $post)
    {
        // Skip autosaves
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Verify nonce
        if (!isset($_POST['podlove_episode_location_nonce'])
            || !wp_verify_nonce($_POST['podlove_episode_location_nonce'], 'podlove_episode_location_save')
        ) {
            return;
        }

        if (!isset($_POST['podlove_episode_location'])) {
            return;
        }

        $episode = Episode::find_one_by_property('post_id', $post_id);
        if (!$episode) {
            return;
        }

        $data = $_POST['podlove_episode_location'];

        $location_name = sanitize_text_field($data['location_name'] ?? '');
        $location_lat = $this->sanitize_coordinate($data['location_lat'] ?? '');
        $location_lng = $this->sanitize_coordinate($data['location_lng'] ?? '');
        $location_address = sanitize_text_field($data['location_address'] ?? '');

        // Find or create the location record for this episode
        $location = LocationModel::find_by_episode_id($episode->id);

        // If all fields are empty, delete existing record if present
        if (empty($location_name) && empty($location_lat) && empty($location_lng) && empty($location_address)) {
            if ($location) {
                $location->delete();
            }

            return;
        }

        if (!$location) {
            $location = new LocationModel();
            $location->episode_id = $episode->id;
        }

        $location->location_name = $location_name;
        $location->location_lat = $location_lat;
        $location->location_lng = $location_lng;
        $location->location_address = $location_address;
        $location->save();
    }

    /**
     * Sanitize a coordinate value (latitude or longitude).
     *
     * @param string $value
     *
     * @return string sanitized decimal value or empty string
     */
    private function sanitize_coordinate($value)
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        // Validate as a decimal number within valid coordinate range
        if (is_numeric($value)) {
            return (string) floatval($value);
        }

        return '';
    }

    /**
     * Enqueue Leaflet.js and custom scripts/styles on episode edit pages only.
     */
    public function enqueue_scripts_and_styles()
    {
        if (!\Podlove\is_episode_edit_screen()) {
            return;
        }

        // Leaflet.js CSS from CDN
        wp_enqueue_style(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
            [],
            '1.9.4'
        );

        // Leaflet.js from CDN
        wp_enqueue_script(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
            [],
            '1.9.4',
            true
        );

        // Module custom styles
        wp_enqueue_style(
            'podlove_episode_location_admin',
            $this->get_module_url().'/css/admin.css',
            ['leaflet'],
            \Podlove\get_plugin_header('Version')
        );

        // Module custom script
        wp_enqueue_script(
            'podlove_episode_location_admin',
            $this->get_module_url().'/js/admin.js',
            ['jquery', 'leaflet'],
            \Podlove\get_plugin_header('Version'),
            true
        );
    }

    /**
     * Add podcast:location tag to feed entries (Podcasting 2.0 namespace).
     *
     * @param mixed $podcast
     * @param mixed $episode
     * @param mixed $feed
     * @param mixed $format
     */
    public function add_location_to_feed($podcast, $episode, $feed, $format)
    {
        $location = LocationModel::find_by_episode_id($episode->id);

        if (!$location || (empty($location->location_lat) && empty($location->location_lng))) {
            return;
        }

        $geo = sprintf('geo:%s,%s', $location->location_lat, $location->location_lng);
        $name = !empty($location->location_name) ? esc_html($location->location_name) : '';

        if ($name) {
            echo sprintf("\n\t\t<podcast:location geo=\"%s\">%s</podcast:location>", esc_attr($geo), $name);
        } else {
            echo sprintf("\n\t\t<podcast:location geo=\"%s\" />", esc_attr($geo));
        }
    }
}
