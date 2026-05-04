<?PHP
if (!defined('init_pages'))
{
    header('HTTP/1.0 404 not found');
    exit;
}

if (!$CURUSER->getPermissions()->isAllowed(PERMISSION_MEDIA_MOVIES))
{
    $CORE->ErrorBox('You do not have the required permissions.');
}

if ($success = $ERRORS->successPrint(array('add_movie', 'delete_movie', 'wallpaper_media'))) { echo $success; }
if ($error = $ERRORS->DoPrint('delete_movie')) { echo $error; }
if ($error = $ERRORS->DoPrint('wallpaper_media')) { echo $error; }

$wallDir = $config['RootPath'] . '/uploads/media/wallpapers';
$manifestFile = $wallDir . '/wallpapers.json';
$wallpapers = array();
if (file_exists($manifestFile))
{
    $decoded = json_decode(file_get_contents($manifestFile), true);
    if (is_array($decoded)) { $wallpapers = $decoded; }
}
?>

<nav id="secondary" class="disable-tabbing">
    <ul>
        <li class="current"><a href="index.php?page=media">Media</a></li>
        <li><a href="index.php?page=movie-add">New Movie</a></li>
    </ul>
</nav>

<section id="content">
    <div class="tab" id="maintab">
        <h2>Wallpaper Media</h2>
        <div class="admin-card">
            <form method="post" action="execute.php?take=wallpaper&action=upload" enctype="multipart/form-data" class="form">
                <section>
                    <label>Wallpaper title <small>Name displayed on the public wallpapers page.</small></label>
                    <div><input type="text" name="title" value="" placeholder="Example: Horde Citadel" style="width:360px;"></div>
                </section>
                <section>
                    <label>Wallpaper image <small>JPG, PNG, GIF<?php echo function_exists('imagecreatefromwebp') ? ', WEBP' : ''; ?>. Max 12 MB.</small></label>
                    <div><input type="file" name="wallpaper" accept="image/*" required></div>
                </section>
                <section>
                    <label></label>
                    <div><input type="submit" class="submit primary" value="Upload Wallpaper"></div>
                </section>
            </form>
        </div>

        <h2>Uploaded Wallpapers</h2>
        <div>
            <?php
            if (count($wallpapers) > 0)
            {
                echo '<ul class="imagelist">';
                foreach ($wallpapers as $item)
                {
                    $title = isset($item['title']) ? stripslashes($item['title']) : 'Wallpaper';
                    $file = isset($item['file']) ? basename($item['file']) : '';
                    $thumb = isset($item['thumb']) ? basename($item['thumb']) : $file;
                    if ($file == '') { continue; }
                    echo '
                    <li>
                        <img src="', $config['BaseURL'], '/uploads/media/wallpapers/thumbs/', htmlspecialchars($thumb), '" alt="', htmlspecialchars($title), '" style="opacity:1; object-fit:cover;">
                        <span>
                            <a href="', $config['BaseURL'], '/uploads/media/wallpapers/', htmlspecialchars($file), '" target="_blank" class="name">', htmlspecialchars(substr($title, 0, 20)), (strlen($title) > 20 ? '...' : ''), '</a>
                            <a href="execute.php?take=wallpaper&action=delete&file=', urlencode($file), '" class="delete" onclick="return deletecheck(\'Are you sure you want to delete this wallpaper?\');"></a>
                        </span>
                    </li>';
                }
                echo '</ul>';
            }
            else
            {
                echo '<p>No wallpapers uploaded yet. The public page will stay empty until you upload your own media here.</p>';
            }
            ?>
        </div>
        <div class="clear"></div>

        <br><br>
        <h2>Movies Management</h2>
        <div>
            <?php
            $res = $DB->query("SELECT `id`, `name`, `image`, `dirname` FROM `movies` ORDER BY `id` DESC;");
            if ($res->rowCount() > 0)
            {
                echo '<ul class="imagelist">';
                while ($arr = $res->fetch())
                {
                    echo '
                    <li>
                        <img src="', $config['BaseURL'], '/uploads/media/movies/', $arr['dirname'], '/thumbnails/medium_', $arr['image'], '" alt="', htmlspecialchars(stripslashes($arr['name'])), '" style="opacity: 1;">
                        <span>
                            <a href="', $config['BaseURL'], '/index.php?page=open-video&id=', $arr['id'], '" target="_new" class="name ajax cboxElement">', htmlspecialchars(substr(stripslashes($arr['name']), 0, 20)), (strlen(stripslashes($arr['name'])) > 20 ? '...' : ''), '</a>
                            <a href="#" class="edit ajax cboxElement"></a>
                            <a href="execute.php?take=delete&action=movie&id='.$arr['id'].'" class="delete" onclick="return deletecheck(\'Are you sure you want to delete this movie?\');"></a>
                        </span>
                    </li>';
                }
                echo '</ul>';
            }
            else { echo '<p>There are no movies.</p>'; }
            unset($res);
            ?>
        </div>
        <div class="clear"></div>
    </div>
</section>

<script>
$(document).ready(function(){
    $('.imagelist img').hover(function(){ $(this).stop().animate({ opacity: '0.75'}, 'fast'); }, function(){ $(this).stop().animate({ opacity: '1'}, 'fast'); });
});
</script>
