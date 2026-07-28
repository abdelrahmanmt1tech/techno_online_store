<?php

namespace App\Filament\Tenant\Widgets;

use App\Models\Tenant\Cart;
use App\Models\Tenant\Contact;
use App\Models\Tenant\Coupon;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Order;
use App\Models\Tenant\Product;
use App\Models\Tenant\Review;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StoreKpis extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $from = Carbon::now()->subDays(30)->startOfDay();
        $to = Carbon::now();

        $totalProducts = Product::count();
        $activeProducts = Product::where('is_active', true)->count();
        $newProducts = Product::whereBetween('created_at', [$from, $to])->count();

        $totalOrders = Order::count();
        $pendingOrders = Order::whereIn('status', ['pending', 'confirmed', 'processing'])->count();
        $newOrders = Order::whereBetween('created_at', [$from, $to])->count();

        $totalCustomers = Customer::count();
        $newCustomers = Customer::whereBetween('created_at', [$from, $to])->count();

        $activeCoupons = Coupon::where('is_active', true)->count();
        $totalCoupons = Coupon::count();

        $totalReviews = Review::count();
        $unapprovedReviews = Review::where('is_approved', false)->count();

        $totalContacts = Contact::count();
        $unreadContacts = Contact::whereNull('read_at')->count();

        $activeCarts = Cart::where('status', 'active')->count();

        return [
            Stat::make(__('dashboard.widget.total_products'), (string) $totalProducts)
                ->description(__('dashboard.widget.products_desc', ['active' => $activeProducts, 'new' => $newProducts]))
                ->descriptionIcon('heroicon-o-cube')
                ->color('primary'),

            Stat::make(__('dashboard.widget.total_orders'), (string) $totalOrders)
                ->description(__('dashboard.widget.orders_desc', ['pending' => $pendingOrders, 'new' => $newOrders]))
                ->descriptionIcon('heroicon-o-shopping-bag')
                ->color($pendingOrders > 0 ? 'warning' : 'success'),

            Stat::make(__('dashboard.widget.total_customers'), (string) $totalCustomers)
                ->description(__('dashboard.widget.customers_desc', ['new' => $newCustomers]))
                ->descriptionIcon('heroicon-o-users')
                ->color('info'),

            Stat::make(__('dashboard.widget.total_coupons'), (string) $totalCoupons)
                ->description(__('dashboard.widget.coupons_desc', ['active' => $activeCoupons]))
                ->descriptionIcon('heroicon-o-ticket')
                ->color('success'),

            Stat::make(__('dashboard.widget.total_reviews'), (string) $totalReviews)
                ->description(__('dashboard.widget.reviews_desc', ['unapproved' => $unapprovedReviews]))
                ->descriptionIcon('heroicon-o-star')
                ->color($unapprovedReviews > 0 ? 'warning' : 'success'),

            Stat::make(__('dashboard.widget.total_contacts'), (string) $totalContacts)
                ->description(__('dashboard.widget.contacts_desc', ['unread' => $unreadContacts]))
                ->descriptionIcon('heroicon-o-envelope')
                ->color($unreadContacts > 0 ? 'danger' : 'success'),

            Stat::make(__('dashboard.widget.active_carts'), (string) $activeCarts)
                ->description(__('dashboard.widget.carts_desc'))
                ->descriptionIcon('heroicon-o-shopping-cart')
                ->color('primary'),
        ];
    }
}
