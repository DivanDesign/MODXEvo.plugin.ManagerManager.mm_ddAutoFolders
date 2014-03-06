<?php
/**
 * mm_ddAutoFolders
 * @version 1.0.2 (2013-05-30)
 * 
 * @desc Automatically move documents (OnBeforeDocFormSave event) based on their date (publication date; any date in tv) into folders of year and month (like 2012/02/). If folders (documents) of year and month doesn`t exist they are created automatically OnBeforeDocFormSave event.
 * 
 * @uses ManagerManager plugin 0.5
 * 
 * @param $ddRoles {comma separated string} - List of role IDs this should be applied to. Leave empty (or omit) for all roles. Default: ''.
 * @param $ddTemplates {comma separated string} - List of template IDs this should be applied to. Leave empty (or omit) for all templates. Default: ''.
 * @param $ddParent {integer} - Ultimate parent ID (parent of the years). @required
 * @param $ddDateSource {string} - Name of template variable which contains the date. Default: 'pub_date'.
 * @param $ddYearTpl {integer} - Template ID for documents of year. Default: 0.
 * @param $ddMonthTpl {integer} - Template ID for documents of month. Default: 0.
 * @param $ddYearPub {0; 1} - Would the documents of year published? Default: 0.
 * @param $ddMonthPub {0; 1} - Would the documents of month published? Default: 0.
 * 
 * @link http://code.divandesign.biz/modx/mm_ddautofolders/1.0.2
 * 
 * @copyright 2013, DivanDesign
 * http://www.DivanDesign.biz
 */

/**
 * mm_ddAutoFolders
 * @version 1.0.2 (2013-05-30)
 * 
 * @desc При сохранении (событие OnBeforeDocFormSave) автоматически перемещает необходимый документ, основываясь на его дате (дата публикации, или любая дата в tv) в папку года и месяца. Если папки (документы) года и/или месяца ещё не созданы, они создадутся автоматически.
 * 
 * @uses ManagerManager plugin 0.5
 * 
 * @param $ddRoles {comma separated string} - ID ролей, к которым необходимо применить правило. Default: ''.
 * @param $ddTemplates {comma separated string} - ID шаблонов, к которым необходимо применить правило. Default: ''.
 * @param $ddParent {integer} - ID корневого родителя. @required
 * @param $ddDateSource {string} - Поле, откуда необходимо брать дату. Default: 'pub_date'.
 * @param $ddYearTpl {integer} - ID шаблона, который необходимо выставлять документам-годам. Default: 0.
 * @param $ddMonthTpl {integer} - ID шаблона, который необходимо выставлять документам-месяцам. Default: 0.
 * @param $ddYearPub {0; 1} - Надо ли публиковать документы-годы. Default: 0.
 * @param $ddMonthPub {0; 1} - Надо ли публиковать документы-месяцы. Default: 0.
 * 
 * @link http://code.divandesign.ru/modx/mm_ddautofolders/1.0.2
 * 
 * @copyright 2013, DivanDesign
 * http://www.DivanDesign.ru
 */

function mm_ddAutoFolders($ddRoles = '', $ddTemplates = '', $ddParent = '', $ddDateSource = 'pub_date', $ddYearTpl = 0, $ddMonthTpl = 0, $ddYearPub = '0', $ddMonthPub = '0'){
	global $modx, $id, $pub_date, $parent, $template, $document_groups, $tmplvars, $mm_fields, $modx_lang_attribute;
	$e = &$modx->Event;
	
	//$ddParent is required
	if (is_numeric($ddParent) && $e->name == 'OnBeforeDocFormSave' && useThisRule($ddRoles, $ddTemplates)){
		$base_path = $modx->config['base_path'];
		$widgetDir = $base_path.'assets/plugins/managermanager/widgets/ddautofolders/';
		
		//Подключаем библиотеку ddTools
// 		require_once $widgetDir.'modx.ddtools.class.php';
		
		//Текущее правило
		$rule = array();
		
		//Дата
		$ddDate = array();
		
		//Если задано, откуда брать дату и это не дата публикации, пытаемся найти в tv`шках
		if ($ddDateSource && $ddDateSource != 'pub_date'){
			//Получаем tv с датой для данного шаблона
			$dateTv = tplUseTvs($template, $ddDateSource);
			
			//Если tv удалось получить, такая tv есть и есть её значение
			if ($dateTv && $dateTv[0]['id'] && $tmplvars[$dateTv[0]['id']] && $tmplvars[$dateTv[0]['id']][1]){
				//Если дата в юникс-времени
				if (is_numeric($tmplvars[$dateTv[0]['id']][1])){
					$ddDate['date'] = $tmplvars[$dateTv[0]['id']][1];
				}else{
					//Пытаемся преобразовать в unix-время
					$ddDate['date'] = strtotime($tmplvars[$dateTv[0]['id']][1]);
				}
				//Пытаемся преобразовать в unix-время
				if (!is_numeric($tmplvars[$dateTv[0]['id']][1])) $ddDate['date'] = strtotime($tmplvars[$dateTv[0]['id']][1]);
			}
		}else{
			$ddDate['date'] = $pub_date;
		}
		
		//Если не задана дата, выбрасываем
		if (!$ddDate['date']) return;
		
		//Псевдонимы родителей (какие должны быть)
		//Год в формате 4 цифры
		$ddDate['y'] = date('Y', $ddDate['date']);
		//Название месяца на английском
		$ddDate['m'] = strtolower(date('F', $ddDate['date']));
		//Порядковый номер месяца
		$ddDate['n'] = date('n', $ddDate['date']);
		
		//Если язык админки — русский
		if (strtolower($modx_lang_attribute) == 'ru'){
			//Все месяцы на русском
			$ruMonthes = array('Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь');
			
			//Название месяца на русском
			$ddDate['mTitle'] = $ruMonthes[$ddDate['n'] - 1];
		}else{
			//Просто запишем на английском
			$ddDate['mTitle'] = date('F', $ddDate['date']);
		}
		
		//Получаем список групп документов, к которым принадлежит текущий документ (пригодится при создании годов и месяцев)
		$docGroups = preg_replace('/,\d*/', '', $document_groups);
		
		//Получаем псевдоним корневого родителя
		$ultimateAlias = '';
		//Если корневой родитель не в корне, допишем путь к нему
		if ($modx->aliasListing[$ddParent]['path'] != '') $ultimateAlias .= $modx->aliasListing[$ddParent]['path'].'/';
		$ultimateAlias .= $modx->aliasListing[$ddParent]['alias'];
		
		//Получаем годы (непосредственных детей корневого родителя)
		$years = $modx->getChildIds($ddParent, 1);
		
		//Получаем id нужного нам года
		$yearId = $years[$ultimateAlias.'/'.$ddDate['y']];
		
		//Если нужный год существует
		if ($yearId){
			//Проставим году нужные параметры
			ddTools::updateDocument($yearId, array(
				'isfolder' => 1,
				'template' => $ddYearTpl,
				'published' => $ddYearPub
			));
			//Получаем месяцы (непосредственных детей текущего года)
			$months = $modx->getChildIds($yearId, 1);
			//Получаем id нужного нам месяца
			$monthId = $months[$ultimateAlias.'/'.$ddDate['y'].'/'.$ddDate['m']];
		//Если нужный год не существует
		}else{
			//Создадим его
			$yearId = ddTools::createDocument(array(
				'pagetitle' => $ddDate['y'],
				'alias' => $ddDate['y'],
				'parent' => $ddParent,
				'isfolder' => 1,
				'template' => $ddYearTpl,
				//Года запихиваем тупо в самый конец
// 				'menuindex' => count($years),
				//Да пусть будут тупо по году, сортироваться нормально зато будут
				'menuindex' => $ddDate['y'] - 2000,
				'published' => $ddYearPub
			), $docGroups);
		}
		
// 		if (!$monthId && $yearId){
		//Если нужный месяц существует
		if ($monthId){
			//Проставим месяцу нужные параметры
			ddTools::updateDocument($monthId, array(
				'isfolder' => 1,
				'template' => $ddMonthTpl,
				'published' => $ddMonthPub
			));
			//Если нужный месяц не существует (на всякий случай проверим ещё и год)
		}else if($yearId){
			$monthId = ddTools::createDocument(array(
				'pagetitle' => $ddDate['mTitle'],
				'alias' => $ddDate['m'],
				'parent' => $yearId,
				'isfolder' => 1,
				'template' => $ddMonthTpl,
				//Для месяца выставляем menuindex в соответствии с его порядковым номером
				'menuindex' => $ddDate['n'] - 1,
				'published' => $ddMonthPub
			), $docGroups);
		}
		
		//Ещё раз на всякий случай проверим, что с месяцем всё хорошо
		if ($monthId && $monthId != $parent) $parent = $monthId;
	}
}
?>