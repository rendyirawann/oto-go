<?php

namespace Tests\Feature\Blog;

use App\Support\HtmlSanitizer;
use Tests\TestCase;

class HtmlSanitizerTest extends TestCase
{
    public function test_it_removes_scripts_handlers_and_dangerous_urls(): void
    {
        $dirty = '<p onclick="x()">Halo <script>alert(1)</script>'
            . '<a href="javascript:alert(1)">a</a><a href=" JaVaScRiPt:alert(2)">b</a><a href="data:text/html,1">c</a>'
            . '<img src="https://evil.test/x.png" onerror="y()"><iframe src="https://evil.test"></iframe>'
            . '<style>body{}</style><svg><script>1</script></svg></p>';

        $clean = HtmlSanitizer::clean($dirty);

        foreach (['<script', 'onclick', 'onerror', 'javascript:', 'JaVaScRiPt', 'data:text', '<iframe', '<style', '<svg', 'evil.test'] as $needle) {
            $this->assertStringNotContainsStringIgnoringCase($needle, $clean, "Found {$needle}");
        }
        $this->assertStringContainsString('Halo', $clean);
    }

    public function test_it_keeps_allowed_markup_and_safe_links(): void
    {
        $html = '<h2>Judul</h2><p><strong>Tebal</strong> <em>miring</em> <a href="https://otogo.id/blog" target="_blank">tautan</a> <a href="/blog">relatif</a></p>'
            . '<ul><li>satu</li></ul><table><thead><tr><th>A</th></tr></thead><tbody><tr><td>1</td></tr></tbody></table>'
            . '<img src="/assets/media/blog/a.png" alt="gambar">';

        $clean = HtmlSanitizer::clean($html);

        $this->assertStringContainsString('<h2>Judul</h2>', $clean);
        $this->assertStringContainsString('<strong>Tebal</strong>', $clean);
        $this->assertStringContainsString('href="https://otogo.id/blog"', $clean);
        $this->assertStringContainsString('rel="noopener noreferrer"', $clean);
        $this->assertStringContainsString('href="/blog"', $clean);
        $this->assertStringNotContainsString('target=', $clean);
        $this->assertStringContainsString('<td>1</td>', $clean);
        $this->assertStringContainsString('src="/assets/media/blog/a.png"', $clean);
    }

    public function test_unknown_tags_are_unwrapped_not_lost(): void
    {
        $this->assertSame('<p>teks penting</p>', HtmlSanitizer::clean('<p><span class="x">teks <font>penting</font></span></p>'));
    }

    public function test_heading_anchors_are_unique(): void
    {
        $result = HtmlSanitizer::withHeadingAnchors('<h2>Rem</h2><p>a</p><h2>Rem</h2>');

        $this->assertSame(['rem', 'rem-2'], array_column($result['toc'], 'id'));
        $this->assertStringContainsString('<h2 id="rem-2">Rem</h2>', $result['html']);
    }
}
