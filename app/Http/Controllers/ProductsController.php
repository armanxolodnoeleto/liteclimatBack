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

        $query = DB::table('products_by_projects')
            ->leftJoin('product_to_categories', 'products_by_projects.product_id', '=', 'product_to_categories.product_id')
            ->leftJoin('products', 'products_by_projects.product_id', '=', 'products.id')
            ->leftJoin('product_manufacturers', 'products.manufacturer_id', '=', 'product_manufacturers.id')
            ->leftJoin('product_characteristics', 'products.id', '=', 'product_characteristics.product_id')
            ->leftJoin('characteristic_attributes', 'characteristic_attributes.characteristic_id', '=', 'product_characteristics.characteristic_id')
            ->where('products_by_projects.project_id', $projectId)
            ->where('product_to_categories.category_id', $categoryId);

        $manufacturerQuery = clone $query;
        $manufacturerCountryQuery = clone $query;
        $brandsQuery = clone $query;
        $servicedAreaQuery = clone $query;
        $energyClassQuery = clone $query;
        $coolingQuery = clone $query;

        $manufacturers = $manufacturerQuery
            ->where('characteristic_attributes.characteristic_id', 14)
            ->select('characteristic_attributes.name_ru', DB::raw('COUNT(*) as count'), 'product_manufacturers.logo', 'product_manufacturers.id')
            ->groupBy('characteristic_attributes.name_ru', 'product_manufacturers.id', 'product_manufacturers.logo')
            ->get();

        $manufacturerCountry = $manufacturerCountryQuery
            ->where('characteristic_attributes.characteristic_id', 14)
            ->select('characteristic_attributes.name_ru', 'characteristic_attributes.id')
            ->groupBy('characteristic_attributes.name_ru', 'characteristic_attributes.id')
            ->get();

        $brandsCountry = $brandsQuery
            ->where('characteristic_attributes.characteristic_id', 15)
            ->select('characteristic_attributes.name_ru', 'characteristic_attributes.id')
            ->groupBy('characteristic_attributes.name_ru', 'characteristic_attributes.id')
            ->get();

        $servicedArea = $servicedAreaQuery
            ->where('characteristic_attributes.characteristic_id', 3)
            ->select('characteristic_attributes.name_ru', 'characteristic_attributes.id')
            ->groupBy('characteristic_attributes.name_ru', 'characteristic_attributes.id')
            ->get();

        $energyClass = $energyClassQuery
            ->where('characteristic_attributes.characteristic_id', 11)
            ->select('characteristic_attributes.name_ru', 'characteristic_attributes.id')
            ->groupBy('characteristic_attributes.name_ru', 'characteristic_attributes.id')
            ->get();

        $cooling = $coolingQuery
            ->where('characteristic_attributes.characteristic_id', 10)
            ->select('characteristic_attributes.name_ru', 'characteristic_attributes.id')
            ->groupBy('characteristic_attributes.name_ru', 'characteristic_attributes.id')
            ->get();

        $data['manufacturers'] = $manufacturers;
        $data['manufacturerCountry'] = $manufacturerCountry;
        $data['brandsCountry'] = $brandsCountry;
        $data['servicedArea'] = $servicedArea;
        $data['energyClass'] = $energyClass;
        $data['cooling'] = $cooling;

        return response()->json($data);

    }
}
