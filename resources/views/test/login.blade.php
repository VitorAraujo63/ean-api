<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Login - ERP</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      padding: 2rem;
      max-width: 400px;
      margin: auto;
    }
    input {
      display: block;
      margin-bottom: 1rem;
      width: 100%;
      padding: 0.5rem;
    }
    button {
      padding: 0.5rem 1rem;
    }
    #message {
      margin-top: 1rem;
      color: red;
    }
  </style>
</head>
<body>

  <h2>Login</h2>
  <form id="loginForm">
    <input type="email" id="email" placeholder="E-mail" required>
    <input type="password" id="password" placeholder="Senha" required>
    <button type="submit">Entrar</button>
  </form>

  <div id="message"></div>

  <script>
    const form = document.getElementById('loginForm');
    const messageDiv = document.getElementById('message');

    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      const email = document.getElementById('email').value;
      const password = document.getElementById('password').value;

      try {
        const response = await fetch('http://localhost/api/login', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ email, password })
        });

        const data = await response.json();

        if (response.ok) {
          // ✅ Salva token no localStorage (ou sessionStorage)
          localStorage.setItem('token', data.token);

          messageDiv.style.color = 'green';
          messageDiv.textContent = 'Login realizado com sucesso!';
          console.log(data);
          console.log(data.token);

          // Redirecionar para outra página, se quiser:
          // window.location.href = "/dashboard.html";
        } else {
          messageDiv.textContent = data.message || 'Erro ao fazer login.';
        }
      } catch (error) {
        messageDiv.textContent = 'Erro de conexão com o servidor.';
      }
    });
  </script>

</body>
</html>
