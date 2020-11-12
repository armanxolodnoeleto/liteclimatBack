<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoriesController extends Controller
{
    protected $projectId = 56;

    public function getCategories() {
        $productCategories = DB::table('product_categories')->orderBy('product_categories_order', 'desc')->where('project_id', $this->projectId)->select('*')->get()->toArray();
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
                $categories[] = $category;
            }
        }
        return $categories;
    }
}
