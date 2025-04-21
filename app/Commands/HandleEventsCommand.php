<?php

namespace App\Commands;

use App\Application;
use App\Database\SQLite;
use App\EventSender\EventSender;
use App\Models\Event;
use App\Queue\RabbitMQ;  
use App\Telegram\TelegramApiImpl;

class HandleEventsCommand extends Command
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function run(array $options = []): void
    {
        $event = new Event(new SQLite($this->app));

        $events = $event->select();

        $queue = new RabbitMQ('eventSender');  

        $eventSender = new EventSender(new TelegramApiImpl($this->app->env('TELEGRAM_TOKEN')), $queue);

        foreach ($events as $event) {

             if ($this->shouldEventBeRan($event)) 
            //if (true)
            {
                $receiverId = $event['receiver_id']; // Получаем receiver_id
                $messageText = $event['text']; // Получаем текст сообщения

                // Отладочная информация
                echo "Полученные данные: receiver_id='{$receiverId}', messageText='{$messageText}'\n";

             if (!empty($receiverId) && is_string($messageText)) {
    $messageToSend = json_encode([
        'receiver' => $receiverId,
        'message' => $messageText
    ]);
    
    $eventSender->sendMessage($messageToSend);
} else {
    echo "Ошибка: receiver_id не должен быть пустым. Получено: receiver_id='{$receiverId}', text='{$messageText}'\n";
}
            }
        }
    }

    public function shouldEventBeRan($event): bool
    {
        $currentMinute = date("i");
        $currentHour = date("H");
        $currentDay = date("d");
        $currentMonth = date("m");
        $currentWeekday = date("w");

        return ((int)$event['minute'] === (int)$currentMinute &&
                (int)$event['hour'] === (int)$currentHour &&
                (int)$event['day'] === (int)$currentDay &&
                (int)$event['month'] === (int)$currentMonth &&
                (int)$event['day_of_week'] === (int)$currentWeekday);
    }
}

