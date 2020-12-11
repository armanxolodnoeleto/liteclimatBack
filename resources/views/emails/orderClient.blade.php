<html>
<body>
<div style="width:90%;margin:0 auto;padding-bottom: 50px;">
    @if ($projectId == 59)
        <a href="https://laitklimat.ru"><img src="http://projects-backend.ru/public/uploads/logos/laitklimat.jpg"></a>
    @else
        <a href="https://xolodnoeleto.ru"><img src="http://projects-backend.ru/public/uploads/logos/xolod.png"></a>
    @endif
    <h1 style="text-align:center">Здравствуйте, Ваш заказ принят!</h1>
    <table cellspacing="0"   align="center">
        <tr>
            <th style="padding: 12px;text-align: left;border-bottom: 1px solid #c7c7c7;">Модель</th>
            <th style="padding: 12px;border-bottom: 1px solid #c7c7c7;">Артикул</th>
            <th style="padding: 12px;border-bottom: 1px solid #c7c7c7;">Количество</th>
            <th style="padding: 12px;border-bottom: 1px solid #c7c7c7;">Цена</th>
        </tr>
        @php
            $productSum = 0;
        @endphp
        @foreach($data['productsInfo'] as $product)
            @php
                $productSum += $product->price;
            @endphp
            <tr>
                <td style="padding: 5px 12px;">{{ $product->model. ' '. $product->series_name. ' '. $product->brand }}</td>
                <td style="padding: 5px 12px;text-align:center;">{{ $product->id }}</td>
                <td style="padding: 5px 12px;text-align:center;">{{ $product->count }}</td>
                <td style="padding: 5px 12px;text-align:center;">{{ $product->price }}</td>
            </tr>
        @endforeach
        <tr>
            <td colspan="3" style="padding: 12px;border-top: 1px solid #c7c7c7;"><b>Стоимость заказа</b></td>
            <td style="padding: 12px;border-top: 1px solid #c7c7c7;"><b>{{ $productSum }}</b></td>
        </tr>
    </table>
    @if ($projectId == 59)
        <p style="margin-top: 60px;"><img src="http://projects-backend.ru/public/uploads/logos/light_climat.png" style="width:530px;"></p>
    @endif
    <p>По всем вопросам<br>E-mail: {{ $projectId == 59 ? 'zakaz@laitklimat.ru' : 'zakaz@xolodnoeleto.ru' }}<br>Телефон: {{ $projectId == 59 ? '+7[495] 668-65-11' : '+7 (499) 286-89-05' }}</p>
</div>
</body>
</html>
