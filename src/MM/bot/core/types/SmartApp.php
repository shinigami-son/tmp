<?php
/**
 * Универсальное приложение по созданию навыков и ботов.
 * @version 1.0
 * @author Maxim-M maximco36895@yandex.ru
 */

namespace MM\bot\core;

use MM\bot\components\button\Buttons;
use MM\bot\components\standard\Text;
use MM\bot\controller\BotController;
use MM\bot\core\types\TemplateTypeModel;

/**
 * Класс, отвечающий за корректную инициализацию и отправку ответа для Сбер SmartApp
 * Class SmartApp
 * @package bot\core\types
 * @see TemplateTypeModel Смотри тут
 */
class SmartApp extends TemplateTypeModel
{
    /**
     * @const float Максимально время, за которое должен ответить навык.
     */
    private const MAX_TIME_REQUEST = 2800;
    /**
     * Информация о сессии пользователя.
     * @var array|null $session
     */
    protected $session;

    /**
     * Получение данных, необходимых для построения ответа пользователю.
     *
     * @return array
     */
    protected function getPayload(): array
    {
        $payload = [
            'pronounceText' => $this->controller->text,
            'pronounceTextType' => 'application/text',
            'device' => $this->session['device'],
            'intent' => $this->controller->thisIntentName,
            'projectName' => $this->session['projectName'],
            'finished' => $this->controller->isEnd,
        ];

        if ($this->controller->emotion) {
            $payload['emotion'] = [
                'emotionId' => $this->controller->emotion
            ];
        }
        if ($this->controller->text) {
            $payload['items'] = [
                [
                    'bubble' => [
                        'text' => Text::resize($this->controller->text, 250),
                        'markdown' => true,
                        'expand_policy' => 'auto_expand'
                    ]
                ]
            ];
        }
        if ($this->controller->tts) {
            $payload['pronounceText'] = $this->controller->tts;
            $payload['pronounceTextType'] = 'application/ssml';
        }

        if ($this->controller->isScreen) {
            if (count($this->controller->card->images)) {
                if (isset($payload['items'])) {
                    $payload['items'] = [];
                }
                $payload['items'][] = $this->controller->card->getCards();
            }
            $payload['suggestions'] = [
                'buttons' => $this->controller->buttons->getButtons(Buttons::T_SMARTAPP_BUTTONS)
            ];
        }
        return $payload;
    }

    /**
     * Инициализация основных параметров. В случае успешной инициализации, вернет true, иначе false.
     *
     * @param string|null $content Запрос пользователя.
     * @param BotController $controller Ссылка на класс с логикой навык/бота.
     * @return bool
     * @see TemplateTypeModel::init() Смотри тут
     * @api
     */
    public function init(?string $content, BotController &$controller): bool
    {
        if ($content) {
            $content = json_decode($content, true);

            $this->controller = $controller;
            $this->controller->requestObject = $content;

            switch ($content['messageName']) {
                case 'MESSAGE_TO_SKILL':
                case 'CLOSE_APP':
                    $this->controller->userCommand = $content['payload']['message']['normalized_text'];
                    $this->controller->originalUserCommand = $content['payload']['message']['original_text'];
                    break;

                case 'SERVER_ACTION':
                case 'RUN_APP':
                    $this->controller->payload = $content['payload']['server_action']['parameters'];
                    if (!is_array($this->controller->payload)) {
                        $this->controller->userCommand = $this->controller->originalUserCommand = $this->controller->payload;
                    }
                    break;
            }

            if (!$this->controller->userCommand) {
                $this->controller->userCommand = $this->controller->originalUserCommand;
            }

            $this->session = [
                'device' => $content['payload']['device'],
                'meta' => $content['payload']['meta'],
                'sessionId' => $content['sessionId'],
                'messageId' => $content['messageId'],
                'uuid' => $content['uuid'],
                'projectName' => $content['payload']['projectName']
            ];

            $this->controller->oldIntentName = $content['payload']['intent'];
            $this->controller->appeal = $content['payload']['character']['appeal'];
            $this->controller->userId = $content['uuid']['userId'];
            mmApp::$params['user_id'] = $this->controller->userId;
            $nlu = [
                'entities' => $content['payload']['message']['entities'],
                'tokens' => $content['payload']['message']['tokenized_elements_list']
            ];
            $this->controller->nlu->setNlu($nlu);

            $this->controller->userMeta = $content['payload']['meta'] ?? [];
            $this->controller->messageId = $content['messageId'];

            mmApp::$params['app_id'] = $content['payload']['app_info']['applicationId'];
            $this->controller->isScreen = $content['payload']['device']['capabilities']['screen']['available'];
            return true;
        } else {
            $this->error = 'SmartApp:init(): Отправлен пустой запрос!';
        }
        return false;
    }

    /**
     * Получение ответа, который отправится пользователю. В случае с Алисой, Марусей и Сбер, возвращается json. С остальными типами, ответ отправляется непосредственно на сервер.
     *
     * @return string
     * @see TemplateTypeModel::getContext() Смотри тут
     * @api
     */
    public function getContext(): string
    {
        $result = [
            'messageName' => 'ANSWER_TO_USER',
            'sessionId' => $this->session['sessionId'],
            'messageId' => $this->session['messageId'],
            'uuid' => $this->session['uuid']
        ];

        if (count($this->controller->sound->sounds) || $this->controller->sound->isUsedStandardSound) {
            if ($this->controller->tts === null) {
                $this->controller->tts = $this->controller->text;
            }
            $this->controller->tts = $this->controller->sound->getSounds($this->controller->tts);
        }
        $result['payload'] = $this->getPayload();
        $timeEnd = $this->getProcessingTime();
        if ($timeEnd >= self::MAX_TIME_REQUEST) {
            $this->error = "SmartApp:getContext(): Превышено ограничение на отправку ответа. Время ответа составило: {$timeEnd} сек.";
        }
        return json_encode($result);
    }
}