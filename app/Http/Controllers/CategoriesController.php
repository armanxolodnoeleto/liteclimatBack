<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoriesController extends Controller
{
    public function getCategories() {
        $projectId = 56;
        $productCategories = DB::table('product_categories')->orderBy('product_categories_order', 'desc')->where('project_id', $projectId)->select('*')->get()->toArray();
        $data = $this->getSubCategory($productCategories);
        return response()->json($data);
    }

    public function getSubCategory($data, $parent = 0) {
        $category = [];
        $categories = [];
        for ($i = 0; $i < count($data); $i++) {
            if ($data[$i]->product_categories_parent_id == $parent) {
                $categories['name'] = $data[$i]->product_categories_name_ru;
                $categories['id'] = $data[$i]->id;
                $categories['sub'] = $this->getSubCategory($data, $data[$i]->id);
                $category[] = $categories;
            }
        }
        return $category;
    }
}
