# 📦 EAN API – Documentação

API desenvolvida em Laravel para cadastro, consulta e gestão de produtos com base no código EAN. A API integra com fontes externas como Cosmos, Open Food Facts e Google Books para buscar informações automaticamente.

---

## 🚀 Como usar

### ✅ Registro de Usuário
Registra um novo usuário no sistema.

**Rota:** `POST /api/register`

**Body (JSON):**

```json
{
  "name": "Nome do usuário",
  "email": "email@exemplo.com",
  "password": "senha123",
  "role": "admin"
}
🔐 Login
Autentica o usuário e retorna o token de acesso.

Rota: POST /api/login

Body (JSON):

json
Copiar
Editar
{
  "email": "email@exemplo.com",
  "password": "senha123"
}
Resposta:

json
Copiar
Editar
{
  "token": "seu_token_aqui",
  "user": { ... }
}
Use o token no Authorization Header:

makefile
Copiar
Editar
Authorization: Bearer seu_token_aqui
📦 Produtos
🔍 Buscar produto por EAN
Consulta fontes externas e cria o produto automaticamente se for encontrado.

Rota: POST /api/produto

Body:

json
Copiar
Editar
{
  "ean": "7891234567890"
}
📄 Listar todos os produtos
Rota: GET /api/produtos

📄 Ver detalhes de um produto
Rota: GET /api/produtos/{id}

➕ Cadastrar um produto manualmente
Rota: POST /api/produtos

Body (multipart/form-data):

makefile
Copiar
Editar
ean: 7891234567890
description: Produto Teste
brand: Marca
image: [arquivo .jpg/.png]
✏️ Atualizar um produto
Rota: PUT /api/produtos/{id}

Body (multipart/form-data):

makefile
Copiar
Editar
ean: 7891234567890
description: Produto Atualizado
image: [arquivo .jpg/.png]
❌ Deletar produto
Rota: DELETE /api/produtos/{id}

Permissão: Apenas admin

📤 Exportar Produtos (CSV)
Rota: GET /api/produtos/export/csv

Autenticado como: admin ou operador

📑 Logs de Atividade
🔍 Ver logs em texto
Rota: GET /api/logs

Permissão: admin

🧾 Ver logs via banco de dados (paginado)
Rota: GET /api/logs/db

Permissão: admin

👤 Dados do Usuário Logado
Rota: GET /api/user

⚙️ Testando no Postman
Faça o registro ou login para obter o token.

Vá em Authorization da requisição e selecione Bearer Token.

Cole o token retornado no login.

Teste as rotas conforme necessário.

🔐 Permissões de Acesso

Rota	Admin	Operador	Público
/register	✅	✅	✅
/login	✅	✅	✅
/produto (buscar EAN)	✅	✅	❌
/produtos (listar)	✅	✅	❌
/produtos/{id}	✅	✅	❌
/produtos (criar)	✅	✅	❌
/produtos/{id} (editar)	✅	✅	❌
/produtos/{id} (excluir)	✅	❌	❌
/produtos/export/csv	✅	✅	❌
/logs, /logs/db	✅	❌	❌
