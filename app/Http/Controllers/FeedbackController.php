<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Services\SendMailSmtpClass;

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
            $data['checkoutData'] = $checkoutData;
            $data['productsInfo'] = $productsInfo;

            $this->sendMail($data, $projectId);

            return response()->json('success');
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }

    private function sendMail($data, $projectId) {
        $theme = 'Заявка с корзины (Купить в 1 клик)';
        if ($projectId == 59) {
            $project = 'LaitKlimat.ru';
            $login = 'zakaz@laitklimat.ru';
            $password = 'Zpass1568';
            $host = 'ssl://smtp.yandex.ru';
        }else {
            $project = 'Xolodnoeleto.ru';
            $login = 'zakaz@xolodnoeleto.ru';
            $password = 'Zpass82827';
            $host = 'ssl://smtp.yandex.ru';
        }

        $message = view('emails.order', compact('data', 'projectId'))->render();
        $sendedMail = $this->sendMailByProject($login, $password, $login, $theme, $message, $host, $project);

        if ($sendedMail) {
            $messageForClient = view('emails.orderClient', compact('data', 'projectId'))->render();
            $this->sendMailByProject($login, $password, $data['checkoutData']['email'], $theme, $messageForClient, $host, $project);
        }
    }

    private function sendMailByProject($login, $password, $email, $theme, $message, $host, $project) {
        $mailSMTP = new SendMailSmtpClass($login, $password, $host, 'Клиент', 465);
        $headers= "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=utf-8\r\n";
        $headers .= "From: ". $project ." <". $login .">\r\n";
        $headers .= "To: <" . $email.">\r\n";
        $result =  $mailSMTP->send($email, $theme, $message, $headers);

        return $result;
    }
}
