<?php
if (!defined('init_template'))
{	
	header('HTTP/1.0 404 not found');
	exit;
}
require_once $config['RootPath'] . '/engine/helpers/site_settings.php';
$WARCRY_COPYRIGHT = warcry_site_setting('copyright', 'Copyright &copy; <b>Warcry CMS</b>&trade; 2026. All Rights Reserved.');
?>
   <!-- BODY Content end here -->
   </div>
  </div>
 </div>
 <!-- BODY-->

 <!-- Footer -->
    <div class="footer-holder">
        <div id="footer">
        	<a class="back-to-top" href="#header"></a>
             
            <div class="left-side">
                <p>
                	<?php echo $WARCRY_COPYRIGHT; ?><br/>
                </p>
            </div>
            
        </div>
        <div class="bot-foot-border"></div>
        <div class="bot-foot-glow"></div>
    </div>
 <!-- Footer.End -->
 </center>
 
 <div id="temp-login-form" style="display: none;">
	<div class="login-box" align="left">
		<form action="<?php echo $config['BaseURL']; ?>/execute.php?take=login" method="post">
            <input type="hidden" name="url_bl" id="js-login-box_urlbl" />
            <p>Account Name</p>
            <input type="text" name="username" autocomplete="on"> <br>
            <p>Password</p>
            <input type="password" name="password" autocomplete="on"><br>
            <div class="login-box-row">
            	<input type="submit" value="Login">
                <label class="label_check">
                    <div></div>
                    <input type="checkbox" value="1" id="rememberme" name="rememberme">
                    <p>Remember me</p>
            	</label>
            </div>
    	</form>
    	<div class="login-box-options">
     		<a href="<?php echo $config['BaseURL']; ?>/index.php?page=password_recovery">Forgot your password ?</a><br>
     		<span>Don't have an account yet ? <a href="<?php echo $config['BaseURL']; ?>/index.php?page=register">Register Now!</a></span>
    	</div>
  	</div>
 </div>
 
<?php
	//Add the default footer js include
	$TPL->AddFooterJs('template/js/footer_include.js');
	//Print the Javascript loader
	$TPL->PrintFooterJavascripts();
	//Print the footer
	$TPL->LoadFooter();
?>
</body>
</html>