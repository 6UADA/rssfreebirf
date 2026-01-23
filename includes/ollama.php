<?php
function reescribir_titulo_con_ollama($titulo)
{
    $prompt = "Reescribe el siguiente título periodístico para que sea más atractivo y profesional, usando solo español. " .
        "Tu respuesta debe contener únicamente el nuevo título, sin comillas, sin encabezados, sin etiquetas, sin introducciones. " .
        "IMPORTANTE: devuélveme ÚNICAMENTE el nuevo título.\n\nTítulo original: $titulo";

    $body = json_encode([
        'model' => 'llama3',
        'prompt' => $prompt,
        'stream' => false
    ]);

    $response = wp_remote_post('https://ollama.maxtres.org/ollama', [
        'headers' => ['Content-Type' => 'application/json'],
        'body' => $body,
        'timeout' => 60
    ]);

    if (is_wp_error($response))
        return $titulo;

    $data = json_decode(wp_remote_retrieve_body($response), true);
    return trim($data['response'] ?? '') ?: $titulo;
}

function reescribir_contenido_con_ollama($contenido)
{
    $contenido_original = $contenido;

    // 1. Limpieza básica de HTML
    $contenido_limpio = wp_strip_all_tags($contenido);

    // 2. LIMPIEZA AGRESIVA POR CÓDIGO (Antes de enviar a la IA)
    // Elimina frases comunes de agencias (EFE, Reuters, Europa Press, etc)
    $patrones_agencias = [
        '/Con información de\s?[\w\s]+/iu',
        '/Foto:\s?[\w\s]+/iu',
        '/Redacción\s?[\w\s]+/iu',
        '/@[\w\d_]+/', // Mencioes @usuario
        '/\s?\([^)]*(EFE|Reuters|AP|AFP|Europa Press|Getty)[^)]*\)/iu' // Paréntesis con nombres de agencias
    ];

    $contenido_limpio = preg_replace($patrones_agencias, '', $contenido_limpio);

    // Elimina cualquier cosa que quede entre paréntesis (donde suelen ir los créditos)
    $contenido_limpio = preg_replace('/\s?\([^)]+\)/', '', $contenido_limpio);

    // Elimina URLs
    $contenido_limpio = preg_replace('/\bhttps?:\/\/\S+/i', '', $contenido_limpio);

    $contenido_prompt = trim(wp_trim_words($contenido_limpio, 1500, '...'));

    // 3. PROMPT REFORZADO PARA OMITIR AGENCIAS
    $prompt = "Actúa como un Editor Jefe de un periódico digital. Tu tarea es reescribir la siguiente noticia para que sea única, profesional, humana y altamente coherente.\n\n" .
        "ESTRUCTURA OBLIGATORIA:\n" .
        "1. PÁRRAFOS: Divide el texto en 3 a 5 párrafos bien definidos. Usa saltos de línea dobles entre cada párrafo.\n" .
        "2. COHERENCIA: Asegura una narrativa fluida. El primer párrafo debe introducir el hecho, los siguientes desarrollar los detalles y el último cerrar con una conclusión o impacto.\n\n" .
        "REGLAS DE FILTRADO (CRÍTICO):\n" .
        "1. SIN CRÉDITOS: No incluyas NUNCA nombres de agencias (EFE, Reuters, Europa Press, etc.), ni frases como 'Con información de'. Borra firmas.\n" .
        "2. SIN REDES SOCIALES: Elimina menciones a X, Twitter, Instagram o @usuarios.\n" .
        "3. SIN RUIDO: Elimina publicidad, enlaces o texto institucional.\n\n" .
        "ESPECIFICACIONES TÉCNICAS:\n" .
        "1. IDIOMA: Español neutro y profesional.\n" .
        "2. EXTENSIÓN: Entre 300 y 450 palabras.\n" .
        "3. FORMATO: Devuelve ÚNICAMENTE el cuerpo de la noticia, sin títulos, sin saludos y sin introducciones tipo 'Aquí tienes el texto'.\n\n" .
        "Contenido original para procesar:\n$contenido_prompt";

    $body = json_encode([
        'model' => 'llama3',
        'prompt' => $prompt,
        'stream' => false,
        'options' => [
            'num_predict' => 650
        ]
    ]);

    $response = wp_remote_post('https://ollama.maxtres.org/ollama', [
        'headers' => ['Content-Type' => 'application/json'],
        'body' => $body,
        'timeout' => 300
    ]);

    if (is_wp_error($response)) {
        error_log('Ollama Error: ' . $response->get_error_message());
        return $contenido_original;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    $resultado = trim($data['response'] ?? '');

    return (strlen($resultado) > 100) ? $resultado : $contenido_original;
}