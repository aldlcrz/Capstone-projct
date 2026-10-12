<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test chat API flow: sending messages, retrieving conversation, and list.
     */
    public function test_chat_api_endpoints(): void
    {
        // 1. Create a customer and a seller
        $customer = User::create([
            'name' => 'John Customer',
            'email' => 'john@example.com',
            'password' => bcrypt('password123'),
            'role' => 'customer',
        ]);

        $seller = User::create([
            'name' => 'Artisan Seller',
            'email' => 'artisan@example.com',
            'password' => bcrypt('password123'),
            'role' => 'seller',
        ]);

        // 2. Act as the customer and send a message
        $response = $this->actingAs($customer)
            ->postJson('/api/chat/message', [
                'receiverId' => $seller->id,
                'content' => 'Hello, is this product available?'
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'id', 'senderId', 'receiverId', 'content', 'read', 'createdAt'
        ]);

        // 3. Act as the customer and retrieve conversations list
        $response = $this->actingAs($customer)
            ->getJson('/api/chat/conversations');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'name' => 'Artisan Seller',
            'role' => 'seller'
        ]);

        // 4. Act as the seller and retrieve conversation history
        $response = $this->actingAs($seller)
            ->getJson('/api/chat/conversation/' . $customer->id);

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment([
            'senderId' => $customer->id,
            'receiverId' => $seller->id,
            'content' => 'Hello, is this product available?'
        ]);

        // 5. Act as the customer and delete the conversation
        $response = $this->actingAs($customer)
            ->deleteJson('/api/chat/conversation/' . $seller->id);

        $response->assertStatus(200);
        
        // Assert it is deleted
        $this->assertEquals(0, Message::count());
    }

    /**
     * Test seller quick replies management endpoints.
     */
    public function test_seller_quick_replies_endpoints(): void
    {
        $seller = User::create([
            'name' => 'Artisan Seller',
            'email' => 'artisan_qr@example.com',
            'password' => bcrypt('password123'),
            'role' => 'seller',
        ]);

        // 1. Get default quick replies
        $response = $this->actingAs($seller)->getJson('/api/chat/quick-replies');
        $response->assertStatus(200);
        $this->assertNotEmpty($response->json());
        $response->assertJsonFragment(['title' => 'Order Confirmation & Thanks']);

        // 2. Save custom quick replies
        $customReplies = [
            [
                'id' => 'custom-1',
                'title' => 'Custom Embroidery Update',
                'category' => 'Order Updates',
                'text' => 'We are working on your custom embroidery piece.'
            ],
            [
                'id' => 'custom-2',
                'title' => 'Fitting Consultation',
                'category' => 'Sizing & Fitting',
                'text' => 'Please provide your chest and sleeve measurements.'
            ]
        ];

        $saveResponse = $this->actingAs($seller)->postJson('/api/chat/quick-replies', [
            'replies' => $customReplies
        ]);
        $saveResponse->assertStatus(200);
        $saveResponse->assertJsonFragment(['title' => 'Custom Embroidery Update']);

        // 3. Retrieve and assert saved custom replies
        $getResponse = $this->actingAs($seller)->getJson('/api/chat/quick-replies');
        $getResponse->assertStatus(200);
        $getResponse->assertJsonCount(2);
        $getResponse->assertJsonFragment(['title' => 'Custom Embroidery Update']);

        // 4. Reset to defaults
        $resetResponse = $this->actingAs($seller)->postJson('/api/chat/quick-replies/reset');
        $resetResponse->assertStatus(200);
        $resetResponse->assertJsonFragment(['title' => 'Order Confirmation & Thanks']);
    }
}
