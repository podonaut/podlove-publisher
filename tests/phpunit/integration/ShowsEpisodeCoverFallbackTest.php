<?php

use Podlove\Modules\Shows\Model\Show;
use Podlove\Modules\Shows\Shows;

/**
 * @internal
 *
 * @coversNothing
 */
class ShowsEpisodeCoverFallbackTest extends WP_UnitTestCase
{
    private $episode_factory;

    public function setUp(): void
    {
        parent::setUp();

        podlove_test_reset_podcast_episodes();
        podlove_test_activate_module('shows', Shows::class);
        Shows::instance()->register_show_taxonomy();

        $podcast = \Podlove\Model\Podcast::get();
        $podcast->title = 'Podcast';
        $podcast->cover_image = 'https://example.com/podcast.jpg';
        $podcast->save();

        $asset_assignment = \Podlove\Model\AssetAssignment::get_instance();
        $asset_assignment->image = 'manual';
        $asset_assignment->save();

        $this->episode_factory = new EpisodeFactory($this->factory);
    }

    public function tearDown(): void
    {
        foreach (Show::all() as $show) {
            $show->delete();
        }

        delete_option('podlove_asset_assignment');
        podlove_test_reset_podcast_episodes();

        parent::tearDown();
    }

    public function testEpisodeCoverStillWinsOverShowCoverFallback()
    {
        $episode = $this->episode_factory->create([
            'cover_art' => 'https://example.com/episode.jpg',
            'title' => 'Episode',
        ]);
        $this->assign_show_to_episode($episode, 'https://example.com/show.jpg');

        $this->assertSame('https://example.com/episode.jpg', $episode->cover_art_with_fallback()->source_url());
    }

    public function testShowCoverIsUsedBeforePodcastCover()
    {
        $episode = $this->episode_factory->create(['title' => 'Episode']);
        $this->assign_show_to_episode($episode, 'https://example.com/show.jpg');

        $this->assertSame('https://example.com/show.jpg', $episode->cover_art_with_fallback()->source_url());
    }

    public function testFeedEpisodeCoverFallsBackToPodcastCoverWhenShowHasNoCover()
    {
        $episode = $this->episode_factory->create(['title' => 'Episode']);
        $this->assign_show_to_episode($episode, '');

        $cover_art = apply_filters(
            'podlove_feed_episode_cover_art',
            $episode->cover_art(),
            $episode,
            null,
            \Podlove\Model\Podcast::get()
        );

        $this->assertSame('https://example.com/podcast.jpg', $cover_art->source_url());
    }

    private function assign_show_to_episode($episode, $image)
    {
        $slug = 'test-show-'.wp_rand(1, 100000);
        $show = Show::create([
            'title' => 'Test Show '.$slug,
            'slug' => $slug,
            'image' => $image,
        ]);

        Shows::set_show_for_episode($episode->post_id, $show->slug);
    }
}
