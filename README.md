# 🍔 Nampula Fodd
### O iFood de Nampula e Nacala

Plataforma web completa de delivery de comida desenvolvida com **HTML, CSS, JavaScript, PHP e MySQL**.

---

## 📁 Estrutura de Pastas

```
nampula-fodd/
├── admin/                  # Painel do Administrador
│   ├── dashboard.php
│   ├── carteira.php        # ★ Minha Carteira (comissões 10%)
│   └── pedidos.php
├── cliente/
│   ├── carrinho.php
│   ├── carteira.php        # E-Wallet (M-Pesa/E-Mola simulado)
│   ├── pedidos.php
│   └── adicionar_carrinho.php
├── restaurante/
│   ├── dashboard.php
│   ├── cardapio.php        # CRUD completo
│   └── criar_restaurante.php
├── config/
│   └── database.php
├── includes/
│   ├── auth.php
│   ├── header.php
│   └── footer.php
├── css/
│   └── style.css
├── js/
│   └── main.js
├── uploads/                # Fotos de restaurantes e pratos
├── database.sql            # Script completo da BD
├── index.php               # Página inicial
├── login.php
├── registo.php
├── logout.php
├── restaurante.php         # Cardápio público
└── README.md
```

---

## 🚀 Como Instalar (Hospedagem)

### 1. Base de Dados
1. Entre no phpMyAdmin do seu hosting
2. Crie uma base de dados chamada `nampula_fodd`
3. Importe o ficheiro `database.sql`

### 2. Configuração
Abra `config/database.php` e altere:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'seu_usuario');
define('DB_PASS', 'sua_senha');
define('DB_NAME', 'nampula_fodd');
```

### 3. Upload
- Envie toda a pasta `nampula-fodd` para a pasta `public_html` (ou `www`) do seu hosting
- Certifique-se que a pasta `uploads/` tem permissão de escrita (chmod 755 ou 777)

### 4. Domínio (DNS)
- No painel do hosting, aponte o domínio para a pasta onde está o projecto
- Exemplo: `www.nampulafodd.co.mz`

---

## 👤 Contas de Demonstração

| Tipo        | Email                      | Senha    |
|-------------|----------------------------|----------|
| **Admin**   | admin@nampulafodd.co.mz    | password |
| **Cliente** | cliente@teste.com          | password |
| **Restaurante** | maria@restaurante.com  | password |

> A senha hash no SQL é de "password". Em produção use `password_hash()`.

---

## 💰 Monetização (o mais importante)

1. Cliente faz pedido → valor total entra no sistema
2. Sistema retém automaticamente **10%** de comissão (ADMIN)
3. Restaurante fica com **90%** e pode solicitar saque
4. No painel Admin → **Minha Carteira** vê tudo:
   - `valor_total`
   - `minha_comissao_10`
   - `valor_restaurante_90`
   - `status_saque`

---

## 🎯 Funcionalidades Implementadas

### Cliente
- [x] Cadastro e login
- [x] Lista de restaurantes com foto, status aberto/fechado
- [x] Filtro por cidade e tipo de comida
- [x] GPS "Perto de mim"
- [x] Botão "Ver no Mapa" (Google Maps)
- [x] Cardápio completo + Prato do Dia com desconto
- [x] Carrinho e checkout
- [x] Carteira Virtual (carregar via M-Pesa/E-Mola simulado)
- [x] Comentários e avaliação (só depois de comprar)

### Restaurante
- [x] Dashboard com pedidos em tempo real
- [x] Aceitar / Recusar / Preparar / Entregar
- [x] CRUD completo do cardápio
- [x] Definir Prato do Dia com desconto
- [x] Ver saldo a receber e solicitar saque

### Admin
- [x] Visão geral de tudo
- [x] Minha Carteira com comissões 10%
- [x] Gerir status de saques dos restaurantes

---

## 🎤 Frase para apresentar ao Professor

> "Professor, usei Introdução a Internet para hospedar, HTML/CSS/JS para o front-end, PHP para o back-end que liga com MySQL, e o servidor DNS para o domínio. O sistema tem três tipos de utilizador, carteira virtual, comissão automática de 10% e é totalmente responsivo."

---

## 🛠️ Tecnologias Usadas (apenas o que aprendeu)

- HTML5 + Tags semânticas
- CSS3 puro (variáveis, Flexbox, Grid, animações)
- JavaScript (DOM, Fetch API, Geolocalização)
- PHP 7+ (PDO, Sessões, password_hash)
- MySQL (relações, agregações)
- Hospedagem + DNS

**Sem React, Node, Laravel ou frameworks.**

---

Desenvolvido como trabalho final académico.  
Nampula & Nacala ❤️
