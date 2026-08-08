<?php

namespace Podlove\Modules\EpisodeLocation\Model;

use Podlove\Model\Base;

class EpisodeLocation extends Base
{
    /**
     * Find location data for a given episode.
     *
     * @param int $episode_id
     *
     * @return EpisodeLocation|null
     */
    public static function find_by_episode_id($episode_id)
    {
        return self::find_one_by_property('episode_id', $episode_id);
    }
}

EpisodeLocation::property('id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY');
EpisodeLocation::property('episode_id', 'INT');
EpisodeLocation::property('location_name', 'VARCHAR(255)');
EpisodeLocation::property('location_lat', 'DECIMAL(10,8)');
EpisodeLocation::property('location_lng', 'DECIMAL(11,8)');
EpisodeLocation::property('location_address', 'TEXT');
