<?php

namespace Podlove\Modules\EpisodeLocation;

use Podlove\Modules\EpisodeLocation\Model\EpisodeLocation as LocationModel;

class TemplateExtensions
{
    /**
     * Location name for the episode.
     *
     * @accessor
     * @dynamicAccessor episode.locationName
     *
     * @param mixed $return
     * @param mixed $method_name
     * @param mixed $episode
     * @param mixed $post
     * @param mixed $args
     */
    public static function accessorEpisodeLocationName($return, $method_name, $episode, $post, $args = [])
    {
        $location = LocationModel::find_by_episode_id($episode->id);

        return $location ? $location->location_name : '';
    }

    /**
     * Location latitude for the episode.
     *
     * @accessor
     * @dynamicAccessor episode.locationLat
     *
     * @param mixed $return
     * @param mixed $method_name
     * @param mixed $episode
     * @param mixed $post
     * @param mixed $args
     */
    public static function accessorEpisodeLocationLat($return, $method_name, $episode, $post, $args = [])
    {
        $location = LocationModel::find_by_episode_id($episode->id);

        return $location ? $location->location_lat : '';
    }

    /**
     * Location longitude for the episode.
     *
     * @accessor
     * @dynamicAccessor episode.locationLng
     *
     * @param mixed $return
     * @param mixed $method_name
     * @param mixed $episode
     * @param mixed $post
     * @param mixed $args
     */
    public static function accessorEpisodeLocationLng($return, $method_name, $episode, $post, $args = [])
    {
        $location = LocationModel::find_by_episode_id($episode->id);

        return $location ? $location->location_lng : '';
    }

    /**
     * Location address for the episode.
     *
     * @accessor
     * @dynamicAccessor episode.locationAddress
     *
     * @param mixed $return
     * @param mixed $method_name
     * @param mixed $episode
     * @param mixed $post
     * @param mixed $args
     */
    public static function accessorEpisodeLocationAddress($return, $method_name, $episode, $post, $args = [])
    {
        $location = LocationModel::find_by_episode_id($episode->id);

        return $location ? $location->location_address : '';
    }
}
