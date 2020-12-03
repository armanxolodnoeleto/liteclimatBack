<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoriesController extends Controller
{
    public function getCategories(Request $request) {
//        $projectId = $request->header('projectId');
        $productCategories = DB::table('product_categories')
            ->orderBy('product_categories_order', 'desc')
//            ->where('project_id', $projectId)
            ->select(['id', 'product_categories_name_ru', 'product_categories_parent_id', 'icon'])->get()->toArray();
        $categories = $this->getSubCategory($productCategories);
        return response()->json($categories);
    }

    public function getSubCategory($data, $parent = 0) {
        $category = [];
        $categories = [];
        for ($i = 0; $i < count($data); $i++) {
            if ($data[$i]->product_categories_parent_id == $parent) {
                if ($parent === 0) {
                    $category['icon'] = $data[$i]->icon;
                }
                $category['name'] = $data[$i]->product_categories_name_ru;
                $category['id'] = $data[$i]->id;
                $category['subCategories'] = $this->getSubCategory($data, $data[$i]->id);
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
