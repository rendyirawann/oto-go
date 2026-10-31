<?php

namespace Tests\Feature\Blog;

use App\Actions\Blog\SavePost;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AdminPostTest extends TestCase
{
    use BlogTestHelpers;
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.posts.index'))->assertRedirect(route('login'));
        $this->post(route('admin.posts.store'), [])->assertRedirect(route('login'));
    }

    public function test_customer_account_cannot_use_blog_admin(): void
    {
        $this->actingAs($this->customer())
            ->get(route('admin.posts.index'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_staff_without_data_master_permission_is_forbidden(): void
    {
        $this->actingAs($this->staff(['view_dashboard']))
            ->get(route('admin.posts.index'))
            ->assertForbidden();
    }

    public function test_admin_with_permission_can_list_and_open_forms(): void
    {
        $post = Post::factory()->create(['title' => 'Artikel Untuk Diubah']);
        $admin = $this->staff(['view_dashboard', 'view_data_master']);

        $this->actingAs($admin)->get(route('admin.posts.index'))->assertOk()->assertSee('Artikel Untuk Diubah');
        $this->actingAs($admin)->get(route('admin.posts.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.posts.edit', $post))->assertOk();
    }

    public function test_superadmin_can_create_post_and_content_is_sanitized(): void
    {
        $admin = $this->superadmin();

        $response = $this->actingAs($admin)->post(route('admin.posts.store'), [
            'title' => 'Panduan Servis Berkala',
            'content' => '<h2>Mulai</h2><p onclick="steal()">Aman <script>alert(1)</script><a href="javascript:alert(1)">x</a></p>',
            'status' => Post::STATUS_PUBLISHED,
            'new_category' => 'Tips Bengkel',
        ]);

        $post = Post::where('slug', 'panduan-servis-berkala')->firstOrFail();
        $response->assertRedirect(route('admin.posts.edit', $post));

        $this->assertSame($admin->id, $post->author_id);
        $this->assertNotNull($post->published_at);
        $this->assertSame('Tips Bengkel', $post->category->name);
        $this->assertStringNotContainsString('script', $post->content);
        $this->assertStringNotContainsString('onclick', $post->content);
        $this->assertStringNotContainsString('javascript:', $post->content);
        $this->assertStringContainsString('<h2>Mulai</h2>', $post->content);

        $this->get(route('blog.show', $post->slug))->assertOk();
    }

    public function test_validation_errors_are_reported(): void
    {
        $this->actingAs($this->superadmin())
            ->post(route('admin.posts.store'), ['title' => 'x', 'content' => '', 'status' => 'bogus'])
            ->assertSessionHasErrors(['title', 'content', 'status']);
    }

    public function test_update_with_cover_upload_and_delete(): void
    {
        $admin = $this->superadmin();
        $post = Post::factory()->create(['title' => 'Judul Lama Sekali']);

        $this->actingAs($admin)->put(route('admin.posts.update', $post), [
            'title' => 'Judul Baru Sekali',
            'slug' => 'judul-baru',
            'content' => '<p>Konten yang sudah diperbarui dengan cukup panjang.</p>',
            'status' => Post::STATUS_DRAFT,
            'cover' => UploadedFile::fake()->image('sampul.jpg', 1200, 630),
        ])->assertRedirect();

        $post->refresh();
        $this->assertSame('judul-baru', $post->slug);
        $this->assertSame(Post::STATUS_DRAFT, $post->status);
        $this->assertStringStartsWith(SavePost::UPLOAD_DIR . '/', (string) $post->cover_path);
        $this->assertFileExists(public_path($post->cover_path));

        $coverFile = public_path($post->cover_path);

        $this->actingAs($admin)->delete(route('admin.posts.destroy', $post))->assertRedirect(route('admin.posts.index'));
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
        $this->assertFileDoesNotExist($coverFile);

        File::delete($coverFile);
    }
}
