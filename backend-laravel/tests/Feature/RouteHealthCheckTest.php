<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteHealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_routes_return_ok_without_500_or_404(): void
    {
        $publicRoutes = [
            '/',
            '/login',
            '/register',
            '/products',
            '/terms',
            '/seller/register',
        ];

        foreach ($publicRoutes as $uri) {
            $response = $this->get($uri);
            $this->assertNotEquals(500, $response->getStatusCode(), "Route {$uri} returned 500 error.");
            $this->assertNotEquals(404, $response->getStatusCode(), "Route {$uri} returned 404 error.");
            $this->assertTrue(in_array($response->getStatusCode(), [200, 302]), "Route {$uri} returned unexpected status {$response->getStatusCode()}.");
        }
    }

    public function test_authenticated_customer_routes_return_ok_without_500_or_404(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
            'isVerified' => true,
        ]);

        $customerRoutes = [
            '/orders',
            '/cart',
            '/wishlist',
            '/profile',
        ];

        foreach ($customerRoutes as $uri) {
            $response = $this->actingAs($customer)->get($uri);
            $this->assertNotEquals(500, $response->getStatusCode(), "Customer route {$uri} returned 500 error.");
            $this->assertNotEquals(404, $response->getStatusCode(), "Customer route {$uri} returned 404 error.");
        }
    }

    public function test_authenticated_seller_routes_return_ok_without_500_or_404(): void
    {
        $seller = User::factory()->create([
            'role' => 'seller',
            'status' => 'active',
            'isVerified' => true,
            'shopName' => 'Heritage Artisan Test Shop',
        ]);

        $sellerRoutes = [
            '/seller/orders',
            '/seller/products',
            '/seller/dashboard',
        ];

        foreach ($sellerRoutes as $uri) {
            $response = $this->actingAs($seller)->get($uri);
            $this->assertNotEquals(500, $response->getStatusCode(), "Seller route {$uri} returned 500 error.");
            $this->assertNotEquals(404, $response->getStatusCode(), "Seller route {$uri} returned 404 error.");
        }
    }

    public function test_authenticated_superadmin_routes_return_ok_without_500_or_404(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'status' => 'active',
        ]);

        $adminRoutes = [
            '/superadmin/dashboard',
            '/superadmin/orders',
            '/superadmin/products',
            '/superadmin/users',
            '/superadmin/sellers',
            '/superadmin/returns',
            '/superadmin/shipping',
        ];

        foreach ($adminRoutes as $uri) {
            $response = $this->actingAs($superadmin)->get($uri);
            $this->assertNotEquals(500, $response->getStatusCode(), "Superadmin route {$uri} returned 500 error.");
            $this->assertNotEquals(404, $response->getStatusCode(), "Superadmin route {$uri} returned 404 error.");
        }
    }
}
