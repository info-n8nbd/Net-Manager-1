<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HelpController extends Controller
{
    public function index(Request $request)
    {
        $helpData = config('help', []);
        $search = $request->query('search', '');

        if ($search) {
            $helpData = collect($helpData)->filter(function ($module) use ($search) {
                $searchLower = mb_strtolower($search);
                if (mb_strpos(mb_strtolower($module['title']), $searchLower) !== false) {
                    return true;
                }
                if (mb_strpos(mb_strtolower($module['description'] ?? ''), $searchLower) !== false) {
                    return true;
                }
                if (mb_strpos(mb_strtolower($module['guide'] ?? ''), $searchLower) !== false) {
                    return true;
                }
                foreach ($module['submenus'] ?? [] as $sub) {
                    if (mb_strpos(mb_strtolower($sub['title'] ?? ''), $searchLower) !== false) {
                        return true;
                    }
                    if (mb_strpos(mb_strtolower($sub['content'] ?? ''), $searchLower) !== false) {
                        return true;
                    }
                }
                return false;
            })->toArray();
        }

        return view('admin.help.index', compact('helpData', 'search'));
    }

    public function contextual(Request $request)
    {
        $routeName = $request->query('route', '');
        $helpData = config('help', []);

        foreach ($helpData as $moduleKey => $module) {
            foreach ($module['submenus'] ?? [] as $route => $submenu) {
                if ($route === $routeName) {
                    return response()->json([
                        'found' => true,
                        'module' => $module['title'],
                        'module_key' => $moduleKey,
                        'icon' => $module['icon'],
                        'title' => $submenu['title'],
                        'content' => $submenu['content'],
                    ]);
                }
            }

            if (($module['route'] ?? '') === $routeName) {
                return response()->json([
                    'found' => true,
                    'module' => $module['title'],
                    'module_key' => $moduleKey,
                    'icon' => $module['icon'],
                    'title' => $module['title'],
                    'content' => $module['guide'] ?? $module['description'],
                ]);
            }
        }

        return response()->json([
            'found' => false,
            'message' => 'No help information found for this page.',
        ]);
    }
}
