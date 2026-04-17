<?php

/**
 * @internal
 *
 * @coversNothing
 */
class FeedHtmlOptimizationTest extends WP_UnitTestCase
{
    public function testPrepareContentEncodedLeavesMarkupUntouchedWhenOptimizationIsDisabled()
    {
        $content = '<p><a href="https://example.com" class="button" id="cta" aria-label="Open">Link</a></p>';

        $prepared = \Podlove\Feeds\prepare_content_encoded($content, false);

        $this->assertSame($content, $prepared);
    }

    public function testPrepareContentEncodedRemovesNonEssentialAttributesWhenOptimizationIsEnabled()
    {
        $content = '<p><a href="https://example.com" class="button" id="cta" aria-label="Open">Link</a></p>';

        $prepared = \Podlove\Feeds\prepare_content_encoded($content, true);

        $this->assertStringContainsString('<a href="https://example.com">Link</a>', $prepared);
        $this->assertStringNotContainsString('class=', $prepared);
        $this->assertStringNotContainsString('id=', $prepared);
        $this->assertStringNotContainsString('aria-label=', $prepared);
    }

    public function testPrepareContentEncodedKeepsEssentialImageAttributes()
    {
        $content = '<p><img src="https://example.com/image.jpg" alt="Cover" class="cover" loading="lazy" aria-hidden="true"></p>';

        $prepared = \Podlove\Feeds\prepare_content_encoded($content, true);

        $this->assertStringContainsString('<img src="https://example.com/image.jpg" alt="Cover"', $prepared);
        $this->assertStringNotContainsString('class=', $prepared);
        $this->assertStringNotContainsString('loading=', $prepared);
        $this->assertStringNotContainsString('aria-hidden=', $prepared);
    }

    public function testPrepareContentEncodedAlwaysRemovesStyleTags()
    {
        $content = '<style>.note{color:red}</style><p>Shownotes</p>';

        $prepared = \Podlove\Feeds\prepare_content_encoded($content, false);

        $this->assertSame('<p>Shownotes</p>', $prepared);
    }
}
