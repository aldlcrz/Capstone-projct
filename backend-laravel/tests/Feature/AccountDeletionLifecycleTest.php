<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\CommissionRecord;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountDeletionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_download_personal_information_archive()
    {
        $customer = User::create([
            'name'         => 'Maria Santos',
            'username'     => 'mariasantos',
            'email'        => 'maria.santos@gmail.com',
            'password'     => Hash::make('Password123!'),
            'role'         => 'customer',
            'status'       => 'active',
            'mobileNumber' => '09171234567',
            'gender'       => 'Female',
            'birthday'     => '1995-05-15',
            'bio'          => 'Artisan enthusiast.',
            'isVerified'   => true,
        ]);

        Address::create([
            'userId'        => $customer->id,
            'recipientName' => 'Maria Santos',
            'phone'         => '09171234567',
            'region'        => 'Region IV-A',
            'houseNo'       => '123',
            'street'        => 'Rizal St.',
            'barangay'      => 'Poblacion',
            'city'          => 'Lumban',
            'province'      => 'Laguna',
            'postalCode'    => '4014',
            'isDefault'     => true,
        ]);

        $this->actingAs($customer);

        $response = $this->get(route('profile.download-information'));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_seller_can_download_shop_information_archive()
    {
        $seller = User::create([
            'name'            => 'Juan Dela Cruz',
            'username'        => 'juandelacruz',
            'email'           => 'juan.artisan@gmail.com',
            'password'        => Hash::make('Password123!'),
            'role'            => 'seller',
            'status'          => 'active',
            'shopName'        => 'Lumban Heritage Embroidery',
            'shopDescription' => 'Specializing in authentic hand-embroidered barongs.',
            'isVerified'      => true,
        ]);

        $product = Product::create([
            'sellerId'     => $seller->id,
            'name'         => 'Traditional Piña Silk Barong',
            'price'        => 4500.00,
            'stock'        => 10,
            'status'       => 'approved',
            'description'  => 'Fine piña silk embroidery.',
            'has_variants' => true,
            'variations'   => [
                ['name' => 'Size L - Cream', 'price' => 4500.00, 'stock' => 5],
            ],
        ]);

        $this->actingAs($seller);

        $response = $this->get(route('profile.download-information'));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_unauthenticated_user_cannot_download_information()
    {
        $response = $this->get(route('profile.download-information'));
        $response->assertRedirect(route('login'));
    }

    public function test_delete_account_validation_strictly_requires_exact_delete_confirmation()
    {
        $customer = User::create([
            'name'       => 'Test Customer',
            'email'      => 'test.delete@gmail.com',
            'password'   => Hash::make('Password123!'),
            'role'       => 'customer',
            'status'     => 'active',
            'isVerified' => true,
        ]);

        $this->actingAs($customer);

        // Test with invalid confirmation inputs
        foreach (['', 'delete', 'Delete', 'DELETE123', 'DELETE!'] as $invalidInput) {
            $response = $this->post(route('profile.delete-account'), [
                'confirm' => $invalidInput,
            ]);
            $response->assertSessionHasErrors('confirm');

            $customer->refresh();
            $this->assertEquals('active', $customer->status);
            $this->assertNull($customer->deletion_scheduled_at);
            $this->assertNull($customer->permanent_deletion_at);
        }
    }

    public function test_customer_account_scheduled_for_7_day_deletion_successfully()
    {
        $customer = User::create([
            'name'       => 'Customer To Delete',
            'email'      => 'customer.delete@gmail.com',
            'password'   => Hash::make('Password123!'),
            'role'       => 'customer',
            'status'     => 'active',
            'isVerified' => true,
        ]);

        $this->actingAs($customer);

        $response = $this->post(route('profile.delete-account'), [
            'confirm' => 'DELETE',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('info');

        $customer->refresh();
        $this->assertEquals('pending_deletion', $customer->status);
        $this->assertNotNull($customer->deletion_scheduled_at);
        $this->assertNotNull($customer->permanent_deletion_at);
        $this->assertNotNull($customer->deleted_at);
        $this->assertFalse(Auth::check());
    }

    public function test_seller_account_scheduled_for_7_day_deletion_successfully()
    {
        $seller = User::create([
            'name'       => 'Seller To Delete',
            'email'      => 'seller.delete@gmail.com',
            'password'   => Hash::make('Password123!'),
            'role'       => 'seller',
            'status'     => 'active',
            'shopName'   => 'Sample Shop',
            'isVerified' => true,
        ]);

        $this->actingAs($seller);

        $response = $this->post(route('seller.delete-account'), [
            'confirm' => 'DELETE',
        ]);

        $response->assertRedirect(route('login'));
        $seller->refresh();
        $this->assertEquals('pending_deletion', $seller->status);
        $this->assertNotNull($seller->deletion_scheduled_at);
        $this->assertNotNull($seller->permanent_deletion_at);
    }

    public function test_pending_deletion_login_intercepted_and_prompts_restore_modal()
    {
        $user = User::create([
            'name'                  => 'Pending User',
            'email'                 => 'pending.user@gmail.com',
            'password'              => Hash::make('SecretPassword123!'),
            'role'                  => 'customer',
            'status'                => 'pending_deletion',
            'deletion_scheduled_at' => now(),
            'permanent_deletion_at' => now()->addDays(7),
            'deleted_at'            => now(),
            'isVerified'            => true,
        ]);

        $response = $this->post('/login', [
            'email'    => 'pending.user@gmail.com',
            'password' => 'SecretPassword123!',
        ]);

        $response->assertSessionHas('restore_account_prompt', true);
        $response->assertSessionHas('restore_account_user_id', $user->id);
        $response->assertSessionHas('restore_account_name', 'Pending User');
        $response->assertSessionHas('restore_account_email', 'pending.user@gmail.com');
        $this->assertFalse(Auth::check());
    }

    public function test_user_can_restore_account_within_7_days()
    {
        $user = User::create([
            'name'                  => 'Restorable User',
            'email'                 => 'restore.user@gmail.com',
            'password'              => Hash::make('SecretPassword123!'),
            'role'                  => 'customer',
            'status'                => 'pending_deletion',
            'deletion_scheduled_at' => now()->subDays(2),
            'permanent_deletion_at' => now()->addDays(5),
            'deleted_at'            => now()->subDays(2),
            'isVerified'            => true,
        ]);

        // Simulate session state from login interception
        $response = $this->withSession([
            'restore_account_user_id' => $user->id,
            'restore_account_email'   => $user->email,
        ])->post(route('account.restore'));

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertEquals('active', $user->status);
        $this->assertNull($user->deletion_scheduled_at);
        $this->assertNull($user->permanent_deletion_at);
        $this->assertNull($user->deleted_at);
        $this->assertTrue(Auth::check());
        $this->assertEquals($user->id, Auth::id());
    }

    public function test_keep_account_scheduled_preserves_pending_deletion_and_original_deadline()
    {
        $originalExpiry = now()->addDays(4)->roundSecond();
        $user = User::create([
            'name'                  => 'Keep Scheduled User',
            'email'                 => 'keep.user@gmail.com',
            'password'              => Hash::make('SecretPassword123!'),
            'role'                  => 'customer',
            'status'                => 'pending_deletion',
            'deletion_scheduled_at' => now()->subDays(3),
            'permanent_deletion_at' => $originalExpiry,
            'deleted_at'            => now()->subDays(3),
            'isVerified'            => true,
        ]);

        $response = $this->withSession([
            'restore_account_user_id' => $user->id,
        ])->post(route('account.restore.cancel'));

        $response->assertRedirect(route('login'));
        $response->assertSessionMissing('restore_account_user_id');

        $user->refresh();
        $this->assertEquals('pending_deletion', $user->status);
        $this->assertEquals($originalExpiry->toDateTimeString(), $user->permanent_deletion_at->toDateTimeString());
        $this->assertFalse(Auth::check());
    }

    public function test_expired_account_cannot_be_restored_and_is_permanently_purged()
    {
        $expiredUser = User::create([
            'name'                  => 'Expired User',
            'email'                 => 'expired.user@gmail.com',
            'password'              => Hash::make('SecretPassword123!'),
            'role'                  => 'customer',
            'status'                => 'pending_deletion',
            'deletion_scheduled_at' => now()->subDays(8),
            'permanent_deletion_at' => now()->subHour(),
            'deleted_at'            => now()->subDays(8),
            'isVerified'            => true,
        ]);

        $response = $this->withSession([
            'restore_account_user_id' => $expiredUser->id,
        ])->post(route('account.restore'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');

        // User should be completely removed from users table
        $this->assertNull(User::withTrashed()->find($expiredUser->id));
    }

    public function test_scheduled_deletion_artisan_command_purges_only_expired_accounts()
    {
        $expiredUser = User::create([
            'name'                  => 'Auto Expired User',
            'email'                 => 'auto.expired@gmail.com',
            'password'              => Hash::make('Password123!'),
            'role'                  => 'customer',
            'status'                => 'pending_deletion',
            'deletion_scheduled_at' => now()->subDays(8),
            'permanent_deletion_at' => now()->subMinutes(10),
            'deleted_at'            => now()->subDays(8),
            'isVerified'            => true,
        ]);

        $stillPendingUser = User::create([
            'name'                  => 'Still Pending User',
            'email'                 => 'still.pending@gmail.com',
            'password'              => Hash::make('Password123!'),
            'role'                  => 'customer',
            'status'                => 'pending_deletion',
            'deletion_scheduled_at' => now()->subDays(2),
            'permanent_deletion_at' => now()->addDays(5),
            'deleted_at'            => now()->subDays(2),
            'isVerified'            => true,
        ]);

        $activeUser = User::create([
            'name'       => 'Active User',
            'email'      => 'active.user@gmail.com',
            'password'   => Hash::make('Password123!'),
            'role'       => 'customer',
            'status'     => 'active',
            'isVerified' => true,
        ]);

        // Run automated cleanup command
        $exitCode = Artisan::call('accounts:process-deletions');
        $this->assertEquals(0, $exitCode);

        // Expired user should be force deleted
        $this->assertNull(User::withTrashed()->find($expiredUser->id));

        // Still pending and active users must remain intact
        $this->assertNotNull(User::withTrashed()->find($stillPendingUser->id));
        $this->assertNotNull(User::find($activeUser->id));

        // Test command idempotency (running second time without errors)
        $exitCodeSecond = Artisan::call('accounts:process-deletions');
        $this->assertEquals(0, $exitCodeSecond);
    }

    public function test_gmail_reuse_after_permanent_account_deletion()
    {
        $email = 'reusable.gmail@gmail.com';

        $user = User::create([
            'name'                  => 'Old Account',
            'email'                 => $email,
            'password'              => Hash::make('OldPassword123!'),
            'role'                  => 'customer',
            'status'                => 'pending_deletion',
            'deletion_scheduled_at' => now()->subDays(8),
            'permanent_deletion_at' => now()->subMinute(),
            'deleted_at'            => now()->subDays(8),
            'isVerified'            => true,
        ]);

        // Process scheduled deletion
        Artisan::call('accounts:process-deletions');
        $this->assertNull(User::withTrashed()->find($user->id));

        // Register new account with the exact same Gmail
        $response = $this->post('/register', [
            'name'                  => 'New Account Owner',
            'email'                 => $email,
            'password'              => 'NewSecurePassword123!',
            'password_confirmation' => 'NewSecurePassword123!',
            'terms_consent'         => '1',
        ]);

        $response->assertSessionHas('pending_registration');
        $pending = session('pending_registration');
        $this->assertEquals($email, $pending['email']);
        $this->assertEquals('New Account Owner', $pending['name']);
    }
}
