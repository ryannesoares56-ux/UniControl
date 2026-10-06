# UniControl — Sistema de Controle de Estoque de Uniformes

O **UniControl** é um sistema web multiempresa desenvolvido para gerenciar a compra, a guarda e a entrega de uniformes aos colaboradores, eliminando controles manuais em planilhas ou papel.

---

## 🛠️ Stack Tecnológica
- **Linguagem:** PHP 8+ (utilizando PDO e Prepared Statements para total segurança contra SQL Injection)
- **Banco de Dados:** MySQL / MariaDB
- **Frontend:** HTML5, CSS3 moderno e responsivo, JavaScript puro (sem dependências de frameworks externos)

---

## 👥 Perfis de Acesso
- **Administrador:** Gerencia empresas clientes, cadastra usuários do sistema e tem acesso a todas as operações de estoque.
- **Operador:** Cadastra funcionários da empresa, peças e variações (tamanho/cor/SKU), registra movimentações (entradas/saídas/baixas) e entregas, além de consultar o dashboard e alertas de estoque baixo.

---

## 🚀 Como Executar Localmente

### 1. Pré-requisitos
- Servidor local com PHP e MySQL (ex.: XAMPP, WampServer ou MariaDB)

### 2. Configuração do Banco de Dados
1. Importe o arquivo `database/schema.sql` no MySQL (ou execute `database/setup.php` no navegador/terminal).
2. Opcionalmente, importe `database/seed.sql` para carregar dados de exemplo e usuários padrão.

### 3. Credenciais Padrão (Seed)
- **Administrador:** `admin@unicontrol.com` | Senha: `admin123`
- **Operador:** `operador@unicontrol.com` | Senha: `operador123`

---

## 📄 Especificação do Sistema
Para mais detalhes sobre requisitos funcionais e regras de negócio, consulte o arquivo [spec.pdf](spec.pdf).
