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

  <h2>Image</h2>
  <form action="{{ route('imagens.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <input type="file" name="imagem">
    <button type="submit">Salvar Imagem</button>
  </form>

  @if (session('caminho'))
    <img src="{{ asset('storage/' . session('caminho')) }}" alt="Imagem enviada" style="max-width: 300px;">
  @endif

</body>
</html>
