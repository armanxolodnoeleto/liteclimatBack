<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            ->leftJoin('product_series_photos', 'product_series.id', '=', 'product_series_photos.series_id')
            ->leftJoin('photos', 'products.id', '=', 'photos.product_id')
            ->leftJoin('prices', 'products.id', '=', 'prices.product_id')
            ->where('products_by_projects.project_id', $projectId)
            ->where('product_to_categories.category_id', $categoryId)
            ->where('prices.status', 1)
            ->where('prices.price', '!=', 0)
            ->where('product_series_photos.cover_photo', '=', 1)
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

    public function getProduct(Request $request, $productId) {
        $projectId = $request->header('projectId');
        $data = [];
        $product = DB::table('products')
            ->leftJoin('prices', 'products.id', '=', 'prices.product_id')
            ->leftJoin('product_manufacturers', 'products.manufacturer_id', '=', 'product_manufacturers.id')
            ->leftJoin('product_manufacturer_certificates', 'product_manufacturers.id', '=', 'product_manufacturer_certificates.product_manufacturer_id')
            ->leftJoin('product_series', 'products.series_id', '=', 'product_series.id')
            ->leftJoin('product_series_photos', 'product_series.id', '=', 'product_series_photos.series_id')
            ->where('prices.project_id', $projectId)
            ->where('products.id', $productId)
            ->select('products.name as model', 'products.id as articule', 'products.description_ru as description', 'prices.market', 'prices.setup_price', 'prices.price', 'product_manufacturers.name as brand', 'product_manufacturers.logo as manufacturer_logo', 'product_manufacturer_certificates.folder as certificate_folder', 'product_manufacturer_certificates.file_name as certificate_file_name', 'product_manufacturer_certificates.file_format as certificate_file_format', 'product_series.series_name_ru as series_name', 'product_series.id as series_id')
            ->groupBy('products.id', 'products.name', 'prices.market', 'prices.setup_price', 'prices.price', 'product_manufacturers.name', 'product_manufacturers.logo', 'product_manufacturer_certificates.folder', 'product_manufacturer_certificates.file_name', 'product_manufacturer_certificates.file_format', 'product_series.series_name_ru', 'product_series_photos.folder', 'product_series_photos.file_format', 'products.description_ru', 'product_series.id')
            ->first();

        $characteristics = DB::table('product_characteristics')
            ->leftJoin('characteristics as ch1', 'product_characteristics.characteristic_id', '=', 'ch1.id')
            ->leftJoin('characteristics as ch2', 'ch1.parent_id', '=', 'ch2.id')
            ->leftJoin('characteristic_attributes', 'product_characteristics.attribute_id', '=', 'characteristic_attributes.id')
            ->where('product_id',$productId)
            ->select('product_characteristics.value as characteristic_value','ch1.name_ru as characteristic_name','characteristic_attributes.name_ru as characteristic_attribute_name', 'ch2.name_ru as title')
            ->get()
            ->toArray();

        $photos = DB::table('photos')
            ->where('product_id', $productId)
            ->get()
            ->toArray();

        if (empty($photos)) {
            $seriesId = $product->series_id;
            $photos = DB::table('product_series_photos')
                ->where('series_id', $seriesId)
                ->get()->toArray();
        }

        $data['product'] = $product;
        $data['characteristics'] = $characteristics;
        $data['photos'] = $photos;

        return response()->json($data);
    }

    public function getFilterData(Request $request, $categoryId) {
        $projectId = $request->header('projectId');
        $data = [];
        $manufacturerCountries = DB::table('products_by_projects')
            ->leftJoin('product_to_categories', 'products_by_projects.product_id', '=', 'product_to_categories.product_id')
            ->leftJoin('products', 'products_by_projects.product_id', '=', 'products.id')
            ->leftJoin('product_manufacturers', 'products.manufacturer_id', '=', 'product_manufacturers.id')
            ->leftJoin('product_characteristics', 'products.id', '=', 'product_characteristics.product_id')
            ->leftJoin('characteristic_attributes', 'characteristic_attributes.characteristic_id', '=', 'product_characteristics.characteristic_id')
            ->where('products_by_projects.project_id', $projectId)
            ->where('product_to_categories.category_id', $categoryId)
            ->where('characteristic_attributes.characteristic_id', 14)
            ->select('characteristic_attributes.name_ru', DB::raw('COUNT(*) as count'), 'product_manufacturers.logo', 'product_manufacturers.id')
            ->groupBy('characteristic_attributes.name_ru', 'product_manufacturers.id', 'product_manufacturers.logo')
            ->get();

        $characteristicAttributes = DB::table('product_categories')
            ->leftJoin('characteristic_to_categories', 'characteristic_to_categories.category_id', '=', 'product_categories.id')
            ->leftJoin('characteristic_attributes', 'characteristic_to_categories.characteristic_id', '=', 'characteristic_attributes.characteristic_id')
            ->leftJoin('characteristics', 'characteristic_attributes.characteristic_id', '=', 'characteristics.id')
            ->leftJoin('value_types', 'characteristics.value_type_id', '=', 'value_types.id')
            ->where('product_categories.project_id', 56)
            ->where('product_categories.id', 2)
            ->select('characteristic_attributes.name_ru', 'characteristic_attributes.id', 'characteristic_attributes.characteristic_id', 'characteristics.name_ru as title', 'value_types.name')
            ->groupBy('characteristic_attributes.name_ru', 'characteristic_attributes.id', 'characteristic_attributes.characteristic_id', 'characteristics.name_ru', 'value_types.name')
            ->get();

        $data['manufacturerCountries'] = $manufacturerCountries;
        $data['characteristicAttributes'] = $characteristicAttributes;

        return response()->json($data);
    }

}
