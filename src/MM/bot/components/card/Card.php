<?php

namespace MM\bot\components\card;


use Exception;
use MM\bot\components\button\Buttons;
use MM\bot\components\card\types\AlisaCard;
use MM\bot\components\card\types\SmartAppCard;
use MM\bot\components\card\types\TelegramCard;
use MM\bot\components\card\types\TemplateCardTypes;
use MM\bot\components\card\types\ViberCard;
use MM\bot\components\card\types\VkCard;
use MM\bot\components\image\Image;
use MM\bot\core\mmApp;

/**
 * Класс отвечающий за отображение определенной карточки, в зависимости от типа приложения.
 * Class Card
 * @package bot\components\card
 */
class Card
{
    /**
     * Заголовок элемента карточки.
     * @var string|null $title
     */
    public $title;
    /**
     * Описание элемента карточки.
     * @var string|null $desc
     */
    public $desc;
    /**
     * Массив с изображениями или элементами карточки.
     * @var Image[]|null $images
     * @see Image Смотри тут
     */
    public $images;
    /**
     * Кнопки элемента карточки.
     * @var Buttons $button
     * @see Buttons Смотри тут
     */
    public $button;
    /**
     * Определяет необходимость отображения 1 элемента карточки.
     * Передайте true, если необходимо отобразить только 1 элемент карточки
     * @var bool $isOne
     */
    public $isOne;

    /**
     * Использование галереи изображений. Передайте true, если хотите отобразить галерею из изображений.
     * @var bool $isUsedGallery
     */
    public $isUsedGallery = false;

    /**
     * Произвольных шаблон, который отобразится вместо стандартного.
     * Рекомендуется использовать для smartApp, так как для него существует множество вариация для отображения карточек + есть списки
     * При использовании переменной, Вы сами отвечаете за корректное отображение карточки.
     * @var null $template
     */
    public $template = null;

    /**
     * Card constructor.
     */
    public function __construct()
    {
        $this->isOne = false;
        $this->button = new Buttons();
        $this->clear();
    }

    /**
     * Очищает все элементы карточки.
     * @api
     */
    public function clear()
    {
        $this->images = [];
    }

    /**
     * Вставляет элемент в каточку|список. В случае успеха вернет true.
     *
     * @param string|null $image Идентификатор или расположение изображения.
     * @param string $title Заголовок изображения.
     * @param string $desc Описание изображения.
     * @param array|null $button Кнопки, обрабатывающие команды при нажатии на элемент.
     * @return bool
     * @api
     */
    public function add(?string $image, string $title, string $desc = ' ', $button = null): bool
    {
        $img = new Image();
        if ($img->init($image, $title, $desc, $button)) {
            $this->images[] = $img;
            return true;
        }
        return false;
    }

    /**
     * Получение всех элементов карточки.
     *
     * @param TemplateCardTypes|null $userCard Пользовательский класс для отображения каточки.
     * @return array
     * @throws Exception
     * @api
     */
    public function getCards(?TemplateCardTypes $userCard = null): array
    {
        if ($this->template) {
            return $this->template;
        }
        $card = null;
        switch (mmApp::$appType) {
            case T_ALISA:
                $card = new AlisaCard();
                break;

            case T_VK:
                $card = new VkCard();
                break;

            case T_TELEGRAM:
                $card = new TelegramCard();
                break;

            case T_VIBER:
                $card = new ViberCard();
                break;

            case T_MARUSIA:
                $card = null;
                break;

            case T_SMARTAPP:
                $card = new SmartAppCard();
                break;

            case T_USER_APP:
                $card = $userCard;
                break;
        }
        if ($card) {
            $card->isUsedGallery = $this->isUsedGallery;
            $card->images = $this->images;
            $card->button = $this->button;
            $card->title = $this->title;
            return $card->getCard($this->isOne);
        }
        return [];
    }

    /**
     * Возвращает json строку со всеми элементами карточки.
     *
     * @param TemplateCardTypes|null $userCard Пользовательский класс для отображения каточки.
     * @return string
     * @throws Exception
     * @api
     */
    public function getCardsJson(?TemplateCardTypes $userCard): string
    {
        $json = $this->getCards($userCard);
        return json_encode($json, JSON_UNESCAPED_UNICODE);
    }
}
