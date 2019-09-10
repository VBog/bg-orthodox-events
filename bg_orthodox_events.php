<?php
/* 
    Plugin Name: Bg Orthodox Events 
    Plugin URI: http://bogaiskov.ru/plugin-orthodox-events/
    Description: Плагин выводит на экран события православного календаря: праздники, памятные даты, дни поминовения усопших, дни почитания икон, посты и сплошные седмицы и другую полезную информацию. 
    Author: VBog
    Version: 0.1
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

define('BG_ORTODOX_EVENTS_VERSION', '0.1.0');

global $bg_orthodox_events;
// Загрузить файл событий
$bg_orthodox_events = bg_ortev_load_xml(plugin_dir_path( __FILE__ ).'events.xml');

function bg_ortev_load_xml($path) {
	
	$xmlfile = file_get_contents($path); 	// Read entire file into string 
	$xml = simplexml_load_string($xmlfile); // Convert xml string into an object 
	$json = json_encode($xml); 				// Convert into json 
	$events = json_decode($json, true); 	// Convert into associative array 

	return $events['event'];
}
  
add_shortcode( 'ortev_dayinfo', 'bg_ortev_dayinfo_shortcode' );
/*******************************************************************************
// Функция обработки шорт-кода ortev_dayinfo
*******************************************************************************/  
function bg_ortev_dayinfo_shortcode($atts) {
	extract( shortcode_atts( array(
		'date' => '',
		'type' => ''
	), $atts ) );
	
	global $bg_orthodox_events;
	
	if (empty($date)) {
		if (!empty($_GET['date'])) $date = $_GET['date'];
		else $date = date('Y-m-d');
	}

	$quote = '';
	$date = bg_ortev_oldDate($date);			// Дата по старому стилю
	list($y, $m, $d) = explode('-', $date);
	$easter = bg_ortev_easter($y);				// Пасха по старому стилю

//	$quote .= $date.'<br>';
	
	foreach ($bg_orthodox_events as $event) {
		if ($event['f_month'] == 0) {
			$finish_date = bg_ortev_shiftDate($easter, $event['f_date']);
		} else {
			$finish_date = $y.'-'.$event['f_month'].'-'.$event['f_date'];
		}
		if ($event['s_month'] == 0) {
			$start_date = bg_ortev_shiftDate($easter, $event['s_date']);
		} else {
			$start_date = $y.'-'.$event['s_month'].'-'.$event['s_date'];
		}
		if (!empty($event['name']) && ($start_date <= $date && $finish_date >= $date)) {
			if (!empty($event['link'])) {
				$quote .= ' <a href="'.$event['link'].'" title="'.$event['discription'].'">'.$event['name'].'</a><br>';
			} else {
				$quote .= $event['name'].'<br>';
			}
		}	
	}
	return "{$quote}";
}

/*******************************************************************************
// Функция смещает дату по старому стилю на заданное количество дней
*******************************************************************************/  
function bg_ortev_shiftDate($date, $dd) {
	list($y, $m, $d) = explode('-', $date);
	
	if ($dd < 0) {
		while ($d + $dd < 1) {
			$m--;
			$dd = bg_ortev_numDays($m-1, $y)+ $dd;
			if ($m < 1) {
				$m = 12;
				$y--;
			}
		}
	} elseif ($dd > 0) {
		while ($d + $dd > bg_ortev_numDays($m-1, $y)) {
			$m++;
			$dd = $dd - bg_ortev_numDays($m-1, $y);
			if ($m > 12) {
				$m = 1;
				$y++;
			}
		}
	}
	$d = $d + $dd;
	return $y.'-'.$m.'-'.$d;
}


/*******************************************************************************
// Функция преобразует дату по новому стилю в дату по старому стилю
*******************************************************************************/  
function bg_ortev_oldDate($date) {
	list($y, $m, $d) = explode('-', $date);
	
	$dd = bg_ortev_dd($y);
	if ($d - $dd < 1) {
		$m--;
		if ($m < 1) {
			$m = 12;
			$y--;
		}
		$d = bg_ortev_numDays($m-1, ($m<3)?($y-1):$y) + $d - $dd;
	}
	return $y.'-'.$m.'-'.$d;
}

/*******************************************************************************
// Функция возвращает количество дней в месяце
*******************************************************************************/  
function bg_ortev_numDays ($month, $year) {
	$dim = array(31,28,31,30,31,30,31,31,30,31,30,31);
	return ($month == 2 && bg_ortev_isLeap($year)) ? 29 : $dim[(int)$month-1];
}


/*******************************************************************************
// Функция возвращает количество дней между датами по новому и старому стилю
*******************************************************************************/  
function bg_ortev_dd($year) {
	return ($year-$year%100)/100 - ($year-$year%400)/400 - 2;
}
/*******************************************************************************
// Функция определяет день Пасхи на заданный год по старому стилю
*******************************************************************************/

function bg_ortev_easter($year) {
	$a=((19*($year%19)+15)%30);
	$b=((2*($year%4)+4*($year%7)+6*$a+6)%7);
	if ($a+$b>9) {
		$day=$a+$b-9;
		$month=4;
	} else {
		$day=22+$a+$b;
		$month=3;
	}
	return $year.'-'.$month.'-'.$day;
}

/*******************************************************************************
// Функция определяет является ли указанный год високосным
*******************************************************************************/  
function bg_ortev_isLeap($year) {
	return (!($year % 4) && ($year % 100) || !($year % 400));
}
