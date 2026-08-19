<?php

/**
 * @internal
 *
 * @coversNothing
 */
class BundledTwigOutputEscapingTest extends WP_UnitTestCase
{
    public function testEveryBundledTwigOutputDeclaresItsEscapingStrategy(): void
    {
        $unsafe_outputs = [];

        foreach ($this->twigFiles() as $file) {
            $contents = file_get_contents($file);
            preg_match_all('/\{\{(.*?)\}\}/s', $contents, $outputs, PREG_OFFSET_CAPTURE);

            foreach ($outputs[1] as [$expression, $offset]) {
                if (preg_match('/\|\s*(?:e\s*\(|esc_url\b|raw\b|wp_kses_post\b)/s', $expression)) {
                    continue;
                }

                $line = substr_count(substr($contents, 0, $offset), "\n") + 1;
                $unsafe_outputs[] = str_replace(\Podlove\PLUGIN_DIR.'/', '', $file).':'.$line;
            }
        }

        $this->assertSame(
            [],
            $unsafe_outputs,
            'Bundled Twig output must use a contextual escape filter or an explicit raw marker.'
        );
    }

    private function twigFiles(): array
    {
        $files = [];

        foreach ([\Podlove\PLUGIN_DIR.'/templates', \Podlove\PLUGIN_DIR.'/lib/modules'] as $directory) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'twig') {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }
}
