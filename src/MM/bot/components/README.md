# Компоненты
Компоненты необходимы для корректной работы движка, а также упрощают разработку. Разделяются на 2 категории:
1. Системные компоненты, отвечающие за корректное отображение карточек, картинок, кнопок, а также воспроизведение звуков.
2. Дополнительные компоненты, упрощающие работу с текстом и навигацией.

Не рекомендуется использовать в своем коде системные компоненты, не входящие в BotController.

## Дополнительные компоненты
Дополнительные компоненты позволяют разработчику быстрее и удобнее разрабатывать продукт. На данный момент, реализован компонент для работы с текстом, а также компонент, упрощающий навигацию.

Рассмотрим их подробнее.

### Text
Класс со статическими методами, упрощающий работу с текстом. В классе предусмотрена возможность по обрезанию текста, поиск вхождений, а также другие методы.
#### Обрезание текста
```php
$text = 'testing long long text';
// Обрезание текста троеточием
Text::resize($text, 7); // -> test...
// Обрезание текста без троеточия
Text::resize($text, 7, false); // -> testing
```
#### Поиск вхождений
В случае успешного поиска вернет true, иначе false.
```php
$text = 'testing long long text';
// Поиск определенного значения
Text::isSayText('test', $text); // -> true
// Поиск одного из возможных значений
Text::isSayText(['and', 'test'], $text); // -> true
// Поиск по регулярному выражению
Text::isSayText(['and', '\btest\b'], $text, true); // -> true
```
#### Схожесть текста
Метод возвращает массив вида:

[\
'status' => bool, Статус выполнения\
'index' => int|string, В каком тексте значение совпало, либо максимальное. При передаче строки вернет 0\
'text' => string, Текст, который совпал\
'percent' => int, На сколько процентов текста похожи\
]
```php
Text::textSimilarity('test', 'test', 90); // -> ['persent' => 100, 'index' => 0, 'status' => true, 'text' => 'test'];
Text::textSimilarity('test', 'demo', 90); // -> ['index' => null, 'status' => false, 'text' => null];
```
#### Добавление правильного окончания
```php
$titles = [
    'Яблоко',
    'Яблока',
    'Яблок'
];

Text::getEnding(1, $titles); // -> Яблоко
Text::getEnding(2, $titles); // -> Яблока
Text::getEnding(10, $titles); // -> Яблок
// Всегда выбирается определенное значение.
Text::getEnding(10, $titles, 0); // -> Яблоко
```

### Navigation
Класс отвечающий за корректную навигацию по элементам меню. Удобен в том случае, если в приложении есть карточки с большим количеством элементов и нужна возможность для навигации.
Класс позволяет не только перемещаться по различным страницам, а также определяет на какой из элементов списка нажал пользователь.
#### Примеры использования
Стандартная навигация
```php
$elements = [1,2,3,4,5,6,7,8,9,0]; // Массив с элементами
$maxVisibleElements = 5; // Количество отображаемых элементов
$nav = new Navigation($maxVisibleElements); // Подключаем класс с навигацией
$showElements = $nav->nav($elements); // Получение отображаемых элементов
echo $showElements; // -> [1,2,3,4,5]
```
Навигация с отслеживанием команд "Дальше" или "Назад"
```php
$elements = [1,2,3,4,5,6,7,8,9,0];
$maxVisibleElements = 5;
$nav = new Navigation($maxVisibleElements);
$showElements = $nav->nav($elements, 'Спасибо');
echo $showElements; // -> [1,2,3,4,5]
$showElements = $nav->nav($elements, 'Дальше');
echo $showElements; // -> [6,7,8,9,0]
$showElements = $nav->nav($elements, 'Назад');
echo $showElements; // -> [1,2,3,4,5]
```
Навигация с отслеживанием текущей страницы пользователя
```php
$elements = [1,2,3,4,5,6,7,8,9,0];
$maxVisibleElements = 5;
$nav = new Navigation($maxVisibleElements);
$nav->thisPage = 1; // Устанавливаем текущую страницу на 2.
$showElements = $nav->nav($elements, 'Дальше');
echo $nav->thisPage; // -> 1, так как мы не можем выйти за максимальное количество страниц
$showElements = $nav->nav($elements, 'Назад');
echo $nav->thisPage; // -> 0
```
Выбор пользователем определенного элемента списка
```php
$elements = [1,2,3,4,5,6,7,8,9,0];
$maxVisibleElements = 5;
$nav = new Navigation($maxVisibleElements);
$nav->thisPage = 1;
$selectedElement = $nav->selectedElement($elements, '1');
echo $selectedElement; // -> 6
$nav->thisPage = 0;
$selectedElement = $nav->selectedElement($elements, '1');
echo $selectedElement; // -> 1
```
Если передается массив массивов, то можно указать ключ для поиска.
```php
$elements = [
    [
        'title' => 'title 1',
        'desc' => 'desc 1'
    ],
    [
        'title' => 'title 2',
        'desc' => 'desc 2'
    ],
    [
        'title' => 'title 3',
        'desc' => 'desc 3'
    ],
    [
        'title' => 'title 4',
        'desc' => 'desc 4'
    ],
];
$maxVisibleElements = 2;
$nav = new Navigation($maxVisibleElements);

$selectedElement = $nav->selectedElement($elements, 'title 1', 'title');
echo $selectedElement; // -> ['title' => 'title 1', 'desc' => 'desc 1']
$nav->thisPage = 1;
$selectedElement = $nav->selectedElement($elements, 'title 4', 'title', 1);
echo $selectedElement; // -> ['title' => 'title 4', 'desc' => 'desc 4']
```

## Системные компоненты
К системным компонентам относятся:
- Кнопки
- Карточки
- Картинки
- NLU
- Звуки

Компоненты необходимы для корректной работы движка. А именно отвечают за корректное отображение результатов.
Все необходимые системные компоненты доступны в BotController.
