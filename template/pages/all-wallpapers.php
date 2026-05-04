<?php
if (!defined('init_pages'))
{
    header('HTTP/1.0 404 not found');
    exit;
}

$TPL->SetTitle('Wallpapers');
$TPL->AddCSS('template/style/page-media.css');
$TPL->LoadHeader();

$manifestFile = $config['RootPath'] . '/uploads/media/wallpapers/wallpapers.json';
$wallpapers = array();
if (file_exists($manifestFile))
{
    $decoded = json_decode(file_get_contents($manifestFile), true);
    if (is_array($decoded)) { $wallpapers = $decoded; }
}
?>
<div class="content_holder">
    <div class="sub-page-title">
        <div id="title"><h1>Media<p></p><span></span></h1></div>
    </div>

    <div class="container_2" align="center" style="padding:30px 40px; width:916px;">
        <div class="media-header">
            <h2>Wallpapers</h2>
            <h3 class="items-number">(<?php echo count($wallpapers); ?>)</h3>
            <div class="clear"></div>
            <div class="bline"></div>
        </div>

        <ul class="screanshots all-wallpapers screanshots-media-page">
            <?php
            if (count($wallpapers) > 0)
            {
                foreach ($wallpapers as $item)
                {
                    $title = isset($item['title']) ? stripslashes($item['title']) : 'Wallpaper';
                    $file = isset($item['file']) ? basename($item['file']) : '';
                    $thumb = isset($item['thumb']) ? basename($item['thumb']) : $file;
                    $width = isset($item['width']) ? (int)$item['width'] : 0;
                    $height = isset($item['height']) ? (int)$item['height'] : 0;
                    if ($file == '') { continue; }
                    echo '
                    <li>
                        <a href="uploads/media/wallpapers/', htmlspecialchars($file), '" target="_blank" class="container_frame" title="', htmlspecialchars($title), '">
                            <span class="cframe_inner" style="background-image:url(uploads/media/wallpapers/thumbs/', htmlspecialchars($thumb), ');"></span>
                            <div class="media-zoom-ico"></div>
                        </a>
                        <div class="wallpaper-info">
                            <h2>', htmlspecialchars($title), '</h2>
                            <div class="dw-res-links"><a href="uploads/media/wallpapers/', htmlspecialchars($file), '" target="_blank">', ($width > 0 && $height > 0 ? $width . 'x' . $height : 'Download'), '</a></div>
                        </div>
                    </li>';
                }
            }
            else
            {
                echo '<p class="there-is-nothing">No wallpapers have been uploaded yet.</p>';
            }
            ?>
            <div class="clear"></div>
        </ul>
    </div>
</div>
<?php $TPL->LoadFooter(); ?>
