# UseWave — Wave Acessórios

Loja de acessórios (PHP + MySQL). O código fica na pasta `wave/`.

**No ar:** https://usewave.fast-page.org

## Rodar localmente (XAMPP)
1. Coloque a pasta no `htdocs`.
2. Crie o banco `wave` e importe `wave/wave.sql` pelo phpMyAdmin.
3. `wave/conexao.php` já vem configurado para localhost/root sem senha.

> A senha de produção (InfinityFree) **não** fica no repositório — edite `wave/conexao.php` direto no servidor.

> O `wave.sql` público não traz as contas de admin — crie um admin pelo cadastro e mude `nivel` para `admin` no phpMyAdmin.
