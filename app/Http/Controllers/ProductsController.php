<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use phpDocumentor\Reflection\Type;

class ProductsController extends Controller
{
    public function getProducts(Request $request, $categoryId) {
        $projectId = $request->header('projectId');
        $data = [];

        $products = DB::table('products_by_projects')
            ->join('product_to_categories', 'products_by_projects.product_id', '=', 'product_to_categories.product_id')
            ->join('products', 'product_to_categories.product_id', '=', 'products.id')
            ->leftJoin('product_manufacturers', 'products.manufacturer_id', '=', 'product_manufacturers.id')
            ->leftJoin('product_series', 'products.series_id', '=', 'product_series.id')
            ->leftJoin('product_series_photos', 'product_series.id', '=', 'product_series_photos.id')
            ->leftJoin('photos', 'products.id', '=', 'photos.product_id')
            ->leftJoin('prices', 'products.id', '=', 'prices.product_id')
            ->where('products_by_projects.project_id', $projectId)
            ->where('product_to_categories.category_id', $categoryId)
            ->select('products.id', 'products.name as model', 'product_manufacturers.name as brand', 'product_manufacturers.logo as brand_logo', 'product_series.series_name_ru as series_name', 'product_series_photos.folder as series_picture_folder', 'product_series_photos.file_name as series_picture_file_name', 'product_series_photos.file_format as series_picture_format', 'photos.folder as product_picture_folder', 'photos.file_name as product_picture_file_name', 'photos.file_format as product_picture_format', 'prices.price', 'prices.setup_price')
            ->paginate(10);

        $productIds = $products->pluck('id');

        $characteristics = DB::table('product_characteristics')
            ->leftJoin('characteristics', 'product_characteristics.characteristic_id', '=', 'characteristics.id')
            ->leftJoin('characteristic_attributes', 'product_characteristics.attribute_id', '=', 'characteristic_attributes.id')
            ->whereIn('product_id',$productIds)
            ->where('product_characteristics.characteristic_id', 3)
            ->select('product_characteristics.product_id as id', 'characteristics.name_ru as characteristic_name_ru','characteristic_attributes.name_ru as characteristic_attribute_name')
            ->get()->toArray();

        $data['products_info']['total'] = $products->total();
        $data['products_info']['characteristics'] = $characteristics;
        $data['products'] = $products->items();

        return response()->json($data);
    }
}
