<?php

namespace App\Http\Controllers;

class BeyondController extends Controller
{
    public function home()
    {
        return view('beyond.home');
    }

    public function subscriptions()
    {
        return redirect()->route('cloud.register');
    }

    public function about()
    {
        return view('beyond.about', [
            'leaders' => \App\Leader::published()->ordered()->get(),
        ]);
    }

    public function services()
    {
        return view('beyond.services', [
            'catalog' => \App\Support\ServiceCatalog::services(),
        ]);
    }

    public function serviceShow($service, $category = null)
    {
        if (! \App\Support\ServiceCatalog::exists($service)) {
            abort(404);
        }
        $meta = \App\Support\ServiceCatalog::services()[$service];
        if (! empty($meta['reservation'])) {
            return redirect('/chill-on-the-hill');
        }
        $categories = \App\Support\ServiceCatalog::categories($service);
        if ($category !== null && ! $categories->firstWhere('id', (int) $category)) {
            abort(404);
        }

        return view('beyond.service-show', [
            'service' => $service,
            'meta' => $meta,
            'categories' => $categories,
            'current' => $category ? $categories->firstWhere('id', (int) $category) : null,
            'items' => \App\Support\ServiceCatalog::products($service, $category),
            'tables' => \Illuminate\Support\Facades\Schema::hasTable('restaurant_tables')
                ? \App\RestaurantTable::orderBy('name')->get()
                : collect(),
            'token' => \Illuminate\Support\Str::random(40),
        ]);
    }

    public function projects()
    {
        return view('beyond.projects', [
            'projects' => [
                [
                    'url' => 'https://www.tiktok.com/@tefurolandmbole/video/7495818139272301829',
                    'title' => 'Project Highlight: Professional Installation',
                ],
                [
                    'url' => 'https://www.tiktok.com/@tefurolandmbole/video/7493245944540974341',
                    'title' => 'Advanced Networking Setup',
                ],
                [
                    'url' => 'https://www.tiktok.com/@tefurolandmbole/video/7492891748327361797',
                    'title' => 'Audio-Visual Excellence',
                ],
            ],
        ]);
    }

    public function gallery()
    {
        return view('beyond.gallery', [
            'items' => \App\GalleryItem::published()->ordered()->get(),
        ]);
    }

    public function contact()
    {
        return redirect(url('/about') . '#contact', 301);
    }

    public function events()
    {
        return view('beyond.events', ['events' => []]);
    }
}
