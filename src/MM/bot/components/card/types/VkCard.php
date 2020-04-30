<?php
/**
 * Created by PhpStorm.
 * User: max18
 * Date: 25.03.2020
 * Time: 15:50
 */

namespace MM\bot\components\card\types;


use MM\bot\components\button\Buttons;
use MM\bot\models\ImageTokens;

/**
 * Class VkCard
 * @package bot\components\card\types
 */
class VkCard extends TemplateCardTypes
{
    /**
     * @param bool $isOne : True, если отобразить только 1 картинку.
     * @return array
     */
    public function getCard(bool $isOne): array
    {
        $object = [];
        $countImage = count($this->images);
        if ($countImage) {
            if ($countImage === 1 || $isOne) {
                if (!$this->images[0]->imageToken) {
                    if ($this->images[0]->imageDir) {
                        $mImage = new ImageTokens();
                        $mImage->type = ImageTokens::T_VK;
                        $this->images[0]->imageToken = $mImage->getToken();
                    }
                }
                if ($this->images[0]->imageToken) {
                    $object[] = $this->images[0]->imageToken;
                }
            } else {
                $elements = [];
                foreach ($this->images as $image) {
                    if (!$image->imageToken) {
                        if ($image->imageDir) {
                            $mImage = new ImageTokens();
                            $mImage->type = ImageTokens::T_VK;
                            $image->imageToken = $mImage->getToken();
                        }
                    }
                    if ($image->imageToken) {
                        $element = [
                            'title' => $image->title,
                            'description' => $image->desc,
                            'photo_id' => str_replace('photo', '', $image->imageToken)
                        ];
                        $button = $image->button->getButtons(Buttons::T_VK_BUTTONS);
                        /**
                         * У карточки в любом случае должна быть хоть одна кнопка.
                         * Максимальное количество кнопок 3
                         */
                        if (count($button)) {
                            $element['buttons'] = array_splice($button['buttons'], 0, 3);
                            $element['action'] = ['type' => 'open_photo'];
                            $elements[] = $element;
                        }
                    }
                }
                if (count($elements)) {
                    $object = [
                        'type' => 'carousel',
                        'elements' => $elements
                    ];
                }
            }
        }
        return $object;
    }
}