<?php

/**
 * @internal
 *
 * @coversNothing
 */
class AssetValidationDashboardTest extends WP_UnitTestCase
{
    public function testFileValidationTemplateRendersVueIslandAndPayload(): void
    {
        $payload = [
            'assets' => [
                [
                    'id' => 123,
                    'title' => 'MP3 Audio',
                ],
            ],
            'episodes' => [
                [
                    'id' => 456,
                    'post_id' => 789,
                    'label' => 'episode-one',
                    'slug_missing' => false,
                    'edit_url' => admin_url('post.php?post=789&action=edit'),
                    'status' => 'publish',
                    'files' => [
                        [
                            'asset_id' => 123,
                            'media_file_id' => 321,
                            'active' => true,
                            'size' => 42,
                        ],
                    ],
                ],
            ],
        ];

        ob_start();
        \Podlove\load_template('settings/dashboard/file_validation', [
            'asset_validation_data' => $payload,
        ]);
        $html = ob_get_clean();

        $this->assertStringContainsString('<podlove-asset-validation></podlove-asset-validation>', $html);
        $this->assertStringContainsString('window.PODLOVE_DATA.asset_validation = ', $html);
        $this->assertStringContainsString('"asset_id":123', $html);
        $this->assertStringContainsString('"media_file_id":321', $html);
    }
}
