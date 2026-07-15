<?php

namespace App\Filament\Widgets;

use App\Models\Blog;
use App\Models\Contact;
use App\Models\MessengerPageRegistry;
use App\Models\Tenant;
use App\Models\Theme;
use App\Models\WhatsAppNumberRegistry;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminKpis extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $from = Carbon::now()->subDays(30);
        $to = Carbon::now();

        $totalTenants = Tenant::count();
        $activeTenants = Tenant::active()->count();
        $newTenants = Tenant::whereBetween('created_at', [$from, $to])->count();

        $totalThemes = Theme::count();
        $activeThemes = Theme::where('is_active', true)->count();
        $featuredThemes = Theme::where('featured', true)->count();

        $totalBlogs = Blog::count();
        $activeBlogs = Blog::where('is_active', true)->count();
        $featuredBlogs = Blog::where('is_featured', true)->count();
        $totalViews = Blog::sum('views_count');

        $totalContacts = Contact::count();
        $unreadContacts = Contact::whereNull('read_at')->count();
        $newContacts = Contact::whereBetween('created_at', [$from, $to])->count();

        $totalWhatsapp = WhatsAppNumberRegistry::count();
        $activeWhatsapp = WhatsAppNumberRegistry::where('is_active', true)->count();

        $totalMessenger = MessengerPageRegistry::count();
        $activeMessenger = MessengerPageRegistry::where('is_active', true)->count();

        return [
            Stat::make(__('dashboard.widget.total_tenants'), (string) $totalTenants)
                ->description(__('dashboard.widget.tenants_desc', ['active' => $activeTenants, 'new' => $newTenants]))
                ->descriptionIcon('heroicon-o-building-storefront')
                ->color('success'),

            Stat::make(__('dashboard.widget.total_themes'), (string) $totalThemes)
                ->description(__('dashboard.widget.themes_desc', ['active' => $activeThemes, 'featured' => $featuredThemes]))
                ->descriptionIcon('heroicon-o-paint-brush')
                ->color('info'),

            Stat::make(__('dashboard.widget.total_blogs'), (string) $totalBlogs)
                ->description(__('dashboard.widget.blogs_desc', ['active' => $activeBlogs, 'featured' => $featuredBlogs, 'views' => number_format($totalViews)]))
                ->descriptionIcon('heroicon-o-document-text')
                ->color('primary'),

            Stat::make(__('dashboard.widget.total_contacts'), (string) $totalContacts)
                ->description(__('dashboard.widget.contacts_desc', ['unread' => $unreadContacts, 'new' => $newContacts]))
                ->descriptionIcon('heroicon-o-envelope')
                ->color($unreadContacts > 0 ? 'warning' : 'success'),

            Stat::make(__('dashboard.widget.whatsapp_numbers'), (string) $totalWhatsapp)
                ->description(__('dashboard.widget.whatsapp_desc', ['active' => $activeWhatsapp]))
                ->descriptionIcon('heroicon-o-chat-bubble-left-right')
                ->color('success'),

            Stat::make(__('dashboard.widget.messenger_pages'), (string) $totalMessenger)
                ->description(__('dashboard.widget.messenger_desc', ['active' => $activeMessenger]))
                ->descriptionIcon('heroicon-o-megaphone')
                ->color('info'),
        ];
    }
}
