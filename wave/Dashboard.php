<?php
session_start();
require_once 'conexão.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $senha = $_POST['senha'];

    $sql = "SELECT * FROM usuarios WHERE email = '$email' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();
        
        // Verifica a senha criptografada
        if (password_verify($senha, $usuario['senha'])) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['nivel'] = $usuario['nivel'];
            $_SESSION['nome'] = $usuario['nome'];

            // Redirecionamento por nível de acesso
            if ($usuario['nivel'] === 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: index.php");
            }
            exit();
        }
    }
    // Se falhar, volta com erro
    header("Location: login.php?erro=invalido");
    exit();
     
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Usewaves</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>


  <!-- SIDEBAR -->
  <aside class="sb" id="sb">
    <div class="sb-hd">
      <img src="IMG_2267.PNG" alt="Wave"/>
      <div class="sb-hd-t">Wave <small>Admin Panel</small></div>
    </div>
    <nav class="sb-nav">
      <div class="sb-glbl">Principal</div>
      <button class="sb-btn on" onclick="go('dashboard',this)"><span class="ic">🏠</span>Dashboard</button>
      <button class="sb-btn" onclick="go('pagamentos',this)"><span class="ic">💳</span>Pagamentos <span class="sb-bgt bgt-g">5</span></button>

      <div class="sb-glbl" style="margin-top:.35rem">Catálogo</div>
      <button class="sb-btn" onclick="go('produtos',this)"><span class="ic">💎</span>Produtos</button>
      <button class="sb-btn" onclick="go('estoque',this)"><span class="ic">🗃️</span>Estoque <span class="sb-bgt bgt-r">3</span></button>

      <div class="sb-glbl" style="margin-top:.35rem">Relacionamento</div>
      <button class="sb-btn" onclick="go('leads',this)"><span class="ic">🎯</span>Leads <span class="sb-bgt bgt-g">12</span></button>

      <div class="sb-glbl" style="margin-top:.35rem">Sistema</div>
      <button class="sb-btn" onclick="go('config',this)"><span class="ic">⚙️</span>Configurações</button>
    </nav>
    <div class="sb-ft">
      <div class="sb-av">A</div>
      <div><div class="sb-un">Admin Wave</div><div class="sb-ur">Administrador</div></div>
      <button class="sb-out" title="Sair" onclick="doLogout()">⏻</button>
    </div>
  </aside>

  <!-- MAIN -->
  <div class="main">
    <header class="topbar">
      <div class="tb-l">
        <button id="mbtn" style="display:none;background:none;border:none;font-size:1.1rem;cursor:pointer;" onclick="document.getElementById('sb').classList.toggle('open')">☰</button>
        <div class="tb-title" id="pgTitle">Dashboard</div>
      </div>
      <div class="tb-r">
        <div class="tb-search"><span>🔍</span><input type="text" placeholder="Pesquisar..."/></div>
        <button class="tb-ic" onclick="document.getElementById('notifM').classList.add('open')">🔔<span class="n-dot"></span></button>
        <div class="sb-av" style="cursor:pointer">A</div>
      </div>
    </header>
  <!-- ═══════════════════════════════
         DASHBOARD
    ═══════════════════════════════ -->
    <div class="pg on" id="pg-dashboard">
      <div class="ph">
        <div>
          <h2>Bom dia, <em style="font-style:italic;color:var(--gold)">Admin</em> 👋</h2>
          <p>Quinta-feira, 26 de fevereiro de 2026</p>
        </div>
        <div style="display:flex;gap:.6rem">
          <button class="btn-s btn-sm" onclick="showToast('Relatório exportado! 📊','ok')">⬇ Exportar</button>
          <button class="btn-g btn-sm">Fevereiro 2026 ▾</button>
        </div>
      </div>

      <!-- KPIs -->
      <div class="g4 mb">
        <div class="kpi bl"><div class="k-ic ic-bl">💰</div><div class="k-lb">Receita do mês</div><div class="k-vl">R$18.420</div><div class="k-dt up">▲ +14% vs mês anterior</div></div>
        <div class="kpi go"><div class="k-ic ic-go">🛒</div><div class="k-lb">Pedidos</div><div class="k-vl">147</div><div class="k-dt up">▲ +8%</div></div>
        <div class="kpi gr"><div class="k-ic ic-gr">🎯</div><div class="k-lb">Novos leads</div><div class="k-vl">63</div><div class="k-dt up">▲ +22%</div></div>
        <div class="kpi pu"><div class="k-ic ic-pu">💎</div><div class="k-lb">Ticket médio</div><div class="k-vl">R$125</div><div class="k-dt dn">▼ -3%</div></div>
      </div>

      <!-- Gráfico + Donut -->
      <div class="g2 mb">
        <div class="card">
          <div class="c-hd">
            <span class="c-t">Receita — últimos 8 meses</span>
            <div class="tabs">
              <button class="tab on" onclick="stab(this)">Mensal</button>
              <button class="tab" onclick="stab(this)">Semanal</button>
            </div>
          </div>
          <div class="c-bd">
            <div class="bc-wrap" style="margin-bottom:.7rem">
              <div class="bc"><div class="bar b-bl" style="height:54%"></div><div class="bc-l">Jul</div></div>
              <div class="bc"><div class="bar b-bl" style="height:66%"></div><div class="bc-l">Ago</div></div>
              <div class="bc"><div class="bar b-bl" style="height:46%"></div><div class="bc-l">Set</div></div>
              <div class="bc"><div class="bar b-bl" style="height:72%"></div><div class="bc-l">Out</div></div>
              <div class="bc"><div class="bar b-bl" style="height:58%"></div><div class="bc-l">Nov</div></div>
              <div class="bc"><div class="bar b-go" style="height:92%"></div><div class="bc-l">Dez</div></div>
              <div class="bc"><div class="bar b-bl" style="height:60%"></div><div class="bc-l">Jan</div></div>
              <div class="bc"><div class="bar b-gr" style="height:76%"></div><div class="bc-l">Fev</div></div>
            </div>
            <div style="display:flex;gap:1rem;flex-wrap:wrap">
              <div style="display:flex;align-items:center;gap:.35rem;font-size:.68rem;color:var(--muted)"><div style="width:9px;height:9px;border-radius:2px;background:var(--blue)"></div>Meses anteriores</div>
              <div style="display:flex;align-items:center;gap:.35rem;font-size:.68rem;color:var(--muted)"><div style="width:9px;height:9px;border-radius:2px;background:var(--gold)"></div>Pico (Dez)</div>
              <div style="display:flex;align-items:center;gap:.35rem;font-size:.68rem;color:var(--muted)"><div style="width:9px;height:9px;border-radius:2px;background:var(--green)"></div>Mês atual</div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="c-hd"><span class="c-t">Vendas por categoria</span></div>
          <div class="c-bd" style="display:flex;align-items:center;gap:1.8rem;flex-wrap:wrap">
            <div class="donut"></div>
            <div class="dl">
              <div class="dl-r"><div class="dl-d" style="background:var(--blue)"></div><span class="dl-n">Colares</span><strong class="dl-p">36%</strong></div>
              <div class="dl-r"><div class="dl-d" style="background:var(--gold)"></div><span class="dl-n">Pulseiras</span><strong class="dl-p">26%</strong></div>
              <div class="dl-r"><div class="dl-d" style="background:var(--green)"></div><span class="dl-n">Brincos</span><strong class="dl-p">16%</strong></div>
              <div class="dl-r"><div class="dl-d" style="background:var(--purple)"></div><span class="dl-n">Kits</span><strong class="dl-p">10%</strong></div>
              <div class="dl-r"><div class="dl-d" style="background:#dde4ec"></div><span class="dl-n">Outros</span><strong class="dl-p">12%</strong></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Atividade + Ranking -->
      <div class="g2 mb">
        <div class="card">
          <div class="c-hd"><span class="c-t">⚡ Atividade recente</span></div>
          <div class="c-bd" style="padding:.8rem 1.3rem">
            <div class="fi"><div class="fi-ic" style="background:var(--green-lt)">🛒</div><div class="fi-t"><h5>Novo pedido #1047</h5><p>Ana Silva — Kit Verão Wave ×2 — R$298</p></div><div class="fi-tm">2 min</div></div>
            <div class="fi"><div class="fi-ic" style="background:var(--blue-xs)">🎯</div><div class="fi-t"><h5>Novo lead captado</h5><p>Maria Oliveira — via newsletter</p></div><div class="fi-tm">18 min</div></div>
            <div class="fi"><div class="fi-ic" style="background:var(--red-lt)">⚠️</div><div class="fi-t"><h5>Estoque crítico</h5><p>Brinco Flor Azul — apenas 2 unidades</p></div><div class="fi-tm">1h</div></div>
            <div class="fi"><div class="fi-ic" style="background:var(--gold-lt)">⭐</div><div class="fi-t"><h5>Avaliação 5★ recebida</h5><p>"Amei o colar, chegou perfeito!" — Júlia M.</p></div><div class="fi-tm">3h</div></div>
            <div class="fi"><div class="fi-ic" style="background:var(--green-lt)">💳</div><div class="fi-t"><h5>Pagamento confirmado</h5><p>Pedido #1043 — R$156 via PIX</p></div><div class="fi-tm">5h</div></div>
          </div>
        </div>

        <div class="card">
          <div class="c-hd"><span class="c-t">🏆 Mais vendidos</span></div>
          <div class="c-bd" style="display:flex;flex-direction:column;gap:.85rem">
            <div style="display:flex;align-items:center;gap:.75rem"><span style="font-family:var(--fh);font-size:1.25rem;font-weight:600;color:var(--gold);width:1.2rem;text-align:center">1</span><div class="th t2">🌊</div><div style="flex:1;min-width:0"><div style="font-size:.78rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">Pulseira Ondas</div><div style="font-size:.66rem;color:var(--muted)">52 un. vendidas</div></div><strong style="color:var(--blue);font-size:.82rem;flex-shrink:0">R$2.340</strong></div>
            <div style="display:flex;align-items:center;gap:.75rem"><span style="font-family:var(--fh);font-size:1.25rem;font-weight:600;color:var(--blue);width:1.2rem;text-align:center">2</span><div class="th t1">🐚</div><div style="flex:1;min-width:0"><div style="font-size:.78rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">Colar Concha do Mar</div><div style="font-size:.66rem;color:var(--muted)">22 un. vendidas</div></div><strong style="color:var(--blue);font-size:.82rem;flex-shrink:0">R$1.958</strong></div>
            <div style="display:flex;align-items:center;gap:.75rem"><span style="font-family:var(--fh);font-size:1.25rem;font-weight:600;color:var(--muted);width:1.2rem;text-align:center">3</span><div class="th t4">✨</div><div style="flex:1;min-width:0"><div style="font-size:.78rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">Kit Verão Wave</div><div style="font-size:.66rem;color:var(--muted)">12 un. vendidas</div></div><strong style="color:var(--blue);font-size:.82rem;flex-shrink:0">R$1.788</strong></div>
            <div style="display:flex;align-items:center;gap:.75rem"><span style="font-family:var(--fh);font-size:1.25rem;font-weight:600;color:var(--muted);width:1.2rem;text-align:center">4</span><div class="th t3">🌺</div><div style="flex:1;min-width:0"><div style="font-size:.78rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">Brinco Flor Azul</div><div style="font-size:.66rem;color:var(--muted)">21 un. vendidas</div></div><strong style="color:var(--blue);font-size:.82rem;flex-shrink:0">R$1.239</strong></div>
            <div style="display:flex;align-items:center;gap:.75rem"><span style="font-family:var(--fh);font-size:1.25rem;font-weight:600;color:var(--muted);width:1.2rem;text-align:center">5</span><div class="th t6">☀️</div><div style="flex:1;min-width:0"><div style="font-size:.78rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">Brinco Sol de Verão</div><div style="font-size:.66rem;color:var(--muted)">14 un. vendidas</div></div><strong style="color:var(--blue);font-size:.82rem;flex-shrink:0">R$1.092</strong></div>
          </div>
        </div>
      </div>

      <!-- Pedidos recentes -->
      <div class="card mb">
        <div class="c-hd">
          <span class="c-t">📦 Pedidos recentes</span>
          <button class="ab ab-vw btn-sm" onclick="go('pagamentos',null)">Ver todos</button>
        </div>
        <div style="overflow-x:auto">
          <table class="tbl">
            <thead><tr><th>Pedido</th><th>Cliente</th><th>Itens</th><th>Total</th><th>Pagamento</th><th>Status</th><th>Data</th></tr></thead>
            <tbody>
              <tr><td><strong>#1047</strong></td><td><div class="cell"><div class="av">AS</div><div><h5>Ana Silva</h5></div></div></td><td>Kit Verão ×2</td><td><strong>R$298</strong></td><td><span class="pill p-bl">PIX</span></td><td><span class="pill p-go">⏳ Aguardando</span></td><td>Hoje 14h22</td></tr>
              <tr><td><strong>#1046</strong></td><td><div class="cell"><div class="av">CM</div><div><h5>Carla M.</h5></div></div></td><td>Pulseira Ondas ×1</td><td><strong>R$45</strong></td><td><span class="pill p-pu">Cartão</span></td><td><span class="pill p-bl">Preparando</span></td><td>Hoje 11h05</td></tr>
              <tr><td><strong>#1045</strong></td><td><div class="cell"><div class="av">BL</div><div><h5>Beatriz L.</h5></div></div></td><td>Colar + Brinco</td><td><strong>R$148</strong></td><td><span class="pill p-bl">PIX</span></td><td><span class="pill p-gr">✅ Enviado</span></td><td>Ontem</td></tr>
              <tr><td><strong>#1044</strong></td><td><div class="cell"><div class="av">FC</div><div><h5>Fernanda C.</h5></div></div></td><td>Kit Noite ×1</td><td><strong>R$179</strong></td><td><span class="pill p-or">Boleto</span></td><td><span class="pill p-go">⏳ Aguardando</span></td><td>Ontem</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>


    <!-- ═══════════════════════════════
         PAGAMENTOS
    ═══════════════════════════════ -->
    <div class="pg" id="pg-pagamentos">
      <div class="ph">
        <div><h2>Pagamentos</h2><p>Todas as transações da loja</p></div>
        <div style="display:flex;gap:.6rem;align-items:center">
          <button class="btn-s btn-sm" onclick="showToast('Extrato exportado! 📊','ok')">⬇ Exportar</button>
          <div class="tabs">
            <button class="tab on" onclick="stab(this)">Todos</button>
            <button class="tab" onclick="stab(this)">Pendentes</button>
            <button class="tab" onclick="stab(this)">Aprovados</button>
            <button class="tab" onclick="stab(this)">Recusados</button>
          </div>
        </div>
      </div>

      <div class="g4 mb">
        <div class="kpi bl"><div class="k-ic ic-bl">💰</div><div class="k-lb">Receita confirmada</div><div class="k-vl">R$16.240</div><div class="k-dt up">▲ +11%</div></div>
        <div class="kpi go"><div class="k-ic ic-go">⏳</div><div class="k-lb">Aguardando pagto.</div><div class="k-vl">R$1.840</div><div class="k-dt neu">5 transações</div></div>
        <div class="kpi re"><div class="k-ic ic-re">❌</div><div class="k-lb">Recusados</div><div class="k-vl">R$340</div><div class="k-dt dn">2 pedidos</div></div>
        <div class="kpi gr"><div class="k-ic ic-gr">🔄</div><div class="k-lb">Reembolsados</div><div class="k-vl">R$89</div><div class="k-dt neu">1 devolução</div></div>
      </div>

      <!-- Gráfico de métodos -->
      <div class="g2 mb">
        <div class="card">
          <div class="c-hd"><span class="c-t">Receita diária (fev/26)</span></div>
          <div class="c-bd">
            <div class="bc-wrap" style="margin-bottom:.7rem;height:120px">
              <div class="bc"><div class="bar b-gr" style="height:50%"></div><div class="bc-l">10</div></div>
              <div class="bc"><div class="bar b-gr" style="height:70%"></div><div class="bc-l">12</div></div>
              <div class="bc"><div class="bar b-gr" style="height:40%"></div><div class="bc-l">14</div></div>
              <div class="bc"><div class="bar b-gr" style="height:85%"></div><div class="bc-l">16</div></div>
              <div class="bc"><div class="bar b-gr" style="height:60%"></div><div class="bc-l">18</div></div>
              <div class="bc"><div class="bar b-gr" style="height:75%"></div><div class="bc-l">20</div></div>
              <div class="bc"><div class="bar b-gr" style="height:55%"></div><div class="bc-l">22</div></div>
              <div class="bc"><div class="bar b-gr" style="height:92%"></div><div class="bc-l">24</div></div>
              <div class="bc"><div class="bar b-go" style="height:76%"></div><div class="bc-l">26</div></div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="c-hd"><span class="c-t">Métodos de pagamento</span></div>
          <div class="c-bd" style="display:flex;flex-direction:column;gap:.8rem">
            <div><div style="display:flex;justify-content:space-between;font-size:.76rem;margin-bottom:.3rem"><span>PIX</span><strong>48% — R$7.795</strong></div><div class="prog"><div class="pf" style="width:48%"></div></div></div>
            <div><div style="display:flex;justify-content:space-between;font-size:.76rem;margin-bottom:.3rem"><span>Cartão crédito</span><strong>38% — R$6.171</strong></div><div class="prog"><div class="pf" style="width:38%;background:var(--purple)"></div></div></div>
            <div><div style="display:flex;justify-content:space-between;font-size:.76rem;margin-bottom:.3rem"><span>Boleto</span><strong>10% — R$1.624</strong></div><div class="prog"><div class="pf" style="width:10%;background:var(--orange)"></div></div></div>
            <div><div style="display:flex;justify-content:space-between;font-size:.76rem;margin-bottom:.3rem"><span>Cartão débito</span><strong>4% — R$650</strong></div><div class="prog"><div class="pf" style="width:4%;background:var(--green)"></div></div></div>
          </div>
        </div>
      </div>

      <div class="card mb">
        <div class="c-hd">
          <span class="c-t">Transações recentes</span>
          <div class="tb-search"><span>🔍</span><input type="text" placeholder="Buscar transação..."/></div>
        </div>
        <div style="overflow-x:auto">
          <table class="tbl">
            <thead><tr><th>ID</th><th>Cliente</th><th>Pedido</th><th>Método</th><th>Valor</th><th>Status</th><th>Data</th><th>Ação</th></tr></thead>
            <tbody>
              <tr><td><code style="font-size:.72rem;color:var(--muted)">TXN-8821</code></td><td><div class="cell"><div class="av">AS</div><div><h5>Ana Silva</h5></div></div></td><td>#1047</td><td><span class="pill p-bl">PIX</span></td><td><strong>R$298,00</strong></td><td><span class="pill p-go">⏳ Pendente</span></td><td>Hoje 14h22</td><td><div class="acts"><button class="ab ab-gr btn-sm" onclick="showToast('Pagamento confirmado ✅','ok')">Confirmar</button></div></td></tr>
              <tr><td><code style="font-size:.72rem;color:var(--muted)">TXN-8820</code></td><td><div class="cell"><div class="av">CM</div><div><h5>Carla M.</h5></div></div></td><td>#1046</td><td><span class="pill p-pu">💳 Cartão</span></td><td><strong>R$45,00</strong></td><td><span class="pill p-gr">✅ Aprovado</span></td><td>Hoje 11h05</td><td><div class="acts"><button class="ab ab-vw btn-sm">Detalhes</button></div></td></tr>
              <tr><td><code style="font-size:.72rem;color:var(--muted)">TXN-8819</code></td><td><div class="cell"><div class="av">BL</div><div><h5>Beatriz L.</h5></div></div></td><td>#1045</td><td><span class="pill p-bl">PIX</span></td><td><strong>R$148,00</strong></td><td><span class="pill p-gr">✅ Aprovado</span></td><td>Ontem</td><td><div class="acts"><button class="ab ab-vw btn-sm">Detalhes</button></div></td></tr>
              <tr><td><code style="font-size:.72rem;color:var(--muted)">TXN-8818</code></td><td><div class="cell"><div class="av">FC</div><div><h5>Fernanda C.</h5></div></div></td><td>#1044</td><td><span class="pill p-or">📄 Boleto</span></td><td><strong>R$179,00</strong></td><td><span class="pill p-go">⏳ Pendente</span></td><td>Ontem</td><td><div class="acts"><button class="ab ab-gr btn-sm" onclick="showToast('Pagamento confirmado ✅','ok')">Confirmar</button></div></td></tr>
              <tr><td><code style="font-size:.72rem;color:var(--muted)">TXN-8817</code></td><td><div class="cell"><div class="av">JR</div><div><h5>Juliana R.</h5></div></div></td><td>#1043</td><td><span class="pill p-pu">💳 Cartão</span></td><td><strong>R$156,00</strong></td><td><span class="pill p-gr">✅ Aprovado</span></td><td>24/02</td><td><div class="acts"><button class="ab ab-vw btn-sm">Detalhes</button></div></td></tr>
              <tr><td><code style="font-size:.72rem;color:var(--muted)">TXN-8816</code></td><td><div class="cell"><div class="av">RM</div><div><h5>Roberta M.</h5></div></div></td><td>#1042</td><td><span class="pill p-pu">💳 Cartão</span></td><td><strong>R$89,00</strong></td><td><span class="pill p-re">❌ Recusado</span></td><td>23/02</td><td><div class="acts"><button class="ab ab-dl btn-sm">Cancelar</button></div></td></tr>
              <tr><td><code style="font-size:.72rem;color:var(--muted)">TXN-8815</code></td><td><div class="cell"><div class="av">PM</div><div><h5>Paula M.</h5></div></div></td><td>#1041</td><td><span class="pill p-bl">PIX</span></td><td><strong>R$228,00</strong></td><td><span class="pill p-gr">✅ Aprovado</span></td><td>22/02</td><td><div class="acts"><button class="ab ab-vw btn-sm">Detalhes</button></div></td></tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="pag">
        <button class="pg-b">‹</button>
        <button class="pg-b on">1</button><button class="pg-b">2</button><button class="pg-b">3</button>
        <button class="pg-b">›</button>
      </div>
    </div>


    <!-- ═══════════════════════════════
         PRODUTOS
    ═══════════════════════════════ -->
    <div class="pg" id="pg-produtos">
      <div class="ph">
        <div><h2>Produtos</h2><p>Catálogo completo da loja</p></div>
        <div style="display:flex;gap:.6rem;align-items:center">
          <div class="tabs">
            <button class="tab on" onclick="stab(this)">Todos (157)</button>
            <button class="tab" onclick="stab(this)">Colares (48)</button>
            <button class="tab" onclick="stab(this)">Pulseiras (62)</button>
            <button class="tab" onclick="stab(this)">Brincos (35)</button>
            <button class="tab" onclick="stab(this)">Kits (12)</button>
          </div>
          <div class="tb-search"><span>🔍</span><input type="text" placeholder="Buscar..."/></div>
        </div>
      </div>

      <div class="g4 mb">
        <div class="kpi bl"><div class="k-ic ic-bl">💎</div><div class="k-lb">Produtos ativos</div><div class="k-vl">142</div><div class="k-dt up">de 157 total</div></div>
        <div class="kpi go"><div class="k-ic ic-go">🏆</div><div class="k-lb">Mais vendido</div><div class="k-vl">Pulseira Ondas</div><div class="k-dt neu">52 unidades</div></div>
        <div class="kpi re"><div class="k-ic ic-re">⚠️</div><div class="k-lb">Estoque crítico</div><div class="k-vl">3</div><div class="k-dt dn">produtos</div></div>
        <div class="kpi gr"><div class="k-ic ic-gr">📊</div><div class="k-lb">Receita catálogo</div><div class="k-vl">R$18.420</div><div class="k-dt up">este mês</div></div>
      </div>

      <div class="card mb">
        <div style="overflow-x:auto">
          <table class="tbl">
            <thead><tr><th>Produto</th><th>Categoria</th><th>Preço</th><th>Estoque</th><th>Vendas mês</th><th>Receita</th><th>Status</th><th>Ações</th></tr></thead>
            <tbody>
              <tr><td><div class="cell"><div class="th t1">🐚</div><div><h5>Colar Concha do Mar</h5><p>SKU: WAV-001</p></div></div></td><td>Colares</td><td><strong>R$89,00</strong></td><td><div style="display:flex;align-items:center;gap:.5rem">24<div class="prog" style="width:50px"><div class="pf" style="width:60%"></div></div></div></td><td>22 un.</td><td><strong style="color:var(--green)">R$1.958</strong></td><td><span class="pill p-gr">Ativo</span></td><td><div class="acts"><button class="ab ab-ed">✏️ Editar</button><button class="ab ab-dl">🗑</button></div></td></tr>
              <tr><td><div class="cell"><div class="th t2">🌊</div><div><h5>Pulseira Ondas</h5><p>SKU: WAV-002</p></div></div></td><td>Pulseiras</td><td><strong>R$45,00</strong></td><td><div style="display:flex;align-items:center;gap:.5rem">40<div class="prog" style="width:50px"><div class="pf" style="width:80%"></div></div></div></td><td>52 un.</td><td><strong style="color:var(--green)">R$2.340</strong></td><td><span class="pill p-gr">Ativo</span></td><td><div class="acts"><button class="ab ab-ed">✏️ Editar</button><button class="ab ab-dl">🗑</button></div></td></tr>
              <tr><td><div class="cell"><div class="th t3">🌺</div><div><h5>Brinco Flor Azul</h5><p>SKU: WAV-003</p></div></div></td><td>Brincos</td><td><strong>R$59,00</strong></td><td><div style="display:flex;align-items:center;gap:.5rem"><span style="color:var(--red);font-weight:700">2</span><div class="prog" style="width:50px"><div class="pf" style="width:4%;background:var(--red)"></div></div></div></td><td>21 un.</td><td><strong style="color:var(--green)">R$1.239</strong></td><td><span class="pill p-re">Estoque baixo</span></td><td><div class="acts"><button class="ab ab-ed">✏️ Editar</button><button class="ab ab-dl">🗑</button></div></td></tr>
              <tr><td><div class="cell"><div class="th t4">✨</div><div><h5>Kit Verão Wave</h5><p>SKU: WAV-004</p></div></div></td><td>Kits</td><td><strong>R$149,00</strong></td><td><div style="display:flex;align-items:center;gap:.5rem">15<div class="prog" style="width:50px"><div class="pf" style="width:30%"></div></div></div></td><td>12 un.</td><td><strong style="color:var(--green)">R$1.788</strong></td><td><span class="pill p-gr">Ativo</span></td><td><div class="acts"><button class="ab ab-ed">✏️ Editar</button><button class="ab ab-dl">🗑</button></div></td></tr>
              <tr><td><div class="cell"><div class="th t5">⭐</div><div><h5>Colar Estrela do Mar</h5><p>SKU: WAV-005</p></div></div></td><td>Colares</td><td><strong>R$102,00</strong></td><td><div style="display:flex;align-items:center;gap:.5rem">18<div class="prog" style="width:50px"><div class="pf" style="width:45%"></div></div></div></td><td>9 un.</td><td><strong style="color:var(--muted)">R$918</strong></td><td><span class="pill p-gy">Inativo</span></td><td><div class="acts"><button class="ab ab-ed">✏️ Editar</button><button class="ab ab-dl">🗑</button></div></td></tr>
              <tr><td><div class="cell"><div class="th t6">☀️</div><div><h5>Brinco Sol de Verão</h5><p>SKU: WAV-006</p></div></div></td><td>Brincos</td><td><strong>R$78,00</strong></td><td><div style="display:flex;align-items:center;gap:.5rem">31<div class="prog" style="width:50px"><div class="pf" style="width:62%"></div></div></div></td><td>14 un.</td><td><strong style="color:var(--green)">R$1.092</strong></td><td><span class="pill p-gr">Ativo</span></td><td><div class="acts"><button class="ab ab-ed">✏️ Editar</button><button class="ab ab-dl">🗑</button></div></td></tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="pag">
        <button class="pg-b">‹</button>
        <button class="pg-b on">1</button><button class="pg-b">2</button><button class="pg-b">3</button><button class="pg-b">…</button><button class="pg-b">13</button>
        <button class="pg-b">›</button>
      </div>
    </div>


    <!-- ═══════════════════════════════
         ESTOQUE
    ═══════════════════════════════ -->
    <div class="pg" id="pg-estoque">
      <div class="ph">
        <div><h2>Estoque</h2><p>Inventário em tempo real</p></div>
        <button class="btn-s btn-sm" onclick="showToast('Relatório exportado! 📊','ok')">⬇ Exportar relatório</button>
      </div>

      <div class="g4 mb">
        <div class="kpi bl"><div class="k-ic ic-bl">📦</div><div class="k-lb">Total em estoque</div><div class="k-vl">843</div><div class="k-dt neu">unidades</div></div>
        <div class="kpi re"><div class="k-ic ic-re">⚠️</div><div class="k-lb">Estoque crítico</div><div class="k-vl" style="color:var(--red)">3</div><div class="k-dt dn">abaixo do mínimo</div></div>
        <div class="kpi go"><div class="k-ic ic-go">🔄</div><div class="k-lb">Giro médio</div><div class="k-vl">18 dias</div><div class="k-dt up">saudável</div></div>
        <div class="kpi gr"><div class="k-ic ic-gr">💵</div><div class="k-lb">Valor em estoque</div><div class="k-vl">R$42k</div><div class="k-dt neu">custo total</div></div>
      </div>

      <div class="g2 mb">
        <!-- Críticos -->
        <div class="card">
          <div class="c-hd"><span class="c-t">🔴 Reposição urgente</span></div>
          <div style="overflow-x:auto">
            <table class="tbl">
              <thead><tr><th>Produto</th><th>Estoque</th><th>Mínimo</th><th>Urgência</th></tr></thead>
              <tbody>
                <tr><td><div class="cell"><div class="th t3">🌺</div><div><h5>Brinco Flor Azul</h5></div></div></td><td><strong style="color:var(--red)">2 un.</strong></td><td>5</td><td><span class="pill p-re">🔴 Crítico</span></td></tr>
                <tr><td><div class="cell"><div class="th t5">⭐</div><div><h5>Colar Estrela do Mar</h5></div></div></td><td><strong style="color:var(--orange)">4 un.</strong></td><td>5</td><td><span class="pill p-or">🟡 Baixo</span></td></tr>
                <tr><td><div class="cell"><div class="th t4">✨</div><div><h5>Kit Noite de Verão</h5></div></div></td><td><strong style="color:var(--orange)">4 un.</strong></td><td>5</td><td><span class="pill p-or">🟡 Baixo</span></td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Nível de estoque geral -->
        <div class="card">
          <div class="c-hd"><span class="c-t">Nível por produto</span></div>
          <div class="c-bd" style="display:flex;flex-direction:column;gap:.85rem">
            <div><div style="display:flex;justify-content:space-between;font-size:.76rem;margin-bottom:.3rem"><span>🐚 Colar Concha</span><strong>24 un.</strong></div><div class="prog"><div class="pf" style="width:60%"></div></div></div>
            <div><div style="display:flex;justify-content:space-between;font-size:.76rem;margin-bottom:.3rem"><span>🌊 Pulseira Ondas</span><strong>40 un.</strong></div><div class="prog"><div class="pf" style="width:80%"></div></div></div>
            <div><div style="display:flex;justify-content:space-between;font-size:.76rem;margin-bottom:.3rem"><span>🌺 Brinco Flor Azul</span><strong style="color:var(--red)">2 un.</strong></div><div class="prog"><div class="pf" style="width:4%;background:var(--red)"></div></div></div>
            <div><div style="display:flex;justify-content:space-between;font-size:.76rem;margin-bottom:.3rem"><span>✨ Kit Verão Wave</span><strong>15 un.</strong></div><div class="prog"><div class="pf" style="width:30%"></div></div></div>
            <div><div style="display:flex;justify-content:space-between;font-size:.76rem;margin-bottom:.3rem"><span>⭐ Colar Estrela</span><strong style="color:var(--orange)">4 un.</strong></div><div class="prog"><div class="pf" style="width:8%;background:var(--orange)"></div></div></div>
            <div><div style="display:flex;justify-content:space-between;font-size:.76rem;margin-bottom:.3rem"><span>☀️ Brinco Sol</span><strong>31 un.</strong></div><div class="prog"><div class="pf" style="width:62%"></div></div></div>
          </div>
        </div>
      </div>

      <!-- Histórico de movimentações -->
      <div class="card mb">
        <div class="c-hd"><span class="c-t">📋 Movimentações recentes</span></div>
        <div style="overflow-x:auto">
          <table class="tbl">
            <thead><tr><th>Data</th><th>Produto</th><th>Tipo</th><th>Qtd.</th><th>Responsável</th><th>Saldo após</th></tr></thead>
            <tbody>
              <tr><td>Hoje 14h22</td><td><div class="cell"><div class="th t2">🌊</div><div><h5>Pulseira Ondas</h5></div></div></td><td><span class="pill p-re">Saída (venda)</span></td><td>-1</td><td>Sistema</td><td><strong>40 un.</strong></td></tr>
              <tr><td>Hoje 11h05</td><td><div class="cell"><div class="th t3">🌺</div><div><h5>Brinco Flor Azul</h5></div></div></td><td><span class="pill p-re">Saída (venda)</span></td><td>-1</td><td>Sistema</td><td><strong style="color:var(--red)">2 un.</strong></td></tr>
              <tr><td>Ontem</td><td><div class="cell"><div class="th t1">🐚</div><div><h5>Colar Concha do Mar</h5></div></div></td><td><span class="pill p-gr">Entrada</span></td><td>+10</td><td>Admin</td><td><strong>24 un.</strong></td></tr>
              <tr><td>24/02</td><td><div class="cell"><div class="th t4">✨</div><div><h5>Kit Verão Wave</h5></div></div></td><td><span class="pill p-re">Saída (venda)</span></td><td>-2</td><td>Sistema</td><td><strong>15 un.</strong></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>


    <!-- ═══════════════════════════════
         LEADS
    ═══════════════════════════════ -->
    <div class="pg" id="pg-leads">
      <div class="ph">
        <div><h2>Leads</h2><p>Contatos captados e funil de conversão</p></div>
        <button class="btn-p btn-sm" onclick="showToast('Exportando leads... 📤','ok')">⬇ Exportar CSV</button>
      </div>

      <div class="g4 mb">
        <div class="kpi bl"><div class="k-ic ic-bl">🎯</div><div class="k-lb">Total de leads</div><div class="k-vl">524</div><div class="k-dt up">▲ +63 este mês</div></div>
        <div class="kpi go"><div class="k-ic ic-go">💬</div><div class="k-lb">Em contato</div><div class="k-vl">87</div><div class="k-dt up">▲ +12</div></div>
        <div class="kpi gr"><div class="k-ic ic-gr">🛒</div><div class="k-lb">Convertidos</div><div class="k-vl">65</div><div class="k-dt up">12,4% de taxa</div></div>
        <div class="kpi pu"><div class="k-ic ic-pu">📧</div><div class="k-lb">Newsletter ativos</div><div class="k-vl">389</div><div class="k-dt up">▲ +28</div></div>
      </div>

      <div class="g2 mb">
        <!-- Funil -->
        <div class="card">
          <div class="c-hd"><span class="c-t">Funil de conversão</span></div>
          <div class="c-bd" style="display:flex;flex-direction:column;gap:.9rem">
            <div>
              <div style="display:flex;justify-content:space-between;font-size:.76rem;margin-bottom:.3rem"><span>👁 Visitantes</span><strong>4.280</strong></div>
              <div class="prog" style="height:8px"><div class="pf" style="width:100%;background:#dde4ec"></div></div>
            </div>
            <div>
              <div style="display:flex;justify-content:space-between;font-size:.76rem;margin-bottom:.3rem"><span>🎯 Leads captados</span><strong>524 (12,2%)</strong></div>
              <div class="prog" style="height:8px"><div class="pf" style="width:12.2%;background:var(--blue)"></div></div>
            </div>
            <div>
              <div style="display:flex;justify-content:space-between;font-size:.76rem;margin-bottom:.3rem"><span>💬 Em contato</span><strong>87 (16,6%)</strong></div>
              <div class="prog" style="height:8px"><div class="pf" style="width:16.6%;background:var(--gold)"></div></div>
            </div>
            <div>
              <div style="display:flex;justify-content:space-between;font-size:.76rem;margin-bottom:.3rem"><span>🛒 Convertidos em venda</span><strong>65 (12,4%)</strong></div>
              <div class="prog" style="height:8px"><div class="pf" style="width:12.4%;background:var(--green)"></div></div>
            </div>
          </div>
        </div>

        <!-- Origens -->
        <div class="card">
          <div class="c-hd"><span class="c-t">📍 Origem dos leads</span></div>
          <div class="c-bd">
            <div class="bc-wrap" style="height:120px;margin-bottom:.7rem">
              <div class="bc"><div class="bar b-bl" style="height:42%"></div><div class="bc-l">Newsletter</div></div>
              <div class="bc"><div class="bar b-go" style="height:28%"></div><div class="bc-l">Instagram</div></div>
              <div class="bc"><div class="bar b-gr" style="height:18%"></div><div class="bc-l">WhatsApp</div></div>
              <div class="bc"><div class="bar" style="height:12%;background:linear-gradient(180deg,#d0c0f0,var(--purple))"></div><div class="bc-l">Google</div></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabela de leads -->
      <div class="card mb">
        <div class="c-hd">
          <span class="c-t">Lista de leads</span>
          <div class="tb-search"><span>🔍</span><input type="text" placeholder="Buscar lead..."/></div>
        </div>
        <div style="overflow-x:auto">
          <table class="tbl">
            <thead><tr><th>Nome</th><th>E-mail</th><th>Origem</th><th>Interesse</th><th>Status</th><th>Captado em</th><th>Ação</th></tr></thead>
            <tbody>
              <tr><td><div class="cell"><div class="av">MO</div><div><h5>Maria Oliveira</h5></div></div></td><td style="color:var(--muted)">maria@email.com</td><td><span class="pill p-bl">Newsletter</span></td><td>Colares</td><td><span class="pill p-go">Novo</span></td><td>Hoje</td><td><div class="acts"><button class="ab ab-gr btn-sm" onclick="showToast('WhatsApp aberto! 💬','ok')">Contatar</button></div></td></tr>
              <tr><td><div class="cell"><div class="av">CR</div><div><h5>Camila R.</h5></div></div></td><td style="color:var(--muted)">camila@email.com</td><td><span class="pill p-pu">Instagram</span></td><td>Pulseiras</td><td><span class="pill p-bl">Em contato</span></td><td>Hoje</td><td><div class="acts"><button class="ab ab-gr btn-sm">Contatar</button></div></td></tr>
              <tr><td><div class="cell"><div class="av">TS</div><div><h5>Tatiane S.</h5></div></div></td><td style="color:var(--muted)">tati@email.com</td><td><span class="pill p-gr">WhatsApp</span></td><td>Kits</td><td><span class="pill p-gr">✅ Convertido</span></td><td>Ontem</td><td><div class="acts"><button class="ab ab-vw btn-sm">Ver pedido</button></div></td></tr>
              <tr><td><div class="cell"><div class="av">AP</div><div><h5>Amanda P.</h5></div></div></td><td style="color:var(--muted)">amanda@email.com</td><td><span class="pill p-go">Google</span></td><td>Brincos</td><td><span class="pill p-go">Novo</span></td><td>Ontem</td><td><div class="acts"><button class="ab ab-gr btn-sm">Contatar</button></div></td></tr>
              <tr><td><div class="cell"><div class="av">LF</div><div><h5>Larissa F.</h5></div></div></td><td style="color:var(--muted)">larissa@email.com</td><td><span class="pill p-bl">Newsletter</span></td><td>Colares</td><td><span class="pill p-gy">Inativo</span></td><td>22/02</td><td><div class="acts"><button class="ab ab-ed btn-sm">Reativar</button></div></td></tr>
              <tr><td><div class="cell"><div class="av">RS</div><div><h5>Renata S.</h5></div></div></td><td style="color:var(--muted)">renata@email.com</td><td><span class="pill p-pu">Instagram</span></td><td>Pulseiras</td><td><span class="pill p-bl">Em contato</span></td><td>21/02</td><td><div class="acts"><button class="ab ab-gr btn-sm">Contatar</button></div></td></tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="pag">
        <button class="pg-b">‹</button>
        <button class="pg-b on">1</button><button class="pg-b">2</button><button class="pg-b">3</button>
        <button class="pg-b">›</button>
      </div>
    </div>


    <!-- ═══════════════════════════════
         CONFIGURAÇÕES
    ═══════════════════════════════ -->
    <div class="pg" id="pg-config">
      <div class="ph">
        <div><h2>Configurações</h2><p>Preferências do sistema</p></div>
      </div>

      <div class="g2 mb" style="align-items:start">
        <div style="display:flex;flex-direction:column;gap:1.3rem">

          <!-- Info da loja -->
          <div class="card">
            <div class="c-hd"><span class="c-t">🏪 Dados da loja</span></div>
            <div class="c-bd" style="display:flex;flex-direction:column;gap:.55rem">
              <div style="display:flex;justify-content:space-between;padding:.55rem 0;border-bottom:1px solid var(--border)"><span style="font-size:.78rem;color:var(--muted)">Nome</span><strong style="font-size:.82rem">Wave Acessórios</strong></div>
              <div style="display:flex;justify-content:space-between;padding:.55rem 0;border-bottom:1px solid var(--border)"><span style="font-size:.78rem;color:var(--muted)">E-mail</span><span style="font-size:.82rem">contato@wave.com.br</span></div>
              <div style="display:flex;justify-content:space-between;padding:.55rem 0;border-bottom:1px solid var(--border)"><span style="font-size:.78rem;color:var(--muted)">WhatsApp</span><span style="font-size:.82rem">+55 21 99999-9999</span></div>
              <div style="display:flex;justify-content:space-between;padding:.55rem 0;border-bottom:1px solid var(--border)"><span style="font-size:.78rem;color:var(--muted)">Slogan</span><span style="font-size:.82rem;font-style:italic">Sinta a vibe, viva o estilo.</span></div>
              <div style="display:flex;justify-content:space-between;padding:.55rem 0"><span style="font-size:.78rem;color:var(--muted)">Frete grátis acima de</span><strong style="font-size:.82rem">R$199,00</strong></div>
              <div style="margin-top:.4rem"><button class="btn-s btn-sm" style="width:100%" onclick="showToast('Abrindo editor... ✏️','ok')">✏️ Editar dados da loja</button></div>
            </div>
          </div>

          <!-- Pagamentos aceitos -->
          <div class="card">
            <div class="c-hd"><span class="c-t">💳 Pagamentos aceitos</span></div>
            <div class="c-bd" style="display:flex;flex-direction:column;gap:.3rem">
              <div class="tg-r"><button class="tg on" onclick="this.classList.toggle('on')"></button><span class="tg-l">PIX</span></div>
              <div class="tg-r"><button class="tg on" onclick="this.classList.toggle('on')"></button><span class="tg-l">Cartão de crédito (até 6x)</span></div>
              <div class="tg-r"><button class="tg on" onclick="this.classList.toggle('on')"></button><span class="tg-l">Boleto bancário</span></div>
              <div class="tg-r"><button class="tg" onclick="this.classList.toggle('on')"></button><span class="tg-l">Cartão de débito</span></div>
              <div style="margin-top:.4rem"><button class="btn-p btn-sm" onclick="showToast('Configurações salvas! ✅','ok')">Salvar</button></div>
            </div>
          </div>

          <!-- Links rápidos -->
          <div class="card">
            <div class="c-hd"><span class="c-t">🌐 Páginas da loja</span></div>
            <div class="c-bd" style="display:flex;flex-direction:column;gap:.5rem">
              <a href="waves.html" target="_blank" style="display:flex;align-items:center;gap:.7rem;padding:.65rem .85rem;border-radius:.7rem;background:var(--bg);transition:background .18s" onmouseover="this.style.background='var(--blue-xs)'" onmouseout="this.style.background='var(--bg)'">
                <span>🏠</span><div style="flex:1"><div style="font-size:.8rem;font-weight:600">Página Inicial</div><div style="font-size:.66rem;color:var(--muted)">waves.html</div></div><span style="font-size:.8rem;color:var(--muted)">↗</span>
              </a>
              <a href="catalogo.html" target="_blank" style="display:flex;align-items:center;gap:.7rem;padding:.65rem .85rem;border-radius:.7rem;background:var(--bg);transition:background .18s" onmouseover="this.style.background='var(--blue-xs)'" onmouseout="this.style.background='var(--bg)'">
                <span>🛍️</span><div style="flex:1"><div style="font-size:.8rem;font-weight:600">Catálogo</div><div style="font-size:.66rem;color:var(--muted)">catalogo.html</div></div><span style="font-size:.8rem;color:var(--muted)">↗</span>
              </a>
            </div>
          </div>
        </div>

        <div style="display:flex;flex-direction:column;gap:1.3rem">

          <!-- Notificações -->
          <div class="card">
            <div class="c-hd"><span class="c-t">🔔 Alertas e notificações</span></div>
            <div class="c-bd" style="display:flex;flex-direction:column;gap:.3rem">
              <div class="tg-r"><button class="tg on" onclick="this.classList.toggle('on')"></button><span class="tg-l">Novos pedidos por e-mail</span></div>
              <div class="tg-r"><button class="tg on" onclick="this.classList.toggle('on')"></button><span class="tg-l">Alertas de estoque crítico</span></div>
              <div class="tg-r"><button class="tg" onclick="this.classList.toggle('on')"></button><span class="tg-l">Resumo diário por e-mail</span></div>
              <div class="tg-r"><button class="tg on" onclick="this.classList.toggle('on')"></button><span class="tg-l">Avaliações de clientes</span></div>
              <div class="tg-r"><button class="tg on" onclick="this.classList.toggle('on')"></button><span class="tg-l">Novos leads captados</span></div>
              <div class="tg-r"><button class="tg" onclick="this.classList.toggle('on')"></button><span class="tg-l">Relatório semanal</span></div>
              <div style="margin-top:.4rem"><button class="btn-p btn-sm" onclick="showToast('Preferências salvas! ✅','ok')">Salvar</button></div>
            </div>
          </div>

          <!-- Sessão / Segurança -->
          <div class="card">
            <div class="c-hd"><span class="c-t">🔐 Sessão ativa</span></div>
            <div class="c-bd" style="display:flex;flex-direction:column;gap:.55rem">
              <div style="display:flex;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid var(--border)"><span style="font-size:.78rem;color:var(--muted)">Usuário</span><strong style="font-size:.8rem">admin@wave.com.br</strong></div>
              <div style="display:flex;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid var(--border)"><span style="font-size:.78rem;color:var(--muted)">Perfil</span><span class="pill p-go">Administrador</span></div>
              <div style="display:flex;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid var(--border)"><span style="font-size:.78rem;color:var(--muted)">Último acesso</span><span style="font-size:.8rem">Hoje 14h10</span></div>
              <div style="display:flex;justify-content:space-between;padding:.5rem 0"><span style="font-size:.78rem;color:var(--muted)">IP</span><span style="font-size:.8rem;font-family:monospace">177.42.xxx.xxx</span></div>
              <div style="display:flex;gap:.5rem;margin-top:.4rem">
                <button class="btn-s btn-sm" style="flex:1" onclick="showToast('Senha enviada por e-mail 📧','ok')">Alterar senha</button>
                <button class="btn-s btn-sm" style="flex:1;color:var(--red);border-color:var(--red)" onclick="doLogout()">Sair</button>
              </div>
            </div>
          </div>

          <!-- Sistema -->
          <div class="card">
            <div class="c-hd"><span class="c-t">🖥️ Sistema</span></div>
            <div class="c-bd" style="display:flex;flex-direction:column;gap:.55rem">
              <div style="display:flex;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid var(--border)"><span style="font-size:.78rem;color:var(--muted)">Versão</span><span class="pill p-gr">v2.1.0 — atualizado</span></div>
              <div style="display:flex;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid var(--border)"><span style="font-size:.78rem;color:var(--muted)">Ambiente</span><span class="pill p-bl">Produção</span></div>
              <div style="display:flex;justify-content:space-between;padding:.5rem 0"><span style="font-size:.78rem;color:var(--muted)">Último backup</span><span style="font-size:.8rem">Hoje 00h00</span></div>
            </div>
          </div>

        </div>
      </div>
    </div>

  </div><!-- /main -->
</div><!-- /app -->


<!-- MODAL NOTIFICAÇÕES -->
<div class="m-bg" id="notifM" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="modal">
    <div class="m-hd">
      <span class="m-t">🔔 Notificações</span>
      <button class="m-cl" onclick="document.getElementById('notifM').classList.remove('open')">✕</button>
    </div>
    <div class="fi"><div class="fi-ic" style="background:var(--red-lt)">⚠️</div><div class="fi-t"><h5>Estoque crítico</h5><p>Brinco Flor Azul — 2 un. restantes</p></div><div class="fi-tm">1h</div></div>
    <div class="fi"><div class="fi-ic" style="background:var(--green-lt)">🛒</div><div class="fi-t"><h5>Novo pedido #1047</h5><p>Ana Silva — R$298,00</p></div><div class="fi-tm">2h</div></div>
    <div class="fi"><div class="fi-ic" style="background:var(--gold-lt)">⭐</div><div class="fi-t"><h5>Avaliação 5★</h5><p>"Amei o produto!" — Júlia M.</p></div><div class="fi-tm">3h</div></div>
    <div class="fi"><div class="fi-ic" style="background:var(--blue-xs)">🎯</div><div class="fi-t"><h5>12 novos leads este mês</h5><p>Newsletter + Instagram</p></div><div class="fi-tm">5h</div></div>
    <div class="fi"><div class="fi-ic" style="background:var(--purple-lt)">💳</div><div class="fi-t"><h5>5 pagamentos pendentes</h5><p>Total: R$1.840 aguardando</p></div><div class="fi-tm">6h</div></div>
    <div style="margin-top:1rem"><button class="btn-s btn-sm" style="width:100%" onclick="document.getElementById('notifM').classList.remove('open')">Marcar todas como lidas</button></div>
  </div>
</div>

<!-- TOAST -->
<div class="toast" id="toast"></div>

<script>
  /* LOGIN */
  function doLogin() {
    const e = document.getElementById('lEmail').value;
    const p = document.getElementById('lPass').value;
    if (!e || !p) { showToast('Preencha os campos ❌','er'); return; }
    document.getElementById('loginPage').style.display = 'none';
    document.getElementById('app').classList.add('show');
    showToast('Bem-vindo ao painel! 👋','ok');
  }
  function doLogout() {
    document.getElementById('app').classList.remove('show');
    document.getElementById('loginPage').style.display = 'flex';
  }
  function togglePass() {
    const i = document.getElementById('lPass');
    i.type = i.type === 'password' ? 'text' : 'password';
  }
  document.getElementById('lPass').addEventListener('keydown', e => { if(e.key==='Enter') doLogin(); });

  /* NAVEGAÇÃO */
  const titles = { dashboard:'Dashboard', pagamentos:'Pagamentos', produtos:'Produtos', estoque:'Estoque', leads:'Leads', config:'Configurações' };
  function go(page, btn) {
    document.querySelectorAll('.pg').forEach(p => p.classList.remove('on'));
    document.getElementById('pg-'+page).classList.add('on');
    document.querySelectorAll('.sb-btn').forEach(b => b.classList.remove('on'));
    if (btn) btn.classList.add('on');
    else document.querySelectorAll('.sb-btn').forEach(b => { if(b.textContent.trim().toLowerCase().startsWith(titles[page]?.toLowerCase().slice(0,5))) b.classList.add('on'); });
    document.getElementById('pgTitle').textContent = titles[page] || page;
    document.getElementById('sb').classList.remove('open');
    window.scrollTo({top:0,behavior:'smooth'});
  }

  /* TABS */
  document.querySelectorAll('.tabs').forEach(wrap => {
    wrap.querySelectorAll('.tab').forEach(btn => {
      btn.addEventListener('click', () => {
        wrap.querySelectorAll('.tab').forEach(b => b.classList.remove('on'));
        btn.classList.add('on');
      });
    });
  });
  function stab(btn) { /* handled above */ }

  /* TOAST */
  function showToast(msg, type='ok') {
    const t = document.getElementById('toast');
    t.textContent = msg; t.className = 'toast '+type;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2800);
  }

  /* PAGINAÇÃO */
  document.querySelectorAll('.pag').forEach(pg => {
    pg.querySelectorAll('.pg-b').forEach((btn, i, all) => {
      if (i === 0 || i === all.length - 1) return;
      btn.addEventListener('click', () => {
        pg.querySelectorAll('.pg-b').forEach(b => b.classList.remove('on'));
        btn.classList.add('on');
      });
    });
  });

  /* MOBILE */
  const mq = window.matchMedia('(max-width:860px)');
  const applyMq = e => { document.getElementById('mbtn').style.display = e.matches ? 'flex' : 'none'; };
  applyMq(mq); mq.addEventListener('change', applyMq);
</script>
</body>
</html>