# UniControl — Sistema de Controle de Estoque de Uniformes

O **UniControl** é um sistema web corporativo multiempresa desenvolvido para gerenciar com total precisão a compra, a guarda e a entrega de uniformes e EPIs aos colaboradores, eliminando controles manuais em planilhas ou papel.

🌐 **Acesse o Sistema no Ar (Deploy):** [https://unicontrol-gu5q.onrender.com](https://unicontrol-gu5q.onrender.com)

---

## 🛠️ Stack Tecnológica
- **Linguagem:** PHP 8.2 puro com PDO e Prepared Statements (segurança total contra SQL Injection).
- **Banco de Dados:** Suporte nativo a MySQL 8.0 / MariaDB com fallback transparente para SQLite integrado.
- **Frontend:** HTML5 semântico, CSS3 moderno e responsivo, JavaScript puro (sem dependências de frameworks externos).
- **Deploy:** Container Docker no Render (Plano Free).

---

## 👥 Perfis de Acesso
- **Administrador:** Gerencia empresas clientes, cadastra usuários do sistema, redefine senhas, alterna filiais ativas e tem acesso a todas as operações de estoque.
- **Operador:** Cadastra funcionários da empresa, peças e variações (tamanho/cor/SKU), registra movimentações (entradas/saídas/baixas), realiza entregas com protocolo assinado e consulta o dashboard com alertas de reposição.
- **Visitante / Novo Usuário:** Acessa a vitrine comercial com simulador de economia (`index.php`) e pode realizar o auto-cadastro de sua empresa (`cadastro.php`).

---

## 🔑 Credenciais Fictícias de Demonstração
- **Administrador:** `admin@unicontrol.com` | Senha: `admin123`
- **Operador:** `operador@unicontrol.com` | Senha: `operador123`

---

## 📋 Documentação Oficial
- [spec.md](spec.md) — Especificação técnica final com guia de instalação passo a passo para o avaliador.
- [CHANGELOG.md](CHANGELOG.md) — Histórico detalhado de decisões, correções e revisões humanas sobre a IA.
- [.env.example](.env.example) — Modelo de variáveis de ambiente para execução local.
