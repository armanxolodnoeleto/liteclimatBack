<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
</head>
<body>

@if (!$data['in_cart'])
    <div style="width:90%;margin:0 auto;padding-bottom: 50px;">
        <a href="https://laitklimat.ru"><img src="http://back.projects-backend.ru/public/uploads/logos/laitklimat.jpg"></a>
        <h1 style="text-align:center">Заявка с всплывающего окна</h1>

        <table cellspacing="0" style=" margin-top:15px; padding:10px" align="center">
            <tr><td><h3 style="padding-right: 30px;margin:0px;"><font color="#980e0e">Имя:</font></h3></td><td> {{ $data['name'] }}</td></tr>
            <tr><td><h3 style="padding-right: 30px;margin:0px;"><font color="#980e0e">Номер телефона:</font></h3></td><td> {{ $data['phone'] }}</td></tr>
            <tr><td><h3 style="padding-right: 30px;margin:0px;"><font color="#980e0e">Почта:</font></h3></td><td> {{ $data['email'] }}</td></tr>
            <tr><td><h3 style="padding-right: 30px;margin:0px;"><font color="#980e0e">Ссылка:</font></h3></td><td> {{ $data['url'] }}</td></tr>
        </table>
    </div>
@else
    <div style="width:90%;margin:0 auto;padding-bottom: 50px;">
        <a href="https://laitklimat.ru"><img src="http://back.projects-backend.ru/public/uploads/logos/laitklimat.jpg"></a>
        <h1 style="text-align:center">Заявка с всплывающего окна</h1>

        <table cellspacing="0" style=" margin-top:15px; padding:10px" align="center">
            <tr><td><h3 style="padding-right: 30px;margin:0px;"><font color="#980e0e">Имя:</font></h3></td><td> {{ $data['name'] }}</td></tr>
            <tr><td><h3 style="padding-right: 30px;margin:0px;"><font color="#980e0e">Номер телефона:</font></h3></td><td> {{ $data['phone'] }}</td></tr>
            <tr><td><h3 style="padding-right: 30px;margin:0px;"><font color="#980e0e">Почта:</font></h3></td><td> {{ $data['email'] }}</td></tr>
            <tr><td><h3 style="padding-right: 30px;margin:0px;"><font color="#980e0e">Модель:</font></h3></td><td>
                {{ !empty($data['product'] && isset($data['product']['name']) ? $data['product']['name'] : '') }}</td></tr>
            <tr><td><h3 style="padding-right: 30px;margin:0px;"><font color="#980e0e">Цена:</font></h3></td><td> {{ !empty($data['product'] && isset($data['product']['price']) ? $data['product']['price'] : '') }}</td></tr>
            <tr><td><h3 style="padding-right: 30px;margin:0px;"><font color="#980e0e">Цена с установкой:</font></h3></td><td> {{ !empty($data['product'] && isset($data['product']['price_with_setup']) ? $data['product']['price_with_setup'] : '') }}</td></tr>
            <tr><td><h3 style="padding-right: 30px;margin:0px;"><font color="#980e0e">Цена без установки:</font></h3></td><td> {{ !empty($data['product'] && isset($data['product']['price_without_setup']) ? $data['product']['price_without_setup'] : '') }}</td></tr>
        </table>
    </div>
@endif
</body>
</html>
