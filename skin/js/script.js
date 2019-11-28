// jQuery : Smooth Scrool /////////////////////////////////////////////////////////////

$(function (){

   // Smooth Scroll
   $('a[href^="#"]').click(function() {

      // スクロールの速度(ms)
      var speed = 500;

      // アンカーを取得
      var anchor = $(this).attr("href");

      // ターゲットの位置を取得
      var target = $(anchor == "#" || anchor == "" ? 'html' : anchor);
      var position = target.offset().top　-80;

      // スクロール（アニメーション）
      $('body,html').animate({scrollTop:position}, speed, 'swing');

      return false;

   });

});
