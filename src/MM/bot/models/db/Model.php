<?php
/**
 * Created by PhpStorm.
 * User: max18
 * Date: 06.03.2020
 * Time: 15:44
 */

namespace MM\bot\models\db;


use MM\bot\components\standard\Text;
use MM\bot\core\mmApp;

/**
 * Class Model
 * @package bot\models\db
 *
 * Абстрактный класс для моделей. Все Модели, взаимодействующие с бд наследуют его.
 *
 * @property int $startIndex: Стартовое значение для индекса.
 */
abstract class Model
{
    public $startIndex = 0;
    /**
     * @var Sql: Подключение к базе данных
     */
    private $db;

    /**
     * Правила для обработки полей. Где 1 - Элемент это название поля, 2 - Элемент тип поля, max - Максимальная длина
     *
     * @return array
     *  - @var string|array 0: Название поля
     *  - @var string 1: Тип поля (text, string, integer, ...)
     *  - @var int max: Максимальная длина строки
     */
    public abstract function rules(): array;

    /**
     * Массив с полями таблицы, где ключ это название поля, а значени краткое описание.
     * Для уникального ключа использовать значение ID
     *
     * @return array
     */
    public abstract function attributeLabels(): array;

    /**
     * Название таблицы/файла с данными
     *
     * @return string
     */
    public abstract function tableName(): string;

    /**
     * Model constructor.
     */
    public function __construct()
    {
        if (IS_SAVE_DB) {
            $this->db = new Sql();
        } else {
            $this->db = null;
        }
    }

    /**
     * Декодирование текста(Текст становится приемлемым и безопасным для sql запроса)
     *
     * @param string $text : Исходный текст
     * @return string
     */
    public final function escapeString(string $text): string
    {
        if (IS_SAVE_DB) {
            return $this->db->escapeString($text);
        }
        return $text;
    }

    /**
     * Валидация значений полей для таблицы
     */
    public function validate(): void
    {
        if (IS_SAVE_DB) {
            $rules = $this->rules();
            if ($rules) {
                foreach ($rules as $rule) {
                    if (!is_array($rule[0])) {
                        $rule[0] = [$rule[0]];
                    }
                    $type = 'number';
                    switch ($rule[1]) {
                        case 'string':
                        case 'text':
                            $type = 'string';
                            break;
                        case 'int':
                        case 'integer':
                        case 'bool':
                            $type = 'number';
                            break;
                    }
                    foreach ($rule[0] as $data) {
                        if ($type == 'string') {
                            if (isset($rule['max'])) {
                                $this->$data = Text::resize($this->$data, $rule['max']);
                            }
                            $this->$data = '"' . $this->escapeString($this->$data) . '"';
                        } else {
                            $this->$data = (int)$this->$data;
                        }
                    }
                }
            }
        }
    }

    /**
     * Возвращает тип поля таблицы
     *
     * @param string|int $index : Название поля таблицы
     * @return string|null
     */
    protected function isAttribute($index): ?string
    {
        $rules = $this->rules();
        if ($rules) {
            foreach ($rules as $rule) {
                if (!is_array($rule[0])) {
                    $rule[0] = [$rule[0]];
                }
                foreach ($rule[0] as $data) {
                    if ($data == $index) {
                        return $rule[1];
                    }
                }
            }
        }
        return null;
    }

    /**
     * Получить обработанное значение для сохранения, где строка оборачивается в двойные кавычки
     *
     * @param string|int|double $val : Значение поля
     * @param string $type : Тип поля
     * @return string|null
     */
    protected function getVal($val, $type): ?string
    {
        switch ($type) {
            case 'string':
            case 'text':
                return '"' . $val . '"';
                break;
            case 'int':
            case 'integer':
            case 'bool':
                return $val;
                break;
        }
        return null;
    }

    /**
     * Возвращает название уникального ключа таблицы
     *
     * @return int|string|null
     */
    protected function getId()
    {
        foreach ($this->attributeLabels() as $index => $label) {
            if ($label == 'ID' || $label == 'id') {
                return $index;
            }
        }
        return null;
    }

    /**
     * Инициализация данных для модели
     *
     * @param array $data : Массив с данными
     */
    public function init(array $data): void
    {
        $i = 0 + $this->startIndex;
        foreach ($this->attributeLabels() as $index => $label) {
            if (IS_SAVE_DB) {
                $this->$index = $data[$i];
            } else {
                $this->$index = $data[$index] ?? '';
            }
            $i++;
        }
    }

    /**
     * Выполняет запрос с поиском по уникальному ключу
     *
     * @return bool|\mysqli_result|array|null
     */
    public function selectOne()
    {
        $idName = $this->getId();
        if ($idName) {
            if ($this->$idName) {
                if (IS_SAVE_DB) {
                    return $this->db->query('SELECT * FROM ' . $this->tableName() . " WHERE `{$idName}`={$this->getVal($this->$idName, $this->isAttribute($idName))} LIMIT 1");
                } else {
                    $data = $this->getFileData();
                    return $data[$this->$idName] ?? null;
                }
            }
        }
        return null;
    }

    /**
     * Сохранение значения в базу данных.
     * Если значение уже есть в базе данных, то данные обновятся. Иначе добавляется новое значение.
     *
     * @param bool $isNew : Добавить новую запись в базу данных без поиска по ключу
     * @return bool|\mysqli_result|null
     */
    public function save($isNew = false)
    {
        $this->validate();
        if ($isNew) {
            return $this->add();
        }
        if ($this->selectOne()) {
            return $this->update();
        } else {
            return $this->add();
        }
    }

    /**
     * Обновление значения в таблице
     *
     * @return bool|\mysqli_result|null
     */
    public function update()
    {
        if (IS_SAVE_DB) {
            $this->validate();
            $idName = $this->getId();
            if ($idName) {
                $set = '';
                foreach ($this->attributeLabels() as $index => $label) {
                    if ($index != $idName) {
                        if ($set) {
                            $set .= ',';
                        }
                        $set .= "`{$index}`={$this->$index}";
                    }
                }
                $sql = 'UPDATE ' . $this->tableName() . " SET {$set} WHERE `{$idName}`={$this->getVal($this->$idName, $this->isAttribute($idName))};";
                return $this->db->query($sql);
            }
        } else {
            $data = $this->getFileData();
            $idName = $this->getId();
            if (isset($data[$this->$idName])) {
                $tmp = [];
                foreach ($this->attributeLabels() as $index => $label) {
                    $tmp[$index] = $this->$index;
                }
                $data[$this->$idName] = $tmp;
                mmApp::saveJson("{$this->tableName()}.json", $data);
            }
            return true;
        }
        return null;
    }

    /**
     * Добавление значения в таблицу
     *
     * @return bool|\mysqli_result|null
     */
    public function add()
    {
        if (IS_SAVE_DB) {
            $this->validate();
            $idName = $this->getId();
            if ($idName) {
                $into = '';
                $value = '';
                foreach ($this->attributeLabels() as $index => $label) {
                    if ($index == $idName && !$this->$index) {
                        continue;
                    }
                    if ($into) {
                        $into .= ',';
                    }
                    $into .= "`{$index}`";
                    $value .= $this->$index;
                }
                $sql = 'INSERT INTO ' . $this->tableName() . "({$into}) VALUE ({$value});";
                return $this->db->query($sql);
            }
        } else {
            $data = $this->getFileData();
            $idName = $this->getId();
            $tmp = [];
            foreach ($this->attributeLabels() as $index => $label) {
                $tmp[$index] = $this->$index;
            }
            $data[$this->$idName] = $tmp;
            mmApp::saveJson("{$this->tableName()}.json", $data);
            return true;
        }
        return null;
    }

    /**
     * Удаление значения из таблицы
     *
     * @return bool|\mysqli_result|null
     */
    public function delete()
    {
        if (IS_SAVE_DB) {
            $idString = null;
            $idName = $this->getId();
            if ($idName) {
                $val = $this->getVal($this->$idName, $this->isAttribute($idName));
                if ($val) {
                    $idString = "`{$idName}`={$val}";
                }
            }
            if ($idString) {
                $sql = 'DELETE FROM ' . $this->tableName() . " WHERE {$idString};";
                return $this->db->query($sql);
            }
        } else {
            $data = $this->getFileData();
            $idName = $this->getId();
            if (isset($data[$this->$idName])) {
                unset($data[$this->$idName]);
                mmApp::saveJson("{$this->tableName()}.json", $data);
            }
            return true;
        }
        return false;
    }

    /**
     * Выполнение запроса к данным
     *
     * @param string $where : Запрос к таблице
     * @param bool $isOne : Вывести только 1 результат. Используется только при поиске по файлу
     * @return bool|\mysqli_result|array|null
     */
    public function where($where = '1', bool $isOne = false)
    {
        if (IS_SAVE_DB) {
            $sql = 'SELECT * FROM ' . $this->tableName() . " WHERE {$where}";
            return $this->db->query($sql);
        } else {
            $pattern = "/((`[^`]+`)=((\\\"[^\"]+\\\")|([^ ]+)))/umu";
            preg_match_all($pattern, $where, $data);
            $content = $this->getFileData();
            if (isset($data[0][0])) {
                $result = [];
                foreach ($content as $key => $value) {
                    $isSelected = false;
                    foreach (($data[2] ?? []) as $index => $val) {
                        $val = str_replace('`', '', $val);
                        if (($value[$val] ?? null) == str_replace('"', '', $data[3][$index])) {
                            $isSelected = true;
                        } else {
                            $isSelected = false;
                            break;
                        }
                    }
                    if ($isSelected) {
                        if ($isOne) {
                            return $value;
                        }
                        $result[] = $value;
                    }
                }
                if (count($result)) {
                    return $result;
                }
            }
        }
        return null;
    }

    /**
     * Выполнение запроса и инициализация переменных в случае успешного запроса
     *
     * @param string $where : Запрос к таблице
     * @return bool
     */
    public function whereOne($where = '1'): bool
    {
        if (IS_SAVE_DB) {
            $res = $this->where("{$where} LIMIT 1");
            if ($res && $res->num_rows) {
                $this->init($res->fetch_array(MYSQLI_NUM));
                $res->free_result();
                return true;
            }
        } else {
            $query = $this->where($where, true);
            if ($query) {
                $this->init($query);
                return true;
            }
        }
        return false;
    }

    /**
     * Получение всех значений из файла. Актуально если глобальная константа IS_SAVE_DB равна false
     *
     * @return array|mixed
     */
    public function getFileData()
    {
        $path = mmApp::$config['json'];
        $fileName = str_replace('`', '', $this->tableName());
        $file = "{$path}/{$fileName}.json";
        if (is_file($file)) {
            return json_decode(file_get_contents($file), true);
        } else {
            return [];
        }
    }

    /**
     * Выполнение произвольного запрос к базе данных
     *
     * @param string $sql : Непосредственно запрос к бд
     * @return bool|\mysqli_result|null
     */
    public function query(string $sql)
    {
        if (IS_SAVE_DB) {
            return $this->db->query($sql);
        }
        return null;
    }
}