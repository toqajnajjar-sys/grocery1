<?php

namespace Tests\Feature;

use App\Contracts\QueryFilter;
use App\Exceptions\OperationRejected;
use App\Factories\OrderPlacementStrategyFactory;
use App\Factories\PaymentMethodLabelFactory;
use App\Factories\SmartListMealStrategyFactory;
use App\Factories\UploadStrategyFactory;
use App\Http\Controllers\Api\SettingController;
use App\Models\Offer;
use App\Models\Order;
use App\Models\User;
use App\Services\OfferService;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\ReviewService;
use App\Services\SettingService;
use App\Services\SmartListService;
use Barryvdh\DomPDF\PDF;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ControllerRefactorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Focused SQLite fixtures avoid unrelated MySQL-specific production migrations.
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('code');
            $table->string('type');
            $table->text('description')->nullable();
            $table->decimal('discount_value')->default(10);
            $table->decimal('minimum_purchase')->nullable();
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('usage_limit')->nullable();
            $table->integer('used_count')->default(0);
            $table->timestamps();
        });
        Schema::create('smart_lists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->string('description')->default('');
            $table->string('category')->nullable();
            $table->string('image')->nullable();
            $table->boolean('notify_on_price_drop')->default(true);
            $table->boolean('notify_on_offers')->default(true);
            $table->timestamps();
        });
        Schema::create('meals', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('smart_list_meals', function (Blueprint $table) {
            $table->foreignId('smart_list_id')->constrained()->cascadeOnDelete();
            $table->foreignId('meal_id')->constrained()->cascadeOnDelete();
            $table->unique(['smart_list_id', 'meal_id']);
        });
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('meal_id');
            $table->unsignedInteger('rating');
            $table->boolean('is_approved')->default(false);
            $table->text('comment')->nullable();
            $table->text('images')->nullable();
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->nullable();
            $table->string('email')->nullable();
            $table->string('profile_image')->nullable();
            $table->text('preferred_languages')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('status');
            $table->string('order_number')->nullable();
            $table->string('payment_method')->default('cash_on_delivery');
            $table->decimal('total')->default(20);
            $table->timestamps();
        });
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('meal_id');
        });
        Schema::create('order_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
        });
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('site_name')->nullable();
            $table->string('logo')->nullable();
            $table->string('favicon')->nullable();
            $table->decimal('shipping_fee')->default(0);
            $table->timestamps();
        });
    }

    private function offer(array $attributes = []): Offer
    {
        static $sequence = 0;

        return Offer::create($attributes + ['title' => 'Offer', 'code' => 'CODE'.++$sequence, 'type' => 'percentage',
            'start_date' => now()->subDay(), 'end_date' => now()->addDay(), 'is_active' => true]);
    }

    private function user(int $id = 1): User
    {
        $user = new User;
        $user->id = $id;

        return $user;
    }

    public function test_minimum_purchase_filter_does_not_leak_inactive_or_wrong_type_offers(): void
    {
        $expected = $this->offer(['minimum_purchase' => null]);
        $this->offer(['minimum_purchase' => null, 'is_active' => false]);
        $this->offer(['minimum_purchase' => null, 'type' => 'fixed']);
        $this->offer(['minimum_purchase' => null, 'end_date' => now()->subDays(2)]);
        $this->offer(['minimum_purchase' => 100]);
        $offers = $this->app->make(OfferService::class)->search(['type' => 'percentage', 'min_purchase' => 20]);
        $this->assertSame([$expected->id], $offers->getCollection()->modelKeys());
    }

    public function test_new_filter_can_be_registered_without_changing_pipeline_or_factory(): void
    {
        $expected = $this->offer(['title' => 'Extension']);
        $this->offer();
        config(['controller_strategies.filters.offers.exact_title' => ['class' => ExactTitleFilter::class]]);
        $results = $this->app->make(OfferService::class)->search(['exact_title' => 'Extension']);
        $this->assertSame([$expected->id], $results->getCollection()->modelKeys());
    }

    public function test_offer_code_validation_and_discount_contract(): void
    {
        $offer = $this->offer(['minimum_purchase' => 20, 'discount_value' => 10]);
        $this->getJson('/api/offers/validate?code='.$offer->code.'&amount=100')->assertOk()
            ->assertJsonPath('valid', true)->assertJsonPath('discount_amount', 10);
        $this->getJson('/api/offers/validate?code='.$offer->code.'&amount=5')->assertOk()->assertJsonPath('valid', false);
        $this->getJson('/api/offers/validate?code=missing')->assertNotFound()->assertExactJson(['valid' => false, 'message' => 'Invalid offer code']);
    }

    public function test_offer_request_rejects_invalid_sort_and_pagination(): void
    {
        $this->getJson('/api/offers?order_by=missing_column&per_page=0')->assertUnprocessable()->assertJsonValidationErrors(['order_by', 'per_page']);
        $this->getJson('/api/offers/validate?amount=-1')->assertUnprocessable()->assertJsonValidationErrors(['code', 'amount']);
    }

    public function test_featured_and_search_filters_combine(): void
    {
        $expected = $this->offer(['is_featured' => true, 'title' => 'Summer']);
        $this->offer(['title' => 'Summer']);
        $this->offer(['is_featured' => true]);
        $this->getJson('/api/offers?featured=true&search=Summer')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $expected->id);
    }

    public function test_placement_strategies_preserve_existing_checkout_states(): void
    {
        $factory = $this->app->make(OrderPlacementStrategyFactory::class);
        $this->assertSame('placed', $factory->make('card')->attributes()['status']);
        $this->assertNotNull($factory->make('cash_on_delivery')->attributes()['placed_at']);
        $this->assertSame(['status' => 'awaiting_payment', 'placed_at' => null], $factory->make('stripe_checkout')->attributes());
    }

    public function test_unknown_placement_strategy_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->app->make(OrderPlacementStrategyFactory::class)->make('missing');
    }

    public function test_wrong_strategy_contract_is_rejected(): void
    {
        config(['controller_strategies.smart_list_meals.bad' => ['class' => \stdClass::class]]);
        $this->expectException(\InvalidArgumentException::class);
        $this->app->make(SmartListMealStrategyFactory::class)->make('bad');
    }

    public function test_payment_labels_and_fallback_remain_compatible(): void
    {
        $factory = $this->app->make(PaymentMethodLabelFactory::class);
        foreach (['card' => 'Credit/Debit Card', 'stripe_checkout' => 'Card (Stripe Checkout)',
            'cash_on_delivery' => 'Cash on Delivery', 'cash' => 'Cash', 'stripe' => 'Stripe', 'bank_transfer' => 'Bank transfer'] as $method => $label) {
            $this->assertSame($label, $factory->make($method)->label($method));
        }
    }

    public function test_smart_list_add_is_idempotent_and_replace_and_remove_are_distinct(): void
    {
        DB::table('meals')->insert([['id' => 1], ['id' => 2]]);
        $service = $this->app->make(SmartListService::class);
        $list = $service->create(1, ['name' => 'Weekly', 'meal_ids' => [1]]);
        $list = $service->changeMeals(1, $list->id, 'add', [1, 2]);
        $this->assertEqualsCanonicalizing([1, 2], $list->meals->modelKeys());
        $list = $service->update(1, $list->id, ['name' => 'Weekly', 'description' => null]);
        $this->assertCount(2, $list->meals);
        $this->assertSame('', $list->description);
        $list = $service->changeMeals(1, $list->id, 'remove', [1]);
        $this->assertSame([2], $list->meals->modelKeys());
        $list = $service->update(1, $list->id, ['name' => 'Weekly', 'meal_ids' => []]);
        $this->assertCount(0, $list->meals);
        $service->delete(1, $list->id);
        $this->assertSame(0, DB::table('smart_lists')->count());
    }

    public function test_smart_list_update_cannot_cross_user_boundary(): void
    {
        $service = $this->app->make(SmartListService::class);
        $list = $service->create(1, ['name' => 'Private']);
        $this->expectException(ModelNotFoundException::class);
        $service->update(2, $list->id, ['name' => 'Changed']);
    }

    public function test_smart_list_creation_rolls_back_when_meal_attachment_fails(): void
    {
        try {
            $this->app->make(SmartListService::class)->create(1, ['name' => 'Rollback', 'meal_ids' => [999]]);
        } catch (QueryException $e) {
            $this->assertSame(0, DB::table('smart_lists')->count());

            return;
        }
        $this->fail('An invalid meal should fail its foreign key constraint.');
    }

    public function test_reviews_filter_defaults_and_unapproved_override(): void
    {
        DB::table('reviews')->insert([
            ['user_id' => 1, 'meal_id' => 1, 'rating' => 5, 'is_approved' => true],
            ['user_id' => 1, 'meal_id' => 1, 'rating' => 4, 'is_approved' => false],
            ['user_id' => 2, 'meal_id' => 2, 'rating' => 2, 'is_approved' => true],
        ]);
        $service = $this->app->make(ReviewService::class);
        $this->assertSame(1, $service->search(['meal_id' => 1])->total());
        $this->assertSame(2, $service->search(['meal_id' => 1, 'approved_only' => 'false'])->total());
        $this->assertSame(1, $service->search(['min_rating' => 4])->total());
    }

    public function test_duplicate_review_is_rejected(): void
    {
        DB::table('reviews')->insert(['user_id' => 1, 'meal_id' => 1, 'rating' => 5]);
        $this->expectException(OperationRejected::class);
        $this->app->make(ReviewService::class)->create($this->user(), ['meal_id' => 1, 'rating' => 3]);
    }

    public function test_review_mutation_rejects_another_owner(): void
    {
        $id = DB::table('reviews')->insertGetId(['user_id' => 1, 'meal_id' => 1, 'rating' => 5]);
        $this->expectException(OperationRejected::class);
        $this->app->make(ReviewService::class)->delete($this->user(2), $id);
    }

    public function test_review_owner_can_update_and_admin_can_delete(): void
    {
        $id = DB::table('reviews')->insertGetId(['user_id' => 1, 'meal_id' => 1, 'rating' => 5]);
        $service = $this->app->make(ReviewService::class);
        $service->update($this->user(), $id, ['rating' => 4]);
        $this->assertSame(4, DB::table('reviews')->value('rating'));
        $admin = $this->user(2);
        $admin->is_admin = true;
        $service->delete($admin, $id);
        $this->assertSame(0, DB::table('reviews')->count());
    }

    public function test_order_details_are_scoped_to_authenticated_user(): void
    {
        $id = DB::table('orders')->insertGetId(['user_id' => 1, 'status' => 'placed']);
        $this->expectException(ModelNotFoundException::class);
        $this->app->make(OrderService::class)->show($this->user(2), $id);
    }

    public function test_payment_receipt_and_invoice_reject_another_user(): void
    {
        $order = new Order;
        $order->user_id = 1;
        $service = $this->app->make(PaymentService::class);
        foreach (['receipt', 'invoiceOrder'] as $method) {
            try {
                $service->$method($this->user(2), $order);
                $this->fail('Expected rejection');
            } catch (OperationRejected $e) {
                $this->assertSame('not_found', $e->reason);
            }
        }
    }

    public function test_profile_validation_keeps_error_envelope(): void
    {
        $this->actingAs($this->user())->postJson('/api/profile/image', [])
            ->assertUnprocessable()->assertJsonPath('success', false)->assertJsonPath('message', 'Validation failed');
        $this->putJson('/api/profile/info', ['birthday' => 'not-a-date'])
            ->assertUnprocessable()->assertJsonValidationErrors('birthday');
    }

    public function test_profile_rejects_multiple_images(): void
    {
        $this->actingAs($this->user())->post('/api/profile/image', [
            'image' => [UploadedFile::fake()->image('one.png'), UploadedFile::fake()->image('two.png')],
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonPath('message', 'Only one profile image is allowed');
    }

    public function test_upload_factory_uses_existing_public_disk_directory(): void
    {
        Storage::fake('public');
        $path = $this->app->make(UploadStrategyFactory::class)->make('settings')->store(UploadedFile::fake()->image('logo.png'));
        $this->assertStringStartsWith('settings/', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_settings_update_request_is_admin_only_and_validated(): void
    {
        // The original application exposes only settings.index; exercise the existing update action on a test-only route.
        Route::put('/test/settings', [SettingController::class, 'update']);
        $this->actingAs($this->user())->putJson('/test/settings', [])->assertForbidden();
        $admin = $this->user();
        $admin->is_admin = true;
        $this->actingAs($admin)->putJson('/test/settings', ['shipping_fee' => -1])->assertUnprocessable();
    }

    public function test_all_scoped_controllers_are_resolvable(): void
    {
        foreach (['Offer', 'Order', 'Payment', 'Profile', 'Review', 'Setting', 'SmartList'] as $name) {
            $class = 'App\\Http\\Controllers\\Api\\'.$name.'Controller';
            $this->assertInstanceOf($class, $this->app->make($class));
        }
    }

    public function test_order_routes_and_payment_history_only_include_current_user_orders(): void
    {
        $own = DB::table('orders')->insertGetId(['user_id' => 1, 'status' => 'placed']);
        $other = DB::table('orders')->insertGetId(['user_id' => 2, 'status' => 'placed']);
        $this->actingAs($this->user())->getJson('/api/orders')->assertOk()
            ->assertJsonPath('total_count', 1)->assertJsonPath('data.0.id', $own);
        $this->getJson('/api/orders/'.$own)->assertOk()->assertJsonPath('data.id', $own);
        $this->getJson('/api/orders/'.$other)->assertNotFound();
        $this->getJson('/api/payments/history')->assertOk()->assertJsonPath('total_count', 1)->assertJsonPath('total_amount', 20);
    }

    public function test_owned_receipt_uses_payment_label_strategy(): void
    {
        DB::table('users')->insert(['id' => 1, 'username' => 'saleh']);
        $id = DB::table('orders')->insertGetId(['user_id' => 1, 'status' => 'placed', 'order_number' => 'ORD-TEST']);
        $this->actingAs($this->user())->getJson('/api/payments/receipt/'.$id)->assertOk()
            ->assertJsonPath('data.receipt_number', 'ORD-TEST')->assertJsonPath('data.payment.method_display', 'Cash on Delivery');
    }

    public function test_invoice_returns_a_download_response_instead_of_requiring_json(): void
    {
        $id = DB::table('orders')->insertGetId(['user_id' => 1, 'status' => 'placed']);
        $pdf = \Mockery::mock(PDF::class);
        $pdf->shouldReceive('download')->once()->with('invoice.pdf'.$id.'.pdf')
            ->andReturn(response('%PDF-test', 200, ['Content-Type' => 'application/pdf']));
        \Barryvdh\DomPDF\Facade\Pdf::shouldReceive('loadView')->once()->with('invoices.show', \Mockery::on(fn ($data) => $data['order']->id === $id))->andReturn($pdf);
        $this->actingAs($this->user())->get('/api/payments/invoice/'.$id)->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_profile_update_can_clear_languages_and_upload_and_delete_image(): void
    {
        Storage::fake('public');
        DB::table('users')->insert(['id' => 1, 'username' => 'saleh', 'preferred_languages' => '["en"]']);
        $user = User::findOrFail(1);
        $this->actingAs($user)->putJson('/api/profile/info', ['preferred_languages' => []])->assertOk()->assertJsonPath('data.preferred_languages', []);
        $this->post('/api/profile/image', ['image' => UploadedFile::fake()->image('avatar.png')], ['Accept' => 'application/json'])->assertOk();
        $path = $user->fresh()->profile_image;
        $this->assertStringStartsWith('profile-images/', $path);
        Storage::disk('public')->assertExists($path);
        $this->deleteJson('/api/profile/image')->assertOk();
        Storage::disk('public')->assertMissing($path);
        $this->assertNull($user->fresh()->profile_image);
    }

    public function test_settings_service_persists_fields_and_uploads_through_factory(): void
    {
        Storage::fake('public');
        $settings = $this->app->make(SettingService::class)->update([
            'site_name' => 'Next Level', 'shipping_fee' => 5, 'logo' => UploadedFile::fake()->image('logo.png'),
        ]);
        $this->assertSame('Next Level', $settings->fresh()->site_name);
        $this->assertSame('5.00', $settings->fresh()->shipping_fee);
        Storage::disk('public')->assertExists($settings->logo);
        $this->getJson('/api/settings')->assertOk()->assertJsonPath('data.site_info.site_name', 'Next Level');
    }
}
class ExactTitleFilter implements QueryFilter
{
    public function apply(Builder $query, mixed $value): void
    {
        $query->where('title', $value);
    }
}
