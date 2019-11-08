$(function(){
  $("ul.menu li").hover(function(){
    $("ul.sub:not(:animated)", this).slideDown();
  }, function(){
    $("ul.sub", this).slideUp();
  });
});
