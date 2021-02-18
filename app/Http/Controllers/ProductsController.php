<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProductsController extends Controller
{
    public function getProducts(Request $request, $categoryId = null) {
        $projectId = $request->header('projectId');
        $data = [];
        $query = DB::table('prices')
            ->join('product_to_categories', 'prices.product_id', '=', 'product_to_categories.product_id', 'inner')
            ->leftJoin('products', 'product_to_categories.product_id', '=', 'products.id')
            ->leftJoin('product_manufacturers', 'products.manufacturer_id', '=', 'product_manufacturers.id')
            ->leftJoin('product_series', 'products.series_id', '=', 'product_series.id')
            ->leftJoin('product_series_photos', 'product_series.id', '=', 'product_series_photos.series_id')
            ->leftJoin('photos', 'products.id', '=', 'photos.product_id');

        $filterData = $request->except('manufacturerCountries', 'utm_campaign', 'utm_source', 'utm_medium', 'utm_content', 'utm_term', 'yclid', '/api/getProducts');
        $issetProductCharacteristic = false;
        if ($request->has('checkboxes')) {
            $query = $query->leftJoin('product_characteristics', 'prices.product_id', '=', 'product_characteristics.product_id');
            $issetProductCharacteristic = true;
        }

        $query = $query->where('prices.project_id', $projectId)
            ->where('prices.status', 1)
            ->where('prices.price', '!=', 0);
        if (!is_null($categoryId)) {
            $query->where('product_to_categories.category_id', $categoryId);
        }

        if ($request->has('manufacturerCountries')) {
            if (!$issetProductCharacteristic) {
                $query = $query->leftJoin('product_characteristics', 'prices.product_id', '=', 'product_characteristics.product_id');
                $issetProductCharacteristic = true;
            }
            $query = $query->whereIn('product_manufacturers.id', $request->manufacturerCountries);
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
                $valueProducts = [];
                if (count($fromTo) > 0) {
                    $valueProducts = $this->valuesProducts($fromTo);
                }
                $query = $query->whereIn('product_characteristics.product_id', $valueProducts);
            }
        }

        if ($request->has('checkboxes')) {
            $checkboxes = $filterData['checkboxes'];
            $checkboxProducts = [];
            if (count($checkboxes) > 0) {
                $checkboxProducts = $this->checkboxesProducts($checkboxes);
            }
            $query = $query->whereIn('products.id', $checkboxProducts);
        }

        $query = $query->select('products.id', 'products.manufacturer_id', 'products.name as model', 'product_manufacturers.name as brand', 'product_manufacturers.logo as brand_logo', 'product_manufacturers.warranty', 'product_series.series_name_ru as series_name', DB::raw('CONCAT("[", GROUP_CONCAT(JSON_OBJECT( "cover_photo", product_series_photos.cover_photo,"series_picture_folder",  product_series_photos.folder, "series_picture_file_name", product_series_photos.file_name, "series_picture_format", product_series_photos.file_format)), "]") as cover_photo'), 'photos.folder as product_picture_folder','photos.file_name as product_picture_file_name', 'photos.file_format as product_picture_format', 'prices.price', 'prices.setup_price', 'prices.has_chat', 'prices.has_sale', 'prices.price_with_setup', 'prices.price_without_setup', 'prices.chat_with_percent', 'prices.chat_without_percent', 'products.available')
            ->groupBy('products.id');
        if ($request->has('orderBy')) {
            $query->orderBy('prices.price', $request->orderBy);
        }else {
            $query->orderBy('prices.price', 'ASC');
        }

        $productIds = $query->pluck('id');
        $products = $query->paginate(12);
        $productPageIds = $query->pluck('id');

        $characteristics = DB::table('product_characteristics')
            ->leftJoin('characteristics', 'product_characteristics.characteristic_id', '=', 'characteristics.id')
            ->leftJoin('characteristic_attributes', 'product_characteristics.attribute_id', '=', 'characteristic_attributes.id')
            ->whereIn('product_id', $productPageIds)
            ->where('product_characteristics.characteristic_id', 3)
            ->select('product_characteristics.product_id as id', 'characteristics.name_ru as characteristic_name_ru', 'characteristic_attributes.name_ru as characteristic_attribute_name')
            ->orderBy('characteristic_attributes.id', 'ASC')
            ->get()->toArray();

        $data['products_info']['total'] = $products->total();
        $data['products_info']['characteristics'] = $characteristics;
        $data['products'] = $products->items();
        $data['filters'] = $this->getFilters($productIds, $categoryId, $projectId);

        return response()->json($data);
    }

    private function valuesProducts($fromTo) {
        $column = 'product_characteristics.value';
        $productIds = [];
        $b = 0;
        foreach ($fromTo as $key => $item) {
            $from = $item[0];
            $to = $item[1];
            if (is_null($from) && !is_null($to)) {
                if($b == 0){
                    $b++;
                    $productIds = DB::table('product_characteristics')->where('characteristic_id', $key)->where($column, '<=', $to)->groupBy('product_id')->pluck('product_id');
                }
                $productIds = DB::table('product_characteristics')->where('characteristic_id', $key)->where($column, '<=', $to)->whereIn('product_id', $productIds)->pluck('product_id');
            }elseif (!is_null($from) && is_null($to)) {
                if($b == 0){
                    $b++;
                    $productIds = DB::table('product_characteristics')->where('characteristic_id', $key)->where($column, '>=', $from)->groupBy('product_id')->pluck('product_id');
                }
                $productIds = DB::table('product_characteristics')->where('characteristic_id', $key)->where($column, '>=', $from)->whereIn('product_id', $productIds)->pluck('product_id');
            }elseif (!is_null($from) && !is_null($to)) {
                if($b == 0){
                    $b++;
                    $productIds = DB::table('product_characteristics')->where('characteristic_id', $key)->whereBetween($column, [$from, $to])->groupBy('product_id')->pluck('product_id');
                }
                $productIds = DB::table('product_characteristics')->where('characteristic_id', $key)->whereBetween($column, [$from, $to])->whereIn('product_id', $productIds)->pluck('product_id');
            }
        }
        return $productIds;
    }

    private function checkboxesProducts($checkboxes, $filterUpdate = false) {
        $a = 0;
        $productIds = [];
        foreach ($checkboxes as $key => $items) {
            $issetFilter = DB::table('product_characteristics')->where('characteristic_id', $key)->exists();
            if ($issetFilter) {
                if (!in_array(null, $items)) {
                    if ($a == 0) {
                        $a++;
                        if ($filterUpdate) {
                            $productIds = DB::table('product_characteristics')
                                ->where('characteristic_id', $key)
                                ->whereIn('attribute_id', $items)
                                ->groupBy('product_id')
                                ->pluck('product_id');
                        }else {
                            $productIds = DB::table('product_characteristics')
                                ->whereIn('attribute_id', $items)
                                ->groupBy('product_id')
                                ->pluck('product_id');
                        }
                    }
                    if (count($productIds) > 0) {
                        $productIds = DB::table('product_characteristics')
                            ->whereIn('attribute_id', $items)
                            ->whereIn('product_id', $productIds)
                            ->pluck('product_id');
                    }
                }
            }
        }
        return $productIds;
    }

    public function getProduct(Request $request, $productId) {
        $projectId = $request->header('projectId');
        $characteristicId = 3;
        $data = [];

        $product = DB::table('products')
            ->join('prices', 'products.id', '=', 'prices.product_id', "inner")
            ->leftJoin('product_manufacturers', 'products.manufacturer_id', '=', 'product_manufacturers.id')
            ->leftJoin('product_series', 'products.series_id', '=', 'product_series.id')
            ->leftJoin('product_categories', 'product_series.category_id', '=', 'product_categories.id')
            ->leftJoin('product_series_photos', 'product_series.id', '=', 'product_series_photos.series_id')
            ->where('prices.project_id', $projectId)
            ->where('products.id', $productId)
            ->select('products.name as model', 'products.id as articule', 'prices.market', 'prices.setup_price', 'prices.price', 'prices.has_chat', 'prices.has_sale', 'prices.price_with_setup', 'prices.price_without_setup', 'prices.chat_with_percent', 'prices.chat_without_percent', 'product_manufacturers.name as brand', 'product_manufacturers.id as manufacturer_id', 'product_manufacturers.logo as manufacturer_logo', 'product_manufacturers.warranty', 'product_series.series_name_ru as series_name', 'product_series.id as series_id', 'product_series.series_description_ru as description', 'product_categories.product_categories_name_ru as category_name', 'product_categories.id as category_id', 'products.available')
            ->groupBy('products.id')
            ->first();

        $defaultSetup = $this->defaultSetup($productId, $projectId, $product->category_id);

        $certificate = [];
        if (isset($product->manufacturer_id)) {
            $manufacturerCertificate = $product->manufacturer_id;

            $certificate = DB::table('product_manufacturer_certificates')
                ->where('project_id', $projectId)
                ->where('product_manufacturer_id', $manufacturerCertificate)
                ->select('file_name as certificate_file_name')
                ->first();
        }

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

//        Cache::flush();
        if (!Cache::has('filter_'. $productId)) {
            $filter = $this->getProductFilter($characteristicId, $productId);
            Cache::put('filter_'. $productId, $filter, now()->addMinutes(10));
        }else {
            $filter = Cache::get('filter_'. $productId);
        }

        $designerConditioners = [294,390,303,427,377,426,315,316,436,18,917,1591,449,450,422,1050,241,51,227,1608,169,189,64,70,958,961,1638,1672,1671,92,501,1126,497,498,166,292,108,755,529,1205,538,1562,1567,170,535,19,311,502,334,212,780,548,781,135,1173,254,260,1318,1331,1330,477];

        $prepayment = '';
        if ($product->available == 0) {
            $prepayment = 'Предоплата 100%';
        }elseif ($product->price > 40000 || in_array($product->series_id, $designerConditioners)) {
            $prepayment = 'Предоплата 3%';
        }else {
            $prepayment = 'Заказ от 40 000 руб. 3% предоплата';
        }

        $data['product'] = $product;
        $data['default_setup'] = $defaultSetup - $product->setup_price;
        $data['prepayment'] = $prepayment;
        $data['characteristics'] = $characteristics;
        $data['photos'] = $photos;
        $data['certificate'] = $certificate;
        $data['filter'] = $filter;

        return response()->json($data);
    }

    private function defaultSetup($productId, $projectId, $categoryId){
        $coolingPower = DB::table('product_characteristics')
            ->where(['characteristic_id' => 1, 'product_id' => $productId])
            ->select('value')
            ->first();
        $res = 0;
        if ($coolingPower) {
            $coolingPower = $coolingPower->value;
            if($categoryId == 4 || $categoryId == 5){
                $room_count = 0;
                $margin = $this->getMargin($projectId, $coolingPower);
                $attribute = DB::table('product_characteristics')
                    ->where(['characteristic_id' => 63, 'product_id' => $productId])
                    ->select('attribute_id')
                    ->first();
                $attribute = $attribute->attribute_id;
                if($attribute == 218){
                    $room_count = 2;
                }else if($attribute == 219){
                    $room_count = 3;
                }else if($attribute == 220){
                    $room_count = 4;
                }else if($attribute == 221){
                    $room_count = 5;
                }else if($attribute == 222){
                    $room_count = 6;
                }else if($attribute == 223){
                    $room_count = 7;
                }else if($attribute == 224){
                    $room_count = 8;
                }else if($attribute == 225){
                    $room_count = 9;
                }
                $res = $room_count * $margin;
            }else if($categoryId == 8 || $categoryId == 9 || $categoryId == 10 || $categoryId == 11){
                $number = ltrim($coolingPower,"0");
                $number = (float)$number;
                if ($number<4.5){
                    $res = 11400;
                }else if($number>=4.5 and $number<6){
                    $res = 12400;
                }else if($number>=6 and $number<8){
                    $res = 13400;
                }else if($number>=8 and $number<11){
                    $res = 15400;
                }else if($number>=11 and $number<16){
                    $res = 16400;
                }else if($number>=16 and $number<21){
                    $res = 17400;
                }else if($number>=21 and $number<26){
                    $res = 24900;
                }else if($number>=26 and $number<31){
                    $res = 29900;
                }else if($number>=31 and $number<36){
                    $res = 34900;
                }else if($number>=36 and $number<41){
                    $res = 39900;
                }
            }else{
                $number = ltrim($coolingPower,"0");
                $number = (float)$number;
                if ($number<1.8){
                    $res = 7900;
                }else if($number>=1.8 and $number<2.3){
                    $res = 7900;
                }else if($number>=2.3 and $number<3.1){
                    $res = 7900;
                }else if($number>=3.1 and $number<4.9){
                    $res = 8900;
                }else if($number>=4.9 and $number<6.7){
                    $res = 9900;
                }else if($number>=6.7 and $number<7.8){
                    $res = 10900;
                }else if($number>=7.8 and $number<9.8){
                    $res = 11900;
                }else if($number>=9.8){
                    $res = 12900;
                }
            }
        }

        return $res;
    }

    private function getMargin($projectId, $coolingPower){
        $margins = DB::table('margins')->get();
        $value = 0;
        foreach($margins as $margin){
            $marginValues = DB::table('margin_values')
                ->where(['margin_id' => $margin->id, 'project_id' => $projectId])
                ->sortByDesc('id')
                ->first();
            if($margin->id == 2 && $coolingPower <= $margin->statement1){
                if($marginValues){
                    $value = $marginValues->value;
                }else{
                    $value = 0;
                }
            }
            if($margin->id == 3 && $coolingPower > $margin->statement1 && $coolingPower <= $margin->statement2){
                if($marginValues){
                    $value = $marginValues->value;
                }else{
                    $value = 0;
                }
            }
            if($margin->id == 4 && $coolingPower > $margin->statement1 && $coolingPower <= $margin->statement2){
                if($marginValues){
                    $value = $marginValues->value;
                }else{
                    $value = 0;
                }
            }
            if($margin->id == 5 && $coolingPower > $margin->statement1 && $coolingPower <= $margin->statement2){
                if($marginValues){
                    $value = $marginValues->value;
                }else{
                    $value = 0;
                }
            }
            if($margin->id == 6 && $coolingPower > $margin->statement1 && $coolingPower <= $margin->statement2){
                if($marginValues){
                    $value = $marginValues->value;
                }else{
                    $value = 0;
                }
            }
            if($margin->id == 7 && $coolingPower > $margin->statement1 && $coolingPower <= $margin->statement2){
                if($marginValues){
                    $value = $marginValues->value;
                }else{
                    $value = 0;
                }
            }
            if($margin->id == 8 && $coolingPower > $margin->statement1 && $coolingPower <= $margin->statement2){
                if($marginValues){
                    $value = $marginValues->value;
                }else{
                    $value = 0;
                }
            }
            if($margin->id == 9 && $coolingPower > $margin->statement1){
                if($marginValues->first()){
                    $value = $marginValues->value;
                }else{
                    $value = 0;
                }
            }
        }
        return $value;
    }

    private function getProductFilter($characteristicId, $productId, $characteristicAttr = null) {
        $productSeriesId = DB::table('products')
            ->where('id', $productId)
            ->select('series_id')
            ->first();

        if (!is_null($productSeriesId) && !is_null($productSeriesId->series_id)) {
            $likeProductIds = DB::table('products')
                ->where('series_id', $productSeriesId->series_id)
                ->pluck('id');

            $likeProductId = DB::table('product_characteristics')
                ->where('product_characteristics.characteristic_id', $characteristicId);

            if (!is_null($characteristicAttr)) {
                $likeProductId = $likeProductId->where('attribute_id', $characteristicAttr);
            }
            $likeProductId = $likeProductId->whereIn('product_id', $likeProductIds)
                ->leftJoin('characteristic_attributes', 'product_characteristics.attribute_id', 'characteristic_attributes.id')
                ->select('product_characteristics.product_id', 'product_characteristics.characteristic_id', 'product_characteristics.attribute_id', 'characteristic_attributes.name_ru')
                ->groupBy('product_characteristics.attribute_id')
                ->orderBy('product_characteristics.attribute_id', 'ASC')
                ->get();

            if (!empty($likeProductId)) {
                return $likeProductId;
            }
        }
        return [];
    }

    private function getFilters($productIds = [], $categoryId, $projectId) {
        $values = [1, 2, 4, 5, 47, 48, 49, 50, 51, 52, 53, 54];
        $data = [];
        $attributeValueIds = [];
        $attributeIds = [];

        if (!empty($productIds)) {
            $attributeIds = DB::table('product_characteristics')
                ->whereIn('product_id', $productIds)
                ->whereNotNull('attribute_id')
                ->groupBy('attribute_id')
                ->pluck('attribute_id')
                ->toArray();

            $workAreaAttr = DB::table('characteristic_attributes')
                ->where('characteristic_id', 3)
                ->pluck('id')
                ->toArray();

            $attributeIds = array_unique(array_merge($workAreaAttr, $attributeIds), SORT_REGULAR);

            $attributeValueIds = DB::table('product_characteristics')
                ->whereIn('product_id', $productIds)
                ->whereNull('attribute_id')
                ->groupBy('characteristic_id')
                ->pluck('characteristic_id')
                ->toArray();
        }

        $characteristicAttributes = DB::table('characteristic_to_categories')
            ->leftJoin('characteristic_attributes', 'characteristic_to_categories.characteristic_id', '=', 'characteristic_attributes.characteristic_id')
            ->leftJoin('characteristics', 'characteristic_attributes.characteristic_id', '=', 'characteristics.id')
            ->leftJoin('value_types', 'characteristics.value_type_id', '=', 'value_types.id');

        if (count($attributeIds) > 0) {
            $characteristicAttributes = $characteristicAttributes->whereIn('characteristic_attributes.id', $attributeIds);
        }

        $characteristicAttributes = $characteristicAttributes->where('characteristic_to_categories.category_id', $categoryId)
            ->select('characteristic_attributes.name_ru', 'characteristic_attributes.id', 'characteristic_attributes.characteristic_id', 'characteristics.name_ru as title', 'value_types.name')
            ->groupBy('characteristic_attributes.id')
            ->orderBy('characteristic_attributes.name_ru', 'ASC')
            ->get();

        $textFilters = DB::table('characteristic_to_categories')
            ->leftJoin('product_characteristics', 'characteristic_to_categories.characteristic_id', '=', 'product_characteristics.characteristic_id')
            ->leftJoin('characteristics', 'product_characteristics.characteristic_id', '=', 'characteristics.id')
            ->where('characteristic_to_categories.category_id', $categoryId);

        if (count($attributeValueIds) > 0) {
            $values = array_intersect($attributeValueIds, $values);
        }

        $textFilters = $textFilters->whereIn('product_characteristics.characteristic_id', $values)
            ->where('product_characteristics.attribute_id', null)
            ->select('characteristics.name_ru as title', 'characteristics.id')
            ->groupBy('characteristics.name_ru', 'characteristics.id')
            ->get();


        $dimensionIds = [47, 48, 49, 50, 51, 52, 53, 54];
        $dimensions = [];
        $isDimension = false;

        if (count($attributeValueIds) > 0) {
            $dimensionIds = array_intersect($attributeValueIds, $dimensionIds);
        }

        foreach ($textFilters as $key => $textFilter) {
            if (in_array($textFilter->id, $dimensionIds)) {
                if (!$isDimension) {
                    $dimensions['title'] = 'Габариты';
                    $dimensions['type'] = 'group';
                    $isDimension = true;
                }
                $dimensions['filters'][] = $textFilter;
                unset($textFilters[$key]);
            }
        }
        $textFilters[] = $dimensions;


        $manufacturerCountries = DB::table('prices')
            ->leftJoin('product_to_categories', 'prices.product_id', '=', 'product_to_categories.product_id')
            ->leftJoin('products', 'product_to_categories.product_id', '=', 'products.id')
            ->leftJoin('product_manufacturers', 'products.manufacturer_id', '=', 'product_manufacturers.id');

        $manufacturerCountries = $manufacturerCountries->where('prices.project_id', $projectId)
            ->where('product_to_categories.category_id', $categoryId)
            ->where('prices.status', 1)
            ->where('prices.price', '!=', 0)

            ->select(DB::raw('COUNT(prices.product_id) as count'), 'product_manufacturers.logo', 'product_manufacturers.name as brand', 'product_manufacturers.id')
            ->groupBy('product_manufacturers.id')
            ->orderBy('product_manufacturers.name', 'ASC')
            ->get();

        $data['manufacturerCountries'] = $manufacturerCountries;
        $data['characteristicAttributes'] = $characteristicAttributes;
        $data['textFilters'] = $textFilters;

        return $data;
    }

    public function getFilterData(Request $request, $categoryId) {
        $projectId = $request->header('projectId');
        $values = [1, 2, 4, 5, 47, 48, 49, 50, 51, 52, 53, 54];
        $data = [];

        $characteristicAttributes = DB::table('characteristic_to_categories')
            ->leftJoin('characteristic_attributes', 'characteristic_to_categories.characteristic_id', '=', 'characteristic_attributes.characteristic_id')
            ->leftJoin('characteristics', 'characteristic_attributes.characteristic_id', '=', 'characteristics.id')
            ->leftJoin('value_types', 'characteristics.value_type_id', '=', 'value_types.id');

        $characteristicAttributes = $characteristicAttributes->where('characteristic_to_categories.category_id', $categoryId)
            ->select('characteristic_attributes.name_ru', 'characteristic_attributes.id', 'characteristic_attributes.characteristic_id', 'characteristics.name_ru as title', 'value_types.name')
            ->groupBy('characteristic_attributes.id')
            ->orderBy('characteristic_attributes.name_ru', 'ASC')
            ->get();

        $textFilters = DB::table('characteristic_to_categories')
            ->leftJoin('product_characteristics', 'characteristic_to_categories.characteristic_id', '=', 'product_characteristics.characteristic_id')
            ->leftJoin('characteristics', 'product_characteristics.characteristic_id', '=', 'characteristics.id')
            ->where('characteristic_to_categories.category_id', $categoryId);

        $textFilters = $textFilters->whereIn('product_characteristics.characteristic_id', $values)
            ->where('product_characteristics.attribute_id', null)
            ->select('characteristics.name_ru as title', 'characteristics.id')
            ->groupBy('characteristics.name_ru', 'characteristics.id')
            ->get();


        $dimensionIds = [47, 48, 49, 50, 51, 52, 53, 54];
        $dimensions = [];
        $isDimension = false;

        foreach ($textFilters as $key => $textFilter) {
            if (in_array($textFilter->id, $dimensionIds)) {
                if (!$isDimension) {
                    $dimensions['title'] = 'Габариты';
                    $dimensions['type'] = 'group';
                    $isDimension = true;
                }
                $dimensions['filters'][] = $textFilter;
                unset($textFilters[$key]);
            }
        }
        $textFilters[] = $dimensions;


        $manufacturerCountries = DB::table('prices')
            ->leftJoin('product_to_categories', 'prices.product_id', '=', 'product_to_categories.product_id')
            ->leftJoin('products', 'product_to_categories.product_id', '=', 'products.id')
            ->leftJoin('product_manufacturers', 'products.manufacturer_id', '=', 'product_manufacturers.id');

        $manufacturerCountries = $manufacturerCountries->where('prices.project_id', $projectId)
            ->where('product_to_categories.category_id', $categoryId)
            ->where('prices.status', 1)
            ->where('prices.price', '!=', 0)

            ->select(DB::raw('COUNT(prices.product_id) as count'), 'product_manufacturers.logo', 'product_manufacturers.name as brand', 'product_manufacturers.id')
            ->groupBy('product_manufacturers.id')
            ->orderBy('product_manufacturers.name', 'ASC')
            ->get();

        $data['manufacturerCountries'] = $manufacturerCountries;
        $data['characteristicAttributes'] = $characteristicAttributes;
        $data['textFilters'] = $textFilters;

        return $data;
    }

    public function searchProduct(Request $request, $categoryId = []) {
        $projectId = $request->header('projectId');
        $searchBy = $request->search;

        $searchableColumns = ['products.full_name_ru', 'products.full_name_am', 'products.full_name_en'];

        if ($searchBy) {
            $searchResponse = DB::table('prices')
                ->join('product_to_categories', 'prices.product_id', '=', 'product_to_categories.product_id', 'inner')
                ->leftJoin('products', 'product_to_categories.product_id', '=', 'products.id')
                ->leftJoin('product_manufacturers', 'products.manufacturer_id', '=', 'product_manufacturers.id')
                ->leftJoin('product_series', 'products.series_id', '=', 'product_series.id')
                ->leftJoin('product_series_photos', 'product_series.id', '=', 'product_series_photos.series_id')
                ->leftJoin('photos', 'products.id', '=', 'photos.product_id');

            if (!empty($categoryId)) {
                if (is_array($categoryId)) {
                    $searchResponse = $searchResponse->whereIn('product_to_categories.category_id', $categoryId);
                }else {
                    $searchResponse = $searchResponse->where('product_to_categories.category_id', $categoryId);
                }
            }

            $searchResponse = $searchResponse->where('prices.project_id', $projectId)
                ->where('prices.status', 1)
                ->where('prices.price', '!=', 0)
                ->where(function($q) use ($searchableColumns, $searchBy) {
                    foreach ($searchableColumns as $searchableColumn) {
                        $q->orWhere($searchableColumn, 'LIKE', "%{$searchBy}%");
                    }
                })
                ->select('products.id', 'products.name as model', 'product_manufacturers.name as brand', 'product_manufacturers.logo as brand_logo', DB::raw('CONCAT("[", GROUP_CONCAT(JSON_OBJECT( "cover_photo", product_series_photos.cover_photo,"series_picture_folder",  product_series_photos.folder, "series_picture_file_name", product_series_photos.file_name, "series_picture_format", product_series_photos.file_format)), "]") as cover_photo'), 'photos.folder as product_picture_folder','photos.file_name as product_picture_file_name', 'photos.file_format as product_picture_format', 'prices.price')
                ->groupBy('products.id')
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
            ->join('products', 'prices.product_id', '=', 'products.id', 'inner')
            ->leftJoin('product_manufacturers', 'products.manufacturer_id', '=', 'product_manufacturers.id')
            ->leftJoin('product_series', 'products.series_id', '=', 'product_series.id')
            ->leftJoin('product_series_photos', 'product_series.id', '=', 'product_series_photos.series_id')
            ->leftJoin('photos', 'products.id', '=', 'photos.product_id')
            ->where('prices.project_id', $projectId)
            ->where('prices.status', 1)
            ->where('product_series_photos.cover_photo',1)
            ->where('prices.price', '!=', 0)
            ->select('products.id', 'products.name as model', 'product_manufacturers.name as brand', 'product_manufacturers.logo as brand_logo', DB::raw('CONCAT("[", GROUP_CONCAT(JSON_OBJECT( "cover_photo", product_series_photos.cover_photo,"series_picture_folder",  product_series_photos.folder, "series_picture_file_name", product_series_photos.file_name, "series_picture_format", product_series_photos.file_format)), "]") as cover_photo'), 'photos.folder as product_picture_folder','photos.file_name as product_picture_file_name', 'photos.file_format as product_picture_format', 'prices.price', 'prices.has_chat', 'prices.has_sale', 'prices.price_with_setup', 'prices.price_without_setup', 'prices.chat_with_percent', 'prices.chat_without_percent')
            ->groupBy('products.id')
            ->orderByDesc('prices.product_id')
            ->take(6)
            ->get();

        return response()->json($newProducts);
    }

    public function getBrands(Request $request) {
        $projectId = $request->header('projectId');
        $data = [];
        $brands = DB::table('prices')
            ->join('products', 'prices.product_id', '=', 'products.id', 'inner')
            ->leftJoin('product_manufacturers', 'products.manufacturer_id', '=', 'product_manufacturers.id')
            ->where('prices.project_id', $projectId)
            ->where('prices.status', 1)
            ->where('prices.price', '!=', 0);

        if ($request->has('searchBrand')) {
            $searchBrand = $request->get('searchBrand');
            $searchBrand = str_split($searchBrand);
            $brands = $brands->where(function ($query) use ($searchBrand) {
                for ($i = 0; $i < count($searchBrand); $i++){
                    $query->orWhere('product_manufacturers.name', 'LIKE', "{$searchBrand[$i]}%");
                }
            });
        }

        $brands = $brands->select('product_manufacturers.id', 'product_manufacturers.name as brand', 'product_manufacturers.logo as brand_logo', DB::raw('COUNT(prices.product_id) as product_count'))
            ->groupBy('product_manufacturers.id')
            ->paginate(10);

        $data['brands'] = $brands->items();
        $data['total'] = $brands->total();

        return response()->json($data);
    }

    public function getCertificates(Request $request) {
        $projectId = $request->header('projectId');
        $certificates = DB::table('product_manufacturers')
            ->leftJoin('product_manufacturer_certificates', 'product_manufacturers.id', 'product_manufacturer_certificates.product_manufacturer_id')
            ->where('product_manufacturer_certificates.project_id', $projectId)
            ->whereNotNull('product_manufacturers.logo')
            ->select('product_manufacturers.id', 'product_manufacturers.name', 'product_manufacturers.logo', 'product_manufacturer_certificates.file_name')
            ->groupBy('product_manufacturers.id')
            ->get();

        return response()->json($certificates);
    }

    public function getCarouselData(Request $request) {
        $projectId = $request->header('projectId');

        $productImages = DB::table('prices')
            ->join('products', 'prices.product_id', '=', 'products.id', 'inner')
            ->leftJoin('product_manufacturers', 'products.manufacturer_id', '=', 'product_manufacturers.id')
            ->leftJoin('product_series', 'products.series_id', '=', 'product_series.id')
            ->leftJoin('product_series_photos', 'product_series.id', '=', 'product_series_photos.series_id')
            ->leftJoin('photos', 'products.id', '=', 'photos.product_id')
            ->where('prices.project_id', $projectId)
            ->where('prices.status', 1)
            ->where('product_series_photos.cover_photo',1)
            ->where('prices.price', '!=', 0)
            ->where('prices.slide_show_active', 1)
            ->select('products.id', 'products.name as model', 'product_manufacturers.name as brand', DB::raw('CONCAT("[", GROUP_CONCAT(JSON_OBJECT( "cover_photo", product_series_photos.cover_photo,"series_picture_folder",  product_series_photos.folder, "series_picture_file_name", product_series_photos.file_name, "series_picture_format", product_series_photos.file_format)), "]") as cover_photo'), 'product_series.series_name_ru as series_name', 'photos.folder as product_picture_folder','photos.file_name as product_picture_file_name', 'photos.file_format as product_picture_format', 'prices.price')
            ->groupBy('products.id')
            ->orderByDesc('prices.product_id')
            ->get();

        return response()->json($productImages);
    }

}
