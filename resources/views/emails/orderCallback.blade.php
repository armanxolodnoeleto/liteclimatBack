<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
</head>
<body>
<table align="center">
    <thead>
    <tr>
        <th>Имя</th>
        <th>Фамилия</th>
        <th>Телефон</th>
        <th>Удобное время для звонка</th>
        <th>Комментарий</th>
    </tr>
    </thead>
    <tbody>
    <tr align="center">
        <td>{{ $data['name'] }}</td>
        <td>{{ $data['last_name'] }}</td>
        <td>{{ $data['phone'] }}</td>
        <td>{{ $data['hour'] }}</td>
        <td>{{ $data['comment'] }}</td>
    </tr>
    </tbody>
</table>
</body>
</html>
