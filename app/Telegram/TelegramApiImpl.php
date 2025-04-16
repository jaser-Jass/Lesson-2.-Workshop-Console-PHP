<?php
namespace App\Telegram;

class TelegramApiImpl implements TelegramApi {
  const ENDPOINT = 'https://api.telegram.org/bot';
  private int $offset;
  private string $token;

  public function __construct(string $token)
  {
    $this->token = $token;
  }
  public function getMessages(int $offset): array
{
    $url = self::ENDPOINT . $this->token . '/getUpdates?timeout=1';
    $result = [];
    
    $attempt = 0;
    $maxAttempts = 5; // Максимальное количество попыток

    while ($attempt < $maxAttempts) {
        $attempt++;
        $ch = curl_init("{$url}&offset={$offset}");

        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = json_decode(curl_exec($ch), true);
        
        // Логгируем ответ API
        error_log("API Response: " . json_encode($response));

        if (!$response['ok']) {
            error_log("Error: " . json_encode($response));
            break; // Если API вернет ошибку, выходим из цикла
        }

        if (empty($response['result'])) break; // Прекращаем, если результата нет

        foreach ($response['result'] as $data) {
            $chatId = $data['message']['chat']['id'];
            $result[$chatId] = [...($result[$chatId] ?? []), $data['message']['text']];
            $offset = $data['update_id'] + 1; // Обновляем offset
        }

        curl_close($ch);

        // Прерываем цикл, если меньше 100 сообщений
        if (count($response['result']) < 100) break;
    }

    return [
        'offset' => $offset,
        'result' => $result,
    ];
}
  public function sendMessage(string $chatId, string $text): void
  {
    $url = self::ENDPOINT . $this->token . '/sendMessage';
    $data = [
      'chat_id' => $chatId,
      'text' => $text,
    ];
    $ch = curl_init($url);
    $jsonData = json_encode($data);
    
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
  }
}