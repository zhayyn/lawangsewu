import sys

filepath = '/var/www/html/pasemarang/wp-content/mu-plugins/Layanan-Bantuan-Mas-Satset.php'

with open(filepath, 'r') as f:
    content = f.read()

target1 = """    $response = wp_remote_post(pa_semarang_chat_runtime_url(), [
        'timeout' => 45,
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ],
        'body' => wp_json_encode([
            'prompt' => $message,
            'phoneNumber' => 'webchat',
            'systemPrompt' => pa_semarang_chat_system_prompt(),
            'temperature' => 0.15,
            'maxOutputTokens' => 260,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ]);

    if (is_wp_error($response)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Layanan chat tidak dapat dihubungi saat ini.',
            'detail' => $response->get_error_message(),
        ], 502);
    }

    $status = (int) wp_remote_retrieve_response_code($response);
    $body = json_decode((string) wp_remote_retrieve_body($response), true);

    if ($status < 200 || $status >= 300 || !is_array($body) || empty($body['success'])) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Layanan chat belum memberikan jawaban yang valid.',
            'upstreamStatus' => $status,
            'upstreamBody' => $body,
        ], 502);
    }"""

replacement1 = """    $response = wp_remote_post('http://127.0.0.1:11434/api/generate', [
        'timeout' => 45,
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ],
        'body' => wp_json_encode([
            'model' => 'qwen2.5:7b',
            'prompt' => $message,
            'system' => pa_semarang_chat_system_prompt(),
            'stream' => false,
            'options' => [
                'temperature' => 0.15,
                'num_predict' => 260
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ]);

    if (is_wp_error($response)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Layanan chat (Ollama) tidak dapat dihubungi saat ini.',
            'detail' => $response->get_error_message(),
        ], 502);
    }

    $status = (int) wp_remote_retrieve_response_code($response);
    $body = json_decode((string) wp_remote_retrieve_body($response), true);

    if ($status < 200 || $status >= 300 || !is_array($body) || empty($body['response'])) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Ollama belum memberikan jawaban yang valid.',
            'upstreamStatus' => $status,
            'upstreamBody' => $body,
        ], 502);
    }"""

target2 = """    return new WP_REST_Response([
        'success' => true,
        'text' => trim((string) ($body['text'] ?? '')),
        'model' => (string) ($body['model'] ?? ''),"""

replacement2 = """    return new WP_REST_Response([
        'success' => true,
        'text' => trim((string) ($body['response'] ?? '')),
        'model' => (string) ($body['model'] ?? 'ollama'),"""

content = content.replace(target1, replacement1)
content = content.replace(target2, replacement2)

with open(filepath, 'w') as f:
    f.write(content)
print("Patch applied successfully.")
