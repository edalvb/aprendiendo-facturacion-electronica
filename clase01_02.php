// Cargar el archivo XML existente
$dom = new DOMDocument();
$dom->load('libros.xml');

// Obtener todos los elementos de tipo 'libro'
$libros = $dom->getElementsByTagName('libro');

// Recorrer y mostrar la información de cada libro
foreach ($libros as $libro) {
    $titulo = $libro->getElementsByTagName('titulo')->item(0)->nodeValue;
    $autor = $libro->getElementsByTagName('autor')->item(0)->nodeValue;
    $precio = $libro->getElementsByTagName('precio')->item(0)->nodeValue;

    echo "Título: $titulo, Autor: $autor, Precio: $precio <br>";
}