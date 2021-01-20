<?php

namespace App\Http\Controllers;

use App\Services\MailService;
use App\Services\PhotoUploadService;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class FeedbackController extends Controller
{
    public function checkout(Request $request) {
        $checkoutData = $request->except(['products', '/api/checkout']);
        $projectId = $request->header('projectId');
        $validatedArray = [
            'name' => 'required|max:255',
            'email' => 'email|required',
            'phone_number' => 'required',
            'delivery_address' => 'required',
        ];

        if ($projectId == config('projects.lk')) {
            $validatedArray['last_name'] = 'required';
        }

        $validator = Validator::make($checkoutData, $validatedArray);

        if ($validator->fails()) {
            return response()->json(['errors'=>$validator->errors()]);
        }

        try {
            $products = $request->products;
            $checkoutData['project_id'] = $projectId;
            $checkoutId = DB::table('checkouts')
                ->insertGetId($checkoutData);

            $data = [];
            $productsInfo = [];
            foreach ($products as $productId => $count) {
                DB::table('checkout_products')->insert(['checkout_id' => $checkoutId, 'product_id' => $projectId, 'count' => $count]);
                $productsInfo[$productId] = DB::table('prices')
                    ->leftJoin('products', 'prices.product_id', '=', 'products.id')
                    ->leftJoin('product_manufacturers', 'products.manufacturer_id', '=', 'product_manufacturers.id')
                    ->leftJoin('product_series', 'products.series_id', '=', 'product_series.id')
                    ->where('prices.product_id', $productId)
                    ->where('prices.project_id', $projectId)
                    ->select('products.id', 'products.name as model', 'product_manufacturers.name as brand', 'product_series.series_name_ru as series_name', 'prices.price')
                    ->first();
                $productsInfo[$productId]->count = $count;
            }
            $data['checkoutData'] = $checkoutData;
            $data['productsInfo'] = $productsInfo;
            $theme = 'Заявка с корзины';

            $mailSender = new MailService($projectId);
            $mailSender->sendMail($data, $theme, 'emails.order');

            $email = $data['checkoutData']['email'];
            $mailSender->sendMailToClient($email, $data, $theme, 'emails.orderClient');

            return response()->json('success');
        } catch (\Exception $exception) {
            return response()->json(['errors'=>$validator->errors()]);
        }
    }

    public function oneClickOrder(Request $request) {
        $orderData = $request->all();
        $projectId = $request->header('projectId');

        $validator = Validator::make($orderData, [
            'name' => 'required|max:255',
            'phone' => 'required|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors'=>$validator->errors()]);
        }

        if ($projectId == config('projects.lk')) {
            $theme = 'Заявка с корзины (Купить в 1 клик)';
            $view = 'emails.oneClickOrder';
        }else {
            $theme = 'Заказ обратного звонка';
            $view = 'emails.orderCallback';
        }

        try {
            $mailSender = new MailService($projectId);
            $mailSender->sendMail($orderData, $theme, $view);

            return response()->json('success');
        }catch (\Exception $exception) {
            return response()->json(['errors'=>$validator->errors()]);
        }
    }

    public function contactUs(Request $request) {
        $contactUsData = $request->all();
        $projectId = $request->header('projectId');

        $validator = Validator::make($contactUsData, [
            'full_name' => 'required',
            'phone' => 'required',
            'comment' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors'=>$validator->errors()]);
        }

        $theme = 'Обратная связь';
        $view = 'emails.contactUs';

        try {
            $mailSender = new MailService($projectId);
            $mailSender->sendMail($contactUsData, $theme, $view);

            return response()->json('success');
        }catch (\Exception $exception) {
            return response()->json(['errors'=>$exception->getMessage()]);
        }
    }

    public function review(Request $request) {
        $projectId = $request->header('projectId');
        $reviewData = $request->except('file', '/api/review');

        if ($projectId == config('projects.lk')) {
            $validator = Validator::make($reviewData, [
                'name' => 'required',
                'last_name' => 'required',
                'advantages' => 'required',
                'rating' => 'required',
                'date' => 'required'
            ]);
            $table = 'lt_reviews';
        }else {
            $validator = Validator::make($reviewData, [
                'full_name' => 'required',
                'date' => 'required',
                'comment' => 'required',
                'rating' => 'required',
            ]);
            $table = 'xl_reviews';
        }
        if ($validator->fails()) {
            return response()->json(['errors'=>$validator->errors()]);
        }

        try {
            $newReviewPhotos = [];
            $reviewData['project_id'] = $projectId;
            $reviewData['admin_comment'] = 'Администратор сайта лайт климат здравствуйте, '. $reviewData['name'] . ' ' . $reviewData['last_name'] .'. Спасибо вам за отзыв, всегда готовы вам помочь.';
            $reviewId = DB::table($table)
                ->insertGetId($reviewData);

            if ($request->hasFile('file')) {
                $reviewImages = $request->file();
                $photoUploader = new PhotoUploadService($projectId);
                $dir = 'uploads/reviews/';
                foreach ($reviewImages as $key => $reviewImage) {
                    $newReviewPhotos = [];
                    foreach ($reviewImage as $item) {
                        $photo = $photoUploader->uploadPhoto($item, $dir);
                        $newReviewPhotos[] = ['project_id' => $projectId, 'review_id' => $reviewId, 'file_original_name' => $photo['original_name'], 'folder' => $photo['dir'], 'file_name' => $photo['name'], 'file_format' => $photo['format']];
                    }
                }
                DB::table('project_review_images')
                    ->insert($newReviewPhotos);
            }
            return response()->json('success');
        }catch (\Exception $exception) {
            return response()->json(['errors'=>$exception->getMessage()]);
        }
    }

    public function getReviews(Request $request) {
        $projectId = $request->header('projectId');
        if ($projectId == config('projects.lk')) {
            $table = 'lt_reviews';
        }else {
            $table = 'xl_reviews';
        }
        try {
            $response = [];
            $reviews = DB::table($table)
                ->where('approve', 1)
                ->orderBy('rating', 'DESC')
                ->paginate(12);
            $reviewIds = $reviews->pluck('id');

            $reviewImages = DB::table('project_review_images')
                ->whereIn('review_id', $reviewIds)
                ->where('project_id', $projectId)
                ->select('review_id', 'folder', 'file_name', 'file_format')
                ->get();

            $response['total'] = $reviews->total();
            $response['reviews'] = $reviews->items();
            $response['reviewImages'] = $reviewImages;
            return response()->json($response);
        }catch (\Exception $exception) {
            return response()->json(['errors'=>$exception->getMessage()]);
        }
    }

    public function chatFeedBack(Request $request) {
        $projectId = $request->header('projectId');
        $chatData = $request->except('/api/chatFeedBack');
        $validator = Validator::make($chatData, [
            'phone' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors'=>$validator->errors()]);
        }

        $inCart = $chatData['in_cart'];
        if ($request->has('is_unique')) {
            $isUnique = 'Y- ';
        }else {
            $isUnique = 'Y+ ';
        }
        $theme = 'Заявка с всплывающего окна '.$inCart.' '.$isUnique;
        $view = 'emails.chatFeedBack';

        try {
            $product = [];
            if ($inCart == 'true') {
                if ($request->has('product_id')) {
                    $productId = $chatData['product_id'];
                    $product = DB::table('products')
                        ->join('prices', 'products.id', '=', 'prices.product_id')
                        ->where('products.id', $productId)
                        ->where('prices.project_id', $projectId)
                        ->select('products.name', 'prices.price', 'prices.price_with_setup', 'prices.price_without_setup')
                        ->get()
                        ->toArray();
                }
            }

            $chatData['product'] = $product;
            $mailSender = new MailService($projectId);
            $mailSender->sendMail($chatData, $theme, $view);

            return response()->json('success');
        }catch (\Exception $exception) {
            return response()->json(['errors'=>$exception->getMessage()]);
        }
    }

}
