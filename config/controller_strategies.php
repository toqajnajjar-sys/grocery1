<?php

use App\Strategies\Filters\ApprovedReviewFilter;
use App\Strategies\Filters\ColumnFilter;
use App\Strategies\Filters\FeaturedOfferFilter;
use App\Strategies\Filters\MinimumPurchaseFilter;
use App\Strategies\Filters\OfferSearchFilter;
use App\Strategies\Orders\HostedCheckoutOrderPlacement;
use App\Strategies\Orders\ImmediateOrderPlacement;
use App\Strategies\Payments\ConfiguredPaymentLabel;
use App\Strategies\Payments\HumanizedPaymentLabel;
use App\Strategies\SmartLists\AddMeals;
use App\Strategies\SmartLists\RemoveMeals;
use App\Strategies\SmartLists\ReplaceMeals;
use App\Strategies\Uploads\PublicDirectoryUpload;
use App\Strategies\Uploads\PublicDiskUpload;

// Extend behavior by registering an implementation of the relevant contract.
// Controllers and factories do not need new conditionals for new strategies.
return [
    'filters' => [
        'offers' => [
            'type' => ['class' => ColumnFilter::class, 'parameters' => ['column' => 'type']],
            'min_purchase' => ['class' => MinimumPurchaseFilter::class],
            'featured' => ['class' => FeaturedOfferFilter::class],
            'search' => ['class' => OfferSearchFilter::class],
        ],
        'reviews' => [
            'meal_id' => ['class' => ColumnFilter::class, 'parameters' => ['column' => 'meal_id']],
            'user_id' => ['class' => ColumnFilter::class, 'parameters' => ['column' => 'user_id']],
            'rating' => ['class' => ColumnFilter::class, 'parameters' => ['column' => 'rating']],
            'approved_only' => ['class' => ApprovedReviewFilter::class],
            'min_rating' => ['class' => ColumnFilter::class, 'parameters' => ['column' => 'rating', 'operator' => '>=']],
        ],
    ],
    'payment_labels' => [
        'card' => ['class' => ConfiguredPaymentLabel::class, 'parameters' => ['text' => 'Credit/Debit Card']],
        'stripe_checkout' => ['class' => ConfiguredPaymentLabel::class, 'parameters' => ['text' => 'Card (Stripe Checkout)']],
        'cash_on_delivery' => ['class' => ConfiguredPaymentLabel::class, 'parameters' => ['text' => 'Cash on Delivery']],
        'cash' => ['class' => ConfiguredPaymentLabel::class, 'parameters' => ['text' => 'Cash']],
        'stripe' => ['class' => ConfiguredPaymentLabel::class, 'parameters' => ['text' => 'Stripe']],
        'default' => ['class' => HumanizedPaymentLabel::class],
    ],
    'order_placement' => [
        'card' => ['class' => ImmediateOrderPlacement::class],
        'cash_on_delivery' => ['class' => ImmediateOrderPlacement::class],
        'stripe_checkout' => ['class' => HostedCheckoutOrderPlacement::class],
    ],
    'uploads' => [
        'profile' => ['class' => PublicDiskUpload::class, 'parameters' => ['directory' => 'profile-images']],
        'settings' => ['class' => PublicDiskUpload::class, 'parameters' => ['directory' => 'settings']],
        'smart_lists' => ['class' => PublicDirectoryUpload::class, 'parameters' => ['directory' => 'images/smart-lists']],
    ],
    'smart_list_meals' => [
        'replace' => ['class' => ReplaceMeals::class],
        'add' => ['class' => AddMeals::class],
        'remove' => ['class' => RemoveMeals::class],
    ],
];
