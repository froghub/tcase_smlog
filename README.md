Smart Logistics test case  

### Настройка .env  

**Создаем конфиг:**  
```bash
cp .env.example .env  
```  

**Заменяем:**  
```dotenv
QUEUE_CONNECTION=rabbitmq
CACHE_STORE=redis
```  
  
**Подставляем нужное:**

```dotenv
RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=guest
DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password
```
### Зависимости и запуск:

_Сначала грузим зависимости:_
```bash
docker run --rm \
-u "$(id -u):$(id -g)" \
-v $(pwd):/var/www/html \
-w /var/www/html \
composer:latest \
composer install --ignore-platform-reqs
```


_Затем уже пользуемся:_  

**Запуск**:  
```bash
./vendor/bin/sail up -d
```

**Остановка**:  
```bash
./vendor/bin/sail down
```

При первом запуске мигрируем БД:
```bash
./vendor/bin/sail artisan migrate
```  

Запуск тестов:
```bash
./vendor/bin/sail artisan test
```

Что есть:  
1.Api для отправки/просмотра статуса,разделение по очередям с приоритетом  
2.Использование rabbitmq, доставка без повторов постановки задачи и отправки уведомления  
3.Тесты, описание api (в корне проекта openapi.yaml)  

Чего нет:  
1. Упаковка всего в самостоятельный контейнер. Запускать по инструкции выше  

Почему:  
   - Это тестовое задание. Тестовому заданию - тестовая среда  
   - По времени, для неоплаченного тесового задания и так вышел за допустимые рамки  
---
2. Логгирование и обработка части ошибок, которые не мешают основной логике

Почему:
   - В тестовом задании никто не будет читать логи/анализировать ответы
   - см. "почемучку" пункта 1
---
3. Авторизации
