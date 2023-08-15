<?php

require_once '../vendor/autoload.php';

// регистрация загрузчкиа классов ...
spl_autoload_register(function ($class) {
    $path = __DIR__.'/'.$class.'.php';
    if (file_exists($path)) {
        include $path;
    }
});

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class App
{
    /**
     * Имя используемой очереди в rabbitMQ
     *
     * @var        string
     */
    const QUEUE_NAME = 'test-job';

    /**
     * Набор хранилищ данных
     *
     * @var        array
     */
    const DB_EXPORTERS = ['Mysql', 'ClickH'];

    public function __construct()
    {
        $argsPos = strpos($_SERVER['REQUEST_URI'], '?');
        $action = substr($_SERVER['REQUEST_URI'], 1, $argsPos === false ? null :  $argsPos - 1);
        $actName = 'action' . implode('', array_map('ucfirst', explode('-', $action)));

        if (method_exists($this, $actName)) {
            try {
                $this->$actName($_GET);
            } catch (Exception $e) {
                http_response_code(500);
                echo $e->getMessage();
            }
        } else {
            http_response_code(404);
        }
    }

    /**
     * просто phpInfo
     */
    private function actionInfo()
    {
        phpinfo();
    }

    /**
     * генерация контента ...
     */
    private function actionGenerate()
    {
        $contentLength = rand(100, 2000);

        header('Content-type: text/plain');
        header("Content-Length: " . ($contentLength * 2));
        echo bin2hex(random_bytes($contentLength));
    }

    /**
     * конект к RabbotMQ и инициализация очереди ..
     *
     * @return     AMQPChannel  Канал для работы с RabbitMQ
     */
    private function getRabbitChennal()
    {
        $connection = new AMQPStreamConnection(
            getenv('RABBIT_HOST'),
            getenv('RABBIT_PORT'),
            getenv('RABBIT_USER'),
            getenv('RABBIT_PASS')
        );
        $channel = $connection->channel();
        $channel->queue_declare(static::QUEUE_NAME, false, false, false, false);
        return $channel;
    }

    /**
     * Завершение работы с RabbitMQ
     *
     * @param      <type>  $channel  The channel
     */
    private function closeRabbitChennal($channel)
    {
        $conn = $channel->getConnection();
        $channel->close();
        $conn->close();
    }

    /**
     * Получение данных и добавление данных в очередь Rabbit
     *
     * @param      array  $params  Парамтеры Get запроса
     */
    private function actionPutToRabbit($params)
    {
        $count = 10;
        if (!empty($params['count'])) {
            $count = intval($params['count']);
        }
        $count = $count ?: 10;

        $channel = $this->getRabbitChennal();

        $res = [];

        $time = time();
        for ($i = 0; $i < $count; $i++) {
            $el = [
                'id' => $i,
                'url' => $this->getRandomUrl(),
                'time' => $time,
            ];
            $res[] = $el;
            $msg = new AMQPMessage($el['url'], ['timestamp' => $time]);
            $channel->basic_publish($msg, '', static::QUEUE_NAME);
            $time += rand(5, 30);
        }

        $this->closeRabbitChennal($channel);

        header('Content-type: application/json');
        echo json_encode($res);
    }

    /**
     * Полкчение данных из RabbitMQ и запись обработанных даннных в базы данных
     *
     * @param      array  $params  The parameters
     */
    private function actionRabbitRead($params = [])
    {
        $channel = $this->getRabbitChennal();
        $res = [];
        if (!empty($params['count'])) {
           $co = intval($params['count']);
        }

        while ($mes = $channel->basic_get(static::QUEUE_NAME)) {
            if (isset($co)) {
                if (!$co) {
                    break;
                }
                $co--;
            }
            $el = [
                'url'=> $mes->body,
                'ts' => date('Y-m-d H:i:s', $mes->get('timestamp')),
                'len' => $this->getContentLen($mes->body),
            ];
            $res[] = $el;
            $mes->ack();
        }

        $this->closeRabbitChennal($channel);

        $this->putToDB($res);

        header('Content-type: application/json');
        echo json_encode($res);
    }

    /**
     * обображение статистики по таблице
     *
     * @param      array      $params  Параметры get-запроса ..
     *
     * @throws     Exception  Если что-то пошло нетак ...
     */
    private function actionGetStatistic($params = [])
    {
        if (empty($params['db']) || !in_array(ucfirst($params['db']), static::DB_EXPORTERS)) {
            throw new Exception('Отсутствует или неверный параметр "db"');
        }
        $class = ucfirst($params['db']);
        $db = new $class();
        $list = $db->getStatictic();

        header('Content-type: application/json');
        echo json_encode($list);

    }

    /**
     * Запись данных в базу ...
     *
     * @param      array  $res    Данные, которые нужно записать в базу
     */
    private function putToDB($res)
    {
        if (!$res) {
            return;
        }
        // выкигули поле url из всех элементов...
        $res = array_map(function($el) {
            unset($el['url']);
            return $el;
        }, $res);

        foreach (static::DB_EXPORTERS as $class) {
            $db = new $class();
            $db->addRows($res);
        }
    }

    /**
     * Генерация одного случайного url для запроса контента
     *
     * @return     string  The random url.
     */
    private function getRandomUrl()
    {
        return 'http://nginx/generate?param=' . rand();
    }

    /**
     * Запрос данных по url и получение из него длины контенра
     *
     * @param      string  $url    Запрашиваемый url
     *
     * @return     int  Длина контента . запрошенного по url
     */
    private function getContentLen($url)
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HEADER => false,
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => true,
        ]);
        curl_exec($ch);
        $size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        curl_close($ch);
        return $size;
    }
}