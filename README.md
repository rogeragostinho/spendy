# spendy

## 🚀 Instalação do Projeto Laravel

Siga os passos abaixo para configurar o projeto localmente:

### 1. Clonar o repositório

```bash
git clone <url-do-repositorio>
cd <nome-do-projeto>
```

### 2. Instalar dependências do PHP

```bash
composer install
```

### 3. Configurar o arquivo de ambiente

```bash
cp .env.example .env
```

### 4. Gerar a chave da aplicação

```bash
php artisan key:generate
```

### 5. Configurar a base de dados

Edite o arquivo `.env` e configure as variáveis:

```
DB_DATABASE=nome_da_base
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha
```

Em seguida, execute as migrações:

```bash
php artisan migrate
```

### 6. Instalar dependências do frontend (opcional)

Caso o projeto utilize Vite/NPM:

```bash
npm install
npm run dev
```

### 7. Iniciar o servidor

```bash
php artisan serve
```

A aplicação estará disponível em:

```
http://127.0.0.1:8000
```

---

### ⚠️ Observações

* Certifique-se de ter o PHP, Composer, Node.js e MySQL instalados.
* Caso ocorra algum erro, verifique o arquivo `.env` e as permissões da base de dados.
