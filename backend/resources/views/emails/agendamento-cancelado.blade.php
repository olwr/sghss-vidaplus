<p>Olá, {{ $dados['paciente'] }}.</p>
<p>O atendimento de {{ $dados['quando'] }} na unidade {{ $dados['unidade'] }} foi cancelado.</p>
@if (! empty($dados['motivo_cancelamento']))
<p><strong>Motivo:</strong> {{ $dados['motivo_cancelamento'] }}</p>
@endif
<p>SGHSS VidaPlus</p>
