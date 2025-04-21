# reminger-tg-bot
команда для запуска:

php runner -c tg_messages

кешируются только сообщения от пользователей

Очередию Команды:
php runner -c handle_events
rabbitmqadmin get queue=eventSender
php runner -c queue_manager
