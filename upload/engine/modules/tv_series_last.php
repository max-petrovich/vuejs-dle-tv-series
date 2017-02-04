<?php

if( !defined( 'DATALIFEENGINE' )) {
    die( "Hacking attempt!" );
}

$countDays = 7;
$cache_name = 'tv_series_last_'.$countDays;

$tv_series_last = dle_cache($cache_name);

if (!$tv_series_last) {
    $themeUrl = $config['http_home_url'] . 'templates/' . $config['skin'] . '/';

// get last date (query)
    $start_date = $db->super_query("SELECT DATE(release_date) as release_date FROM ".PREFIX."_tv_series GROUP BY DATE(release_date) ORDER BY release_date DESC LIMIT ".$countDays.",1");

    // get 7 days (start from last date)
    $db->query("SELECT s.*, p.title, p.category, p.alt_name as news_alt_name, p.date as news_date, lang
FROM ".PREFIX."_tv_series_links l
LEFT JOIN ".PREFIX."_tv_series s
ON s.id = l.series_id
LEFT JOIN ".PREFIX."_post p
ON p.id = s.news_id
WHERE DATE(s.release_date) <= CURRENT_DATE
".($start_date ? " AND DATE(s.release_date) > '".$start_date['release_date']."'" : '')."
GROUP BY l.lang, l.series_id
ORDER BY s.release_date DESC, s.created_at");

    if($db->num_rows()) {
        $series = array();
        while ($row = $db->get_row()) {
            $series[date('d.m.Y', strtotime($row['release_date']))][] = $row;
        }

        $tv_series_last = '';

        foreach ($series as $seriesDate => $seriesArray) {
            $dateText = $seriesDate;

            if ($dateText == date('d.m.Y')) {
                $dateText = 'Сегодня';
            }
            elseif ($dateText == date('d.m.Y', strtotime('-1 day'))) {
                $dateText = 'Вчера';
            }

            $template = <<<HTML
            <div class="date"><span>{$dateText}</span></div>
            <div class="items">
HTML;
            foreach ($seriesArray as $seriesI) {
                $row = $seriesI;
                if( $config['allow_alt_url'] ) {
                    if( $config['seo_type'] == 1 OR $config['seo_type'] == 2  ) {
                        if( $row['category'] and $config['seo_type'] == 2 ) {
                            $full_link = $config['http_home_url'] . get_url( $row['category'] ) . "/" . $row['news_id'] . "-" . $row['news_alt_name'] . ".html";
                        } else {
                            $full_link = $config['http_home_url'] . $row['news_id'] . "-" . $row['news_alt_name'] . ".html";
                        }
                    } else {
                        $full_link = $config['http_home_url'] . date( 'Y/m/d/', $row['news_date'] ) . $row['news_alt_name'] . ".html";
                    }
                } else {
                    $full_link = $config['http_home_url'] . "index.php?newsid=" . $row['news_id'];
                }

                // add episode & lang
                $full_link .= '#e'.$row['number'].'-' . $row['lang'];

                $template .= '<a href="'.$full_link.'"><div class="item"><span class="name" style="background-image: url(\''.$themeUrl.'images/lang/'.$row['lang'].'.png\');">'.$cat_info[(int)$row['category']]['name'].'</span><span class="sezon">'.$row['title'].' Серия '.$row['number'].'</span></div></a>';
            }
            //<img src="'.$themeUrl.'images/lang/'.$row['lang'].'.png" alt="'.$row['lang'].'">
            $template .= <<<HTML
    </div>
HTML;

            $tv_series_last .= $template;
        }

        create_cache($cache_name, $tv_series_last);
    }
}

echo $tv_series_last;