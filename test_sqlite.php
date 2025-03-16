   <?php
   try {
       $pdo = new PDO('sqlite:database/database.sqlite');
       echo "Соединение с базой данных успешно установлено.";
   } catch (PDOException $e) {
       echo "Ошибка подключения: " . $e->getMessage();
   }
   ?>