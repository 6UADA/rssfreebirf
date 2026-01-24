<?php
defined('ABSPATH') || exit;

/* ===========================
   CONFIG GLOBAL ANTI-CUELGUES
=========================== */
if (!defined('OLLAMA_TIMEOUT_TITULO'))
    define('OLLAMA_TIMEOUT_TITULO', 25);
if (!defined('OLLAMA_TIMEOUT_CONTENIDO'))
    define('OLLAMA_TIMEOUT_CONTENIDO', 120);
if (!defined('OLLAMA_MAX_WORDS_INPUT'))
    define('OLLAMA_MAX_WORDS_INPUT', 350);

@set_time_limit(0);

/* ===========================
   REESCRITURA DE TÍTULO
=========================== */
function reescribir_titulo_con_ollama($titulo)
{
    $prompt = "Reescribe el siguiente título periodístico para que sea más atractivo y profesional. " .
        "Devuelve únicamente el nuevo título, en español, sin comillas ni explicaciones.\n\n" .
        "Título original: $titulo";

    $body = json_encode([
        'model' => 'llama3',
        'prompt' => $prompt,
        'stream' => false
    ]);

    $response = wp_remote_post('https://ollama.maxtres.org/ollama', [
        'headers' => ['Content-Type' => 'application/json'],
        'body' => $body,
        'timeout' => OLLAMA_TIMEOUT_TITULO
    ]);

    if (is_wp_error($response)) {
        error_log('[Ollama Error Titulo] ' . $response->get_error_message());
        return $titulo;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    return !empty($data['response']) ? trim($data['response']) : $titulo;
}

/* ===========================
   REESCRITURA DE CONTENIDO
=========================== */
function reescribir_contenido_con_ollama($contenido)
{
    $contenido_original_clean = wp_strip_all_tags($contenido);

    // Reducimos input para evitar cuelgues
    $contenido_para_prompt = trim(
        wp_trim_words($contenido_original_clean, OLLAMA_MAX_WORDS_INPUT, '...')
    );

    $prompt = "Actúa como redactor periodístico profesional. Reescribe el siguiente texto en español (México/Iberoamérica), " .
        "con estilo claro, fluido y atractivo. Mantén la veracidad de los hechos. " .
        "El texto final debe tener al menos 350 palabras. " .
        "No agregues títulos, subtítulos, firmas ni introducciones tipo 'Aquí tienes'. " .
        "Devuelve únicamente el texto final.\n\n" .
        "Texto original:\n$contenido_para_prompt";

    $body = json_encode([
        'model' => 'llama3',
        'prompt' => $prompt,
        'stream' => false
    ]);

    $response = wp_remote_post('https://ollama.maxtres.org/ollama', [
        'headers' => ['Content-Type' => 'application/json'],
        'body' => $body,
        'timeout' => OLLAMA_TIMEOUT_CONTENIDO
    ]);

    if (is_wp_error($response)) {
        error_log('[Ollama Error Contenido] ' . $response->get_error_message());
        return $contenido;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    return !empty($data['response']) ? trim($data['response']) : $contenido;
}
