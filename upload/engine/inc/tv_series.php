<?php

if(!defined('DATALIFEENGINE'))
{
    die("Hacking attempt!");
}

require_once ENGINE_DIR . '/data/tv_series.php';


class TV_Series_Admin {

    private $db;
    private $config;
    private $cat_info;
    private $dle_login_hash;
    private $member_id;

    private $tv_series_config;

    private $module_id = 'tv_series';
    private $module_url;
    private $pageLimit = 20;

    private $action;

    private $pageHeader;
    private $pageFooter;
    private $pageHeaderToolbar;


    public function __construct()
    {
        global $db, $config, $cat_info, $tv_series_config, $dle_login_hash, $member_id;
        $this->db = $db;
        $this->config = $config;
        $this->cat_info = $cat_info;
        $this->tv_series_config = $tv_series_config;
        $this->module_url = $config['http_home_url'] . $config['admin_path'] . '?mod=' . $this->module_id;
        $this->dle_login_hash = $dle_login_hash;
        $this->member_id = $member_id;
    }

    public function run()
    {
        $this->action = !empty($_REQUEST['action']) ? $_REQUEST['action'] : 'index';

        switch ($this->action) {
            case 'index':
                $this->showPage($this->pageIndex());
                break;
            case 'file_sharing':
                $this->showPage($this->pageFileSharing());
                break;
            case 'show':
                $this->showPage($this->pageShow());
                break;
            case 'add':
                $this->showPage($this->pageAdd());
                break;
            case 'edit':
                $this->showPage($this->pageEdit());
                break;
            default:
                $this->pageHeader = 'Ошибка';
                $this->showPage('Раздел не найден');
        }
    }

    private function showPage($content)
    {
        echoheader('TV Series', 'Сериалы в полной новости');
        echo <<<HTML
<script src="engine/classes/sdopFields.js"></script>
HTML;
        echo '
<div class="box">
	<div class="box-header">
		<div class="title">'.$this->pageHeader.'</div>';
        if ($this->pageHeaderToolbar) {
            echo '<ul class="box-toolbar">';
            if (is_array($this->pageHeaderToolbar)) {
                $toolbarHtml = '<li class="toolbar-link">'.implode('</li><li class="toolbar-link">', $this->pageHeaderToolbar).'</li>';
            } else {
                $toolbarHtml = $this->pageHeaderToolbar;
            }
            echo $toolbarHtml;
            echo '</ul>';
        }
        echo '
	</div>

	<div class="box-content">
		'.$content.'
	</div>';

        if ($content && $this->pageFooter) {
            echo '<div class="box-footer padded">
	        '.$this->pageFooter.'
	    </div>';
        }
        echo '
</div>
			';

        echofooter();
    }

    private function pageIndex()
    {
        $this->pageHeader = 'Список новостей';
        // Pagination
        $cstart = (int)$_GET['cstart'];
        if($cstart < 1) $cstart = 1;
        $dbstart = ($cstart-1)* $this->pageLimit;

        $content = '';
        $this->db->query("SELECT SQL_CALC_FOUND_ROWS * FROM ".PREFIX."_post WHERE approve = 1 ORDER BY id DESC LIMIT ".$dbstart.", ". $this->pageLimit);

        if ($this->db->num_rows()) {
            $content .= '<table class="table table-normal"><tbody><tr><th width="5%">ID</th><th>Заголовок</th><th width="10%">Действие</th></tr></tbody>';
            while ($row = $this->db->get_row()) {
                $content .= '<tr class="list_item">
                    <td>'.$row['id'].'</td>
                    <td><a href="'. $this->config['http_home_url'].'?newsid='.$row['id'].'" target="_blank">'. $this->getRealNewsTitle($row).'</a></td>
                    <td><a href="'.$this->module_url.'&action=show&news_id='.$row['id'].'" class="btn btn-primary">Редактировать</a></td>
                </tr>';
            }
            $content .= '</table>';

            $count_rows = $this->db->super_query("SELECT FOUND_ROWS() as rows");
            $count_rows = $count_rows['rows'];

            if ($count_rows > $this->pageLimit) {
                $countPages = ceil($count_rows / $this->pageLimit);


                $nav = '<div style="text-align: center"><ul class="pagination pagination-sm">';
                for($i=1; $i<=$countPages; $i++){
                    $nav .= '<li '.($i == $cstart ? 'class="active"' : '').'><a href="'.$this->module_url.'&cstart='.$i.'">'.$i.'</a></li>';
                }

                $nav .= '</ul></div>';
                $this->pageFooter = $nav;
            }
        } else {
            $content = 'Новости не найдены';
        }

        $this->pageHeaderToolbar = array(
            '<a href="'.$this->module_url .'&action=file_sharing"><i class="icon-file"></i>Файлообменники</a>'
        );
        return $content;
    }

    private function pageShow()
    {
        $this->pageHeader = '<a href="'.$this->module_url.'"><< Вернуться к списку новостей</a>';
        $content = '';
        $news_id = (int)$_GET['news_id'];

        $news = $this->db->super_query("SELECT * FROM ".PREFIX."_post WHERE id = '".$news_id."'");

        if (!$news) {
            $content .= 'Новость не найдена';
        } else {
            $this->pageHeader .= ' | Сериал: '. $this->getRealNewsTitle($news);
            $this->pageHeaderToolbar = array(
                '<a href="'.$this->module_url.'&action=add&news_id='.$news['id'].'"><i class="icon-plus"></i> Добавить серию</a>'
            );

            // get series

            $this->db->query("SELECT * FROM ".PREFIX."_tv_series WHERE news_id = '{$news_id}' ORDER BY number ASC");

            if ($this->db->num_rows()) {
                $series = array();
                while ($row = $this->db->get_row()) {
                    $series[$row['id']] = $row;
                }

                // get links for every series
                foreach ($series as $seriesItem) {
                    $this->db->query("SELECT * FROM ".PREFIX."_tv_series_links WHERE series_id = '{$seriesItem['id']}' GROUP BY lang ");

                    $langsForSeries[$seriesItem['id']] = array();
                    while ($row = $this->db->get_row()) {
                        $langsForSeries[$seriesItem['id']][] = $row['lang'];
                    }
                }

                // show series with langs

                $content .= '<div class="row padded">';
                foreach ($series as $seriesItem) {
                    $content .= '<div class="col-lg-1" style="font-size: 14px;margin-bottom:10px;"><a href="'.$this->module_url.'&action=edit&series_id='.$seriesItem['id'].'&news_id='.$seriesItem['news_id'].'">Серия '.$seriesItem['number'].'</a></div><div class="col-lg-11">';
                    foreach ($langsForSeries[$seriesItem['id']] as $sLang) {
                        $content .= '<img src="'.$this->tv_series_config['langs'][$sLang]['icon'].'" title="">&nbsp;';
                    }

                    $content .= '</div><div class="clearfix"></div>';
                }
                $content .= '</div>';

            } else {
                $content = '<div class="padded">Серии не найдены</div>';
            }
        }

        return $content;
    }

    private function pageAdd()
    {
        $content = '';
        $news_id = (int)$_GET['news_id'];


        $news = $this->db->super_query("SELECT * FROM ".PREFIX."_post WHERE id = '".$news_id."'");

        if (!$news) {
            $content .= 'Новость не найдена';
        } else {
            $content .= $this->form();


            $this->pageHeader .= ' | Добавление серии ('.$this->getRealNewsTitle($news).')';
        }

        return $content;
    }

    private function pageEdit()
    {
        $content = '';
        $series_id = (int)$_REQUEST['series_id'];

        $series = $this->db->super_query("SELECT tvs.*, p.category, p.title FROM ".PREFIX."_tv_series tvs 
        LEFT JOIN ".PREFIX."_post p
            ON p.id = tvs.news_id
        WHERE tvs.id = '".$series_id."'");

        if (!$series) {
            $content = 'Сериал не найден';
        } else {
            $content .= $this->form($series);
            $this->pageHeader .= ' | Редактирование серии ('.$this->getRealNewsTitle($series).' &raquo; Серия '.$series['number'].')';
            $this->pageHeaderToolbar = array(
                '<a href="'.$this->module_url.'&action=add&news_id='.$series['news_id'].'"><i class="icon-plus"></i> Добавить серию</a>'
            );
        }

        return $content;
    }

    private function pageFileSharing()
    {
        $content = '';
        $actionResponse = $this->actionFileSharing();

        if ($actionResponse != 'ok' && !empty($actionResponse)) {
            $content .= $actionResponse;
        } elseif ($actionResponse == 'ok') {
            $content .= '<div class="padded" style="background: #dff0d8;">Успешно сохранено!</div>';
        }

        $content .= <<<HTML
<script>


    $(document).ready(function() {
        $('body').on('click', '#tableFileSharing .remove', function() {
            var tr = $(this).closest('tr');
            tr.remove();
        });
        $('#actionAddFileSharing').click(function(e) {
            e.preventDefault();
            var html = '<tr><td><input class="form-control" name="id[]" placeholder="название" required></td>' + 
'<td><input class="form-control" name="icon[]" placeholder="укажите путь от: /templates/ваш_шаблон/images/" required></td>'+
'<td><button class="btn btn-danger remove">Удалить</button></td>'+
'</tr>';
            $('#tableFileSharing').append(html);
        });
    });
</script>
HTML;


        $this->pageHeader = 'Настройка файлообменников';
        $this->pageHeaderToolbar = array(
            '<a href="'.$this->module_url.'">&laquo; Вернуться к списку новостей</a>',
            '<a href="" id="actionAddFileSharing"><i class="icon-plus"></i> Добавить файлообменник</a>'
        );
        $this->pageFooter = '<div style="text-align: center"><button class="btn btn-success">Сохранить</button></div></form>';

        $this->db->query("SELECT * FROM ".PREFIX."_tv_series_file_sharing ORDER BY id ASC");

        $content .= '<form method="post"><table class="table" id="tableFileSharing"><thead><tr><th width="50%">Название (ID)</th><th>Путь к иконке</th><th width="5%">Действие</th></tr></thead>';
        if ($this->db->num_rows()) {
            $fs = array();
            while ($row = $this->db->get_row()) {
                $fs[$row['id']] = $row['icon'];
            }
        }

        if (isset($this->actionFS_fields) && is_array($this->actionFS_fields) && count($this->actionFS_fields)) {
            if (!is_array($fs)) {
                $fs = array();
            }
            $fs = $fs +  $this->actionFS_fields;
        }


        foreach ($fs as $fsKey => $fsValue) {
            $content .= '<tr><td><input class="form-control" name="id['.$fsKey.']" placeholder="название" value="'.$fsKey.'" required></td>
<td><input class="form-control" name="icon['.$fsKey.']" placeholder="укажите путь от: /templates/ваш_шаблон/images/" value="'.$fsValue.'" required></td>
<td><button class="btn btn-danger remove">Удалить</button></td>
</tr>';
        }

        if (!$this->db->num_rows()){
            $content .= '<tr><td><input class="form-control" name="id[]" placeholder="название" required></td>
<td><input class="form-control" name="icon[]" placeholder="укажите путь от: /templates/ваш_шаблон/images/" required></td>
<td><button class="btn btn-danger remove">Удалить</button></td>
</tr>';
        }
        $content .= '</table>';

        return $content;
    }

    public function actionFileSharing()
    {
        $content = '';
        $error = array();
        if (isset($_POST['id']) && isset($_POST['icon'])) {
            foreach ($_POST['id'] as $key=>$value) {
                if (isset($_POST['icon'][$key])) {
                    $name = strip_tags(trim($_POST['id'][$key]));
                    $icon = strip_tags(trim($_POST['icon'][$key]));

                    if (!file_exists( ROOT_DIR . '/templates/' . $this->config['skin'] . '/images/' . $icon)) {
                        $error[] = 'Для файлообменника <b>'. $name . '</b> не найдена иконка <b>'. $icon . '</b>';
                    }

                    $fs[$name] = $icon;
                }
            }

            $this->actionFS_fields = $fs;

            if (count($error)) {
                $content = '<div class="padded" style="background:#f2dede; padding-left: 40px;"><ul><li>'.implode('</li><li>', $error).'</li></ul></div>';
            } elseif (count($fs)) {
                $this->db->query("TRUNCATE TABLE ".PREFIX."_tv_series_file_sharing");

                foreach ($fs as $fsKey=>$fsValue) {
                    $this->db->query("INSERT INTO ".PREFIX."_tv_series_file_sharing VALUES ('{$fsKey}', '{$fsValue}')");
                }

                $content = 'ok';
            }
        }

        return $content;
    }


    /**
     * ADD/EDIT series form
     */
    private function form($data)
    {
        $content = '';

        $news_id = (int)$_GET['news_id'];

        $processFormResponse = $this->processForm();

        if ($processFormResponse == 'ok') {
            header("Location: ". $this->module_url . '&action=show&news_id=' . $news_id);
            die();
        } elseif (!empty($processFormResponse)) {
            $content .= $processFormResponse;
        }


        $this->pageHeader = '<a href="'.$this->module_url.'&action=show&news_id='.$news_id.'"><< Вернуться к сериалу</a>';

        if ($this->action == 'edit' && is_array($data)) {
            // get links
            $series_links = array();
            $this->db->query("SELECT * FROM ".PREFIX."_tv_series_links WHERE series_id = '{$data['id']}' ");

            while ($row = $this->db->get_row()) {
                $series_links[$row['lang']][$row['file_sharing_id']][] = $row;
            }
        }


        $translate = '';

        $firstLang = reset($this->tv_series_config['langs']);
        foreach ($this->tv_series_config['langs'] as $seriesLangId=>$seriesLang) {
            $translate .= '<label '.(!isset($series_links[$seriesLangId]) ? 'class="empty"' : '').'><input type="radio" name="lang" value="'.$seriesLangId.'" '.($firstLang['id'] == $seriesLangId ? 'checked' : '').'/><img src="'.$seriesLang['icon'].'" title="'.$seriesLang['name'].'"></label>';
        }

        $content .= <<<HTML
<style>
    .translates label.empty {
        opacity: 0.5;
    }
    .translates label:hover {
    opacity: 1;
    }
    
    .translates label > input{ /* HIDE RADIO */
  visibility: hidden; /* Makes input not-clickable */
  position: absolute; /* Remove input from document flow */
}
.translates label {
margin-right:5px;
}
.translates label > input + img{ /* IMAGE STYLES */
  cursor:pointer;
  border:2px solid transparent;
}
.translates label > input:checked + img{ /* (RADIO CHECKED) IMAGE STYLES */
  border:2px solid #000;
  border-radius: 2px;
}

.ins-holder .dop-fld{
display: block;
}
.ins-holder .dop-fld:not(:first-child) {
margin-bottom:10px;
}
</style>
<script>
$(function(){
  $('.fs_field').dopField();
});

$(document).ready(function() {
    $('input[type=radio][name=lang]').change(function() {
        $('.seriesLinks').hide();
        $('.seriesLinks[data-lang='+this.value+']').show();
    });
});
</script>
<form class="form-horizontal" method="post" style="padding:20px;">
    <div class="form-group">
        <label class="col-sm-2 control-label">Серия:</label>
        <div class="col-sm-2">
            <input type="text" name="number" class="form-control" placeholder="Номер серии" pattern="\d+" title="0-9. Только число" value="{$data['number']}" required>
        </div>
    </div>
    
    <div class="form-group">
        <label class="col-sm-2 control-label">Название:</label>
        <div class="col-sm-2">
            <input type="text" name="name" class="form-control" placeholder="Название серии" value="{$data['name']}" required>
        </div>
    </div>
    
    <div class="form-group">
        <label class="col-sm-2 control-label">Дата выхода:</label>
        <div class="col-sm-2">
            <input type="text" name="release_date" class="form-control" placeholder="Дата выхода серии" data-rel="calendar" value="{$data['release_date']}" required>
        </div>
    </div>
    
    <div class="form-group">
        <label class="col-sm-2 control-label">Перевод:</label>
        <div class="col-sm-2 translates">
            {$translate}
        </div>
    </div>
    
    <hr/>

HTML;

        $this->db->query("SELECT * FROM ".PREFIX."_tv_series_file_sharing ORDER BY id ASC");

        while ($row = $this->db->get_row()) {
            $fs_array[] = $row;
        }

        $firstLang = reset($this->tv_series_config['langs']);
        foreach ($this->tv_series_config['langs'] as $tv_series_lang) {
            $currentLangId = $tv_series_lang['id'];
            $content .= '<div class="seriesLinks" data-lang="'.$currentLangId.'" '.($currentLangId != $firstLang['id'] ? 'style="display:none;"' : '').'>';

            foreach ($fs_array as $fs_item) {
                $inputValue = '';
                if (isset($series_links[$currentLangId][$fs_item['id']])) {
                    $inputValue = array();
                    foreach ($series_links[$currentLangId][$fs_item['id']] as $linkItem) {
                        $inputValue[] = $linkItem['url'];
                    }
                    $inputValue = implode(',', $inputValue);
                }
                $content .= '<div class="form-group">
                    <label class="col-sm-2 control-label">'.$fs_item['id'].':</label>
                    <div class="col-sm-8">
                        <input type="text" name="links['.$currentLangId.']['.$fs_item['id'].']" class="form-control fs_field" value="'.$inputValue.'">
                    </div>
                </div>';
            }

            $content .= '</div>';
        }

        // action=edit
        if ($this->action == 'edit') {
            $content .= '<input type="hidden" name="series_id" value="'.$data['id'].'" />';
        }

        $content .= '<input type="hidden" name="news_id" value="'.$data['news_id'].'" /><input type="hidden" name="user_hash" value="'.$this->dle_login_hash.'" />';

        $content .= '
    <div style="text-align: center">
    <button type="submit" name="submit" value="save" class="btn btn-success">Сохранить</button>
    '.($this->action == 'edit' ? '&nbsp;<button type="submit" name="submit" value="delete" class="btn btn-danger" onclick="if (!confirm(\'Точно удалить?\')) return false;">Удалить</button>' : '').'
    </div>
</form>
';

        return $content;
    }

    private function processForm()
    {
        $error = array();
        $content = '';

        if (count($_POST)) {
            if ($_POST['user_hash'] != $this->dle_login_hash) die('Hacker');

            $news_id = (int)$_GET['news_id'];
            $number = (int)$_POST['number'];

            // get news
            $news = $this->db->super_query("SELECT * FROM ".PREFIX."_post WHERE id = '{$news_id}'");

            if ($news && $number > 0) {

                if ($this->action == 'edit') {
                    $seriesId = (int)$_POST['series_id'];
                    $db_tv_series = $this->db->super_query("SELECT * FROM ".PREFIX."_tv_series WHERE id = '{$seriesId}' ");

                    if (!$db_tv_series) {
                        $error[] = 'Серия не найдена';
                    }
                }


                if (!count($error)) {

                    if ($_POST['submit'] == 'save') {
                        // Check [news_id + series] ( unique )
                        if (!$this->db->super_query("SELECT * 
                        FROM ".PREFIX."_tv_series 
                        WHERE news_id = '{$news_id}' 
                            AND number = '{$number}' ".
                            (isset($db_tv_series) ? " AND number != '".$db_tv_series['number']."' " : '')." 
                        "))
                        {
                            $created_at = date('Y-m-d H:i:s');
                            $user_id = $this->member_id['user_id'];
                            $release_date = strip_tags(trim($_POST['release_date']));
                            $name = strip_tags(trim($_POST['name']));

                            // INSERT // UPDATE
                            if ($this->action == 'add') {
                                $this->db->query("INSERT INTO ".PREFIX."_tv_series (news_id, user_id, release_date, number, name, created_at) 
                            VALUES ('{$news['id']}', '{$user_id}', '{$release_date}', '{$number}', '{$name}', '{$created_at}')");

                                $seriesId = $this->db->insert_id();
                            } else {
                                $this->db->query("UPDATE ".PREFIX."_tv_series SET number = '{$number}', name = '{$name}', release_date = '{$release_date}' WHERE id = '{$seriesId}'");
                            }

                            $this->db->query("DELETE FROM ".PREFIX."_tv_series_links WHERE series_id = '{$seriesId}' ");


                            foreach ($_POST['links'] as $linksLang=>$links) {
                                if (is_array($links)) {
                                    foreach ($links as $fsKey=>$link) {
                                        if (!empty($link)) {
                                            $link = strip_tags(trim($link));
                                            // split links
                                            if (!empty($link)) {
                                                $multiLinks = explode(',', $link);
                                            }

                                            foreach ($multiLinks as $mLink) {
                                                $this->db->query("INSERT INTO ".PREFIX."_tv_series_links (series_id, lang, file_sharing_id, url, created_at)
                                         VALUES ('{$seriesId}', '{$linksLang}', '{$fsKey}', '{$mLink}', '{$created_at}')");
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            $error[] = 'Такая серия уже присутствует в сериале';
                        }
                    } elseif ($_POST['submit'] == 'delete') {
                        $this->db->query("DELETE FROM ".PREFIX."_tv_series WHERE id = '{$seriesId}' ");
                        $this->db->query("DELETE FROM ".PREFIX."_tv_series_links WHERE series_id = '{$seriesId}' ");
                    }
                }
            } else {
                $error[] = 'Новость не найдена';
            }

            if (count($error)) {
                $content = '<div style="padding:20px; background: indianred; color:#fff;"><ul><li>'.implode('</li><li>', $error).'</li></ul></div>';
            } else {
                $content = 'ok';
            }

        }

        return $content;
    }

    private function getRealNewsTitle($row)
    {
        return '<span>'.$this->cat_info[(int)$row['category']]['name'] . '</span> &raquo; ' . $row['title'];
    }
}

$module = new TV_Series_Admin();

$module->run();