<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Services\Money\PhpMoneyFormatter;
use App\Services\Money\SymbolStrategy;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function home()
    {
        $featuredProducts = Product::orderByDesc('stock_quantity')
            ->take(8)
            ->get();

        $formatter = new PhpMoneyFormatter(new SymbolStrategy());

        $banner = [
            'badge' => SiteSetting::getValue('hero_badge', 'いらっしゃいませ • WELCOME TO KOHAN MART'),
            'title' => SiteSetting::getValue('hero_title', 'Everyday Japanese comforts, reserved for your pickup.'),
            'subtitle' => SiteSetting::getValue('hero_subtitle', 'Browse authentic Japanese ramen, matcha drinks, snacks, and skincare from our Project 8 shelf. Lock your items online in real time and pay comfortably when you drop by.'),
            'image' => SiteSetting::getValue('hero_image', 'https://images.unsplash.com/photo-1528360983277-13d401cdc186?auto=format&fit=crop&w=900&q=80'),
        ];

        $settings = [
            'hero_badge' => $banner['badge'],
            'hero_title' => $banner['title'],
            'hero_subtitle' => $banner['subtitle'],
            'hero_image' => $banner['image'],
        ];

        return view('pages.home', compact('featuredProducts', 'formatter', 'banner', 'settings'));
    }

    public function index(Request $request)
    {
        $query = Product::query();

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category') && $request->category !== 'All') {
            $query->where('category', $request->category);
        }

        $products = $query->orderBy('name')->get();
        $categories = ['All', 'Foods', 'Drinks', 'Skincare', 'Other'];
        $formatter = new PhpMoneyFormatter(new SymbolStrategy());

        return view('store.index', compact('products', 'categories', 'formatter'));
    }

    public function about()
    {
        $about = [
            'heading' => SiteSetting::getValue('about_heading', 'A quiet corner of Japan in Quezon City.'),
            'body_1' => SiteSetting::getValue('about_body_1', 'Kohan Mart (湖畔マート) began as a neighborhood specialty shop in Project 8, Quezon City, dedicated to bringing everyday Japanese pantry essentials, bottled teas, seasonal snacks, and gentle skincare products to local residents.'),
            'body_2' => SiteSetting::getValue('about_body_2', 'Because imported Japanese goods arrive in curated small batches, popular items like Hakata tonkotsu ramen and Hada Labo lotions often sell out quickly. We built this online reservation system so neighbors can check live shelf availability before heading out and have their basket held safely at the counter.'),
        ];

        $settings = [
            'about_heading' => $about['heading'],
            'about_body_1' => $about['body_1'],
            'about_body_2' => $about['body_2'],
        ];

        return view('pages.about', compact('about', 'settings'));
    }

    public function contact()
    {
        $settings = [
            'contact_intro' => SiteSetting::getValue('contact_intro', 'Looking for a specific Japanese snack, skincare refill, or bulk pickup arrangement? Visit our Project 8 store or send us a message below.'),
            'contact_address' => SiteSetting::getValue('contact_address', '41 Insurance, Project 8, Quezon City, Metro Manila'),
            'contact_hours' => SiteSetting::getValue('contact_hours', 'Monday – Sunday, 9:00 AM to 8:00 PM'),
            'contact_phone_email' => SiteSetting::getValue('contact_phone_email', '+63 (02) 8555-0194 • hello@kohanmart.test'),
        ];

        return view('pages.contact', compact('settings'));
    }

    public function sendContact(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150'],
            'message' => ['required', 'string', 'max:1500'],
        ]);

        return back()->with('success', 'Thank you! Your message has been sent to our Project 8 store team.');
    }

    public function myOrders(Request $request)
    {
        $orders = Order::with('items.product')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        $formatter = new PhpMoneyFormatter(new SymbolStrategy());

        return view('store.orders', compact('orders', 'formatter'));
    }
}