=== Bg Orthodox Calendar ===
Contributors: VBog
Donate link: http://bogaiskov.ru/about-me/donate/
Tags: calendar, orthodoxy, christianity, календарь, православие, христианство, месяцеслов, именины, браковенчание, ορθοδοξία, χριστιανισμός
Requires at least: 3.0.1
Tested up to: 5.2.3
Stable tag: trunk
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Orthodox calendar on your site.

== Description ==
Плагин позволяет вывести на экран информацию о указанном дне.
 
## Информация о дне ##

Шорт-код `[ortev_dayinfo]` позволяет вывести в тексте заметки/страницы информацию о событиях указанного дня.

В шорт-коде могут использоваться следующие параметры:

* **date** - дата по Григорианскому календарю в формате `YYYY-m-d`;
* **type** - типы событий, если пусто, то все типы событий.

Если дата не указана, то плагин пытается найти её в адресе страницы: `https://azbyka.ru/bogosluzhebnye-ukazaniya?date=2019-09-11`.
Если её и там нет, то принимается текущая дата на сервере.

## Структура базы данных плагина ##

БД плагина представляет собой файл `events.xml`, расположенный в корневой папке плагина.

```xml
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<events xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
	<event>
		<s_month>8</s_month>
		<s_date>29</s_date>
		<f_month>8</f_month>
		<f_date>29</f_date>
		<name>Молебное пение о страждущих недугом винопиянства или наркомании</name>
		<link>https://azbyka.ru/molitvoslov/molebnoe-penie-o-strazhdushhix-nedugom-vinopiyanstva-ili-narkomanii.html</link>
		<discription>Текст утвержден Священным Синодом Русской Православной Церкви 25 июля 2014 года (журнал № 80)</discription>
		<type>999</type>
	</event>
	<event>
		<s_month>0</s_month>
		<s_date>0</s_date>
		<f_month>0</f_month>
		<f_date>0</f_date>
		<name></name>
		<link></link>
		<discription></discription>
		<type>999</type>
	</event>
</events>
```

Где:
* `<event>` - событие;
* `<s_month>` - месяц начала события;
* `<s_date>` - день начала события;
* `<f_month>` - месяц окончания события;
* `<f_date>` - день окончания события;
* `<name>` - название события;
* `<link>` - ссылка на страницу с информацией о событии;
* `<discription>` - описание события;
* `<type>` - тип события. 

### Даты ###

Даты начала и окончания события указываются **по старому стилю (Юлианскому календарю)**. 

Чтобы указать переходящую дату начала или окончания события, необходимо указать в поле "месяц" 0, а в поле "день" количество дней **до** (отрицательное значение) или **после** (положительное значение) Пасхи.
Например, Петров (Апостольский) пост должен быть задан следующим образом:
```xml
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<events xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
	<event>
		<s_month>0</s_month>
		<s_date>57</s_date>
		<f_month>6</f_month>
		<f_date>28</f_date>
		<name>Петров (Апостольский) пост</name>
		<link></link>
		<discription></discription>
		<type>10</type>
	</event>
</events>
```


### Типы событий ###
* 0		Светлое Христово Воскресение. Пасха
* 1		Двунадесятые праздники 
* 2		Великие праздники
* 3		Средние бденные праздники
* 4		Средние полиелейные праздники
* 5		Малые славословные праздники
* 6		Малые шестиричные праздники
* 7		Вседневные. Cовершается служба, не отмеченная в Типиконе никаким знаком
* 8		Памятные даты
* 9		Дни особого поминовения усопших
* 10	Посты (многодневные и однодневные)
* 17	Дни почитания икон
* 18	Дни памяти святых
* 19	Новомученики и исповедники российские
* 20	Браковенчание не совершается
* 100	Сплошные седмицы
* 999	Произвольное событие, введенное пользователем, отображается как ссылка в тексте поста.

В версии 0.1 реализован только тип 999




== Installation ==

1. Upload 'bg-orthodox-events' directory to the '/wp-content/plugins/' directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

== Changelog ==


= 0.1 =

* Стартовый релиз


== License ==

GNU General Public License v2

