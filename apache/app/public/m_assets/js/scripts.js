$(document).ready(function(){
 
    $('.container, .rowzz').each(function(){  
       
      var highestBox = 0;
       
      $('.column', this).each(function(){
         
        if($(this).height() > highestBox) {
          highestBox = $(this).height(); 
        }
      
      });   
             
      $('.column',this).height(highestBox);
                    
    }); 
    
    $('[data-toggle="tooltip"]').tooltip();
    
    $('.itemx').matchHeight();
     

});