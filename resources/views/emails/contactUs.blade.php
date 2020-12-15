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
        <th>ФИО</th>
        <th>Емайл</th>
        <th>Телефон</th>
        <th>Коммениарий</th>
    </tr>
    </thead>
    <tbody>
    <tr align="center">
        <td>{{ $data['full_name'] }}</td>
        <td>{{ isset($data['email']) ? $data['email'] : '' }}</td>
        <td>{{ $data['phone'] }}</td>
        <td>{{ $data['comment'] }}</td>
    </tr>
    </tbody>
</table>
</body>
</html>
