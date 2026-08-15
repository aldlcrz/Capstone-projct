<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SellerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test seller registration with residency certificate and BIR document.
     */
    public function test_seller_registration_with_updated_fields(): void
    {
        $testEmail = 'test.seller.registration@example.com';

        // Clean up user if already exists
        DB::table('users')->where('email', $testEmail)->delete();

        // Perform the registration request
        $response = $this->post('/seller/register', [
            'name' => 'Test Seller Registration',
            'email' => $testEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'mobileNumber' => '09876543210',
            'residencyCertificate' => UploadedFile::fake()->create('residency.pdf', 100),
            'businessPermit' => UploadedFile::fake()->create('permit.jpg', 100),
            'birDocument' => UploadedFile::fake()->create('bir.pdf', 100),
        ]);

        // Assert it redirects (e.g. to login)
        $response->assertStatus(302);

        // Assert user was created with correct fields
        $user = User::where('email', $testEmail)->first();
        $this->assertNotNull($user, 'User should be created in the database.');
        $this->assertEquals('seller', $user->role);
        $this->assertStringContainsString('residency', $user->residencyCertificate);
        $this->assertStringContainsString('permit', $user->businessPermit);
        $this->assertStringContainsString('bir', $user->birDocument);

        // Clean up
        $user->delete();
    }
}
