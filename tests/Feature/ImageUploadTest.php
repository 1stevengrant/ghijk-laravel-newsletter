<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ImageUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_authenticated_user_can_upload_image(): void
    {
        $user = User::factory()->create();

        $file = UploadedFile::fake()->image('test.jpg', 1200, 800);

        $response = $this->actingAs($user)
            ->postJson('/images/upload', [
                'image' => $file,
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'path',
            'url',
            'full_url',
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertStringStartsWith('campaign-images/', $data['path']);
        $this->assertStringEndsWith('.jpg', $data['path']);

        // Verify file was actually stored
        Storage::disk('public')->assertExists($data['path']);
    }

    public function test_guest_cannot_upload_image(): void
    {
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->postJson('/images/upload', [
            'image' => $file,
        ]);

        $response->assertStatus(401);
    }

    public function test_invalid_file_type_is_rejected(): void
    {
        $user = User::factory()->create();

        $file = UploadedFile::fake()->create('test.txt', 100);

        $response = $this->actingAs($user)
            ->postJson('/images/upload', [
                'image' => $file,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['image']);
    }

    public function test_authenticated_user_can_delete_image(): void
    {
        $user = User::factory()->create();

        // Create a fake file
        $path = 'campaign-images/test.jpg';
        Storage::disk('public')->put($path, 'fake content');

        $response = $this->actingAs($user)
            ->deleteJson('/images', [
                'path' => $path,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify file was deleted
        Storage::disk('public')->assertMissing($path);
    }

    public function test_cannot_delete_file_outside_campaign_images(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->deleteJson('/images', [
                'path' => 'other-folder/test.jpg',
            ]);

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Invalid path']);
    }
}
