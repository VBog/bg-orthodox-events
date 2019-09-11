<?php
/* 
    Plugin Name: Bg Orthodox Events 
    Plugin URI: http://bogaiskov.ru/plugin-orthodox-events/
    Description: Плагин выводит на экран события православного календаря: праздники, памятные даты, дни поминовения усопших, дни почитания икон, посты и сплошные седмицы и другую полезную информацию. 
    Author: VBog
    Version: 0.2
    Author URI: http://bogaiskov.ru 
	License:     GPL2
*/

/*  Copyright 2019  Vadim Bogaiskov  (email: vadim.bogaiskov@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*****************************************************************************************
	Блок загрузки плагина
	
******************************************************************************************/

// Запрет прямого запуска скрипта
if ( !defined('ABSPATH') ) {
	die( 'Sorry, you are not allowed to access this page directly.' ); 
}

define('BG_ORTODOX_EVENTS_VERSION', '0.2.0');

global $bg_orthodox_events;
include_once('inc/date.php');

// Загрузить файлы событий
$upload_dir = wp_upload_dir();
$bg_orthodox_events = bg_ortev_load_xml(plugin_dir_path( __FILE__ ).'calendar.xml');
$bg_orthodox_events = array_merge($bg_orthodox_events, bg_ortev_load_xml($upload_dir['basedir'].'/bg_ortev/events.xml'));
//$bg_orthodox_events = bg_ortev_load_xml($upload_dir['basedir'].'/bg_ortev/events.xml');

/*******************************************************************************
	Функция загружает XML-файл и преобразует его в массив
	XML-файл должен содержать не менее 2-х записей
*******************************************************************************/  
function bg_ortev_load_xml($path) {
	
	$xmlfile = file_get_contents($path); 	// Read entire file into string 
	$xml = simplexml_load_string($xmlfile); // Convert xml string into an object 
	$json = json_encode($xml); 				// Convert into json 
	$events = json_decode($json, true); 	// Convert into associative array 

	return $events['event'];
}
  
add_shortcode( 'ortev_dayinfo', 'bg_ortev_dayinfo_shortcode' );
/*******************************************************************************
	Функция обработки шорт-кода ortev_dayinfo
*******************************************************************************/  
function bg_ortev_dayinfo_shortcode($atts) {
	extract( shortcode_atts( array(
		'date' => '',
		'type' => '',
		'separator'=> ''
	), $atts ) );
	
	global $bg_orthodox_events;
	
	if (empty($date)) {	// Дата не задана
		if (!empty($_GET['date'])) $date = $_GET['date'];	// Ищем дату в адресной строке
		else $date = date('Y-m-d');							// Иначе "сегодня"
	}
	$date = bg_ortev_oldDate($date);			// Дата по старому стилю
	list($y, $m, $d) = explode('-', $date);		// Год, месяц, день
	$easter = bg_ortev_easter($y);				// Пасха по старому стилю
	$date = bg_ortev_date ($y, $m, $d);
	
	// Массив разрешенных для отображения типов событий
	if ($type) $typeArr = explode(',', $type);

	$quote = '';

	// Для каждого события в БД
	foreach ($bg_orthodox_events as $event) {
		// Проверяем разрешенные типы событий
		if ($type && !in_array($event['type'], $typeArr)) continue;
		
		/* Дата окончания события */
		// Подвижные события относительно Пасхи
		if ($event['f_month'] == 0) $finish_date = bg_ortev_shiftDate($easter, $event['f_date']);
		// Неподвижные события
		elseif ($event['f_month'] > 0 && $event['f_month'] < 13) $finish_date = bg_ortev_date ($y, $event['f_month'], $event['f_date']);
		// Другие случаи нерелевантны - ошибка
		else continue;

		/* Дата начала события */
		// Особые случаи подвижных событий
		if ($event['s_month'] > -5 && $event['s_month'] < 0) {			
			$finish_wd = bg_ortev_WeekDay ($finish_date);
			switch ($event['s_month']) {
			case -1:		// --- Сб./Вс. перед/после события
				$dd = $event['s_date'] - $finish_wd;					// смещение относительно даты события
				if (!$dd) $event['name'] = '';							// в день основного события не отмечается
				$finish_date = bg_ortev_shiftDate($finish_date, $dd);			
				break;
			case -2:		// --- Событие в Сб./Вс. перед/после даты
				$dd = $event['s_date'] - $finish_wd;					// смещение относительно указанной даты
				$finish_date = bg_ortev_shiftDate($finish_date, $dd);
				break;
			case -3:		// --- Событие в только указанный день недели
				if (!($finish_wd == $event['s_date'])) $event['name'] = '';
				break;
			case -4:		// --- Событие в ближайшее Сб./Вс. к дате
				$dd = $event['s_date'] - $finish_wd;					// смещение относительно указанной даты
				$dd = $dd + ($finish_wd>3?7:0);							// отсчет от середины недели
				$finish_date = bg_ortev_shiftDate($finish_date, $dd);
				break;
			}
			$start_date = $finish_date;
		}
		// Подвижные события относительно Пасхи
		elseif ($event['s_month'] == 0) $start_date = bg_ortev_shiftDate($easter, $event['s_date']);
		// Неподвижные события
		elseif ($event['s_month'] > 0 && $event['s_month'] < 13) $start_date = bg_ortev_date ($y, $event['s_month'], $event['s_date']);
		// Другие случаи нерелевантны - ошибка
		else continue;
		
		// Обрабатываем случай, когда событие начинается в одном году, а заканчивается в следующем
		if ($start_date > $finish_date) {
			if ($start_date <= $date) {	//	Если текущая дата ПОЗЖЕ даты начала события,
										//	то дата окончания события в следующем году
				if ($event['f_month'] == 0) $finish_date = bg_ortev_shiftDate(bg_ortev_easter($y+1), $event['f_date']);
				else $finish_date = bg_ortev_date ($y+1, $event['f_month'], $event['f_date']);
			} else { 					// 	Если текущая дата ДО даты начала события, 
										// 	то дата начала события в предыдущем году
				if ($event['s_month'] == 0) $start_date = bg_ortev_shiftDate(bg_ortev_easter($y-1), $event['s_date']);
				else $start_date = bg_ortev_date ($y-1, $event['s_month'], $event['s_date']);
			}
		}
		
		// Проверяем попадает ли заданная дата в диапазон дат события
		if (!empty($event['name']) && ($start_date <= $date && $finish_date >= $date)) {
//			$quote .= $start_date." ". $date." ". bg_ortev_WeekDayName (bg_ortev_WeekDay ($date))." ". $finish_date."<br>";
			if (empty($event['link'])) $q = $event['name'];
			else $q = '<a href="'.$event['link'].'" title="'.$event['discription'].'">'.$event['name'].'</a>';
			$q = '<span class="bg_ortev_type'.$event['type'].'">'.$q.'</span>';
			if ($separator == 'ul' || $separator == 'ol') $quote .= '<li>'.$q.'</li>';
			else $quote .= $q.$separator;
		}	
	}
	if ($quote && $separator == 'ul') $quote = '<ul>'.$quote.'</ul>';
	elseif ($quote && $separator == 'ol') $quote = '<ol>'.$quote.'</ol>';
	return "{$quote}";
}

