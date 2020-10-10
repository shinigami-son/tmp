<?php
/**
 * Универсальное приложение по созданию навыков и ботов.
 * @version 1.0
 * @author Maxim-M maximco36895@yandex.ru
 */

namespace MM\bot\components\card\types;


use MM\bot\components\button\Buttons;

/**
 * Класс отвечающий за отображение карточки в Viber.
 * Class ViberCard
 * @package bot\components\card\types
 */
class ViberCard extends TemplateCardTypes
{
    /**
     * Получить карточку для отображения пользователю.
     *
     * @param bool $isOne True, если в любом случае использовать 1 картинку.
     * @return array
     * @api
     */
    public function getCard(bool $isOne): array
    {
        $object = [];
        $countImage = count($this->images);
        if ($countImage > 7) {
            $countImage = 7;
        }
        if ($countImage) {
            if ($countImage === 1 || $isOne) {
                if (!$this->images[0]->imageToken) {
                    if ($this->images[0]->imageDir) {
                        $this->images[0]->imageToken = $this->images[0]->imageDir;
                    }
                }
                if ($this->images[0]->imageToken) {
                    $object = [
                        'Columns' => 1,
                        'Rows' => 6,
                        'Image' => $this->images[0]->imageToken
                    ];
                    $btn = $this->images[0]->button->getButtons(Buttons::T_VIBER_BUTTONS);
                    if (isset($btn['Buttons'])) {
                        $object = array_merge($object, $btn['Buttons'][0]);
                        $object['Text'] = "<font color=#000><b>{$this->images[0]->title}</b></font><font color=#000>{$this->images[0]->desc}</font>";
                    }
                }
            } else {
                foreach ($this->images as $image) {
                    if (!$image->imageToken) {
                        if ($image->imageDir) {
                            $image->imageToken = $image->imageDir;
                        }
                    }

                    $element = [
                        'Columns' => $countImage,
                        'Rows' => 6,
                    ];
                    if ($image->imageToken) {
                        $element['Image'] = $image->imageToken;
                    }
                    $btn = $image->button->getButtons(Buttons::T_VIBER_BUTTONS);
                    if (isset($btn['Buttons'])) {
                        $element = array_merge($element, $btn['Buttons'][0]);
                        $element['Text'] = "<font color=#000><b>{$image->title}</b></font><font color=#000>{$image->desc}</font>";
                    }
                    $object[] = $element;
                }
            }
        }
        return $object;
    }
}
