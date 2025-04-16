<?php

namespace App\Commands;

use App\Application;
use App\Telegram\TelegramApiImpl;
use Predis\Client as PredisClient;

class TgMessagesCommand extends Command 
{
    protected Application $app;
    private int $offset;
    private ?array $oldMessages; // Изменено на тип ?array
    private PredisClient $redis;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->offset = 0;
        $this->oldMessages = [];
        $this->redis = new PredisClient(); // Создаем экземпляр PredisClient
        // (предположим, что Redis работает на локальном хосте с дефолтными настройками)
    }

    public function run(array $options = []): void 
    {
        $tgApi = new TelegramApiImpl($this->app->env('TELEGRAM_TOKEN'));
        echo json_encode($this->receiveNewMessages());
    }

    protected function getTelegramApiImpl(): TelegramApiImpl
    {
        return new TelegramApiImpl($this->app->env('TELEGRAM_TOKEN'));
    }

   private function receiveNewMessages(): array
{
    $offset = intval($this->redis->get('tg_messages:offset') ?: '0');
    print_r('Current Offset: ' . $offset . PHP_EOL);

    $result = $this->getTelegramApiImpl()->getMessages($offset);
    print_r('Telegram API Response: ' . json_encode($result) . PHP_EOL); // вывод всей структуры ответа

    if (isset($result['offset'])) {
        $this->redis->set('tg_messages:offset', $result['offset']); // Если в результате есть новый offset, сохраняем его
    }

    // Обработка старых сообщений
    $oldMessagesRaw = $this->redis->get('tg_messages:old_messages');
    $oldMessages = !empty($oldMessagesRaw) ? json_decode($oldMessagesRaw, true) : [];

    $messages = [];
    foreach ($result['result'] ?? [] as $chatId => $newMessage) {
        if (isset($oldMessages[$chatId])) {
            $oldMessages[$chatId] = array_merge($oldMessages[$chatId], $newMessage);
        } else {
            $oldMessages[$chatId] = $newMessage;
        }
        $messages[$chatId] = $oldMessages[$chatId];
    }

    $this->redis->set('tg_messages:old_messages', json_encode($oldMessages));

    return $messages;
}
}

