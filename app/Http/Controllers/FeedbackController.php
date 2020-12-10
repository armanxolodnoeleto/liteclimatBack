<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class FeedbackController extends Controller
{
    public function checkout(Request $request) {
        $checkoutData = $request->except('products');
        $projectId = $request->header('projectId');
        $validator = Validator::make($checkoutData, [
            'name' => 'required|max:255',
            'last_name' => 'required',
            'email' => 'email|required',
            'phone_number' => 'required',
            'delivery_address' => 'required',
        ]);

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
            $subject = "Заявка с корзины";
            $data['checkoutData'] = $checkoutData;
            $data['productsInfo'] = $productsInfo;

            $this->sendMail($data, $subject, $projectId);

            return response()->json('success');
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }

    private function sendMail($data, $subject, $projectId) {
        if ($projectId == 59) {
            $email = 'zakaz@laitklimat.ru';
        }else {
            $email = 'zakaz@xolodnoeleto.ru';
        }
        Mail::send('emails.order', ['data' => $data], function($message) use ($email, $subject) {
            $message->to($email)->subject($subject);
            $message->from('zakup@xolodnoeleto.ru');
        });
    }
}
