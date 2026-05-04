<?php
if (!defined('init_pages'))
{	
	header('HTTP/1.0 404 not found');
	exit;
}

//Set the title
$TPL->SetTitle('How To');
//CSS
$TPL->AddCSS('template/style/page-support-all.css');
//Print the header
$TPL->LoadHeader();

?>
<div class="content_holder">

 <div class="sub-page-title">
  <div id="title"><h1>How to<p></p><span></span></h1></div>
 </div>
 
  	<div class="container_2" align="center">
    
    	<div class="container_3 archived-news" align="left">
        	<!-- How To -->
            
            	<div class="how-to-top-info">
                	Please select one of the articles.
                </div>
              	
                <div id="accordion">
                
	            	<ul class="howto-row">
	                	<li class="howto-row-title">How to Connect - Wardonic-Reborn 255 (Fun Realm)</li>
	                    <li class="howto-row-content">
						
						
							<p>To download, you need a torrent program, we advise using: <a href="https://www.qbittorrent.org/download.php"><b>qBittorent</b></a>.</p>
							<br>
							<br>
							<p>1.)Create an account <a href="http://192.168.1.2/warcry/index.php?page=terms-before-register"><b>Register account</b></a>
							<p>2.)Download <a href="magnet:?xt=urn:btih:b296ea8947b36c68f6e022f5a642ecc406ad8968&dn=World%20of%20Warcraft%203.3.5a%20(no%20install)"><b>World of Warcraft: Wrath of the Lich King</b></a></p>
							<p>3.)Download <a href="https://mega.nz/#!c6Q2gapL!2VFy3VShvIJHbWZmtywcwJb21qEaavLMGPcQ33ufzbo"><b>our patch</b></a></p>
							<p>4.)Navigate to <b>"World of Warcraft/Data/enUS/"</b> and open <b>"realmlist.wtf"</b> with any text editor</p>
							<p>5.)Replace everything with: <b>SET REALMLIST logon.project-reborn.com</b></p>
							<p>6.)Place the patch inside <b>"World of Warcraft/Data/"</b> folder.</p>
							<p>7.)Make sure to delete the <b>"Cache"</b> folder in your WoW directory</p>
							<p>8.)Start the game, by starting <b>"WoW.exe"</b>, then use your credentials made at step 1</p>

													</li>
	                </ul>
					
					<ul class="howto-row">
	                	<li class="howto-row-title">How to Connect - Gundrak (Blizzlike Realm)</li>
	                    <li class="howto-row-content">
						
						
							<p>To download, you need a torrent program, we advise using: <a href="https://www.qbittorrent.org/download.php"><b>qBittorent</b></a>.</p>
							<br>
							<br>
							<p>1.)Create an account <a href="http://192.168.1.2/warcry/index.php?page=terms-before-register"><b>Register account</b></a>
							<p>2.)Download <a href="magnet:?xt=urn:btih:b296ea8947b36c68f6e022f5a642ecc406ad8968&dn=World%20of%20Warcraft%203.3.5a%20(no%20install)"></a><b>World of Warcraft: Wrath of the Lich King</b></p>
							<p>3.)Navigate to <b>"World of Warcraft/Data/enUS/"</b> and open <b>"realmlist.wtf"</b> with any text editor</p>
							<p>4.)Replace everything with: <b>SET REALMLIST logon.project-reborn.com</b>
							<p>5.)Make sure to delete the <b>"Cache"</b> folder in your WoW directory</p>
							<p>6.)Start the game, by starting <b>"WoW.exe"</b>, then use your credentials made at step 1</p>

													</li>
	                </ul>
                    
                    <ul class="howto-row">
	                	<li class="howto-row-title">Discord</li>
	                    <li class="howto-row-content">
	                    	<p><b>Click on connect, to join our discord channel.</b></p>
							<br/>
							<iframe src="https://discordapp.com/widget?id=323501582060748811&theme=dark" width="750" height="400" allowtransparency="true" frameborder="0"></iframe>
                            <br/><br/>
                           </p>
                           
                            
	                    </li>
	                </ul>
	                
	                                   
                    <ul class="howto-row">
	                	<li class="howto-row-title">How To Earn Coins</li>
	                    <li class="howto-row-content how-coins">
                        	<h2 id="gold"><p></p>Gold Coins<span></span></h2>
	                    	<ul class="methods-rows">
                                <li>
                                    <a href="<?php echo $config['BaseURL']; ?>/index.php?page=purchase-gcoins">PURCHASE GOLD COINS</a> - You can purchase Gold Coins via PayPal, Credit Card, Bank Transactions, SMS or by Phone. 1 Gold Coin is 1 USD.
                                </li>
                                <li>
                                    <a href="<?php echo $config['BaseURL']; ?>/index.php?page=earn-gcoins">EARN GOLD COINS</a> - You can earn Gold Coins by completing offers (e.g. surveys, downloads, videos, sign ups and more).
                                </li>
                            </ul>
                            
                            <h2 id="silver"><p></p>Silver Coins<span></span></h2>
                
                            <ul class="methods-rows">
                                <li>
                                    <a href="<?php echo $config['BaseURL']; ?>/index.php?page=vote">VOTE FOR US</a> - Voting helps us grow. As a reward you'll get 2 Silver Coins for each site you vote on every 12 hours.
                                </li>
                                

                                <li>                                </li>
                                
                                <li class="not-allowed">
                                    <a href="<?php echo $config['BaseURL']; ?>/index.php?page=bugtracker">BUG TRACKER</a> - Is one of your spells not working or found some other bug? Report them and get 4 Silver Coins for each aproved report.
                                </li>
                                
                                <li>
                                    <a href="#">LIKE US ON FACEBOOK</a> - Help us spread Warcry and like us on Facebook. We'll give you 5 Silver Coins if you do!
                                </li>
                                 
                            </ul>
	                    </li>
	                </ul>
	            	
                </div>
            
            <!-- How To.End -->
    	</div>
        
    </div>
    
</div>

<script>
	$(document).ready(function()
	{
		$("#accordion").accordion({ header: '.howto-row-title', autoHeight: false, active: false });
		
		<?php
		//do we need to activate one of the guides?
		$activate = isset($_GET['activate']) ? (int)$_GET['activate'] : false;
		
		if ($activate !== false)
		{
			echo '$("#accordion").accordion("activate", ', $activate, ');';
		}
		
		unset($activate);
		?>
	});
</script>

<?php

//Add to the loader
$TPL->AddFooterJs('template/js/jquery-ui-1.8.16.custom.min.js');
//Print the header
$TPL->LoadFooter();

?>