<p>Olá, {{ $dados['paciente'] }}.</p>
<p>Seu atendimento foi agendado:</p>
<ul>
    <li><strong>Quando:</strong> {{ $dados['quando'] }}</li>
    <li><strong>Profissional:</strong> {{ $dados['profissional'] ?? 'a definir' }}</li>
    <li><strong>Unidade:</strong> {{ $dados['unidade'] }}</li>
    <li><strong>Modalidade:</strong> {{ $dados['modalidade'] === 'telemedicina' ? 'Teleconsulta (o link de acesso fica disponível no aplicativo)' : 'Presencial' }}</li>
    <li><strong>Protocolo:</strong> {{ $dados['protocolo'] }}</li>
</ul>
<p>SGHSS VidaPlus</p>
