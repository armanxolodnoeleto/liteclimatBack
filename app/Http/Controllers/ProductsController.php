<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductsController extends Controller
{
    public function getProducts(Request $request, $categoryId) {
        $projectId = $request->header('projectId');
        $data = [];
        $query = DB::table('prices')
            ->leftJoin('product_to_categories', 'prices.product_id', '=', 'product_to_categories.product_id')
            ->leftJoin('products', 'product_to_categories.product_id', '=', 'products.id')
            ->leftJoin('product_manufacturers', 'products.manufacturer_id', '=', 'product_manufacturers.id')
            ->join('product_series', 'products.series_id', '=', 'product_series.id', 'left outer')
            ->join('product_series_photos', 'product_series.id', '=', 'product_series_photos.series_id', 'left outer')
            ->leftJoin('photos', 'products.id', '=', 'photos.product_id');

        $filterData = $request->except('manufacturerCountries');
        $issetProductCharacteristic = false;
        if ($request->has('checkboxes')) {
            $query = $query->leftJoin('product_characteristics', 'prices.product_id', '=', 'product_characteristics.product_id');
            $issetProductCharacteristic = true;
        }

        $query = $query->where('prices.project_id', $projectId)
            ->where('product_to_categories.category_id', $categoryId)
            ->where('prices.status', 1)
//            ->where('product_series_photos.cover_photo', '=', 1)
            ->where('prices.price', '!=', 0);

//        return $query->where('products.id', 5497)->get();

        if ($request->has('manufacturerCountries')) {
            if (!$issetProductCharacteristic) {
                $query = $query->leftJoin('product_characteristics', 'prices.product_id', '=', 'product_characteristics.product_id');
                $issetProductCharacteristic = true;
            }
            $query = $query->whereIn('product_manufacturers.id', $request->manufacturerCountries);
            $query = $query->where('product_characteristics.characteristic_id', '=', 14);
        }

        if ($request->has('fromTo')) {
            $fromTo = $filterData['fromTo'];
            if (isset($fromTo['price'])) {
                $filterArray = $fromTo;
                unset($filterArray['price']);
                if (count($filterArray) > 0 && !$issetProductCharacteristic) {
                    $query = $query->leftJoin('product_characteristics', 'prices.product_id', '=', 'product_characteristics.product_id');
                }
                $from = $fromTo['price'][0];
                $to = $fromTo['price'][1];
                $column = 'prices.price';
                $query = $this->getModeFilter($query, $from, $to, $column);
            }else {
                if (count($fromTo) > 0 && !$issetProductCharacteristic) {
                    $query = $query->leftJoin('product_characteristics', 'prices.product_id', '=', 'product_characteristics.product_id');
                }
            }


            $query = $query->where(function ($q) use ($fromTo) {
                $column = 'product_characteristics.value';
                if (isset($fromTo['price'])) {
                    unset($fromTo['price']);
                }
                foreach ($fromTo as $key => $item) {
                    $from = $item[0];
                    $to = $item[1];
                    $this->getModeFilter($q, $from, $to, $column, $key);
                }
            });
        }

        if ($request->has('checkboxes')) {
            $checkboxes = $filterData['checkboxes'];
            if (!empty($checkboxes)) {
                $query = $query->where(function ($q) use ($checkboxes) {
                    foreach ($checkboxes as $key => $item) {
                        $q->where('product_characteristics.characteristic_id', $key)->whereIn('product_characteristics.attribute_id', $item);
                    }
                });
            }
        }

        $query = $query->select('products.id', 'products.name as model', 'product_manufacturers.name as brand', 'product_manufacturers.logo as brand_logo', 'product_series.series_name_ru as series_name', DB::raw('CONCAT("[", GROUP_CONCAT(JSON_OBJECT( "cover_photo", product_series_photos.cover_photo,"series_picture_folder",  product_series_photos.folder, "series_picture_file_name", product_series_photos.file_name, "series_picture_format", product_series_photos.file_format)), "]") as cover_photo'), 'photos.folder as product_picture_folder','photos.file_name as product_picture_file_name', 'photos.file_format as product_picture_format', 'prices.price', 'prices.setup_price')
            ->groupBy('products.id', 'products.name', 'product_manufacturers.name', 'product_manufacturers.logo', 'product_series.series_name_ru', 'photos.folder','photos.file_name', 'photos.file_format', 'prices.price', 'prices.setup_price');
//            ->distinct();

        if ($request->has('orderBy')) {
            $query->orderBy('prices.price', $request->orderBy);
        }

        $products = $query->paginate(10);

        $productIds = $products->pluck('id');

        $characteristics = DB::table('product_characteristics')
            ->leftJoin('characteristics', 'product_characteristics.characteristic_id', '=', 'characteristics.id')
            ->leftJoin('characteristic_attributes', 'product_characteristics.attribute_id', '=', 'characteristic_attributes.id')
            ->whereIn('product_id', $productIds)
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

        $manufacturerCountries = DB::table('prices')
            ->leftJoin('product_to_categories', 'prices.product_id', '=', 'product_to_categories.product_id')
            ->leftJoin('products', 'product_to_categories.product_id', '=', 'products.id')
            ->leftJoin('product_manufacturers', 'products.manufacturer_id', '=', 'product_manufacturers.id')
            ->leftJoin('product_series', 'products.series_id', '=', 'product_series.id')
            ->leftJoin('product_series_photos', 'product_series.id', '=', 'product_series_photos.series_id')

            ->leftJoin('product_characteristics', 'prices.product_id', '=', 'product_characteristics.product_id')
            ->leftJoin('characteristic_attributes', 'product_characteristics.characteristic_id', '=', 'characteristic_attributes.characteristic_id')

            ->where('characteristic_attributes.characteristic_id', 14)
            ->where('prices.project_id', $projectId)
            ->where('product_to_categories.category_id', $categoryId)
            ->where('prices.status', 1)
            ->where('product_series_photos.cover_photo', '=', 1)
            ->where('prices.price', '!=', 0)

            ->select('characteristic_attributes.name_ru', DB::raw('COUNT(prices.product_id) as count'), 'product_manufacturers.logo', 'product_manufacturers.id')
            ->groupBy('characteristic_attributes.name_ru', 'product_manufacturers.id', 'product_manufacturers.logo')
            ->get();

        $characteristicAttributes = DB::table('characteristic_to_categories')
            ->leftJoin('characteristic_attributes', 'characteristic_to_categories.characteristic_id', '=', 'characteristic_attributes.characteristic_id')
            ->leftJoin('characteristics', 'characteristic_attributes.characteristic_id', '=', 'characteristics.id')
            ->leftJoin('value_types', 'characteristics.value_type_id', '=', 'value_types.id')
//            ->where('product_categories.project_id', $projectId)
            ->where('characteristic_to_categories.category_id', $categoryId)
            ->select('characteristic_attributes.name_ru', 'characteristic_attributes.id', 'characteristic_attributes.characteristic_id', 'characteristics.name_ru as title', 'value_types.name')
            ->groupBy('characteristic_attributes.name_ru', 'characteristic_attributes.id', 'characteristic_attributes.characteristic_id', 'characteristics.name_ru', 'value_types.name')
            ->get();

        $textFilters = DB::table('characteristic_to_categories')
            ->leftJoin('product_characteristics', 'characteristic_to_categories.characteristic_id', '=', 'product_characteristics.characteristic_id')
            ->leftJoin('characteristics', 'product_characteristics.characteristic_id', '=', 'characteristics.id')
            ->where('characteristic_to_categories.category_id', $categoryId)
            ->whereIn('product_characteristics.characteristic_id', [1, 2, 4, 5])
            ->where('product_characteristics.attribute_id', null)
            ->select('characteristics.name_ru as title', 'characteristics.id')
            ->groupBy('characteristics.name_ru', 'characteristics.id')
            ->get();


        $data['manufacturerCountries'] = $manufacturerCountries;
        $data['characteristicAttributes'] = $characteristicAttributes;
        $data['textFilters'] = $textFilters;

        return response()->json($data);
    }

    public function searchProduct(Request $request) {
        $projectId = $request->header('projectId');
        $searchBy = $request->search;

        $searchableColumns = ['products.name', 'product_manufacturers.name', 'product_series.series_name_ru'];

        if ($searchBy) {
            $searchResponse = DB::table('prices')
                ->leftJoin('products', 'prices.product_id', '=', 'products.id')
                ->leftJoin('product_manufacturers', 'products.manufacturer_id', '=', 'product_manufacturers.id')
                ->leftJoin('product_series', 'products.series_id', '=', 'product_series.id')
                ->leftJoin('product_series_photos', 'product_series.id', '=', 'product_series_photos.series_id')
                ->leftJoin('photos', 'products.id', '=', 'photos.product_id')
                ->where('prices.project_id', $projectId)
                ->where('prices.status', 1)
                ->where('product_series_photos.cover_photo',1)
                ->where('prices.price', '!=', 0)
                ->where(function($q) use ($searchableColumns, $searchBy) {
                    foreach ($searchableColumns as $searchableColumn) {
                        $q->orWhere($searchableColumn, 'LIKE', "%{$searchBy}%");
                    }
                })
                ->select('products.id', 'products.name as model', 'product_manufacturers.name as brand', 'product_manufacturers.logo as brand_logo', 'product_series.series_name_ru as series_name', 'product_series_photos.folder as series_picture_folder', 'product_series_photos.file_name as series_picture_file_name','product_series_photos.file_format as series_picture_format','photos.folder as product_picture_folder','photos.file_name as product_picture_file_name', 'photos.file_format as product_picture_format', 'prices.price')
                ->groupBy('products.id', 'products.name', 'product_manufacturers.name', 'product_manufacturers.logo', 'product_series.series_name_ru', 'product_series_photos.folder', 'product_series_photos.file_name','product_series_photos.file_format','photos.folder','photos.file_name', 'photos.file_format', 'prices.price')
                ->paginate(15);

            $data['searchResponse'] = $searchResponse->items();
            $data['total'] = $searchResponse->total();
            return response()->json($data);
        }
        return [];
    }

    private function getModeFilter($query, $from, $to, $column, $characteristicId = 0) {
        if (is_null($from) && !is_null($to)) {
            $query = $query->where($column, '<=', $to);
        }elseif (!is_null($from) && is_null($to)) {
            $query = $query->where($column, '>=', $from);
        }elseif (!is_null($from) && !is_null($to)) {
            $query = $query->whereBetween($column, [$from, $to]);
        }
        if ($characteristicId) {
            $query = $query->where('product_characteristics.characteristic_id', '=', $characteristicId);
        }
        return $query;
    }

    public function newProducts(Request $request) {
        $projectId = $request->header('projectId');
        $newProducts = DB::table('prices')
            ->leftJoin('products', 'prices.product_id', '=', 'products.id')
            ->leftJoin('product_manufacturers', 'products.manufacturer_id', '=', 'product_manufacturers.id')
            ->leftJoin('product_series', 'products.series_id', '=', 'product_series.id')
            ->leftJoin('product_series_photos', 'product_series.id', '=', 'product_series_photos.series_id')
            ->leftJoin('photos', 'products.id', '=', 'photos.product_id')
            ->where('prices.project_id', $projectId)
            ->where('prices.status', 1)
            ->where('product_series_photos.cover_photo',1)
            ->where('prices.price', '!=', 0)
            ->select('products.id', 'products.name as model', 'product_manufacturers.name as brand', 'product_manufacturers.logo as brand_logo', 'product_series.series_name_ru as series_name', 'product_series_photos.folder as series_picture_folder', 'product_series_photos.file_name as series_picture_file_name','product_series_photos.file_format as series_picture_format','photos.folder as product_picture_folder','photos.file_name as product_picture_file_name', 'photos.file_format as product_picture_format', 'prices.price')
            ->groupBy('products.id', 'products.name', 'product_manufacturers.name', 'product_manufacturers.logo', 'product_series.series_name_ru', 'product_series_photos.folder', 'product_series_photos.file_name','product_series_photos.file_format','photos.folder','photos.file_name', 'photos.file_format', 'prices.price')
            ->orderBy('prices.product_id', 'DESC')
            ->take(6)
            ->get();

        return response()->json($newProducts);
    }

}
