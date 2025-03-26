<?php

namespace App\Commands;

use App\Application;
use App\Database\SQLite;
use App\EventSender\EventSender;
use App\Models\Event;
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
        $eventModel = new Event(new SQLite($this->app));
        $events = $eventModel->select();

        $eventSender = new EventSender(new TelegramApiImpl($this->app->env('TELEGRAM_TOKEN')));

foreach ($events as $event) {
    if ($this->shouldEventBeRan($event)) {
        $receiverId = $event['receiver_id'] ?? null; 
        $messageText = $event['text'] ?? null; 

        // Отладочная информация
        echo "Полученные данные: receiver_id='{$receiverId}', messageText='{$messageText}'\n";

        if (!empty($receiverId) && is_string($messageText)) {
            $eventSender->sendMessage($receiverId, $messageText);
        } else {
            echo "Ошибка: receiver_id не должен быть пустым. Получено: receiver_id='{$receiverId}', text='{$messageText}'\n";
        }
    }
}
    }

    private function shouldEventBeRan($event): bool
    {
        $currentMinute = date("i");

        $currentHour = date("H");

        $currentDay = date("d");

        $currentMonth = date("m");

        $currentWeekday = date("w");
//die(var_dump(123, $currentMinute, $currentHour, $currentDay, $currentMonth, $currentWeekday));

  return ((int)$event['minute'] === (int)$currentMinute &&

            (int)$event['hour'] === (int)$currentHour &&

            (int)$event['day'] === (int)$currentDay &&

            (int)$event['month'] === (int)$currentMonth &&

            (int)$event['day_of_week'] === (int)$currentWeekday);
    }
}
