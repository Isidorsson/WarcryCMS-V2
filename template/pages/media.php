<?php
if (!defined('init_pages'))
{
    header('HTTP/1.0 404 not found');
    exit;
}

$TPL->SetTitle('Media');
$TPL->AddCSS('template/style/page-media.css');
$TPL->LoadHeader();

$manifestFile = $config['RootPath'] . '/uploads/media/wallpapers/wallpapers.json';
$wallpapers = array();
if (file_exists($manifestFile))
{
    $decoded = json_decode(file_get_contents($manifestFile), true);
    if (is_array($decoded)) { $wallpapers = array_slice($decoded, 0, 4); }
}
?>

<div class="content_holder">
    <div class="sub-page-title">
        <div id="title"><h1>Media<p></p><span></span></h1></div>
    </div>

    <div class="container_2" align="center" style="padding:30px 40px; width:916px;">
        <div class="media-container flleft half-w" align="left">
            <div class="media-c-header">
                <h3>Videos</h3>
                <a class="view-alll" href="index.php?page=all-videos">View all</a>
            </div>
            <?php
            $res = $DB->query("SELECT `id`, `name`, `short_text`, `youtube`, `image`, `dirname` FROM `movies` ORDER BY `id` DESC LIMIT 2;");
            if ($res->rowCount() > 0)
            {
                while ($arr = $res->fetch())
                {
                    echo '
                    <div class="media-video-container" align="left">
                        <div class="media-video-thumb container_frame">
                            <div class="cframe_inner">
                                <a href="index.php?page=open-video&id=', $arr['id'], '">
                                    <div class="image-thumb-preview" style="background-image:url(\'', $config['BaseURL'], '/uploads/media/movies/', $arr['dirname'], '/thumbnails/small_', $arr['image'], '\');"></div>
                                    <div class="play-button-small"></div>
                                </a>
                            </div>
                        </div>
                        <div class="video-info">
                            <h3>', htmlspecialchars(stripslashes($arr['name'])), '</h3>
                            <p>', htmlspecialchars(stripslashes($arr['short_text'])), '</p>
                            <a href="', htmlspecialchars($arr['youtube']), '" class="youtube-link" target="_blank">Watch in YouTube</a>
                        </div>
                        <div class="clear"></div>
                    </div>';
                }
            }
            else { echo '<p class="there-is-nothing">There are no movies.</p>'; }
            unset($res);
            ?>
        </div>

        <div class="media-container flright half-w" align="left">
            <div class="media-c-header">
                <h3>Wallpapers</h3>
                <a class="view-alll" href="index.php?page=all-wallpapers">View all</a>
            </div>
            <ul class="screanshots screanshots-media-page">
                <?php
                if (count($wallpapers) > 0)
                {
                    foreach ($wallpapers as $item)
                    {
                        $title = isset($item['title']) ? stripslashes($item['title']) : 'Wallpaper';
                        $thumb = isset($item['thumb']) ? basename($item['thumb']) : '';
                        echo '
                        <li>
                            <a href="index.php?page=all-wallpapers" class="container_frame" title="', htmlspecialchars($title), '">
                                <span class="cframe_inner" style="background-image:url(uploads/media/wallpapers/thumbs/', htmlspecialchars($thumb), ');"></span>
                            </a>
                        </li>';
                    }
                }
                else { echo '<p class="there-is-nothing">No wallpapers uploaded yet.</p>'; }
                ?>
                <div class="clear"></div>
            </ul>
        </div>
        <div class="clear"></div><br>
        <div class="clear"></div>
    </div>
</div>
<?php
$TPL->LoadFooter();
?>
