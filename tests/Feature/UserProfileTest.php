<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Organization $org;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Organization::create([
            'name' => 'Apex Agency',
            'slug' => 'apex-agency',
            'currency' => 'USD',
            'status' => 'active',
        ]);

        $this->user = User::create([
            'organization_id' => $this->org->id,
            'name' => 'Blen Tadesse',
            'email' => 'blen@apex.com',
            'phone' => '+251911223344',
            'password' => Hash::make('OldPassword123!'),
            'role' => User::ROLE_ORGANIZATION_ADMIN,
            'is_active' => true,
        ]);
    }

    public function test_guest_is_redirected_to_login_when_accessing_profile()
    {
        $response = $this->get(route('profile.edit'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_profile_settings()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('profile.edit'));
        $response->assertOk();
        $response->assertSee('Profile');
        $response->assertSee('Blen Tadesse');
        $response->assertSee('blen@apex.com');
        $response->assertSee('Apex Agency');
    }

    public function test_user_can_update_personal_information()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('profile.update'), [
            'name' => 'Blen Tadesse Updated',
            'email' => 'blen.new@apex.com',
            'phone' => '+251922334455',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('success');

        $this->user->refresh();
        $this->assertEquals('Blen Tadesse Updated', $this->user->name);
        $this->assertEquals('blen.new@apex.com', $this->user->email);
        $this->assertEquals('+251922334455', $this->user->phone);
    }

    public function test_user_can_upload_profile_picture()
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $file = UploadedFile::fake()->image('blen_avatar.jpg', 300, 300);

        $response = $this->post(route('profile.avatar.update'), [
            'avatar' => $file,
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('success');

        $this->user->refresh();
        $this->assertNotNull($this->user->avatar);
        Storage::disk('public')->assertExists($this->user->avatar);
        $this->assertStringContainsString('storage/' . $this->user->avatar, $this->user->getAvatarUrl());
    }

    public function test_user_can_remove_avatar_and_restore_default()
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $file = UploadedFile::fake()->image('custom_avatar.png');
        $path = $file->store('avatars/' . $this->user->id, 'public');
        $this->user->update(['avatar' => $path]);

        $this->assertNotNull($this->user->avatar);

        $response = $this->delete(route('profile.avatar.remove'));
        $response->assertRedirect(route('profile.edit'));

        $this->user->refresh();
        $this->assertNull($this->user->avatar);
        $this->assertStringContainsString('ui-avatars.com', $this->user->getAvatarUrl());
    }

    public function test_user_can_update_password_with_valid_current_password()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('profile.password.update'), [
            'current_password' => 'OldPassword123!',
            'password' => 'NewSecurePass2026!',
            'password_confirmation' => 'NewSecurePass2026!',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('success');

        $this->user->refresh();
        $this->assertTrue(Hash::check('NewSecurePass2026!', $this->user->password));
    }

    public function test_user_cannot_update_password_with_wrong_current_password()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('profile.password.update'), [
            'current_password' => 'WrongCurrentPassword!',
            'password' => 'NewSecurePass2026!',
            'password_confirmation' => 'NewSecurePass2026!',
        ]);

        $response->assertSessionHasErrors('current_password');

        $this->user->refresh();
        $this->assertTrue(Hash::check('OldPassword123!', $this->user->password));
    }
}
