<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoriesController extends Controller
{
    public function getCategories() {
        $productCategories = DB::table('product_categories')
            ->orderBy('product_categories_order', 'asc')
            ->select(['id', 'product_categories_name_ru', 'product_categories_parent_id', 'icon'])
            ->get();
        $categories = $this->makeCategoryTree($productCategories);

        return response()->json($categories);
    }

    private function makeCategoryTree($data, $parent = 0) {
        $category = [];
        $categories = [];
        foreach ($data as $item) {
            if ($item->product_categories_parent_id == $parent) {
                if ($parent === 0) {
                    $category['icon'] = $item->icon;
                }
                $category['name'] = $item->product_categories_name_ru;
                $category['id'] = $item->id;
                $category['subCategories'] = $this->makeCategoryTree($data, $item->id);
                if (empty($category['subCategories'])) {
                    unset($category['subCategories']);
                    if ($parent === 0) {
                        continue;
                    }
                }
                $categories[] = $category;
            }
        }
        return $categories;
    }
}
