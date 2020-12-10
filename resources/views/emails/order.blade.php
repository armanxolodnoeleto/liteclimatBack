<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
</head>
<body>
    <p style="font-weight: bold">Информация о клиенте:</p>
    <table>
        <thead>
            <tr>
                <th>Имя</th>
                <th>Фамилия</th>
                <th>Емайл</th>
                <th>Телефон</th>
                <th>Адресс доставки</th>
                <th>Тип Доставки</th>
                <th>Тип Оплаты</th>
                <th>Комментарий</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $data['checkoutData']['name'] }}</td>
                <td>{{ $data['checkoutData']['last_name'] }}</td>
                <td>{{ $data['checkoutData']['email'] }}</td>
                <td>{{ $data['checkoutData']['phone_number'] }}</td>
                <td>{{ $data['checkoutData']['delivery_address'] }}</td>
                <td>{{ $data['checkoutData']['delivery_type'] }}</td>
                <td>{{ $data['checkoutData']['payment_type'] }}</td>
                <td>{{ $data['checkoutData']['comment'] }}</td>
            </tr>
        </tbody>
    </table>

    <p style="font-weight: bold">Информация заказа:</p>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Модель</th>
                <th>Цена</th>
                <th>Количество</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['productsInfo'] as $product)
                <tr>
                    <td>{{ $product->id }}</td>
                    <td>{{ $product->model. ' '. $product->series_name. ' '. $product->brand }}</td>
                    <td>{{ $product->price }}</td>
                    <td>{{ $product->count }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
