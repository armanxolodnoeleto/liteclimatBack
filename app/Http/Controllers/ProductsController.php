<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductsController extends Controller
{
    public function getProducts(Request $request) {
        $projectId = $request->header('projectid');
        $page = $request->page;
        $skip = ($page - 1) * 10;
        $projectId = 56;
        $products = DB::table('products_by_projects')
            ->join('products', 'products_by_projects.product_id', '=', 'products.id')
            ->leftJoin('product_manufacturers', 'products.manufacturer_id', '=', 'product_manufacturers.id')
            ->leftJoin('product_series', 'products.series_id', '=', 'product_series.id')
            ->leftJoin('product_series_photos', 'product_series.id', '=', 'product_series_photos.id')
            ->leftJoin('photos', 'products.id', '=', 'photos.product_id')
            ->leftJoin('prices', 'products.id', '=', 'prices.product_id')
            ->leftJoin('product_characteristics', 'products.id', '=', 'product_characteristics.product_id')
            ->leftJoin('characteristics', 'product_characteristics.characteristic_id', '=', 'characteristics.id')
            ->leftJoin('characteristic_attributes', 'product_characteristics.attribute_id', '=', 'characteristic_attributes.id')
            ->where('products_by_projects.project_id', $projectId)
            ->where('product_characteristics.characteristic_id', 3)
            ->where('product_series_photos.cover_photo', 1)
            ->where('photos.cover_photo', 1)
            ->select('products.id', 'products.name_ru as model', 'product_manufacturers.name_ru as brand', 'product_manufacturers.logo as brand_logo', 'product_series.series_name_ru as series_name', 'product_series_photos.folder as series_picture_folder', 'product_series_photos.file_name as series_picture_file_name', 'product_series_photos.file_format as series_picture_format', 'photos.folder as product_picture_folder', 'photos.file_name as product_picture_file_name', 'photos.file_format as product_picture_format', 'prices.price', 'prices.setup_price', 'characteristics.name_ru as characteristic_name_ru', 'characteristic_attributes.name_ru as characteristic_attribute_name')
            ->skip($skip)
            ->take(10)
            ->get();

        return response()->json($products);
    }
}
