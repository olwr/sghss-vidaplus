<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>{{ $dados['titulo'] }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #bbb; padding: 4px 6px; text-align: left; }
        th { background: #eee; }
    </style>
</head>
<body>
    <h2>{{ $dados['titulo'] }}</h2>
    <table>
        <thead><tr>@foreach ($dados['colunas'] as $c)<th>{{ $c }}</th>@endforeach</tr></thead>
        <tbody>
        @foreach ($dados['linhas'] as $linha)
            <tr>@foreach ($linha as $v)<td>{{ $v }}</td>@endforeach</tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>
