<?php
// Конфигурация для AmoCRM
$clientId = 'd199b903-ad67-4562-9c0a-aa1d776cb9cc'; 
$clientSecret = 'YmbtzMJv4wDhDDYZz6Af47FB1tByx5CPjzGIRlS50qGMIwos7228JVqoJf3WLGHZ'; 
$redirectUri = 'Yd199b903-ad67-4562-9c0a-aa1d776cb9cc'; 
$subdomain = 'pysskix12'; // Поддомен вашего аккаунта в amoCRM

// Получение токена доступа
$accessToken = getAccessToken();

try {
    // Обработка данных формы
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $price = (float)$_POST['price'];
        $timeSpent = (int)$_POST['time_spent']; // 1 или 0

        // Формирование данных для контакта
        $contactData = [
            'name' => $name,
            'custom_fields_values' => [
                [
                    'field_id' => 627017, // Укажите ID вашего поля Email
                    'values' => [
                        [
                            'value' => $email,
                        ],
                    ],
                ],
                [
                    'field_id' => 627015, // Укажите ID вашего поля Телефон
                    'values' => [
                        [
                            'value' => $phone,
                        ],
                    ],
                ],
            ],
        ];

        // Отправка запроса на создание контакта
        $contactResponse = sendRequest(
            "https://{$subdomain}.amocrm.ru/api/v4/contacts",
            $accessToken['access_token'],
            'POST',
            json_encode([$contactData])
        );

        if (isset($contactResponse['_embedded']['contacts'][0]['id'])) {
            $contactId = $contactResponse['_embedded']['contacts'][0]['id'];

            // Формирование данных для сделки
            $leadData = [
                'name' => 'Сделка от ' . $name,
                'price' => $price,
                'custom_fields_values' => [
                    [
                        'field_id' => 627205, // Укажите ID вашего поля "Более 30 секунд"
                        'values' => [
                            [
                                'value' => (bool)$timeSpent, // Устанавливаем значение чекбокса
                            ],
                        ],
                    ],
                ],
                '_embedded' => [
                    'contacts' => [
                        ['id' => $contactId], // Связываем сделку с контактом
                    ],
                ],
            ];

            // Отправка запроса на создание сделки
            $leadResponse = sendRequest(
                "https://{$subdomain}.amocrm.ru/api/v4/leads",
                $accessToken['access_token'],
                'POST',
                json_encode([$leadData])
            );

            if (isset($leadResponse['_embedded']['leads'][0]['id'])) {
                echo "Контакт успешно создан. ID Контакта: $contactId<br>Сделка успешно создана.ID Сделки: " . $leadResponse['_embedded']['leads'][0]['id'];
            } else {
                echo "Ошибка при создании сделки: " . json_encode($leadResponse);
            }
        } else {
            echo "Ошибка при создании контакта: " . json_encode($contactResponse);
        }
    }
} catch (Exception $e) {
    echo 'Ошибка: ' . $e->getMessage();
}

// Функция для отправки HTTP-запросов
function sendRequest($url, $accessToken, $method = 'GET', $data = null) {
    $headers = [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json',
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

// Функция для получения токена доступа
function getAccessToken() {
    $token = json_decode(file_get_contents(__DIR__ . '/../token.json'), true);

    if (!$token || !isset($token['access_token'], $token['expires'])) {
        throw new Exception('Ошибка: токен не найден или некорректен.');
    }

    if (time() >= $token['expires']) {
        throw new Exception('Ошибка: токен истек. Пожалуйста, пройдите повторную авторизацию.');
    }

    return $token;
}
?>
