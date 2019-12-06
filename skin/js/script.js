// Menu Toggle /////////////////////////////////////////////////////////////

const breakPoint = 768;

function ShowAndHide(win){
  if( win > breakPoint ){
    $('#menuButton').hide();
    $('#menuList').show();
    $('#menuList').css({'flex-direcrion':'row'});
  } else {
    $('#menuButton').show();
    $('#menuButton').removeClass('open');
    $('#menuList').hide();
  }
}

$(function (){

  ShowAndHide( $(window).width() );

  $('#menuButton a').on('click', function () {
    $('#menuList').slideToggle(500);
    console.log('ok');
  });

  $(window).resize(function(){
    ShowAndHide( $(window).width() );
  });

});

// Smooth Scrool /////////////////////////////////////////////////////////////

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
