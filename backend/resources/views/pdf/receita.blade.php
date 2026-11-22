<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Receita {{ $receita->uuid }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border-bottom: 1px solid #ccc; padding: 6px; text-align: left; }
        .aviso { margin-top: 24px; font-size: 10px; color: #555; }
    </style>
</head>
<body>
    <h1>SGHSS VidaPlus — Receita</h1>
    <p><strong>Unidade:</strong> {{ $receita->unidade->nome }}</p>
    <p><strong>Paciente:</strong> {{ $receita->paciente->nome }}</p>
    <p><strong>Profissional:</strong> {{ $receita->profissional->usuario->nome }}
        ({{ $receita->profissional->conselho_sigla }} {{ $receita->profissional->conselho_numero }}/{{ $receita->profissional->conselho_uf }})</p>
    <p><strong>Emitida em:</strong> {{ $receita->emitida_em->setTimezone(config('sghss.fuso_agenda'))->format('d/m/Y H:i') }}</p>

    <table>
        <thead><tr><th>Medicamento</th><th>Dosagem</th><th>Posologia</th><th>Duração</th><th>Qtd.</th></tr></thead>
        <tbody>
        @foreach ($receita->itens as $item)
            <tr>
                <td>{{ $item->medicamento }}</td><td>{{ $item->dosagem }}</td>
                <td>{{ $item->posologia }}</td><td>{{ $item->duracao }}</td><td>{{ $item->quantidade }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <p class="aviso">Documento sem assinatura digital qualificada (ICP-Brasil), emitido para fins de demonstração acadêmica.
        Protocolo: {{ $receita->uuid }}</p>
</body>
</html>
