<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Images - ERP</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f9f9f9;
      padding: 2rem;
      max-width: 600px;
      margin: 2rem auto;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
      background: white;
      border-radius: 8px;
    }

    h2 {
      text-align: center;
      margin-bottom: 1.5rem;
    }

    form {
      margin-bottom: 2rem;
    }

    input[type="file"] {
      display: block;
      margin-bottom: 1rem;
      width: 100%;
      padding: 0.5rem;
      border: 1px solid #ccc;
      border-radius: 4px;
    }

    button {
      padding: 0.6rem 1.2rem;
      background-color: #3490dc;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }

    button:hover {
      background-color: #2779bd;
    }

    #message {
      margin-top: 1rem;
      color: red;
    }

    .image-preview {
      text-align: center;
      margin-bottom: 2rem;
    }

    .gallery {
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      justify-content: center;
    }

    .gallery img {
      max-width: 200px;
      border-radius: 4px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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
    <div class="image-preview">
      <img src="{{ asset('storage/' . session('caminho')) }}" alt="Imagem enviada" style="max-width: 300px;">
    </div>
  @endif

  <div class="gallery">
    @foreach ($files as $file)
      <img src="{{ asset('storage/' . $file) }}" alt="Image" />
    @endforeach
  </div>

</body>
</html>
