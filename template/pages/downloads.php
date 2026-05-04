<?php
if (!defined('init_pages'))
{	
	header('HTTP/1.0 404 not found');
	exit;
}

//Set the title
$TPL->SetTitle('Downloads');
//CSS
$TPL->AddCSS('template/style/page-support-all.css');
//Print the header
$TPL->LoadHeader();

?>
<div class="content_holder">

 <div class="sub-page-title">
  <div id="title"><h1>Downloads<p></p><span></span></h1></div>
 </div>
 
  	<div class="container_2 features" align="center">
    
    	<br/>
        
        <ul>
        
     
            
            <!-- Launcher ROW -->
        	<li class="container_3 archived-news w-addons" id="launcher">
            	<div class="w-addon-row">
            	<img src="template/style/images/media/launcher.jpg" width="268" height="163" alt="Warcry XP Rate Changer Addon"/>
                <div class="addon-info">
                	<h1>Custom Patch</h1>
                    <p>
                     We've implanted a brand new feature and updates items / instances and much more you need this patch to see our custom content.
                    </p>
                    <div class="war-links">
                    	<a class="download" href="<?php echo $config['BaseURL']; ?>/resources/addons/Warcry-XP-Rate-Changer.zip" title="Download Warcry WoW XP Rate Changer" target="_self">Download</a>
                    </div>
                </div>
                </div>
                <div class="clear"></div>
            </li>
            <!-- Launcher ROW . End -->
            
        </ul>
        
        
        <!-- FEATURES BG --> <div class="features-bg"></div>
        
    </div>
    
</div>

<?php
	$TPL->LoadFooter();
?>