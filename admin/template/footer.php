<?php if (!defined('init_template')) { header('HTTP/1.0 404 not found'); exit; } ?>
    </section>
  </div>
  <footer id="copyright">Warcry Admin Panel © 2026 — Modernized for PHP 8 / AzerothCore</footer>
</div>
<script>
function tabAutoSwitch(tab){
  if ($("nav#secondary").hasClass('disable-tabbing')) { $(".tab").show(); return; }
  var $this = $("nav#secondary ul li:nth-child("+tab+") a");
  if($this.length){ $("nav#secondary ul li").removeClass('current'); $this.parent().addClass('current'); $(".tab").hide(); $($this.attr('href')).show(); changeCurrentTab($this.attr('href')); }
}
function changeCurrentTab(tab){ $currentTab = $(tab); }
function deletecheck(message){ return confirm(message); }
$(function(){
  var sw = '<?php echo (isset($_GET['switchTab']) ? (int)$_GET['switchTab'] : ''); ?>';
  tabAutoSwitch(sw !== '' ? sw : 1);
  $('nav#secondary:not(.disable-tabbing) ul li a').on('click', function(){ tabAutoSwitch($(this).parent().index()+1); return false; });
  $('#search').on('keyup', function(){ var q=$(this).val().toLowerCase(); $('#content table tbody tr, #content .admin-card').each(function(){ $(this).toggle($(this).text().toLowerCase().indexOf(q)>-1); }); });
});
</script>
<script src="template/js/jquery.datatables.js"></script>
<script src="template/js/jquery.form.js"></script>
<script src="template/js/forms.js"></script>
</body>
</html>
