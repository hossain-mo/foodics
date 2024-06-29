<?php

namespace Tests\Feature;

use App\Jobs\UpdateIngredientStockJob;
use App\Mail\LowStockAlert;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\ProductIngredient;
use App\Services\IngredientService;
use App\Services\ProductIngredientService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Ingredient::factory()->create([
            'id' => 1,
            'name' => 'Beef',
            'stock' => 20000,
            'remaining' => 20000,
        ]);

        Ingredient::factory()->create([
            'id' => 2,
            'name' => 'Cheese',
            'stock' => 5000,
            'remaining' => 5000,
        ]);

        Ingredient::factory()->create([
            'id' => 3,
            'name' => 'Onion',
            'stock' => 1000,
            'remaining' => 1000,
        ]);

        $burger = Product::factory()->create([
            'id' => 1,
            'name' => 'Burger',
        ]);

        ProductIngredient::factory()->create([
            'product_id' => 1,
            'ingredient_id' => 1,
            'quantity' => 150,
        ]);

        ProductIngredient::factory()->create([
            'product_id' => 1,
            'ingredient_id' => 2,
            'quantity' => 30,
        ]);

        ProductIngredient::factory()->create([
            'product_id' => 1,
            'ingredient_id' => 3,
            'quantity' => 20,
        ]);

        // Cache all product ingredients in Redis
        $productIngredientCacheService = new ProductIngredientService();
        $productIngredientCacheService->cacheProductIngredients($burger->id);
    }
    
    /** @test */
    public function it_creates_an_order_and_updates_ingredient_stock_with_running_queue()
    {
        Queue::fake();
        Mail::fake();

        $payload = [
            'products' => [
                [
                    'product_id' => 1, // Assuming the seeded burger product has ID 1
                    'quantity' => 2,
                ]
            ]
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(201);

        // Assert order is created
        $this->assertDatabaseHas('orders', []);

        // Assert order products are created
        $this->assertDatabaseHas('order_products', [
            'product_id' => 1,
            'quantity' => 2,
        ]);

        // Manually handle the job to simulate queue processing
        $job = new UpdateIngredientStockJob(1, 2);
        $job->handle(new IngredientService(new ProductIngredientService()));

        // Assert ingredient stock is updated
        $beef = Ingredient::find(1);
        $this->assertEquals(19700, $beef->remaining);

        $cheese = Ingredient::find(2);
        $this->assertEquals(4940, $cheese->remaining);

        $onion = Ingredient::find(3);
        $this->assertEquals(960, $onion->remaining);

        // Assert the job was pushed onto the queue
        Queue::assertPushed(UpdateIngredientStockJob::class);
    }

    /** @test */
    public function it_creates_an_order_and_updates_ingredient_stock_without_running_queue()
    {
        Queue::fake();
        Mail::fake();

        $payload = [
            'products' => [
                [
                    'product_id' => 1, // Assuming the seeded burger product has ID 1
                    'quantity' => 2,
                ]
            ]
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(201);

        // Assert order is created
        $this->assertDatabaseHas('orders', []);

        // Assert order products are created
        $this->assertDatabaseHas('order_products', [
            'product_id' => 1,
            'quantity' => 2,
        ]);

        // Assert ingredient stock is updated
        $beef = Ingredient::find(1);
        $this->assertEquals(20000, $beef->remaining);

        $cheese = Ingredient::find(2);
        $this->assertEquals(5000, $cheese->remaining);

        $onion = Ingredient::find(3);
        $this->assertEquals(1000, $onion->remaining);

        // Assert the job was pushed onto the queue
        Queue::assertPushed(UpdateIngredientStockJob::class);
    }

    /** @test */
    public function it_returns_validation_errors_for_invalid_payload()
    {
        $payload = [
            'products' => [
                [
                    'product_id' => 999, // Invalid product ID
                    'quantity' => 0, // Invalid quantity
                ]
            ]
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['products.0.product_id', 'products.0.quantity']);
    }

    /** @test */
    public function it_fails_when_ingredient_stock_is_insufficient()
    {
        $payload = [
            'products' => [
                [
                    'product_id' => 1,
                    'quantity' => 52,
                ]
            ]
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(422)
                 ->assertJson([
                     'message' => 'Insufficient stock for some ingredients for the product: 1',
                     'errors' => [
                         'products.0.quantity' => ['Insufficient stock for some ingredients for the product: 1']
                     ]
                 ]);
    }
    /** @test */
    public function it_creates_an_order_with_multiple_products_with_running_queue()
    {
        Queue::fake();
        Mail::fake();

        $secondProduct = Product::factory()->create([
            'id' => 2,
            'name' => 'Fries',
        ]);
        
        ProductIngredient::factory()->create([
            'product_id' => $secondProduct->id,
            'ingredient_id' => 2,
            'quantity' => 100,
        ]);

        // Cache all product ingredients in Redis
        $productIngredientCacheService = new ProductIngredientService();
        $productIngredientCacheService->cacheProductIngredients($secondProduct->id);
        $payload = [
            'products' => [
                [
                    'product_id' => 1,
                    'quantity' => 1,
                ],
                [
                    'product_id' => 2,
                    'quantity' => 1,
                ],
            ]
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(201);

        // Assert order is created
        $this->assertDatabaseHas('orders', []);

        // Assert order products are created
        $this->assertDatabaseHas('order_products', [
            'product_id' => 1,
            'quantity' => 1,
        ]);
        $this->assertDatabaseHas('order_products', [
            'product_id' => 2,
            'quantity' => 1,
        ]);

        // Manually handle the job to simulate queue processing
        $job = new UpdateIngredientStockJob(1, 1);
        $job->handle(new IngredientService(new ProductIngredientService()));

        $job = new UpdateIngredientStockJob(2, 1);
        $job->handle(new IngredientService(new ProductIngredientService()));

        // Assert ingredient stock is updated for both products
        $beef = Ingredient::find(1);
        $this->assertEquals(19850, $beef->remaining);
        $cheese = Ingredient::find(2);
        $this->assertEquals(4870, $cheese->remaining);
    }

    /** @test */
    public function it_creates_an_order_with_multiple_products_without_running_queue()
    {
        Queue::fake();
        Mail::fake();

        $secondProduct = Product::factory()->create([
            'id' => 2,
            'name' => 'Fries',
        ]);
        
        ProductIngredient::factory()->create([
            'product_id' => $secondProduct->id,
            'ingredient_id' => 2,
            'quantity' => 100,
        ]);

        // Cache all product ingredients in Redis
        $productIngredientCacheService = new ProductIngredientService();
        $productIngredientCacheService->cacheProductIngredients($secondProduct->id);
        $payload = [
            'products' => [
                [
                    'product_id' => 1,
                    'quantity' => 1,
                ],
                [
                    'product_id' => 2,
                    'quantity' => 1,
                ],
            ]
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(201);

        // Assert order is created
        $this->assertDatabaseHas('orders', []);

        // Assert order products are created
        $this->assertDatabaseHas('order_products', [
            'product_id' => 1,
            'quantity' => 1,
        ]);
        $this->assertDatabaseHas('order_products', [
            'product_id' => 2,
            'quantity' => 1,
        ]);

        // Assert ingredient stock is updated for both products
        $beef = Ingredient::find(1);
        $this->assertEquals(20000, $beef->remaining);
        $cheese = Ingredient::find(2);
        $this->assertEquals(5000, $cheese->remaining);

        // Assert the job was pushed onto the queue
        Queue::assertPushed(UpdateIngredientStockJob::class);
    }

    /** @test */
    public function it_sends_low_stock_alert_only_once_with_running_queue()
    {
        Queue::fake();
        Mail::fake();

        $payload = [
            'products' => [
                [
                    'product_id' => 1,
                    'quantity' => 26, // Consume enough to trigger low stock
                ]
            ]
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(201);

        // Manually handle the job to simulate queue processing
        $job = new UpdateIngredientStockJob(1, 26);
        $job->handle(new IngredientService(new ProductIngredientService()));

        // Assert low stock alert email is sent
        Mail::assertQueued(LowStockAlert::class, function ($mail) {
            return $mail->hasTo(env('MERCHANT_EMAIL'));
        });

        // Assert the job was pushed onto the queue
        Queue::assertPushed(UpdateIngredientStockJob::class);
    }

    /** @test */
    public function it_not_sends_low_stock_alert_only_once_without_running_queue()
    {
        Queue::fake();
        Mail::fake();

        $payload = [
            'products' => [
                [
                    'product_id' => 1,
                    'quantity' => 26, // Consume enough to trigger low stock
                ]
            ]
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(201);

        // Assert low stock alert email is sent
        Mail::assertNotQueued(LowStockAlert::class, function ($mail) {
            return $mail->hasTo(env('MERCHANT_EMAIL'));
        });

        // Assert the job was pushed onto the queue
        Queue::assertPushed(UpdateIngredientStockJob::class);
    }

    /** @test */
    public function it_handles_redis_unavailability_gracefully_with_running_queue()
    {
        Queue::fake();
        Mail::fake();

        // Simulate Redis down
        Redis::shouldReceive('get')
         ->with('product_1_ingredients')
         ->andReturn(false) // Simulate Redis being down or empty for this key
         ->once(); // Expect this method to be called once

        $payload = [
            'products' => [
                [
                    'product_id' => 1,
                    'quantity' => 2,
                ]
            ]
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(201);

        // Assert order is created
        $this->assertDatabaseHas('orders', []);

        // Assert order products are created
        $this->assertDatabaseHas('order_products', [
            'product_id' => 1,
            'quantity' => 2,
        ]);

        // Manually handle the job to simulate queue processing
        $job = new UpdateIngredientStockJob(1, 2);
        $job->handle(new IngredientService(new ProductIngredientService()));

        // Assert ingredient stock is updated using database fallback
        $beef = Ingredient::find(1);
        $this->assertEquals(19700, $beef->remaining);

        // Assert the job was pushed onto the queue
        Queue::assertPushed(UpdateIngredientStockJob::class);
    }

    /** @test */
    public function it_handles_redis_unavailability_gracefully_without_running_queue()
    {
        Queue::fake();
        Mail::fake();

        // Simulate Redis down
        Redis::shouldReceive('get')
         ->with('product_1_ingredients')
         ->andReturn(false); // Simulate Redis being down or empty for this key

        $payload = [
            'products' => [
                [
                    'product_id' => 1,
                    'quantity' => 2,
                ]
            ]
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(201);

        // Assert order is created
        $this->assertDatabaseHas('orders', []);

        // Assert order products are created
        $this->assertDatabaseHas('order_products', [
            'product_id' => 1,
            'quantity' => 2,
        ]);

        // Assert ingredient stock is updated using database fallback
        $beef = Ingredient::find(1);
        $this->assertEquals(20000, $beef->remaining);

        // Assert the job was pushed onto the queue
        Queue::assertPushed(UpdateIngredientStockJob::class);
    }
}
