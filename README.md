Smart Logistics test case  

### Настройка .env  

**Заменяем:**  
QUEUE_CONNECTION=rabbitmq  
CACHE_STORE=redis  

**Подставляем нужное:**  
RABBITMQ_HOST=rabbitmq  
RABBITMQ_PORT=5672  
RABBITMQ_USER=guest  
RABBITMQ_PASSWORD=guest  


### Зависимости и запуск:

_Сначала грузим зависимости:_

docker run --rm \
-u "$(id -u):$(id -g)" \
-v $(pwd):/var/www/html \
-w /var/www/html \
composer:latest \
composer install --ignore-platform-reqs



_Затем уже пользуемся:_  

**Запуск**:  
./vendor/bin/sail up -d

**Остановка**:  
./vendor/bin/sail down


Что есть:  
1.Api для отправки/просмотра статуса,разделение по очередям с приоритетом  
2.Использование rabbitmq, доставка без повторов постановки задачи и отправки уведомления  
3.Тесты, описание api (в корне проекта openapi.yaml)  

Чего нет:  
1. Упаковка всего в самостоятельный контейнер. Запускать по инструкции выше  

Почему:  
   - Это тестовое задание. Тестовому заданию - тестовая среда  
   - По времени, для неоплаченного тесового задания и так вышел за допустимые рамки
